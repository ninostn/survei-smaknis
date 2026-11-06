@extends('layouts.app')

@section('title', 'Tambah Akun')

@section('content')
    <main class="mx-auto w-full max-w-2xl px-4 py-8">
        <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar akun</a>

        <div class="mt-3 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Area Admin</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">Tambah Akun</h1>

            <div class="mt-5 inline-flex rounded-2xl bg-slate-100 p-1">
                <a href="{{ route('admin.users.create', ['role' => 'teacher']) }}"
                   @class([
                       'rounded-xl px-4 py-2 text-xs font-bold transition-colors',
                       'bg-white text-ink shadow-sm' => $role === 'teacher',
                       'text-ink-muted hover:text-ink' => $role !== 'teacher',
                   ])>Guru</a>
                <a href="{{ route('admin.users.create', ['role' => 'student']) }}"
                   @class([
                       'rounded-xl px-4 py-2 text-xs font-bold transition-colors',
                       'bg-white text-ink shadow-sm' => $role === 'student',
                       'text-ink-muted hover:text-ink' => $role !== 'student',
                   ])>Siswa</a>
            </div>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ $role === 'teacher' ? route('admin.users.teachers.store') : route('admin.users.students.store') }}"
                  class="mt-6 space-y-4">
                @csrf

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Username</span>
                    <input type="text" name="username" value="{{ old('username') }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Nama lengkap</span>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                @if ($role === 'teacher')
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">NIP <span class="font-normal">(opsional)</span></span>
                        <input type="text" name="nip" value="{{ old('nip') }}"
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    </label>
                @else
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">NIS</span>
                        <input type="text" name="nis" value="{{ old('nis') }}" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Kelas</span>
                        <select name="class_id" required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                            <option value="">Pilih kelas aktif</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>
                                    {{ $class->name }} — {{ $class->academic_year }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Password awal</span>
                    <input type="password" name="password" required autocomplete="new-password"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    <span class="mt-1 block text-[11px] text-ink-muted">Minimal 12 karakter.</span>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Konfirmasi password</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                <button type="submit"
                        class="w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                    Simpan Akun {{ $role === 'teacher' ? 'Guru' : 'Siswa' }}
                </button>
            </form>
        </div>
    </main>
@endsection
