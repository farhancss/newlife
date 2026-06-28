@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Squarespace integration</h1>
                <p class="mt-1 max-w-2xl text-sm text-gray-600">
                    Connect your Squarespace store over OAuth, register the webhook subscription, and
                    review every notification and API call. New orders and contacts sync into the portal
                    automatically.
                </p>
            </div>
            <a href="{{ $logViewerUrl }}" target="_blank" rel="noopener"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h13M9 11L4 7m5 4l-5 4M3 4h6"/></svg>
                Open log viewer
            </a>
        </div>

        {{-- Connection status --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <span @class([
                        'mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                        'bg-emerald-50 text-emerald-600' => $connected,
                        'bg-amber-50 text-amber-600' => ! $connected,
                    ])>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">OAuth connection</h2>
                        @if (! $configured)
                            <p class="mt-1 text-sm text-amber-700">
                                Missing credentials. Set <code class="rounded bg-amber-50 px-1">SQUARESPACE_CLIENT_ID</code>
                                and <code class="rounded bg-amber-50 px-1">SQUARESPACE_CLIENT_SECRET</code> in your environment.
                            </p>
                        @elseif ($connected)
                            <p class="mt-1 text-sm text-emerald-700">Connected and ready.</p>
                            @unless ($canRefresh)
                                <p class="mt-1 text-xs text-amber-700">No refresh token was returned — the access token will expire and you'll need to reconnect. Ensure the app requests offline access.</p>
                            @endunless
                        @else
                            <p class="mt-1 text-sm text-gray-600">Not connected yet. Authorize access to start receiving data.</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if ($connected)
                        <a href="{{ route('admin.squarespace.connect') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Reconnect
                        </a>
                        <form method="POST" action="{{ route('admin.squarespace.disconnect') }}"
                            onsubmit="return confirm('Disconnect Squarespace? Stored tokens will be removed.');">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                                Disconnect
                            </button>
                        </form>
                    @else
                        <a href="{{ $configured ? route('admin.squarespace.connect') : '#' }}"
                            @class([
                                'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-sm',
                                'bg-brand-500 hover:bg-brand-700' => $configured,
                                'cursor-not-allowed bg-gray-300' => ! $configured,
                            ])>
                            Connect Squarespace
                        </a>
                    @endif
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 border-t border-gray-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Website ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $credential?->website_id ?? config('squarespace.website_id') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Token expires</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $credential?->expires_at?->diffForHumans() ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Connected</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $credential?->connected_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2 lg:col-span-1">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Scopes</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @forelse ($scopes as $scope)
                            <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $scope }}</span>
                        @empty
                            <span class="text-sm text-gray-500">—</span>
                        @endforelse
                    </dd>
                </div>
            </dl>

            <div class="mt-4 rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Redirect URI (must match your Squarespace app)</p>
                <p class="mt-1 break-all font-mono text-xs text-gray-700">{{ $redirectUri }}</p>
            </div>
        </div>

        {{-- Webhook subscription --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Webhook subscription</h2>
                    <p class="mt-1 text-sm text-gray-600">Squarespace will POST notifications to this endpoint.</p>
                    <p class="mt-2 break-all rounded-lg bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">{{ $endpointUrl }}</p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach ($topics as $topic)
                            <span class="inline-flex rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $topic }}</span>
                        @endforeach
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.squarespace.webhooks.register') }}">
                    @csrf
                    <button type="submit"
                        @disabled(! $connected)
                        @class([
                            'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-sm',
                            'bg-brand-500 hover:bg-brand-700' => $connected,
                            'cursor-not-allowed bg-gray-300' => ! $connected,
                        ])>
                        Register / refresh subscription
                    </button>
                </form>
            </div>

            @unless ($connected)
                <p class="mt-3 text-xs text-amber-700">Connect over OAuth first — the WebhookSubscriptions API requires an access token.</p>
            @endunless

            <div class="mt-5 overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-2.5">Subscription</th>
                            <th class="px-4 py-2.5">Topics</th>
                            <th class="px-4 py-2.5">Secret</th>
                            <th class="px-4 py-2.5">Last test</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($subscriptions as $sub)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <span class="block font-mono text-xs text-gray-700">{{ $sub->subscription_id }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-400">{{ $sub->remote_created_on?->format('M j, Y') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (($sub->topics ?? []) as $topic)
                                            <span class="inline-flex rounded bg-blue-50 px-1.5 py-0.5 text-[11px] font-medium text-blue-700">{{ $topic }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($sub->secret)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Stored
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">None</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    @if ($sub->last_test_at)
                                        HTTP {{ $sub->last_test_status }} · {{ $sub->last_test_at->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.squarespace.webhooks.test', $sub) }}" class="flex items-center gap-1">
                                            @csrf
                                            <select name="topic" class="rounded-lg border border-gray-300 py-1 pl-2 pr-7 text-xs focus:border-brand-500 focus:ring-brand-500">
                                                @foreach (($sub->topics ?? $topics) as $topic)
                                                    <option value="{{ $topic }}">{{ $topic }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">Test</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.squarespace.webhooks.rotate', $sub) }}"
                                            onsubmit="return confirm('Rotate the signing secret? The old secret stops working immediately.');">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">Rotate</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.squarespace.webhooks.delete', $sub) }}"
                                            onsubmit="return confirm('Delete this webhook subscription?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 bg-white px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                    No webhook subscriptions registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Logs --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-6-8h6M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Request &amp; response logs</h2>
                        <p class="mt-1 max-w-xl text-sm text-gray-600">
                            Every inbound webhook and outbound API call is written to the
                            <code class="rounded bg-gray-100 px-1">{{ $logChannel }}</code> log channel, including the full
                            request and response bodies (sensitive tokens masked). Browse them in Log Viewer.
                        </p>
                    </div>
                </div>
                <a href="{{ $logViewerUrl }}" target="_blank" rel="noopener"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    Open log viewer
                </a>
            </div>
        </div>
    </div>
@endsection
