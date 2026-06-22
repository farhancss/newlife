<?php

namespace App\Console\Commands;

use App\Services\DeadlineService;
use Illuminate\Console\Command;

class EvaluateDeadlinesCommand extends Command
{
    protected $signature = 'deadlines:evaluate';

    protected $description = 'Complete satisfied deadlines, flag overdue ones, and send one-day reminders (run daily).';

    public function handle(DeadlineService $deadlines): int
    {
        $summary = $deadlines->evaluate();

        $this->info(sprintf(
            'Deadlines evaluated — %d completed, %d overdue, %d reminded.',
            $summary['completed'],
            $summary['overdue'],
            $summary['reminded'],
        ));

        return self::SUCCESS;
    }
}
