<?php

namespace App\Http\Controllers;

use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDeadlineController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $deadlines = Deadline::query()
            ->with(['studentProfile.user', 'deadlinable'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('studentProfile', function ($profileQuery) use ($search): void {
                    $profileQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('new_life_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                })->orWhere('title', 'like', "%{$search}%");
            })
            ->when(DeadlineStatus::isValid($status), fn ($query) => $this->applyEffectiveStatus($query, $status))
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 WHEN 'upcoming' THEN 1 ELSE 2 END")
            ->orderBy('due_at')
            ->paginate(25)
            ->withQueryString();

        return view('pages.portal.admin.deadlines', [
            'title' => 'Deadline Center',
            'pageHeading' => 'Deadline Center',
            'portal' => 'admin',
            'deadlines' => $deadlines,
            'search' => $search,
            'statusFilter' => $status,
            'statuses' => DeadlineStatus::all(),
            'stats' => $this->stats(),
        ]);
    }

    /**
     * Constrain a query to an effective status — a still-open deadline whose
     * date has passed counts as overdue even before the daily sweep flips it.
     */
    private function applyEffectiveStatus($query, string $status)
    {
        $now = now();

        return match ($status) {
            DeadlineStatus::UPCOMING => $query->where('status', DeadlineStatus::UPCOMING)->where('due_at', '>=', $now),
            DeadlineStatus::OVERDUE => $query->where(function ($inner) use ($now): void {
                $inner->where('status', DeadlineStatus::OVERDUE)
                    ->orWhere(function ($q) use ($now): void {
                        $q->where('status', DeadlineStatus::UPCOMING)->where('due_at', '<', $now);
                    });
            }),
            default => $query->where('status', $status),
        };
    }

    /**
     * @return array{total: int, upcoming: int, overdue: int, completed: int}
     */
    private function stats(): array
    {
        $now = now();

        $completed = Deadline::query()->where('status', DeadlineStatus::COMPLETED)->count();
        $overdue = Deadline::query()
            ->where('status', '!=', DeadlineStatus::COMPLETED)
            ->where(function ($q) use ($now): void {
                $q->where('status', DeadlineStatus::OVERDUE)
                    ->orWhere('due_at', '<', $now);
            })
            ->count();
        $upcoming = Deadline::query()
            ->where('status', DeadlineStatus::UPCOMING)
            ->where('due_at', '>=', $now)
            ->count();

        return [
            'total' => $completed + $overdue + $upcoming,
            'upcoming' => $upcoming,
            'overdue' => $overdue,
            'completed' => $completed,
        ];
    }
}
