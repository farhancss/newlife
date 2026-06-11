<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\UploadContainerPhotoRequest;
use App\Models\Container;
use App\Models\ContainerPhoto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StudentContainerPhotoController extends Controller
{
    public function store(UploadContainerPhotoRequest $request, Container $container): RedirectResponse
    {
        $this->authorizeContainer($container);

        abort_unless($container->acceptsPhotos(), Response::HTTP_FORBIDDEN, 'Photos can only be uploaded while your container is being packed.');

        $disk = (string) config('portal.container_photos.disk', 'public');
        $remaining = $container->remainingPhotoSlots();

        /** @var array<int, UploadedFile> $files */
        $files = $request->file('photos', []);

        if ($remaining <= 0) {
            return back()->withErrors([
                'photos' => "You have reached the maximum of {$container->photoCap()} photos for this container.",
            ]);
        }

        if (count($files) > $remaining) {
            return back()->withErrors([
                'photos' => "You can upload {$remaining} more photo(s) for this container.",
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        foreach ($files as $file) {
            $path = $file->store("container-photos/{$container->id}", $disk);

            ContainerPhoto::query()->create([
                'container_id' => $container->id,
                'uploaded_by_user_id' => $user->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }

        return back()->with('status', 'Container photos uploaded.');
    }

    public function destroy(Container $container, ContainerPhoto $photo): RedirectResponse
    {
        $this->authorizeContainer($container);

        abort_unless($photo->container_id === $container->id, Response::HTTP_NOT_FOUND);
        abort_unless($container->acceptsPhotos(), Response::HTTP_FORBIDDEN, 'Photos can only be changed while your container is being packed.');

        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Photo removed.');
    }

    private function authorizeContainer(Container $container): void
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $user->studentProfile;

        abort_unless($profile !== null && $container->student_profile_id === $profile->id, Response::HTTP_FORBIDDEN);
    }
}
