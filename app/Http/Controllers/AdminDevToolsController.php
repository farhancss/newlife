<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\StudentProfile;
use App\Services\AddOnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminDevToolsController extends Controller
{
    public function __construct()
    {
        // The whole feature is hidden unless explicitly enabled via the environment.
        abort_unless((bool) config('devtools.enabled'), Response::HTTP_NOT_FOUND);
    }

    public function index(AddOnService $addOnService): View
    {
        return view('pages.portal.admin.dev-tools', [
            'title' => 'Developer Tools',
            'pageHeading' => 'Developer Tools',
            'portal' => 'admin',
            'catalog' => $addOnService->catalog(),
            'packages' => Package::query()->orderBy('sort_order')->get(),
            'students' => $this->students(),
        ]);
    }

    public function inviteStudent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'package' => ['required', 'string', Rule::in($this->packageSlugs())],
        ]);

        $options = [
            'email' => $validated['email'],
            '--package' => $validated['package'],
        ];

        if (! empty($validated['first_name'])) {
            $options['--first-name'] = $validated['first_name'];
        }
        if (! empty($validated['last_name'])) {
            $options['--last-name'] = $validated['last_name'];
        }

        return $this->runCommand(
            'portal:invite-student',
            $options,
            "Student {$validated['email']} onboarded and invitation email sent."
        );
    }

    public function buyAddon(Request $request, AddOnService $addOnService): RedirectResponse
    {
        $slugs = $addOnService->catalog()->pluck('slug')->all();

        $validated = $request->validate([
            'student' => ['required', 'string', Rule::in($this->studentEmails())],
            'slug' => ['required', 'string', Rule::in($slugs)],
        ]);

        $addOnName = $addOnService->findInCatalog($validated['slug'])['name'] ?? $validated['slug'];

        return $this->runCommand('portal:buy-addon', [
            'student' => $validated['student'],
            'slug' => $validated['slug'],
        ], "“{$addOnName}” add-on purchased for {$validated['student']}.");
    }

    /**
     * @return \Illuminate\Support\Collection<int, StudentProfile>
     */
    private function students()
    {
        return StudentProfile::query()
            ->with('user')
            ->whereHas('user')
            ->get()
            ->sortBy(fn (StudentProfile $profile) => $profile->fullName() ?: $profile->user?->email)
            ->values();
    }

    /**
     * @return list<string>
     */
    private function studentEmails(): array
    {
        return $this->students()
            ->pluck('user.email')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function packageSlugs(): array
    {
        return Package::query()->pluck('slug')->all();
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function runCommand(string $command, array $arguments, string $successMessage): RedirectResponse
    {
        try {
            $exitCode = Artisan::call($command, $arguments);
            $output = trim(Artisan::output());
        } catch (\Throwable $e) {
            $exitCode = 1;
            $output = $e->getMessage();
        }

        $success = $exitCode === 0;

        return redirect()
            ->route('admin.dev-tools.index')
            ->with('dev_result', [
                'success' => $success,
                'message' => $success
                    ? $successMessage
                    : ('Command failed. ' . ($output !== '' ? $output : 'Please check the inputs and try again.')),
            ]);
    }
}
