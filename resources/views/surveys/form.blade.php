@extends('layouts.app')

@section('title', $survey ? 'Edit Survei' : 'Buat Survei')

@section('heading', $survey ? 'Edit Survei' : 'Buat Survei')
@section('subheading', $survey ? 'Ubah judul atau deskripsi survei.' : 'Isi judul dan deskripsi singkat. Soal ditambahkan setelah survei dibuat.')

@section('actions')
    <x-button href="{{ route('surveys.index') }}" variant="secondary">Kembali</x-button>
@endsection

@section('content')
    <div class="max-w-2xl">
        <x-card>
            <form method="POST"
                  action="{{ $survey ? route('surveys.update', $survey) : route('surveys.store') }}"
                  class="space-y-5">
                @csrf
                @if ($survey)
                    @method('PUT')
                @endif

                <x-field label="Judul" name="judul" hint="Maksimal 150 karakter.">
                    <input id="judul" name="judul" type="text" class="input" maxlength="150"
                           value="{{ old('judul', $survey->judul ?? '') }}"
                           placeholder="Contoh: Survei Kepuasan Siswa" autofocus>
                </x-field>

                <x-field label="Deskripsi" name="deskripsi" hint="Opsional. Jelaskan tujuan survei.">
                    <textarea id="deskripsi" name="deskripsi" rows="4" class="textarea"
                              placeholder="Tuliskan tujuan survei...">{{ old('deskripsi', $survey->deskripsi ?? '') }}</textarea>
                </x-field>

                <div class="flex items-center gap-2 pt-1">
                    <x-button type="submit">Simpan</x-button>
                    <x-button href="{{ route('surveys.index') }}" variant="ghost">Batal</x-button>
                </div>
            </form>
        </x-card>
    </div>
@endsection
