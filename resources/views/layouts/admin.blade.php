@extends('layouts.app')

@section('content')
    <style>
        @keyframes dash-rise {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: no-preference) {
            .dash-rise { animation: dash-rise .5s cubic-bezier(.22, 1, .36, 1) backwards; }
        }
    </style>

    <div class="mx-auto flex min-h-screen w-full max-w-[1600px] border-x border-slate-200 bg-slate-50">
        <x-admin-sidebar />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col bg-slate-50">
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 sm:px-8">
                <div class="min-w-0">
                    @hasSection('admin-subtitle')
                        <h1 class="truncate text-sm font-bold leading-5 text-slate-900">@hasSection('admin-heading')@yield('admin-heading')@else @yield('title', 'Survei SMAKNIS') @endif</h1>
                        <p class="line-clamp-2 text-xs leading-4 text-slate-500">@yield('admin-subtitle')</p>
                    @else
                        <p class="truncate text-sm font-bold text-slate-900">@yield('title', 'Survei SMAKNIS')</p>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @yield('admin-topbar-actions')
                    <button type="button" data-admin-theme-toggle aria-pressed="false" aria-label="Mode gelap" title="Mode gelap"
                        class="inline-flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                        <path d="M20.9 13.3A9 9 0 0 1 10.7 3.1a9 9 0 1 0 10.2 10.2Z" />
                    </svg>
                    </button>
                </div>
            </header>

            <main class="mx-auto w-full @yield('admin-main-width', 'max-w-6xl') flex-1 px-6 pb-12 pt-8 sm:px-8">
                @yield('admin-content')
            </main>

            <footer class="border-t border-slate-200 px-6 py-4 sm:px-8">
                <p class="text-xs text-slate-500">&copy; 2026 SMK Nurul Islam Affandiyah, Cianjur</p>
            </footer>
        </div>
    </div>
@endsection

@push('head')
    <script>
        (() => {
            let theme;
            try {
                theme = localStorage.getItem('smaknis-admin-theme');
            } catch {}

            document.documentElement.dataset.adminTheme = theme === 'light' || theme === 'dark'
                ? theme
                : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        })();
    </script>
    {{-- Preload font ikon Material Symbols agar terunduh sebelum render (cegah ligature tampil sebagai teks mentah) --}}
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-outlined.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-fill.woff2') }}" crossorigin>
@endpush
