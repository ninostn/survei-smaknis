
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Survei Murid</title>
</head>
<body>
    <h1>Riwayat Survei Saya</h1>

    <p>Berikut daftar survei yang sudah kamu isi.</p>

    @if ($responses->isEmpty())
        <p>Kamu belum pernah mengisi survei.</p>
    @else
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Judul Survei</th>
                    <th>Tanggal Pengisian</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($responses as $response)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $response->survey->judul ?? 'Survei tidak tersedia' }}</td>
                        <td>{{ $response->submitted_at }}</td>
                        <td>Sudah diisi</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p>
        <a href="{{ route('murid.surveys.index') }}">
            Kembali ke Daftar Survei
        </a>
    </p>

    <p>
        <a href="/murid">Kembali ke Dashboard</a>
    </p>
</body>
</html>
