@extends('layouts.guest')

@section('title', 'Masuk - Survei SMAKNIS')

@section('content')
    <div class="w-full max-w-sm">
        <div class="mb-7 text-center">
            <span class="mx-auto grid h-11 w-11 place-items-center rounded-card border border-accent/40 bg-accent/10 font-mono text-base font-bold text-accent">S</span>
            <h1 class="mt-4 text-xl">Survei SMAKNIS</h1>
            <p class="mt-1.5 text-sm text-muted">Masuk untuk mengisi atau mengelola survei.</p>
        </div>

        <div class="rounded-card border border-line bg-surface p-6">
            @include('partials.flash')

            <form method="POST" action="/login" class="space-y-4">
                @csrf

                <x-field label="NIS / NIP / Username" name="nis">
                    <input id="nis" name="nis" type="text" class="input" value="{{ old('nis') }}"
                           placeholder="1003" autocomplete="username" autofocus>
                </x-field>

                <x-field label="Password" name="password">
                    <input id="password" name="password" type="password" class="input"
                           placeholder="••••••••" autocomplete="current-password">
                </x-field>

                <x-button type="submit" class="w-full">Masuk</x-button>
            </form>
        </div>

        <p class="mt-5 text-center text-[12px] leading-relaxed text-muted">
            Akun demo: <span class="num text-white">1001</span> admin ·
            <span class="num text-white">1002</span> guru ·
            <span class="num text-white">1003</span> murid<br>
            Password: <span class="num text-white">password123</span>
        </p>
    </div>
@endsection
