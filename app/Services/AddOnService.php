<?php

namespace App\Services;

use App\Enums\AddOnStatus;
use App\Models\Container;
use App\Models\StudentAddOn;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddOnService
{
    public function __construct(
        private readonly ContainerWorkflowService $containerWorkflow,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Available add-ons from the hardcoded catalog.
     *
     * @return Collection<int, array{slug: string, name: string, price_cents: int, description: string, icon: string, url: string, formatted_price: string}>
     */
    public function catalog(): Collection
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = config('addons.catalog', []);

        return collect($items)->map(function (array $item): array {
            $priceCents = (int) ($item['price_cents'] ?? 0);

            return [
                'slug' => (string) $item['slug'],
                'name' => (string) $item['name'],
                'price_cents' => $priceCents,
                'description' => (string) $item['description'],
                'icon' => (string) ($item['icon'] ?? 'storage'),
                'url' => (string) $item['url'],
                'formatted_price' => '$' . number_format($priceCents / 100, 2),
            ];
        })
            ->sortBy('price_cents')
            ->values();
    }

    /**
     * Find a single catalog entry by slug.
     *
     * @return array{slug: string, name: string, price_cents: int, description: string, icon: string, url: string, formatted_price: string}|null
     */
    public function findInCatalog(string $slug): ?array
    {
        return $this->catalog()->firstWhere('slug', $slug);
    }

    /**
     * Buy an add-on for a student. The purchase is recorded as active right away
     * (payment is handled out-of-band on Squarespace). The "Additional Container"
     * add-on also provisions a trackable container that follows the full move
     * journey.
     *
     * @param  array{slug: string, name: string, price_cents: int, url: string}  $addOn
     */
    public function purchase(StudentProfile $profile, array $addOn, ?User $actor = null): StudentAddOn
    {
        $record = DB::transaction(function () use ($profile, $addOn, $actor): StudentAddOn {
            $containerId = null;

            if ($addOn['slug'] === StudentAddOn::ADDITIONAL_CONTAINER_SLUG) {
                $containerId = $this->containerWorkflow->createForStudent(
                    $profile,
                    $actor,
                    Container::SOURCE_ADD_ON,
                )->id;
            }

            /** @var StudentAddOn $record */
            $record = $profile->addOns()->create([
                'add_on_slug' => $addOn['slug'],
                'name' => $addOn['name'],
                'price_cents' => $addOn['price_cents'],
                'squarespace_url' => $addOn['url'],
                'status' => AddOnStatus::ACTIVE,
                'container_id' => $containerId,
                'requested_at' => Carbon::now(),
                'activated_at' => Carbon::now(),
                'activated_by_user_id' => $actor?->id,
            ]);

            return $record;
        });

        // Confirm the purchase to the student (in-app inbox + email) once the
        // record is committed.
        $record->setRelation('studentProfile', $profile->loadMissing('user'));
        $this->notifications->addOnPurchased($record);

        return $record;
    }

    /**
     * Activate an add-on that was purchased through Squarespace. Idempotent per
     * order + slug so re-delivered order webhooks don't create duplicates. The
     * "Additional Container" add-on provisions one trackable container per unit.
     *
     * @param  array{slug: string, name: string, price_cents: int, url: string}  $addOn
     */
    public function activateFromSquarespace(
        StudentProfile $profile,
        array $addOn,
        string $squarespaceOrderId,
        int $quantity = 1,
    ): void {
        $alreadyActivated = $profile->addOns()
            ->where('add_on_slug', $addOn['slug'])
            ->where('squarespace_order_id', $squarespaceOrderId)
            ->exists();

        if ($alreadyActivated) {
            return;
        }

        $isContainer = $addOn['slug'] === StudentAddOn::ADDITIONAL_CONTAINER_SLUG;
        $units = $isContainer ? max(1, $quantity) : 1;

        for ($i = 0; $i < $units; $i++) {
            $record = DB::transaction(function () use ($profile, $addOn, $squarespaceOrderId, $isContainer): StudentAddOn {
                $containerId = $isContainer
                    ? $this->containerWorkflow->createForStudent($profile, null, Container::SOURCE_ADD_ON)->id
                    : null;

                /** @var StudentAddOn $record */
                $record = $profile->addOns()->create([
                    'add_on_slug' => $addOn['slug'],
                    'name' => $addOn['name'],
                    'price_cents' => $addOn['price_cents'],
                    'squarespace_url' => $addOn['url'],
                    'status' => AddOnStatus::ACTIVE,
                    'container_id' => $containerId,
                    'squarespace_order_id' => $squarespaceOrderId,
                    'requested_at' => Carbon::now(),
                    'activated_at' => Carbon::now(),
                ]);

                return $record;
            });

            $record->setRelation('studentProfile', $profile->loadMissing('user'));
            $this->notifications->addOnPurchased($record);
        }
    }

    /**
     * Student's purchased / requested add-ons, newest first.
     *
     * @return Collection<int, StudentAddOn>
     */
    public function purchasesFor(StudentProfile $profile): Collection
    {
        return $profile->addOns()->with('container')->get();
    }
}
