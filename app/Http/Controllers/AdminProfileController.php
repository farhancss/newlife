<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function show(): View
    {
        return view('pages.portal.admin.profile', [
            'title' => 'My Profile',
            'pageHeading' => 'My Profile',
            'portal' => 'admin',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'name' => $validated['name'],
        ])->save();

        return redirect()
            ->route('admin.profile')
            ->with('status', 'Profile updated successfully.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $disk = (string) config('portal.avatars.disk', 'public');
        $maxKb = (int) config('portal.avatars.max_size_kb', 4096);
        /** @var list<string> $allowed */
        $allowed = (array) config('portal.avatars.allowed_mimes', ['jpeg', 'jpg', 'png', 'webp']);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:' . implode(',', $allowed), 'max:' . $maxKb],
        ]);

        $previous = $user->avatar_path;
        $path = $request->file('avatar')->store("avatars/{$user->id}", $disk);

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous !== null && $previous !== '' && $previous !== $path) {
            Storage::disk($disk)->delete($previous);
        }

        return redirect()
            ->route('admin.profile')
            ->with('status', 'Profile photo updated.');
    }

    public function destroyAvatar(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path !== null && $user->avatar_path !== '') {
            Storage::disk((string) config('portal.avatars.disk', 'public'))->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return redirect()
            ->route('admin.profile')
            ->with('status', 'Profile photo removed.');
    }
}
