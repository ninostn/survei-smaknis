<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar Survey</title>
</head>
<body>
  <h1>Daftar Survey</h1>

  <p>
    <a href="/dashboard">Dashboard</a> |
    <a href="/surveys/create">Buat Survey</a>
  </p>

  @if (session('ok'))
    <p>{{ session('ok') }}</p>
  @endif

  <table border="1" cellpadding="8">
    <tr>
      <th>Judul</th>
      <th>Status</th>
      <th>Soal</th>
      <th>Pengisi</th>
      <th>Aksi</th>
    </tr>
    @forelse ($surveys as $s)
      <tr>
        <td>{{ $s->judul }}</td>
        <td>{{ $s->status }}</td>
        <td>{{ $s->questions_count }}</td>
        <td>{{ $s->responses_count }}</td>
        <td>
          <a href="{{ route('surveys.edit', $s) }}">Edit</a>

          <form method="POST" action="{{ route('surveys.publish', $s) }}" style="display:inline">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ $s->status === 'aktif' ? 'nonaktif' : 'aktif' }}">
            <button type="submit">{{ $s->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
          </form>

          <form method="POST" action="{{ route('surveys.destroy', $s) }}" style="display:inline" onsubmit="return confirm('Hapus survey ini? Semua soal + jawaban ikut hilang.')">
            @csrf
            @method('DELETE')
            <button type="submit">Hapus</button>
          </form>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="5">Belum ada survey.</td>
      </tr>
    @endforelse
  </table>
</body>
</html>
