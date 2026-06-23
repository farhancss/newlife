@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Support Center</h1>
            <p class="mt-1 text-sm text-gray-600">Submit support tickets and browse help topics.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Submit a Support Ticket</h2>
                <form class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                        <select class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                            <option>Move Status</option>
                            <option>Container Issue</option>
                            <option>Billing Question</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Message</label>
                        <textarea rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Describe your issue"></textarea>
                    </div>
                    <button type="button" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white">Submit Ticket</button>
                </form>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Help Topics</h2>
                <ul class="mt-3 space-y-2 text-sm text-gray-700">
                    <li class="rounded-lg bg-gray-50 px-3 py-2">Getting Started</li>
                    <li class="rounded-lg bg-gray-50 px-3 py-2">Tracking Containers</li>
                    <li class="rounded-lg bg-gray-50 px-3 py-2">Billing & Payments</li>
                    <li class="rounded-lg bg-gray-50 px-3 py-2">Refund Policy</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
