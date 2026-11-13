@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-8">
        <div class="w-full max-w-md">
            <section class="rounded-2xl border border-slate-200 bg-white px-7 py-9 shadow-sm sm:px-10 sm:py-10">
                <div class="mb-8 text-center">
                    <img class="mx-auto h-16 w-16 object-contain" src="{{ asset('images/logo-smaknis.png') }}" alt="Logo SMK Nurul Islam Affandiyah">
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Survei SMAKNIS</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Masuk menggunakan akun sekolah Anda.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Username</span>
                        <input type="text" name="username" value="{{ old('username') }}" required autofocus
                               autocomplete="username" placeholder="Masukkan username"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100">
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
                        <input type="password" name="password" required
                               autocomplete="current-password" placeholder="Masukkan password"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100">
                    </label>

                    <button type="submit"
                            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Masuk
                    </button>
                </form>
            </section>
        </div>
    </main>
@endsection
