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
<body class="bg-night">
    <div class="relative flex min-h-screen flex-col overflow-hidden">
        <div class="pointer-events-none absolute -top-32 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-accent/10 blur-3xl"></div>

        <div class="relative flex flex-1 items-center justify-center px-4 py-10">
            @yield('content')
        </div>

        <p class="relative pb-6 text-center text-[12px] text-muted">Survei SMAKNIS</p>
    </div>
</body>
</html>
