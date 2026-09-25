<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event->name }} | Leaderboard</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-maroon-700">Tabulation System</p>
            @if ($internal)
                <a href="{{ route('events.show', $event) }}" class="text-sm font-semibold text-maroon-700 hover:underline">Back to event</a>
            @endif
        </div>

        <header class="rounded-3xl bg-gradient-to-br from-maroon-700 to-maroon-950 p-7 text-white shadow-xl shadow-maroon-950/10 sm:p-10">
            <p class="text-sm font-semibold text-maroon-200">{{ $internal ? 'Internal leaderboard' : 'Event leaderboard' }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-5xl">{{ $event->name }}</h1>
            <p class="mt-3 text-sm text-maroon-100/80">{{ $event->start_date->format('F j, Y') }} – {{ $event->end_date->format('F j, Y') }}</p>
            <p id="leaderboard-status" class="mt-5 inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white" aria-live="polite">Loading results…</p>
        </header>

        @if ($internal)
            <section class="mt-6 flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-6 sm:flex-row sm:items-center">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&amp;data={{ urlencode(route('leaderboards.show', $event)) }}" alt="QR code for the public leaderboard" width="120" height="120" class="h-30 w-30 rounded-lg border border-slate-200 bg-white p-1">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-950">Share the public leaderboard</h2>
                    <p class="mt-1 text-sm text-slate-500">Visitors can scan this code or enter the link. The ranking table is obscured while frozen.</p>
                    <a href="{{ route('leaderboards.show', $event) }}" class="mt-3 block break-all text-sm font-semibold text-maroon-700 hover:underline">{{ route('leaderboards.show', $event) }}</a>
                </div>
            </section>
        @endif

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-4 border-b border-slate-200 p-5 sm:grid-cols-3">
                <div>
                    <label for="leaderboard-scope" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">View</label>
                    <select id="leaderboard-scope" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                        <option value="overall">Overall</option>
                        <option value="category">By category</option>
                        <option value="game">By game</option>
                    </select>
                </div>
                <div id="category-filter" class="hidden">
                    <label for="leaderboard-category" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                    <select id="leaderboard-category" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                        @foreach ($event->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="game-filter" class="hidden">
                    <label for="leaderboard-game" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Game</label>
                    <select id="leaderboard-game" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                        @foreach ($event->categories as $category)
                            @foreach ($category->competitions as $competition)
                                <option value="{{ $competition->id }}" data-scoring-method="{{ $competition->scoring_method->value }}">{{ $category->name }} · {{ $competition->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="frozen-message" class="hidden border-b border-amber-200 bg-amber-50 px-6 py-4 text-sm font-semibold text-amber-900" role="status">The public ranking table is frozen. Check back later.</div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-6 py-4">Rank</th><th class="px-6 py-4">Competitor / department</th><th id="department-heading" class="hidden px-6 py-4">Department</th><th id="result-heading" class="hidden px-6 py-4 text-right">Score</th><th class="px-6 py-4 text-right">Points</th></tr>
                    </thead>
                    <tbody id="leaderboard-rows" class="divide-y divide-slate-100">
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Loading results…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        <p class="mt-4 text-center text-xs text-slate-500">Finalized results update automatically.</p>
    </main>

    <script>
        (() => {
            const dataUrl = @json($dataUrl);
            const scope = document.getElementById('leaderboard-scope');
            const category = document.getElementById('leaderboard-category');
            const game = document.getElementById('leaderboard-game');
            const rows = document.getElementById('leaderboard-rows');
            const status = document.getElementById('leaderboard-status');
            const frozenMessage = document.getElementById('frozen-message');
            const departmentHeading = document.getElementById('department-heading');
            const resultHeading = document.getElementById('result-heading');
            let loading = false;

            function showMessage(message) {
                rows.replaceChildren();
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 5;
                cell.className = 'px-6 py-10 text-center text-slate-500';
                cell.textContent = message;
                row.append(cell);
                rows.append(row);
            }

            function cell(row, value, className = 'px-6 py-4') {
                const element = document.createElement('td');
                element.className = className;
                element.textContent = value;
                row.append(element);
                return element;
            }

            async function refresh() {
                if (loading) return;
                loading = true;
                const currentScope = scope.value;
                document.getElementById('category-filter').classList.toggle('hidden', currentScope !== 'category');
                document.getElementById('game-filter').classList.toggle('hidden', currentScope !== 'game');
                departmentHeading.classList.toggle('hidden', currentScope !== 'game');
                resultHeading.classList.toggle('hidden', currentScope !== 'game');
                resultHeading.textContent = game.selectedOptions[0]?.dataset.scoringMethod === 'wins' ? 'W–L' : 'Score';

                const url = new URL(dataUrl);
                url.searchParams.set('scope', currentScope);
                if (currentScope === 'category' && category.value) url.searchParams.set('category_id', category.value);
                if (currentScope === 'game' && game.value) url.searchParams.set('competition_id', game.value);

                try {
                    if ((currentScope === 'category' && !category.value) || (currentScope === 'game' && !game.value)) {
                        showMessage('No categories or games have been configured.');
                        status.textContent = 'Results pending';
                        return;
                    }

                    const response = await fetch(url, { cache: 'no-store', headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Unable to load the leaderboard.');
                    const data = await response.json();
                    frozenMessage.classList.toggle('hidden', !data.frozen);
                    rows.replaceChildren();

                    if (data.frozen) {
                        status.textContent = 'Public results frozen';
                        const row = document.createElement('tr');
                        row.className = 'blur-sm select-none';
                        cell(row, '#—');
                        cell(row, 'Results hidden until unfrozen');
                        if (currentScope === 'game') cell(row, '—');
                        if (currentScope === 'game') cell(row, '—', 'px-6 py-4 text-right');
                        cell(row, '—', 'px-6 py-4 text-right');
                        rows.append(row);
                    } else if (data.rows.length === 0) {
                        status.textContent = 'Results pending';
                        showMessage('Results pending. No game has been finalized yet.');
                    } else {
                        status.textContent = 'Live finalized results';
                        for (const result of data.rows) {
                            const row = document.createElement('tr');
                            cell(row, `#${result.rank}`, 'px-6 py-4 font-bold text-maroon-700');
                            const name = cell(row, result.name, 'px-6 py-4 font-semibold text-slate-900');
                            if (result.members?.length) {
                                const members = document.createElement('span');
                                members.className = 'mt-1 block text-xs font-normal text-slate-500';
                                members.textContent = result.members.join(', ');
                                name.append(members);
                            }
                            if (currentScope === 'game') cell(row, result.department ?? '', 'px-6 py-4 text-slate-600');
                            if (currentScope === 'game') cell(row, result.record ?? result.result ?? '', 'px-6 py-4 text-right text-slate-600');
                            cell(row, Number(result.points).toFixed(2), 'px-6 py-4 text-right font-semibold');
                            rows.append(row);
                        }
                    }
                } catch {
                    status.textContent = 'Connection problem';
                    showMessage('Could not load results. Retrying shortly.');
                } finally {
                    loading = false;
                }
            }

            [scope, category, game].forEach(element => element.addEventListener('change', refresh));
            refresh();
            setInterval(refresh, 5000);
        })();
    </script>
</body>
</html>
