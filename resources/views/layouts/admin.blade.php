@extends('layouts.app')

@section('content')
    <header class="border-b border-white/60 bg-white/50 backdrop-blur">
        <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-1 gap-y-2 px-4 py-3">
            <a href="{{ route('admin.dashboard') }}" class="mr-3 flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-tr from-accent-lavender to-accent-lavender-soft text-white">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 18h4V6L4 18zm6 0h4V6l-4 12zm6-12v12h4L16 6z"></path>
                    </svg>
                </span>
                <span class="text-sm font-bold tracking-tight text-ink">SMAKNIS <span class="font-medium text-ink-muted">Admin</span></span>
            </a>

            @php
                $adminNav = [
                    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard'],
                    ['label' => 'Manajemen Akun', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*'],
                    ['label' => 'Kelas', 'route' => 'admin.classes.index', 'pattern' => 'admin.classes.*'],
                    ['label' => 'Mata Pelajaran', 'route' => 'admin.subjects.index', 'pattern' => 'admin.subjects.*'],
                    ['label' => 'Penugasan Mengajar', 'route' => 'admin.teaching-assignments.index', 'pattern' => 'admin.teaching-assignments.*'],
                    ['label' => 'Survei', 'route' => 'surveys.index', 'pattern' => 'surveys.*'],
                    ['label' => 'Kategori Survei', 'route' => 'admin.survey-categories.index', 'pattern' => 'admin.survey-categories.*'],
                ];
            @endphp

            <nav class="flex flex-1 flex-wrap items-center gap-1">
                @foreach ($adminNav as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors',
                           'bg-white text-ink shadow-sm ring-1 ring-slate-200/70' => request()->routeIs($item['pattern']),
                           'text-ink-muted hover:bg-white/70 hover:text-ink' => ! request()->routeIs($item['pattern']),
                       ])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                    Keluar
                </button>
            </form>
        </div>
    </header>

    @yield('admin-content')
@endsection
