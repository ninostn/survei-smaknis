@extends('layouts.app')

@section('title', 'Survei Aktif')

@section('heading', 'Survei Aktif')
@section('subheading', 'Pilih survei yang ingin kamu isi. Setiap survei hanya bisa diisi satu kali.')

@section('actions')
    <x-button href="{{ route('murid.surveys.history') }}" variant="secondary">Riwayat</x-button>
@endsection

@section('content')
    @if ($surveys->isEmpty())
        <x-empty-state
            title="Belum ada survei aktif"
            description="Survei akan muncul di sini setelah guru atau admin mengaktifkannya.">
            <x-button href="/murid" variant="secondary">Kembali ke Dashboard</x-button>
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($surveys as $survey)
                @php
                    $sudahDiisi = $survey->responses->isNotEmpty();
                    $adaSoal = $survey->questions_count > 0;
                @endphp

                <div class="flex flex-col rounded-card border border-line bg-surface p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-[15px] font-semibold leading-snug">{{ $survey->judul }}</h2>
                        <x-badge :status="$sudahDiisi ? 'sudah' : 'belum'" />
                    </div>

                    <p class="mt-2 line-clamp-2 text-sm text-muted">
                        {{ $survey->deskripsi ?: 'Tidak ada deskripsi.' }}
                    </p>

                    <div class="mt-4 flex items-center gap-2 text-[12px] text-muted">
                        <span class="num text-white">{{ $survey->questions_count }}</span> pertanyaan
                    </div>

                    <div class="mt-5 pt-1">
                        @if ($sudahDiisi)
                            <x-button variant="secondary" class="w-full" disabled>Sudah diisi</x-button>
                        @elseif ($adaSoal)
                            <x-button href="{{ route('murid.surveys.show', $survey->id) }}" class="w-full">Isi Survei</x-button>
                        @else
                            <x-button variant="secondary" class="w-full" disabled>Belum ada soal</x-button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
