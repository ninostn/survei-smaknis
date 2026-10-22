@extends('layouts.app')

@section('title', 'Dashboard Murid')

@section('heading', 'Halo, ' . auth()->user()->nama)
@section('subheading', 'Pilih survei yang tersedia, atau lihat riwayat pengisianmu.')

@section('actions')
    <x-button href="{{ route('murid.surveys.index') }}">Isi Survei</x-button>
    <x-button href="{{ route('murid.surveys.history') }}" variant="secondary">Riwayat</x-button>
@endsection

@section('content')
    <div class="grid gap-4 md:grid-cols-3">
        <x-card class="md:col-span-2">
            <p class="text-[12px] font-medium uppercase tracking-wider text-muted">Identitas</p>

            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-muted">NIS</dt>
                    <dd class="num mt-0.5">{{ auth()->user()->nis }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Kelas</dt>
                    <dd class="mt-0.5">{{ auth()->user()->kelas ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Peran</dt>
                    <dd class="mt-1"><x-badge :status="auth()->user()->role" /></dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <p class="text-[12px] font-medium uppercase tracking-wider text-muted">Catatan</p>
            <p class="mt-3 text-sm text-muted">Satu survei hanya bisa diisi satu kali. Jawaban yang sudah dikirim tidak bisa diubah.</p>
        </x-card>
    </div>
@endsection
