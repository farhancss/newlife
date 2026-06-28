<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UploadContainerHubPhotoRequest;
use App\Models\Container;
use App\Models\ContainerPhoto;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-side container photo evidence captured when a container is received at
 * the New Life hub. These complement the student's exterior packing photos and
 * are surfaced back to the student as proof of condition.
 */
class AdminContainerPhotoController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function store(UploadContainerHubPhotoRequest $request, Container $container): RedirectResponse
    {
        abort_unless(
            $container->acceptsHubPhotos(),
            Response::HTTP_FORBIDDEN,
            'Evidence photos can only be uploaded once the container is delivered to the dorm.',
        );

        $disk = (string) config('portal.container_photos.disk', 'public');
        $remaining = $container->remainingHubPhotoSlots();

        /** @var array<int, UploadedFile> $files */
        $files = $request->file('photos', []);

        if ($remaining <= 0) {
            return back()->withErrors([
                'photos' => "This container already has the maximum of {$container->hubPhotoCap()} hub evidence photos.",
            ]);
        }

        if (count($files) > $remaining) {
            return back()->withErrors([
                'photos' => "You can upload up to {$remaining} more hub evidence photo(s) for this container.",
            ]);
        }

        /** @var User $admin */
        $admin = Auth::user();
        $stored = 0;

        foreach ($files as $file) {
            $path = $file->store("container-photos/{$container->id}/hub", $disk);

            ContainerPhoto::query()->create([
                'container_id' => $container->id,
                'type' => ContainerPhoto::TYPE_HUB_INTAKE,
                'uploaded_by_user_id' => $admin->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);

            $stored++;
        }

        if ($stored > 0) {
            $container->loadMissing('studentProfile.user');
            $this->notifications->containerHubEvidenceAdded($container, $stored, $admin);
        }

        return back()->with('status', "Uploaded {$stored} hub evidence photo(s) for container {$container->code}.");
    }

    public function destroy(Container $container, ContainerPhoto $photo): RedirectResponse
    {
        abort_unless($photo->container_id === $container->id, Response::HTTP_NOT_FOUND);
        abort_unless($photo->type === ContainerPhoto::TYPE_HUB_INTAKE, Response::HTTP_FORBIDDEN, 'Only hub evidence photos can be removed here.');

        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Hub evidence photo removed.');
    }
}
