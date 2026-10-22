@extends('layouts.guest')

@section('title', '404 Not Found - Survei SMAKNIS')

@section('content')
    <div class="w-full max-w-md text-center">
        <p class="num text-[12px] font-bold tracking-[0.25em] text-accent">404</p>
        <h1 class="mt-3 text-xl">Halaman tidak ditemukan</h1>

        <p class="mt-2 text-sm text-muted">The page you are looking for does not exist.</p>
        <p class="mt-1.5 text-sm text-muted">Kemungkinan alamat salah ketik, atau data (misalnya survei) sudah dihapus.</p>

        <div class="mt-6">
            <x-button href="/dashboard" variant="secondary">Kembali ke Dashboard</x-button>
        </div>
    </div>
@endsection
