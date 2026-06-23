@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Communications Center</h1>
            <p class="mt-1 text-sm text-gray-600">Manage outbound and inbound communication threads.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Compose Message</h2>
                <form class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Audience</label>
                            <select class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                <option>All Students</option>
                                <option>In Transit Students</option>
                                <option>Delivered Students</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Template</label>
                            <select class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                <option>Move ETA Reminder</option>
                                <option>Delay Notice</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Subject</label>
                        <input type="text" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" placeholder="Enter subject" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Message</label>
                        <textarea rows="5" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Type your message"></textarea>
                    </div>
                    <button type="button" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white">Send</button>
                </form>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Recent Communications</h2>
                <ul class="mt-3 space-y-2 text-sm text-gray-700">
                    <li class="rounded-lg bg-gray-50 px-3 py-2">Move ETA Broadcast - May 17</li>
                    <li class="rounded-lg bg-gray-50 px-3 py-2">Package Reminder - May 15</li>
                    <li class="rounded-lg bg-gray-50 px-3 py-2">Delay Notification - May 13</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
