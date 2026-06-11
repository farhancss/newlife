<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ShippingAddress;
use App\Models\SquarespaceAddressEntry;
use App\Models\StudentProfile;
use App\Models\StudentSubscription;
use App\Models\User;
use App\Services\Squarespace\PackageTierMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountProvisioningService
{
    public function __construct(
        private readonly NewLifeIdGenerator $newLifeIdGenerator,
        private readonly InvitationMailService $invitationMailService,
        private readonly PackageTierMapper $packageTierMapper,
        private readonly StudentPackageService $studentPackageService,
        private readonly UserStatusService $userStatusService,
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
     * @param array<string, mixed> $order
     */
    public function enrichFromOrder(array $order): StudentProfile
    {
        return DB::transaction(function () use ($order): StudentProfile {
            $orderId = (string) ($order['id'] ?? $order['orderId'] ?? '');
            $customerId = (string) ($order['customerId'] ?? '');
            $email = (string) ($order['customerEmail'] ?? $order['billingAddress']['email'] ?? '');
            $lineItems = $order['lineItems'] ?? [];

            $profile = null;

            if ($customerId) {
                $profile = StudentProfile::query()
                    ->where('squarespace_contact_id', $customerId)
                    ->first();
            }

            if (!$profile instanceof StudentProfile && $email !== '') {
                $user = User::query()->where('email', $email)->first();
                $profile = $user?->studentProfile;
            }

            if (!$profile instanceof StudentProfile && $email !== '') {
                $profile = $this->upsertFromContact([
                    'contactId' => $customerId,
                    'firstName' => $order['shippingAddress']['firstName'] ?? '',
                    'lastName' => $order['shippingAddress']['lastName'] ?? '',
                    'primaryEmail' => ['value' => $email],
                ], true);
            }

            if (!$profile instanceof StudentProfile) {
                throw new \RuntimeException('Unable to resolve student profile for order ' . $orderId);
            }

            $tier = $this->packageTierMapper->mapFromLineItems($lineItems);
            $this->studentPackageService->assignFromTier($profile, $tier);

            $this->syncShippingFromOrder($profile, $order);
            $this->upsertSubscription($profile, $order);

            $fresh = $profile->fresh(['subscriptions', 'shippingAddress']);

            if (!$fresh instanceof StudentProfile) {
                throw new \RuntimeException('Failed to reload student profile for order ' . $orderId);
            }

            return $fresh;
        });
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
     * @param array<string, mixed> $order
     */
    private function syncShippingFromOrder(StudentProfile $profile, array $order): void
    {
        if ($profile->isOnboardingComplete()) {
            return;
        }

        $shipping = $order['shippingAddress'] ?? null;

        if (!is_array($shipping)) {
            return;
        }

        $this->upsertHomeShipping($profile, $shipping);
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
