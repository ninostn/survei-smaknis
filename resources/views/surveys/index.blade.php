@extends('layouts.app')

@section('title', 'Daftar Survei')

@section('heading', 'Daftar Survei')
@section('subheading', 'Kelola survei, status, dan hasilnya.')

@section('actions')
    <x-button href="{{ route('surveys.create') }}">Buat Survei</x-button>
@endsection

@section('content')
    @if ($surveys->isEmpty())
        <x-empty-state
            title="Belum ada survei"
            description="Buat survei pertama untuk mulai mengumpulkan jawaban.">
            <x-button href="{{ route('surveys.create') }}" variant="secondary">Buat Survei</x-button>
        </x-empty-state>
    @else
        <x-card class="p-0">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Status</th>
                            <th class="text-right">Soal</th>
                            <th class="text-right">Pengisi</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($surveys as $s)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $s->judul }}</p>
                                    @if ($s->deskripsi)
                                        <p class="mt-0.5 line-clamp-1 text-[12px] text-muted">{{ $s->deskripsi }}</p>
                                    @endif
                                </td>
                                <td><x-badge :status="$s->status" /></td>
                                <td class="num text-right">{{ $s->questions_count }}</td>
                                <td class="num text-right">{{ $s->responses_count }}</td>
                                <td>
                                    <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2">
                                        <a href="{{ route('surveys.results', $s) }}" class="text-[13px] font-medium text-accent hover:underline">Hasil</a>
                                        <a href="{{ route('surveys.edit', $s) }}" class="text-[13px] font-medium text-muted hover:text-white">Edit</a>

                                        <form method="POST" action="{{ route('surveys.publish', $s) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $s->status === 'aktif' ? 'nonaktif' : 'aktif' }}">
                                            <button type="submit" class="text-[13px] font-medium {{ $s->status === 'aktif' ? 'text-muted hover:text-white' : 'text-ok hover:underline' }}">
                                                {{ $s->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('surveys.destroy', $s) }}"
                                              onsubmit="return confirm('Hapus survey ini? Semua soal + jawaban ikut hilang.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[13px] font-medium text-danger/80 hover:text-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endsection
