<?php

namespace App\Console\Commands;

use App\Services\Squarespace\SquarespaceOAuthService;
use App\Services\Squarespace\SquarespaceWebhookSubscriptionService;
use Illuminate\Console\Command;
use Throwable;

class ManageSquarespaceWebhooks extends Command
{
    protected $signature = 'squarespace:webhooks
        {action : One of: list, register, delete, test, rotate}
        {--id= : Subscription id (for delete, test, rotate)}
        {--topic=order.create : Topic to send for the test action}';

    protected $description = 'Manage Squarespace webhook subscriptions (list/register/delete/test/rotate)';

    public function handle(
        SquarespaceOAuthService $oauth,
        SquarespaceWebhookSubscriptionService $subscriptions,
    ): int {
        if (! $oauth->isConnected()) {
            $this->error('Squarespace is not connected. Complete the OAuth connection from the admin panel first.');

            return self::FAILURE;
        }

        $action = (string) $this->argument('action');

        try {
            return match ($action) {
                'list' => $this->listSubscriptions($subscriptions),
                'register' => $this->register($subscriptions),
                'delete' => $this->delete($subscriptions),
                'test' => $this->test($subscriptions),
                'rotate' => $this->rotate($subscriptions),
                default => $this->unknown($action),
            };
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function listSubscriptions(SquarespaceWebhookSubscriptionService $subscriptions): int
    {
        $rows = collect($subscriptions->list())->map(fn (array $sub): array => [
            $sub['id'] ?? '',
            implode(', ', $sub['topics'] ?? []),
            $sub['endpointUrl'] ?? '',
        ])->all();

        $this->table(['ID', 'Topics', 'Endpoint'], $rows);

        return self::SUCCESS;
    }

    private function register(SquarespaceWebhookSubscriptionService $subscriptions): int
    {
        $subscription = $subscriptions->create();
        $this->info('Registered subscription ' . $subscription->subscription_id);
        $this->line('Signing secret stored securely for signature verification.');

        return self::SUCCESS;
    }

    private function delete(SquarespaceWebhookSubscriptionService $subscriptions): int
    {
        $id = $this->requireId();
        $subscriptions->delete($id);
        $this->info('Deleted subscription ' . $id);

        return self::SUCCESS;
    }

    private function test(SquarespaceWebhookSubscriptionService $subscriptions): int
    {
        $id = $this->requireId();
        $status = $subscriptions->sendTest($id, (string) $this->option('topic'));
        $this->info("Test notification sent — endpoint responded with HTTP {$status}.");

        return self::SUCCESS;
    }

    private function rotate(SquarespaceWebhookSubscriptionService $subscriptions): int
    {
        $id = $this->requireId();
        $subscriptions->rotateSecret($id);
        $this->info('Rotated signing secret for subscription ' . $id);

        return self::SUCCESS;
    }

    private function requireId(): string
    {
        $id = (string) $this->option('id');

        if ($id === '') {
            throw new \RuntimeException('The --id option is required for this action.');
        }

        return $id;
    }

    private function unknown(string $action): int
    {
        $this->error("Unknown action '{$action}'. Use list, register, delete, test, or rotate.");

        return self::FAILURE;
    }
}
