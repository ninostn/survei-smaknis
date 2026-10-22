<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Survei SMAKNIS')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-night">
    @php
        $role = auth()->user()->role ?? null;

        $nav = match ($role) {
            'murid' => [
                ['label' => 'Dashboard', 'url' => '/murid', 'active' => request()->is('murid')],
                ['label' => 'Survei', 'url' => route('murid.surveys.index'), 'active' => request()->routeIs('murid.surveys.index', 'murid.surveys.show')],
                ['label' => 'Riwayat', 'url' => route('murid.surveys.history'), 'active' => request()->routeIs('murid.surveys.history')],
            ],
            default => [
                ['label' => 'Dashboard', 'url' => $role === 'admin' ? '/admin' : '/guru', 'active' => request()->is('admin') || request()->is('guru')],
                ['label' => 'Survei', 'url' => route('surveys.index'), 'active' => request()->routeIs('surveys.*')],
            ],
        };
    @endphp

    <header class="sticky top-0 z-20 border-b border-line bg-night/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
            <a href="/dashboard" class="flex shrink-0 items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-control border border-accent/40 bg-accent/10 font-mono text-[13px] font-bold text-accent">S</span>
                <span class="hidden text-sm font-semibold tracking-tight sm:block">Survei SMAKNIS</span>
            </a>

            <nav class="flex items-center gap-1 overflow-x-auto">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'is-active' : '' }} whitespace-nowrap">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-3">
                <div class="hidden text-right md:block">
                    <p class="text-[13px] leading-tight">{{ auth()->user()->nama ?? '' }}</p>
                    <p class="text-[11px] uppercase tracking-wide text-muted">{{ $role }}</p>
                </div>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="rounded-control border border-line px-3 py-2 text-[13px] font-medium text-muted transition hover:border-danger/50 hover:text-danger">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1>@yield('heading', 'Dashboard')</h1>
                @hasSection('subheading')
                    <p class="mt-1 text-sm text-muted">@yield('subheading')</p>
                @endif
            </div>
            @hasSection('actions')
                <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
            @endif
        </div>

        @include('partials.flash')

        @yield('content')
    </main>
</body>
</html>
