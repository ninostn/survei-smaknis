
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Survei</title>
</head>
<body>
    <h1>Hasil Survei</h1>

    <h2>1. Jumlah Jawaban (COUNT)</h2>

    @php
        $countRows = collect($counts ?? []);
        $firstCount = $countRows->first();
        $countColumns = $firstCount !== null
            ? array_keys((array) $firstCount)
            : [];
    @endphp

    <table border="1">
        <thead>
            <tr>
                @foreach ($countColumns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($countRows as $count)
                <tr>
                    @foreach ($countColumns as $column)
                        <td>{{ data_get($count, $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td>Belum ada data jumlah jawaban.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>2. Rata-rata Likert</h2>

    @php
        $likertRows = collect($avgLikert ?? []);
        $firstLikert = $likertRows->first();
        $likertColumns = $firstLikert !== null
            ? array_keys((array) $firstLikert)
            : [];
    @endphp

    <table border="1">
        <thead>
            <tr>
                @foreach ($likertColumns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($likertRows as $item)
                <tr>
                    @foreach ($likertColumns as $column)
                        <td>{{ data_get($item, $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td>Belum ada data rata-rata Likert.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Jawaban Esai (Maksimal 20)</h2>

    @php
        $essayRows = collect($essays ?? [])->take(20);
        $firstEssay = $essayRows->first();
        $essayColumns = $firstEssay !== null
            ? array_keys((array) $firstEssay)
            : [];
    @endphp

    <table border="1">
        <thead>
            <tr>
                @foreach ($essayColumns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($essayRows as $essay)
                <tr>
                    @foreach ($essayColumns as $column)
                        <td>{{ data_get($essay, $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td>Belum ada jawaban esai.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
