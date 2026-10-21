<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard Murid</title>
</head>
<body>
  <h1>Dashboard Murid</h1>
  <p>Nama: {{ auth()->user()->nama }}</p>
  <p>NIS: {{ auth()->user()->nis }}</p>
  <p>Kelas: {{ auth()->user()->kelas }}</p>

  <form method="POST" action="/logout">
    @csrf
    <button type="submit">Keluar</button>
  </form>
  
</body>
</html>
