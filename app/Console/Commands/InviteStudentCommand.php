<?php

namespace App\Console\Commands;

use App\Services\AccountProvisioningService;
use Illuminate\Console\Command;

class InviteStudentCommand extends Command
{
    protected $signature = 'portal:invite-student
        {email : Student email address}
        {--first-name= : First name (defaults to email local-part)}
        {--last-name= : Last name (defaults to empty)}
        {--contact-id= : Optional external contact id (Squarespace, CRM, etc.)}
        {--no-email : Skip sending the branded invitation email}';

    protected $description = 'Provision a student account and send the branded invitation email synchronously (no queue worker required).';

    public function handle(AccountProvisioningService $provisioning): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email address is required.');

            return self::FAILURE;
        }

        $firstName = (string) ($this->option('first-name') ?? '');
        $lastName = (string) ($this->option('last-name') ?? '');
        $contactId = (string) ($this->option('contact-id') ?? '');
        $sendEmail = !$this->option('no-email');

        if ($firstName === '') {
            $firstName = (string) (explode('@', $email)[0] ?? '');
        }

        $contactPayload = [
            'contactId' => $contactId !== '' ? $contactId : null,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'primaryEmail' => ['value' => $email],
        ];

        $previousQueueConnection = config('queue.default');
        config(['queue.default' => 'sync']);

        try {
            $result = $provisioning->provisionFromContact($contactPayload, sendInvitationIfNew: $sendEmail);
        } finally {
            config(['queue.default' => $previousQueueConnection]);
        }

        $user = $result->user;
        $profile = $result->profile;

        if (!$result->isNewUser) {
            $this->warn("User {$user->email} already exists (status: {$user->status}).");
            $this->line("  New Life ID: {$profile->new_life_id}");

            return self::SUCCESS;
        }

        $this->info('Student account provisioned.');
        $this->line("  Email:           {$user->email}");
        $this->line("  Name:            {$user->name}");
        $this->line("  New Life ID:     {$profile->new_life_id}");
        $this->line("  Status:          {$user->status}");

        if ($result->temporaryPassword !== null) {
            $this->newLine();
            $this->warn('Temporary password (share securely if email delivery fails):');
            $this->line("  {$result->temporaryPassword}");
        }

        $this->newLine();
        $this->line(
            $sendEmail
                ? 'Branded invitation email sent (sync).'
                : 'Invitation email skipped (--no-email).'
        );

        return self::SUCCESS;
    }
}
