<?php

use App\Enums\ContainerStatus;
use App\Models\Container;

it('shows the admin student listing with search', function () {
    [, $profile] = completeStudent();

    $this->actingAs(makeAdmin())
        ->get(route('admin.students', ['q' => 'Student']))
        ->assertOk();

    $this->actingAs(makeAdmin())
        ->get(route('admin.students.show', $profile))
        ->assertOk();
});

it('shows the admin container listing and edit drawer', function () {
    [, $profile] = completeStudent();
    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-ADM-1',
        'status' => ContainerStatus::CONTAINER_PREPARED,
        'source' => Container::SOURCE_MOVE,
    ]);

    $this->actingAs(makeAdmin())
        ->get(route('admin.containers', ['q' => 'CTN-ADM-1', 'edit' => $container->id]))
        ->assertOk()
        ->assertSee('CTN-ADM-1');
});

it('lets an admin save container logistics fields alongside a status change', function () {
    [, $profile] = completeStudent();
    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-ADM-2',
        'status' => ContainerStatus::CONTAINER_PREPARED,
        'source' => Container::SOURCE_MOVE,
    ]);

    $this->actingAs(makeAdmin())
        ->put(route('admin.containers.update', $container), [
            'status' => ContainerStatus::LABEL_GENERATED,
            'status_note' => 'Label printed at hub',
            'location' => 'Hub Bay 3',
            'outbound_tracking' => '1Z-OUT-123',
            'internal_notes' => 'Handle with care',
        ])
        ->assertRedirect(route('admin.containers'))
        ->assertSessionHas('status');

    $container->refresh();
    expect($container->status)->toBe(ContainerStatus::LABEL_GENERATED)
        ->and($container->location)->toBe('Hub Bay 3')
        ->and($container->outbound_tracking)->toBe('1Z-OUT-123')
        ->and($container->internal_notes)->toBe('Handle with care');
});
