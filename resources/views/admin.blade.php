<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard Admin</title>
</head>
<body>
  <h1>Dashboard Admin</h1>
  <p>Nama: {{ auth()->user()->nama }}</p>
  <p>NIS: {{ auth()->user()->nis }}</p>
  <p>Role: {{ auth()->user()->role }}</p>

  <form method="POST" action="/logout">
    @csrf
    <button type="submit">Keluar</button>
  </form>
</body>
</html>
