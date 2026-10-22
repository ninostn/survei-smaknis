<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>403 Forbidden - Survei SMAKNIS</title>
</head>
<body>
  <h1>403 Forbidden</h1>

  @php($pesan = isset($exception) ? trim($exception->getMessage()) : '')

  @if ($pesan !== '')
    <p>{{ $pesan }}</p>
  @else
    <p>You don't have permission to access this page.</p>

    <hr>

    <p>Kamu sudah login, tapi peranmu tidak boleh membuka halaman ini.</p>
    <p>Contoh: murid membuka halaman admin, atau guru membuka survey milik guru lain.</p>
  @endif

  <p>
    <a href="/dashboard">Kembali ke Dashboard</a>
  </p>
</body>
</html>
