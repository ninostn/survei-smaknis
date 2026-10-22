<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Isi Survei')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-night">
    <header class="sticky top-0 z-20 border-b border-line bg-night/90 backdrop-blur">
        <div class="mx-auto flex max-w-2xl items-center gap-3 px-4 py-3">
            <div class="min-w-0">
                <p class="truncate text-[13px] font-medium">{{ $survey->judul ?? 'Isi Survei' }}</p>
                <p class="text-[11px] text-muted">{{ auth()->user()->nama ?? '' }}</p>
            </div>
            <a href="{{ route('murid.surveys.index') }}"
               class="ml-auto shrink-0 rounded-control border border-line px-3 py-2 text-[13px] font-medium text-muted transition hover:border-danger/50 hover:text-danger">
                Batal
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-2xl px-4 pb-32 pt-5">
        @include('partials.flash')
        @yield('content')
    </main>
</body>
</html>
