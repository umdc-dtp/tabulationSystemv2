@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Home')

@section('content')
    <section class="rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-900 p-7 text-white shadow-xl shadow-maroon-900/10 sm:p-9">
        <p class="text-sm font-semibold text-maroon-200">Welcome back</p>
        <h2 class="mt-2 text-3xl font-semibold tracking-tight">{{ auth()->user()->name }}</h2>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">Use the menu to manage events, user accounts, activity logs, and system settings.</p>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Dashboard summary">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Active events</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">0</p>
            <p class="mt-2 text-xs text-slate-400">No active events</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Registered users</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">—</p>
            <p class="mt-2 text-xs text-slate-400">Connect user statistics</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Recent activity</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">—</p>
            <p class="mt-2 text-xs text-slate-400">Connect system logs</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">System status</p>
            <div class="mt-4 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-base font-semibold text-slate-950">Ready</span>
            </div>
            <p class="mt-3 text-xs text-slate-400">Dashboard is available</p>
        </article>
    </section>
@endsection
