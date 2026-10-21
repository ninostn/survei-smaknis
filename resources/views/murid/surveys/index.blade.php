
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Survei Murid</title>
</head>
<body>
    <h1>Daftar Survei Aktif</h1>

    <p>
        Halo, {{ auth()->user()->nama }}!
        Silakan pilih survei yang ingin kamu isi.
    </p>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if (session('error'))
        <p>{{ session('error') }}</p>
    @endif

    @if ($surveys->isEmpty())
        <p>Belum ada survei aktif saat ini.</p>
    @else
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Judul Survei</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Soal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($surveys as $survey)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $survey->judul }}</td>
                        <td>{{ $survey->deskripsi ?? '-' }}</td>
                        <td>{{ $survey->questions_count }}</td>
                        <td>
                            @if ($survey->responses->isNotEmpty())
                                Sudah diisi
                            @elseif ($survey->questions_count > 0)
                                <a href="{{ route('murid.surveys.show', $survey->id) }}">
                                    Isi Survei
                                </a>
                            @else
                                Belum ada soal
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p>
        <a href="/murid">Kembali ke Dashboard</a>
    </p>
    <p>
    <a href="{{ route('murid.surveys.history') }}">
        Lihat Riwayat Survei
    </a>
</p>
</body>
</html>
