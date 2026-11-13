@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.app')

@section('title', 'Tambah Survei')

@section(auth()->user()->role === 'admin' ? 'admin-content' : 'content')
    @if (auth()->user()->role !== 'admin')
        @include('surveys._nav')
    @endif

    <main class="w-full max-w-4xl py-2">
        <a href="{{ route('surveys.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar survei</a>

        <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">Tambah Survei</h1>
            <p class="mt-1 text-xs font-medium text-ink-muted">Survei baru disimpan sebagai draft. Target responden dapat diatur sekarang; tanggal pengisian diatur pada tahap berikutnya.</p>

            @include('surveys._form')
        </div>
    </main>
@endsection
