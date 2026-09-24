@extends('layouts.admin')

@section('title', 'Logs')
@section('heading', 'Activity logs')

@section('content')
    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 text-white shadow-xl shadow-maroon-950/10">
        <div class="flex flex-col gap-5 p-7 sm:p-9 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-maroon-200">System audit trail</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight">Activity logs</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">Review sign-in attempts and data changes made throughout the system.</p>
            </div>

            <a href="{{ route('logs.download', array_filter($filters, static fn ($value) => $value !== null && $value !== '')) }}" data-no-loading class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/30">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Download CSV
            </a>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <form method="GET" action="{{ route('logs.index') }}" class="grid gap-4 lg:grid-cols-[minmax(16rem,1fr)_13rem_10rem_auto] lg:items-end">
            <div>
                <label for="log-search" class="mb-1.5 block text-sm font-semibold text-slate-700">Search logs</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" stroke-linecap="round" />
                    </svg>
                    <input id="log-search" name="q" type="search" value="{{ $filters['q'] }}" maxlength="100" class="h-11 w-full rounded-xl border border-slate-300 pl-10 pr-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="User, action, route, IP, status…">
                </div>
            </div>

            <div>
                <label for="log-sort" class="mb-1.5 block text-sm font-semibold text-slate-700">Sort by</label>
                <select id="log-sort" name="sort" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                    <option value="created_at" @selected($filters['sort'] === 'created_at')>Date and time</option>
                    <option value="actor_name" @selected($filters['sort'] === 'actor_name')>User</option>
                    <option value="action" @selected($filters['sort'] === 'action')>Action</option>
                    <option value="method" @selected($filters['sort'] === 'method')>Request method</option>
                    <option value="status_code" @selected($filters['sort'] === 'status_code')>Status code</option>
                </select>
            </div>

            <div>
                <label for="log-direction" class="mb-1.5 block text-sm font-semibold text-slate-700">Direction</label>
                <select id="log-direction" name="direction" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                    <option value="desc" @selected($filters['direction'] === 'desc')>Descending</option>
                    <option value="asc" @selected($filters['direction'] === 'asc')>Ascending</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="h-11 flex-1 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Apply</button>
                @if ($filters['q'] !== null || $filters['sort'] !== 'created_at' || $filters['direction'] !== 'desc')
                    <a href="{{ route('logs.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Recorded interactions</h3>
                <p class="mt-1 text-sm text-slate-500">{{ number_format($logs->total()) }} {{ Str::plural('record', $logs->total()) }} found</p>
            </div>
            <p class="text-xs text-slate-400">Form values and passwords are never recorded.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1280px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">Date & time</th>
                        <th class="px-5 py-3.5 font-semibold">User</th>
                        <th class="px-5 py-3.5 font-semibold">Interaction</th>
                        <th class="px-5 py-3.5 font-semibold">Affected record</th>
                        <th class="px-5 py-3.5 font-semibold">Request</th>
                        <th class="px-5 py-3.5 font-semibold">IP address</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        @php
                            $typeClasses = match ($log->interaction_type) {
                                'create' => 'bg-emerald-50 text-emerald-700',
                                'update' => 'bg-amber-50 text-amber-700',
                                'delete' => 'bg-red-50 text-red-700',
                                'authentication' => 'bg-violet-50 text-violet-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                            $resultClasses = $log->status_code < 400
                                ? 'bg-emerald-50 text-emerald-700'
                                : ($log->status_code < 500
                                    ? 'bg-amber-50 text-amber-700'
                                    : 'bg-red-50 text-red-700');
                            $affectedRecords = $log->affectedRecords();
                        @endphp
                        <tr class="align-top transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="block font-semibold text-slate-800">{{ $log->created_at->format('M j, Y') }}</span>
                                <span class="mt-0.5 block text-xs text-slate-400">{{ $log->created_at->format('g:i:s A') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-maroon-50 text-xs font-bold text-maroon-700">{{ $log->actor_name ? strtoupper(substr($log->actor_name, 0, 1)) : '?' }}</span>
                                    <span class="min-w-0">
                                        <span class="block max-w-44 truncate font-semibold text-slate-800">{{ $log->actor_name ?? 'Guest' }}</span>
                                        <span class="block max-w-44 truncate text-xs text-slate-400">{{ $log->actor_username ? '@'.$log->actor_username : 'Unauthenticated' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-2">
                                    <span class="mt-0.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $typeClasses }}">{{ $log->interaction_type }}</span>
                                    <div class="min-w-0">
                                        <p class="max-w-sm font-medium text-slate-800">{{ $log->description }}</p>
                                        <p class="mt-1 font-mono text-[11px] text-slate-400">{{ $log->action }}</p>
                                        @if ($log->context || $log->user_agent)
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-semibold text-maroon-700">Technical details</summary>
                                                <div class="mt-2 max-w-md space-y-1 break-words rounded-lg bg-slate-50 p-3 font-mono text-[11px] leading-5 text-slate-500">
                                                    @if ($log->context)
                                                        <p>{{ json_encode($log->context, JSON_UNESCAPED_SLASHES) }}</p>
                                                    @endif
                                                    @if ($log->user_agent)
                                                        <p>{{ $log->user_agent }}</p>
                                                    @endif
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @forelse ($affectedRecords as $record)
                                    <div @class(['mb-3 last:mb-0' => count($affectedRecords) > 1])>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">{{ $record['operation'] }}</span>
                                        <p class="mt-1 font-semibold text-slate-800">{{ $record['type'] }} #{{ $record['id'] }}</p>
                                        @if ($record['label'])
                                            <p class="mt-0.5 max-w-48 truncate text-xs text-slate-500" title="{{ $record['label'] }}">{{ $record['label'] }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 font-mono text-[11px] font-bold text-slate-600">{{ $log->method }}</span>
                                <p class="mt-2 max-w-56 truncate font-mono text-xs text-slate-500" title="{{ $log->path }}">{{ $log->path }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $log->duration_ms }} ms</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $resultClasses }}">{{ $log->status_code }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 5h10M9 12h10M9 19h10M5 5h.01M5 12h.01M5 19h.01" stroke-linecap="round" /></svg>
                                </span>
                                <p class="mt-4 font-semibold text-slate-700">No matching logs</p>
                                <p class="mt-1 text-sm text-slate-400">Try a different search or clear the filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </section>
@endsection
