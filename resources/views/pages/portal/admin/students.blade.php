@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Student Management</h1>
            <p class="mt-1 text-sm text-gray-600">Manage student records and move details.</p>
        </div>

        <x-portal.data-table table-class="min-w-[850px]">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>New Life ID</th>
                    <th>University</th>
                    <th>Package</th>
                    <th data-sortable="false">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php $student = $row['profile']; @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-gray-900 hover:text-brand-700 hover:underline">
                                {{ $student->fullName() ?: $student->user?->name }}
                            </a>
                            @if ($student->user?->email)
                                <span class="mt-0.5 block text-xs text-gray-500">{{ $student->user->email }}</span>
                            @endif
                        </td>
                        <td>{{ $student->new_life_id }}</td>
                        <td>{{ $student->housingInfo?->university ?: $student->school ?: '—' }}</td>
                        <td>{{ $student->package?->shortLabel() ?: '—' }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <x-portal.action-button :href="route('admin.students.show', $student)" icon="eye">View</x-portal.action-button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <p class="text-sm font-medium text-gray-900">No students found</p>
                            <p class="mt-1 text-sm text-gray-500">Students appear here after they are provisioned from Squarespace.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-portal.data-table>
    </div>
@endsection
