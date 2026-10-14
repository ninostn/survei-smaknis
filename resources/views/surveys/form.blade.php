<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $survey ? 'Edit Survey' : 'Buat Survey' }}</title>
</head>
<body>
  <h1>{{ $survey ? 'Edit Survey' : 'Buat Survey' }}</h1>

  <p>
    <a href="{{ route('surveys.index') }}">Kembali</a>
  </p>

  @if ($errors->any())
    <p>{{ $errors->first() }}</p>
  @endif

  <form method="POST" action="{{ $survey ? route('surveys.update', $survey) : route('surveys.store') }}">
    @csrf
    @if ($survey)
      @method('PUT')
    @endif

    <div>
      <label>Judul</label><br>
      <input type="text" name="judul" value="{{ old('judul', $survey->judul ?? '') }}" maxlength="150">
    </div>

    <br>

    <div>
      <label>Deskripsi</label><br>
      <textarea name="deskripsi" rows="4">{{ old('deskripsi', $survey->deskripsi ?? '') }}</textarea>
    </div>

    <br>

    <button type="submit">Simpan</button>
  </form>
</body>
</html>
