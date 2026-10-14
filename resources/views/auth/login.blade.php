<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login - Survei SMAKNIS</title>
</head>

<body>
    <h1>Login Survei</h1>

    <form method="POST" action="/login">
        @csrf

        <div>
            <label>NIS</label><br>
            <input type="text" name="nis" value="{{ old('nis') }}">
        </div>

        <br>

        <div>
            <label>Password</label><br>
            <input type="password" name="password">
        </div>

        <br>

        <button type="submit">Masuk</button>
    </form>

    @if ($errors->any())
    <p>{{ $errors->first() }}</p>
    @endif

    <hr>
    <p>Akun tes: 1001 / 1002 / 1003 — password: password123</p>
</body>

</html>