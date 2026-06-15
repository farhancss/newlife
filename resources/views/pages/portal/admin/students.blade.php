@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Student Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage student records and move details.</p>
            </div>
            <form method="GET" action="{{ route('admin.students') }}" class="flex w-full max-w-sm gap-2 sm:w-auto">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search name or Move ID…"
                    class="h-11 flex-1 rounded-lg border border-gray-300 px-4 text-sm shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-500/20" />
                <button type="submit" class="rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Search</button>
            </form>
        </div>

        <x-portal.data-table table-class="min-w-[850px]">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Move ID</th>
                    <th>University</th>
                    <th>Package</th>
                    <th>Ship by</th>
                    <th>Status</th>
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
                        </td>
                        <td>{{ $student->new_life_id }}</td>
                        <td>{{ $student->housingInfo?->university ?: $student->school ?: '—' }}</td>
                        <td>{{ $student->package?->shortLabel() ?: '—' }}</td>
                        <td>{{ $row['eta'] ? $row['eta']->format('M j, Y') : '—' }}</td>
                        <td><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $row['status'] }}</span></td>
                        <td>
                            <div class="flex items-center gap-2 whitespace-nowrap">
                                <a href="{{ route('admin.students.show', $student) }}"
                                    class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-brand-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    View
                                </a>
                                <a href="{{ route('admin.containers', ['q' => $student->new_life_id]) }}"
                                    class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200">Containers</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <p class="text-sm font-medium text-gray-900">No students found</p>
                            <p class="mt-1 text-sm text-gray-500">Students appear here after they are provisioned from Squarespace.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-portal.data-table>
    </div>
@endsection
