
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Isi Survei</title>
</head>
<body>
    <h1>{{ $survey->judul }}</h1>

    <p>{{ $survey->deskripsi ?? 'Tidak ada deskripsi survei.' }}</p>

    @if ($survey->questions->isEmpty())
        <p>Survei ini belum memiliki pertanyaan.</p>
    @else
        <form method="POST" action="{{ route('murid.surveys.store', $survey->id) }}">
            @csrf

            @foreach ($survey->questions as $question)
                <div>
                    <p>
                        <strong>
                            {{ $loop->iteration }}. {{ $question->teks }}
                        </strong>
                    </p>

                    @error('jawaban.' . $question->id)
                        <p>{{ $message }}</p>
                    @enderror

                    @if ($question->type === 'essay')
                        <textarea
                            name="jawaban[{{ $question->id }}]"
                            rows="4"
                            cols="40"
                            required
                        >{{ old('jawaban.' . $question->id) }}</textarea>

                    @else
                        @foreach ($question->options as $option)
                            <label>
                                <input
                                    type="radio"
                                    name="jawaban[{{ $question->id }}]"
                                    value="{{ $option->id }}"
                                    required
                                    {{ old('jawaban.' . $question->id) == $option->id ? 'checked' : '' }}
                                >
                                {{ $option->label }}
                            </label>
                            <br>
                        @endforeach
                    @endif
                </div>
                <hr>
            @endforeach

            <button type="submit">Kirim Jawaban</button>
        </form>
    @endif

    <p>
        <a href="{{ route('murid.surveys.index') }}">
            Kembali ke Daftar Survei
        </a>
    </p>
</body>
</html>
