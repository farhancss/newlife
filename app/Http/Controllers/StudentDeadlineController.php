<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DeadlineService;
use App\Services\StudentProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDeadlineController extends Controller
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly DeadlineService $deadlines,
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);

        $grouped = $this->deadlines->groupedForStudent($profile);

        return view('pages.portal.student.deadlines', [
            'title' => 'Deadline Center',
            'pageHeading' => 'Deadline Center',
            'portal' => 'student',
            'upcoming' => $grouped['upcoming'],
            'overdue' => $grouped['overdue'],
            'completed' => $grouped['completed'],
        ]);
    }
}
