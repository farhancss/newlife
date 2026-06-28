<?php

namespace App\Console\Commands;

use App\Services\Squarespace\SquarespaceOAuthService;
use Illuminate\Console\Command;
use Throwable;

class ShowSquarespaceToken extends Command
{
    protected $signature = 'squarespace:token {--curl : Print a ready-to-use curl Authorization header}';

    protected $description = 'Print the current Squarespace OAuth access token (for manual API testing)';

    public function handle(SquarespaceOAuthService $oauth): int
    {
        if (! $oauth->isConnected()) {
            $this->error('Squarespace is not connected. Complete the OAuth connection from the admin panel first.');

            return self::FAILURE;
        }

        try {
            $token = $oauth->validAccessToken();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('curl')) {
            $this->line("--header 'Authorization: Bearer {$token}'");
            $this->line("--header 'User-Agent: " . config('squarespace.user_agent') . "'");
        } else {
            $this->line($token);
        }

        return self::SUCCESS;
    }
}
