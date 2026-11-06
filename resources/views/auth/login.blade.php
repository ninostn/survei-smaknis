@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <div class="rounded-3xl border border-white bg-white/90 p-8 shadow-[0_18px_50px_-18px_rgba(150,165,205,0.45)]">
                <div class="mb-7 flex flex-col items-center text-center">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-accent-lavender to-accent-lavender-soft text-white shadow-sm">
                        <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 18h4V6L4 18zm6 0h4V6l-4 12zm6-12v12h4L16 6z"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold tracking-tight text-ink">Survei SMAKNIS</h1>
                    <p class="mt-1 text-xs font-medium text-ink-muted">SMK Nurul Islam Affandiyah</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Username</span>
                        <input type="text" name="username" value="{{ old('username') }}" required autofocus
                               autocomplete="username"
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-ink placeholder:text-slate-400 focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Password</span>
                        <input type="password" name="password" required
                               autocomplete="current-password"
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-ink placeholder:text-slate-400 focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    </label>

                    <button type="submit"
                            class="w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft focus:outline-none focus:ring-2 focus:ring-accent-lavender/40">
                        Masuk
                    </button>
                </form>
            </div>

            <p class="mt-5 text-center text-[11px] font-medium text-ink-muted">
                Masukkan username dan password yang diberikan sekolah.
            </p>
        </div>
    </main>
@endsection
