<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Survei - {{ $survey->judul }}</title>
</head>
<body>
    <h1>Hasil: {{ $survey->judul }}</h1>

    <p>
        Status: {{ $survey->status }} |
        <a href="{{ route('surveys.index') }}">Kembali ke daftar survey</a>
    </p>

    <h2>1. Jumlah Jawaban (COUNT)</h2>

    @php
        $countRows = collect($counts);
        $countColumns = $countRows->isEmpty()
            ? []
            : array_keys((array) $countRows->first());
    @endphp

    <table border="1" cellpadding="8">
        @if (count($countColumns))
            <thead>
                <tr>
                    @foreach ($countColumns as $column)
                        <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @forelse ($countRows as $count)
                <tr>
                    @foreach ($countColumns as $column)
                        <td>{{ data_get($count, $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($countColumns), 1) }}">Belum ada data jumlah jawaban.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>2. Rata-rata Likert</h2>

    @php
        $likertRows = collect($avgLikert);
        $likertColumns = $likertRows->isEmpty()
            ? []
            : array_keys((array) $likertRows->first());
    @endphp

    <table border="1" cellpadding="8">
        @if (count($likertColumns))
            <thead>
                <tr>
                    @foreach ($likertColumns as $column)
                        <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @forelse ($likertRows as $item)
                <tr>
                    @foreach ($likertColumns as $column)
                        <td>{{ data_get($item, $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($likertColumns), 1) }}">Belum ada data rata-rata Likert.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Jawaban Esai (Maksimal 20)</h2>

    @php
        $essayRows = collect($essays)->take(20);
        $essayColumns = $essayRows->isEmpty()
            ? []
            : array_keys((array) $essayRows->first());
    @endphp

    <table border="1" cellpadding="8">
        @if (count($essayColumns))
            <thead>
                <tr>
                    @foreach ($essayColumns as $column)
                        <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @forelse ($essayRows as $essay)
                <tr>
                    @foreach ($essayColumns as $column)
                        <td>{{ data_get($essay, $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($essayColumns), 1) }}">Belum ada jawaban esai.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
