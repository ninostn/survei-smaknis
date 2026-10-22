@extends('layouts.focus')

@section('title', $survey->judul)

@section('content')
    <div class="mb-5">
        <h1 class="text-xl">{{ $survey->judul }}</h1>
        <p class="mt-1.5 text-sm text-muted">{{ $survey->deskripsi ?: 'Tidak ada deskripsi.' }}</p>
        <p class="mt-3 text-[12px] text-muted">
            <span class="num text-white">{{ $survey->questions->count() }}</span> pertanyaan · semua wajib dijawab
        </p>
    </div>

    @if ($survey->questions->isEmpty())
        <x-empty-state
            title="Survei belum memiliki pertanyaan"
            description="Hubungi guru atau admin yang membuat survei ini.">
            <x-button href="{{ route('murid.surveys.index') }}" variant="secondary">Kembali</x-button>
        </x-empty-state>
    @else
        <form method="POST" action="{{ route('murid.surveys.store', $survey->id) }}">
            @csrf

            <div class="space-y-4">
                @foreach ($survey->questions as $question)
                    <div class="rounded-card border border-line bg-surface p-5">
                        <div class="flex items-start gap-3">
                            <span class="num mt-0.5 shrink-0 rounded-control border border-line bg-night px-2 py-0.5 text-[12px] text-muted">
                                {{ $loop->iteration }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium leading-snug">{{ $question->teks }}</p>
                                <div class="mt-2"><x-badge :status="$question->type" /></div>
                            </div>
                        </div>

                        @error('jawaban.' . $question->id)
                            <p class="mt-3 rounded-control border-l-4 border-danger bg-alert px-3 py-2 text-[12px] text-danger">
                                {{ $message }}
                            </p>
                        @enderror

                        <div class="mt-4 space-y-2">
                            @if ($question->type === 'essay')
                                <textarea name="jawaban[{{ $question->id }}]" rows="4" class="textarea"
                                          placeholder="Tulis jawabanmu..." required>{{ old('jawaban.' . $question->id) }}</textarea>
                            @else
                                @forelse ($question->options as $option)
                                    <label class="option">
                                        <input type="radio" name="jawaban[{{ $question->id }}]" value="{{ $option->id }}"
                                               required {{ (string) old('jawaban.' . $question->id) === (string) $option->id ? 'checked' : '' }}>
                                        <span>{{ $option->label }}</span>
                                    </label>
                                @empty
                                    <p class="text-[12px] text-muted">Belum ada pilihan jawaban untuk pertanyaan ini.</p>
                                @endforelse
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-night/95 backdrop-blur">
                <div class="mx-auto flex max-w-2xl items-center gap-3 px-4 py-3">
                    <p class="hidden text-[12px] text-muted sm:block">Pastikan semua pertanyaan terjawab.</p>
                    <x-button type="submit" class="ml-auto w-full sm:w-auto">Kirim Jawaban</x-button>
                </div>
            </div>
        </form>
    @endif
@endsection
