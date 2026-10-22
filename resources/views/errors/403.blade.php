@extends('layouts.guest')

@section('title', '403 Forbidden - Survei SMAKNIS')

@section('content')
    @php($pesan = isset($exception) ? trim($exception->getMessage()) : '')

    <div class="w-full max-w-md text-center">
        <p class="num text-[12px] font-bold tracking-[0.25em] text-danger">403</p>
        <h1 class="mt-3 text-xl">{{ $pesan !== '' ? $pesan : 'Forbidden' }}</h1>

        @if ($pesan !== '')
            <p class="mt-2 text-sm text-muted">Kamu tidak bisa membuka halaman ini saat ini.</p>
        @else
            <p class="mt-2 text-sm text-muted">You don't have permission to access this page.</p>
            <p class="mt-1.5 text-sm text-muted">Kamu sudah login, tapi peranmu tidak boleh membuka halaman ini.</p>
        @endif

        <div class="mt-6">
            <x-button href="/dashboard" variant="secondary">Kembali ke Dashboard</x-button>
        </div>
    </div>
@endsection
