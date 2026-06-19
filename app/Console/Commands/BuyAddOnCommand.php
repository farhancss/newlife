<?php

namespace App\Console\Commands;

use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AddOnService;
use Illuminate\Console\Command;

class BuyAddOnCommand extends Command
{
    protected $signature = 'portal:buy-addon
        {student : Student email or New Life ID}
        {slug : Add-on catalog slug (e.g. additional-container)}';

    protected $description = 'Temporarily buy an add-on for a student. The Additional Container add-on also provisions a trackable container.';

    public function handle(AddOnService $addOns): int
    {
        $profile = $this->resolveProfile((string) $this->argument('student'));

        if ($profile === null) {
            $this->error('No student matched "' . $this->argument('student') . '" (try an email or New Life ID).');

            return self::FAILURE;
        }

        $slug = trim((string) $this->argument('slug'));
        $catalogEntry = $addOns->findInCatalog($slug);

        if ($catalogEntry === null) {
            $this->error("Unknown add-on slug \"{$slug}\". Available slugs:");
            foreach ($addOns->catalog() as $entry) {
                $this->line("  - {$entry['slug']} ({$entry['formatted_price']})");
            }

            return self::FAILURE;
        }

        $addOn = $addOns->purchase($profile, $catalogEntry, $this->resolveAdminActor());

        $this->info('Add-on purchased.');
        $this->line("  Student:      {$profile->fullName()} ({$profile->new_life_id})");
        $this->line("  Add-on:       {$addOn->name}");
        $this->line("  Price:        {$addOn->formattedPrice()}");
        $this->line("  Status:       {$addOn->statusLabel()}");

        if ($addOn->container) {
            $this->newLine();
            $this->info('Trackable container provisioned:');
            $this->line("  Container:    {$addOn->container->code}");
            $this->line("  Status:       {$addOn->container->statusLabel()}");
            $this->line('  Manage it from Admin → Containers, or the student\'s add-on detail page.');
        }

        return self::SUCCESS;
    }

    private function resolveProfile(string $identifier): ?StudentProfile
    {
        $identifier = trim($identifier);

        $byNewLifeId = StudentProfile::query()->where('new_life_id', $identifier)->first();

        if ($byNewLifeId instanceof StudentProfile) {
            return $byNewLifeId;
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($identifier)])->first();

        return $user?->studentProfile;
    }

    private function resolveAdminActor(): ?User
    {
        return User::query()->where('role', \App\Enums\UserRole::ADMIN)->orderBy('id')->first();
    }
}
