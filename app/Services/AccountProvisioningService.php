<?php

namespace App\Services;

use App\Enums\PackageTier;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ShippingAddress;
use App\Models\SquarespaceAddressEntry;
use App\Models\StudentProfile;
use App\Models\StudentSubscription;
use App\Models\User;
use App\Services\Squarespace\SquarespaceOrderImporter;
use App\Services\Squarespace\SquarespaceOrderMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountProvisioningService
{
    public function __construct(
        private readonly NewLifeIdGenerator $newLifeIdGenerator,
        private readonly InvitationMailService $invitationMailService,
        private readonly StudentPackageService $studentPackageService,
        private readonly UserStatusService $userStatusService,
        private readonly DeadlineService $deadlineService,
        private readonly SquarespaceOrderImporter $orderImporter,
        private readonly SquarespaceOrderMapper $orderMapper,
    ) {
    }

    /**
     * @param array<string, mixed> $notification
     */
    public function upsertFromContactNotification(array $notification): StudentProfile
    {
        $contact = $this->extractContact($notification);

        $sendInvitation = ($notification['topic'] ?? '') === 'contact.create';

        return $this->upsertFromContact($contact, $sendInvitation);
    }

    /**
     * @param array<string, mixed> $contact
     */
    public function upsertFromContact(array $contact, bool $sendInvitationIfNew = true): StudentProfile
    {
        return $this->provisionFromContact($contact, $sendInvitationIfNew)->profile;
    }

    /**
     * Full provisioning entry-point. Returns the profile, the user, whether the
     * account is brand new, and the freshly generated temporary password
     * (`null` when the account already existed).
     *
     * @param array<string, mixed> $contact
     */
    public function provisionFromContact(array $contact, bool $sendInvitationIfNew = true): ProvisionedAccount
    {
        $contactId = (string) ($contact['contactId'] ?? '');
        $email = $this->extractContactEmail($contact);
        $firstName = (string) ($contact['firstName'] ?? '');
        $lastName = (string) ($contact['lastName'] ?? '');

        return DB::transaction(function () use ($contact, $contactId, $email, $firstName, $lastName, $sendInvitationIfNew): ProvisionedAccount {
            $user = User::query()->where('email', $email)->first();
            $isNewUser = $user === null;
            $tempPassword = null;

            if ($isNewUser) {
                $tempPassword = Str::password(16);
                $user = User::query()->create([
                    'name' => trim($firstName . ' ' . $lastName) ?: $email,
                    'email' => $email,
                    'role' => UserRole::STUDENT,
                    'status' => UserStatus::INVITED,
                    'password' => Hash::make($tempPassword),
                    'must_reset_password' => true,
                    'squarespace_contact_id' => $contactId ?: null,
                ]);
            } else {
                if ($contactId && !$user->squarespace_contact_id) {
                    $user->squarespace_contact_id = $contactId;
                    $user->save();
                }
            }

            $profile = StudentProfile::query()->firstOrNew(['user_id' => $user->id]);

            if (!$profile->exists) {
                $profile->new_life_id = $this->newLifeIdGenerator->generate();
            }

            if ($contactId) {
                $profile->squarespace_contact_id = $contactId;
            }

            $profile->first_name = $firstName ?: $profile->first_name;
            $profile->last_name = $lastName ?: $profile->last_name;
            $profile->save();

            $this->syncDefaultShippingFromContact($profile, $contact);

            if ($isNewUser) {
                $this->userStatusService->markInvited($user);

                // Case 01: start the 7-day profile-completion countdown.
                $this->deadlineService->openProfileCompletion($profile);
            }

            if ($isNewUser && $sendInvitationIfNew && $tempPassword !== null) {
                $this->invitationMailService->send($user, $tempPassword);
            }

            $profile->load(['user', 'shippingAddress']);

            return new ProvisionedAccount(
                profile: $profile,
                user: $user->fresh() ?? $user,
                isNewUser: $isNewUser,
                temporaryPassword: $tempPassword,
            );
        });
    }

    /**
     * Backwards-compatible entry point used by the order webhook job.
     *
     * @param array<string, mixed> $order
     */
    public function enrichFromOrder(array $order): StudentProfile
    {
        return $this->provisionFromOrder($order)->profile;
    }

    /**
     * Provision (or update) a student account entirely from a single Squarespace
     * order. The order carries the customer email + id, billing address, the
     * purchased package, and the checkout form answers — enough to create the
     * account, send the invitation (only for brand-new accounts), and pre-fill
     * every onboarding step with sensible defaults the student can later edit.
     *
     * @param array<string, mixed> $order
     */
    public function provisionFromOrder(array $order): ProvisionedAccount
    {
        $mapped = $this->orderMapper->map($order);

        $email = (string) $mapped['email'];
        $orderId = (string) $mapped['order_id'];

        if ($email === '') {
            throw new \RuntimeException('Order ' . $orderId . ' is missing a customer email; cannot provision a student.');
        }

        return DB::transaction(function () use ($order, $mapped, $email, $orderId): ProvisionedAccount {
            $contactId = (string) $mapped['contact_id'];
            /** @var array<string, mixed> $student */
            $student = $mapped['student'];

            $user = User::query()->where('email', $email)->first();
            $isNewUser = $user === null;
            $tempPassword = null;

            if ($isNewUser) {
                $tempPassword = Str::password(16);
                $fullName = trim(((string) ($student['first_name'] ?? '')) . ' ' . ((string) ($student['last_name'] ?? '')));

                $user = User::query()->create([
                    'name' => $fullName !== '' ? $fullName : $email,
                    'email' => $email,
                    'role' => UserRole::STUDENT,
                    'status' => UserStatus::INVITED,
                    'password' => Hash::make($tempPassword),
                    'must_reset_password' => true,
                    'squarespace_contact_id' => $contactId ?: null,
                ]);
            } elseif ($contactId && ! $user->squarespace_contact_id) {
                $user->squarespace_contact_id = $contactId;
                $user->save();
            }

            $profile = StudentProfile::query()->firstOrNew(['user_id' => $user->id]);

            if (! $profile->exists) {
                $profile->new_life_id = $this->newLifeIdGenerator->generate();
            }

            if ($contactId) {
                $profile->squarespace_contact_id = $contactId;
            }

            // Pre-fill the student basics as editable defaults (never clobber a
            // value the student has already entered during onboarding).
            $this->prefillStudent($profile, $student);
            $profile->save();

            // Only assign the package when this order actually contains one. An
            // add-on-only order (same webhook, existing student) must never wipe
            // the student's existing package.
            if ((string) $mapped['tier'] !== PackageTier::UNKNOWN) {
                $this->studentPackageService->assignFromTier($profile, (string) $mapped['tier']);

                // Show what the student actually paid (order grand total) rather
                // than the catalogue list price.
                $grandTotalCents = $mapped['grand_total_cents'];
                if ($grandTotalCents !== null) {
                    $profile->forceFill(['package_price_cents' => (int) $grandTotalCents])->save();
                }
            }

            // Remaining onboarding sections are defaults only while the student
            // has not yet completed onboarding.
            if (! $profile->isOnboardingComplete()) {
                /** @var array<string, mixed> $parent */
                $parent = $mapped['parent'];
                /** @var array<string, mixed> $homeAddress */
                $homeAddress = $mapped['home_address'];
                /** @var array<string, mixed> $housing */
                $housing = $mapped['housing'];

                $this->prefillParent($profile, $parent);
                $this->prefillHomeAddress($profile, $homeAddress);
                $this->prefillHousing($profile, $housing);
            }

            $this->upsertSubscription($profile, $order);

            // Persist the full purchase (header + line items) and activate any
            // add-ons whose SKU is mapped in config.
            $this->orderImporter->import($order, $profile);

            if ($isNewUser) {
                $this->userStatusService->markInvited($user);

                // Case 01: start the 7-day profile-completion countdown.
                $this->deadlineService->openProfileCompletion($profile);

                if ($tempPassword !== null) {
                    $this->invitationMailService->send($user, $tempPassword);
                }
            }

            $fresh = $profile->fresh(['user', 'parentGuardian', 'shippingAddress', 'housingInfo', 'subscriptions']);

            if (! $fresh instanceof StudentProfile) {
                throw new \RuntimeException('Failed to reload student profile for order ' . $orderId);
            }

            return new ProvisionedAccount(
                profile: $fresh,
                user: $user->fresh() ?? $user,
                isNewUser: $isNewUser,
                temporaryPassword: $tempPassword,
            );
        });
    }

    /**
     * @param array<string, mixed> $student
     */
    private function prefillStudent(StudentProfile $profile, array $student): void
    {
        $profile->first_name = $this->firstFilled($profile->first_name, $student['first_name'] ?? null);
        $profile->last_name = $this->firstFilled($profile->last_name, $student['last_name'] ?? null);
        $profile->phone = $this->firstFilled($profile->phone, $student['phone'] ?? null);
        $profile->school = $this->firstFilled($profile->school, $student['school'] ?? null);
        $profile->incoming_year = $this->firstFilled($profile->incoming_year, $student['incoming_year'] ?? null);
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function prefillParent(StudentProfile $profile, array $parent): void
    {
        $model = $profile->parentGuardian()->firstOrNew([]);

        $model->name = $this->firstFilled($model->name, $parent['name'] ?? null);
        $model->email = $this->firstFilled($model->email, $parent['email'] ?? null);
        $model->phone = $this->firstFilled($model->phone, $parent['phone'] ?? null);
        $model->relationship = $this->firstFilled($model->relationship, $parent['relationship'] ?? null);

        if ($model->name !== null || $model->email !== null || $model->phone !== null) {
            $model->student_profile_id = $profile->id;
            $model->save();
        }
    }

    /**
     * @param array<string, mixed> $address
     */
    private function prefillHomeAddress(StudentProfile $profile, array $address): void
    {
        if ($this->isEmptyAddress($address)) {
            return;
        }

        $model = ShippingAddress::query()->firstOrNew([
            'student_profile_id' => $profile->id,
            'type' => 'home',
        ]);

        $model->line1 = $this->firstFilled($model->line1, $address['line1'] ?? null);
        $model->line2 = $this->firstFilled($model->line2, $address['line2'] ?? null);
        $model->city = $this->firstFilled($model->city, $address['city'] ?? null);
        $model->region = $this->firstFilled($model->region, $address['region'] ?? null);
        $model->postal_code = $this->firstFilled($model->postal_code, $address['postal_code'] ?? null);
        $model->country_code = $this->firstFilled($model->country_code, $address['country_code'] ?? null);
        $model->phone = $this->firstFilled($model->phone, $address['phone'] ?? null);
        $model->save();
    }

    /**
     * @param array<string, mixed> $housing
     */
    private function prefillHousing(StudentProfile $profile, array $housing): void
    {
        $university = $housing['university'] ?? null;
        $residenceHall = $housing['residence_hall'] ?? null;

        if (($university === null || $university === '') && ($residenceHall === null || $residenceHall === '')) {
            return;
        }

        $model = $profile->housingInfo()->firstOrNew([]);

        $model->university = $this->firstFilled($model->university, $university);
        $model->residence_hall = $this->firstFilled($model->residence_hall, $residenceHall);
        $model->student_profile_id = $profile->id;
        $model->save();
    }

    /**
     * @param array<string, mixed> $address
     */
    private function isEmptyAddress(array $address): bool
    {
        foreach (['line1', 'city', 'region', 'postal_code'] as $key) {
            if (! empty($address[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Keep an existing non-empty value; otherwise adopt the incoming default.
     */
    private function firstFilled(?string $current, ?string $incoming): ?string
    {
        if ($current !== null && $current !== '') {
            return $current;
        }

        return ($incoming !== null && $incoming !== '') ? $incoming : $current;
    }

    /**
     * @param array<string, mixed> $notification
     */
    public function syncFromAddressNotification(array $notification): void
    {
        $data = $notification['data'] ?? [];
        $contactId = (string) ($data['contactId'] ?? $data['contact']['contactId'] ?? '');
        $entryId = (string) ($data['addressBookEntryId'] ?? $data['address']['addressBookEntryId'] ?? '');
        $address = $data['address']['address'] ?? $data['address'] ?? [];

        if ($contactId === '' || $entryId === '') {
            return;
        }

        $profile = StudentProfile::query()
            ->where('squarespace_contact_id', $contactId)
            ->first();

        if (!$profile) {
            return;
        }

        $shipping = $this->upsertHomeShipping($profile, $address);

        SquarespaceAddressEntry::query()->updateOrCreate(
            ['address_book_entry_id' => $entryId],
            [
                'squarespace_contact_id' => $contactId,
                'shipping_address_id' => $shipping->id,
                'raw_payload' => $data,
            ]
        );
    }

    /**
     * @param array<string, mixed> $notification
     * @return array<string, mixed>
     */
    private function extractContact(array $notification): array
    {
        $data = $notification['data'] ?? [];

        if (isset($data['contact']) && is_array($data['contact'])) {
            return $data['contact'];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function extractContactEmail(array $contact): string
    {
        $email = $contact['primaryEmail']['value']
            ?? $contact['email']
            ?? null;

        if (!is_string($email) || $email === '') {
            throw new \InvalidArgumentException('Contact payload missing primary email.');
        }

        return strtolower($email);
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function syncDefaultShippingFromContact(StudentProfile $profile, array $contact): void
    {
        if ($profile->isOnboardingComplete()) {
            return;
        }

        $default = $contact['defaultShippingAddress']['address'] ?? null;

        if (!is_array($default)) {
            return;
        }

        $this->upsertHomeShipping($profile, $default);
    }

    /**
     * @param array<string, mixed> $addressData
     */
    private function upsertHomeShipping(StudentProfile $profile, array $addressData): ShippingAddress
    {
        return ShippingAddress::query()->updateOrCreate(
            [
                'student_profile_id' => $profile->id,
                'type' => 'home',
            ],
            [
                'line1' => $addressData['line1'] ?? $addressData['address1'] ?? null,
                'line2' => $addressData['line2'] ?? $addressData['address2'] ?? null,
                'city' => $addressData['city'] ?? null,
                'region' => $addressData['region'] ?? $addressData['state'] ?? null,
                'postal_code' => $addressData['postalCode'] ?? $addressData['zip'] ?? null,
                'country_code' => $addressData['countryCode'] ?? $addressData['country'] ?? null,
                'phone' => $addressData['phoneNumber'] ?? $addressData['phone'] ?? null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $order
     */
    private function upsertSubscription(StudentProfile $profile, array $order): void
    {
        $orderId = (string) ($order['id'] ?? $order['orderId'] ?? '');
        $lineItems = $order['lineItems'] ?? [];
        $firstItem = $lineItems[0] ?? [];
        $fulfillment = strtolower((string) ($order['fulfillmentStatus'] ?? 'pending'));

        $status = match (true) {
            in_array($fulfillment, ['cancelled', 'canceled'], true) => SubscriptionStatus::CANCELLED,
            $fulfillment === 'fulfilled' => SubscriptionStatus::COMPLETED,
            default => SubscriptionStatus::ACTIVE,
        };

        StudentSubscription::query()->updateOrCreate(
            ['squarespace_order_id' => $orderId],
            [
                'student_profile_id' => $profile->id,
                'status' => $status,
                'sku' => $firstItem['sku'] ?? $firstItem['productId'] ?? null,
                'product_name' => $firstItem['productName'] ?? $firstItem['name'] ?? null,
                'billing_period' => $order['subscriptionDetails']['billingPeriod'] ?? null,
                'current_period_ends_at' => isset($order['subscriptionDetails']['currentPeriodEnd'])
                    ? now()->parse($order['subscriptionDetails']['currentPeriodEnd'])
                    : null,
                'raw_payload' => $order,
                'synced_at' => now(),
            ]
        );
    }
}
