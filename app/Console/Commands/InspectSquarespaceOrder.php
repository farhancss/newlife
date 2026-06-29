<?php

namespace App\Console\Commands;

use App\Services\AccountProvisioningService;
use App\Services\Squarespace\SquarespaceApiClient;
use App\Services\Squarespace\SquarespaceOrderMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches a real Squarespace order from the Commerce API, shows exactly how it
 * maps into the student-onboarding flow, and (with --provision) runs the real
 * provisioning so you can test the end-to-end "order → student account" path.
 */
class InspectSquarespaceOrder extends Command
{
    protected $signature = 'squarespace:inspect-order
        {orderId : The Squarespace order id (from the webhook data.orderId)}
        {--provision : Create/update the student account from the order and send the invite for new accounts}';

    protected $description = 'Fetch a Squarespace order and show (or run) how it maps into student onboarding';

    public function handle(
        SquarespaceApiClient $api,
        SquarespaceOrderMapper $mapper,
        AccountProvisioningService $provisioning,
    ): int {
        $orderId = (string) $this->argument('orderId');

        $this->components->info("Fetching Squarespace order {$orderId}…");

        try {
            $order = $api->getOrder($orderId);
        } catch (Throwable $e) {
            $this->components->error('Order request failed: ' . $e->getMessage());
            $this->line('  Check that Squarespace OAuth is connected (or SQUARESPACE_API_KEY is set) and the order id is correct.');

            return self::FAILURE;
        }

        $this->dumpJson('ORDER PAYLOAD', $order);
        Log::channel((string) config('squarespace.log_channel', 'squarespace'))
            ->info('Inspect order ' . $orderId, ['label' => 'inspect.order', 'order' => $order]);

        $this->renderOrderMapping($mapper->map($order));

        if (! $this->option('provision')) {
            $this->newLine();
            $this->components->info('Dry run only. Re-run with --provision to create the student account.');

            return self::SUCCESS;
        }

        return $this->provision($provisioning, $order);
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function provision(AccountProvisioningService $provisioning, array $order): int
    {
        $this->newLine();
        $this->components->info('Provisioning student account from the order…');

        try {
            $account = $provisioning->provisionFromOrder($order);
        } catch (Throwable $e) {
            $this->components->error('Provisioning failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Account', $account->isNewUser ? 'created (new)' : 'updated (existing)');
        $this->components->twoColumnDetail('Email', $account->user->email);
        $this->components->twoColumnDetail('New Life ID', (string) $account->profile->new_life_id);
        $this->components->twoColumnDetail('Status', (string) $account->user->status);
        $this->components->twoColumnDetail(
            'Invitation email',
            $account->isNewUser ? 'queued (run `php artisan queue:work` to deliver)' : 'skipped (account already existed)',
        );

        if ($account->temporaryPassword !== null) {
            $this->components->twoColumnDetail('Temp password', $account->temporaryPassword);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dumpJson(string $heading, array $payload): void
    {
        $this->newLine();
        $this->line('<bg=blue;fg=white> ' . $heading . ' </>');
        $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<string, mixed>  $mapped
     */
    private function renderOrderMapping(array $mapped): void
    {
        /** @var array<string, mixed> $student */
        $student = $mapped['student'];
        /** @var array<string, mixed> $parent */
        $parent = $mapped['parent'];
        /** @var array<string, mixed> $home */
        $home = $mapped['home_address'];
        /** @var array<string, mixed> $housing */
        $housing = $mapped['housing'];

        $this->newLine();
        $this->line('<bg=green;fg=black> ORDER → ONBOARDING MAPPING (what gets saved) </>');
        $this->table(['Onboarding field', 'Saved to', 'Value'], [
            ['email (account)', 'users.email + invite', $mapped['email'] ?: '—'],
            ['contact id', 'users / student_profiles.squarespace_contact_id', $mapped['contact_id'] ?: '—'],
            ['first name', 'student_profiles.first_name', $student['first_name'] ?? '—'],
            ['last name', 'student_profiles.last_name', $student['last_name'] ?? '—'],
            ['phone', 'student_profiles.phone', $student['phone'] ?? '—'],
            ['school / university', 'student_profiles.school + housing_infos.university', $student['school'] ?? '—'],
            ['incoming year', 'student_profiles.incoming_year', $student['incoming_year'] ?? '—'],
            ['package tier', 'student_profiles.package_tier/package_id', $mapped['tier']],
            ['parent name', 'parent_guardians.name', $parent['name'] ?? '—'],
            ['parent email', 'parent_guardians.email', $parent['email'] ?? '—'],
            ['parent phone', 'parent_guardians.phone', $parent['phone'] ?? '—'],
            ['home address', 'shipping_addresses(type=home).line1', $home['line1'] ?? '—'],
            ['home city/region/zip', 'shipping_addresses(type=home).*', $this->cityLine($home)],
            ['residence hall', 'housing_infos.residence_hall', $housing['residence_hall'] ?? '—'],
            ['move-in classification', '(not stored — informational)', $housing['move_in_classification'] ?? '—'],
            ['agreements', '(logged for audit)', (string) count($mapped['agreements'])],
        ]);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function cityLine(array $address): string
    {
        $parts = array_filter([
            $address['city'] ?? null,
            $address['region'] ?? $address['state'] ?? null,
            $address['postal_code'] ?? $address['postalCode'] ?? $address['zip'] ?? null,
            $address['country_code'] ?? $address['countryCode'] ?? $address['country'] ?? null,
        ]);

        return $parts === [] ? '—' : implode(', ', $parts);
    }
}
