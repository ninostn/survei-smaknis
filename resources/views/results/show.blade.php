@extends('layouts.app')

@section('title', 'Hasil Survei')

@section('heading', 'Hasil: ' . $survey->judul)
@section('subheading', 'Agregat jawaban mentah, diambil langsung dari tabel answers.')

@section('actions')
    <x-badge :status="$survey->status" />
    <x-button href="{{ route('surveys.index') }}" variant="secondary">Kembali</x-button>
@endsection

@section('content')
    @php
        $countRows = collect($counts);
        $countColumns = $countRows->isEmpty() ? [] : array_keys((array) $countRows->first());

        $likertRows = collect($avgLikert);
        $likertColumns = $likertRows->isEmpty() ? [] : array_keys((array) $likertRows->first());

        $essayRows = collect($essays)->take(20);
        $essayColumns = $essayRows->isEmpty() ? [] : array_keys((array) $essayRows->first());

        $angka = ['jumlah_jawaban', 'jumlah_penilai', 'rata_rata'];
    @endphp

    <div class="space-y-5">
        <x-card class="p-0">
            <div class="border-b border-line px-5 py-4">
                <h2>1. Jumlah Jawaban</h2>
                <p class="mt-1 text-[12px] text-muted">Jumlah jawaban yang masuk untuk setiap pertanyaan.</p>
            </div>

            @if ($countRows->isEmpty())
                <p class="px-5 py-6 text-sm text-muted">Belum ada jawaban masuk.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach ($countColumns as $column)
                                    <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($countRows as $row)
                                <tr>
                                    @foreach ($countColumns as $column)
                                        <td class="{{ in_array($column, $angka) ? 'num text-right' : '' }}">
                                            {{ data_get($row, $column) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card class="p-0">
            <div class="border-b border-line px-5 py-4">
                <h2>2. Rata-rata Likert</h2>
                <p class="mt-1 text-[12px] text-muted">Rata-rata nilai opsi untuk pertanyaan bertipe Likert.</p>
            </div>

            @if ($likertRows->isEmpty())
                <p class="px-5 py-6 text-sm text-muted">Belum ada jawaban Likert.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach ($likertColumns as $column)
                                    <th class="{{ in_array($column, $angka) ? 'text-right' : '' }}">
                                        {{ ucwords(str_replace('_', ' ', $column)) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($likertRows as $row)
                                <tr>
                                    @foreach ($likertColumns as $column)
                                        @if ($column === 'rata_rata')
                                            <td class="num text-right text-[15px] font-semibold text-accent">{{ data_get($row, $column) }}</td>
                                        @else
                                            <td class="{{ in_array($column, $angka) ? 'num text-right' : '' }}">
                                                {{ data_get($row, $column) }}
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card class="p-0">
            <div class="border-b border-line px-5 py-4">
                <h2>3. Jawaban Esai</h2>
                <p class="mt-1 text-[12px] text-muted">Maksimal 20 jawaban terbaru.</p>
            </div>

            @if ($essayRows->isEmpty())
                <p class="px-5 py-6 text-sm text-muted">Belum ada jawaban esai.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach ($essayColumns as $column)
                                    <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($essayRows as $row)
                                <tr>
                                    @foreach ($essayColumns as $column)
                                        @if ($column === 'jawaban')
                                            <td class="max-w-md whitespace-pre-line">{{ data_get($row, $column) }}</td>
                                        @elseif ($column === 'submitted_at')
                                            <td class="num whitespace-nowrap text-muted">{{ data_get($row, $column) }}</td>
                                        @else
                                            <td>{{ data_get($row, $column) }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
@endsection
