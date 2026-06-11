<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Services\MoveProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStudentController extends Controller
{
    public function __construct(
        private readonly MoveProgressService $moveProgressService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $students = StudentProfile::query()
            ->with(['user', 'package', 'housingInfo', 'containers'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('new_life_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $rows = $students->map(function (StudentProfile $profile): array {
            $primary = $profile->containers->sortBy('id')->first();

            return [
                'profile' => $profile,
                'status' => $this->moveProgressService->currentLabel($profile),
                'eta' => $primary?->ship_by_date,
            ];
        });

        return view('pages.portal.admin.customers', [
            'title' => 'Student Management',
            'pageHeading' => 'Students',
            'portal' => 'admin',
            'rows' => $rows,
            'search' => $search,
        ]);
    }
}
