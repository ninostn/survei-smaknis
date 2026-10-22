@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('heading', 'Dashboard Admin')
@section('subheading', 'Ringkasan akses dan pintasan kerja.')

@section('actions')
    <x-button href="{{ route('surveys.index') }}">Kelola Survei</x-button>
@endsection

@section('content')
    <div class="grid gap-4 md:grid-cols-3">
        <x-card class="md:col-span-2">
            <p class="text-[12px] font-medium uppercase tracking-wider text-muted">Masuk sebagai</p>
            <p class="mt-2 text-lg font-semibold">{{ auth()->user()->nama }}</p>

            <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-muted">NIS / NIP</dt>
                    <dd class="num mt-0.5">{{ auth()->user()->nis }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Peran</dt>
                    <dd class="mt-1"><x-badge :status="auth()->user()->role" /></dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <p class="text-[12px] font-medium uppercase tracking-wider text-muted">Akses</p>
            <ul class="mt-3 space-y-2 text-sm text-muted">
                <li class="flex gap-2"><span class="text-accent">›</span> Semua survei</li>
                <li class="flex gap-2"><span class="text-accent">›</span> Aktifkan / nonaktifkan</li>
                <li class="flex gap-2"><span class="text-accent">›</span> Lihat hasil agregat</li>
            </ul>
        </x-card>
    </div>
@endsection
