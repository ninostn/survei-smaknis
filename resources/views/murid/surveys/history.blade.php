@extends('layouts.app')

@section('title', 'Riwayat Survei')

@section('heading', 'Riwayat Survei')
@section('subheading', 'Daftar survei yang sudah kamu isi.')

@section('actions')
    <x-button href="{{ route('murid.surveys.index') }}" variant="secondary">Survei Aktif</x-button>
@endsection

@section('content')
    @if ($responses->isEmpty())
        <x-empty-state
            title="Belum ada riwayat"
            description="Kamu belum pernah mengisi survei. Survei yang sudah dikirim akan muncul di sini.">
            <x-button href="{{ route('murid.surveys.index') }}">Lihat Survei Aktif</x-button>
        </x-empty-state>
    @else
        <x-card class="p-0">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Survei</th>
                            <th>Tanggal Pengisian</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($responses as $response)
                            <tr>
                                <td class="font-medium">{{ $response->survey->judul ?? 'Survei tidak tersedia' }}</td>
                                <td class="num text-muted">{{ $response->submitted_at }}</td>
                                <td><x-badge status="sudah" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endsection
