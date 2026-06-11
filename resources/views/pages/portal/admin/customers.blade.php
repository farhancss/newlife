@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Student Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage student records and move details.</p>
            </div>
            <form method="GET" action="{{ route('admin.customers') }}" class="flex w-full max-w-sm gap-2 sm:w-auto">
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
                        <td class="font-medium text-gray-900">{{ $student->fullName() ?: $student->user?->name }}</td>
                        <td>{{ $student->new_life_id }}</td>
                        <td>{{ $student->housingInfo?->university ?: $student->school ?: '—' }}</td>
                        <td>{{ $student->package?->shortLabel() ?: '—' }}</td>
                        <td>{{ $row['eta'] ? $row['eta']->format('M j, Y') : '—' }}</td>
                        <td><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $row['status'] }}</span></td>
                        <td>
                            <a href="{{ route('admin.containers', ['q' => $student->new_life_id]) }}"
                                class="rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200">Containers</a>
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
