@extends('layouts.admin')

@section('title', 'Edit Akun')

@section('admin-content')
    <main class="mx-auto w-full max-w-2xl px-4 py-8">
        <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar akun</a>

        @if (session('status'))
            <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-3 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                {{ $account->role === 'teacher' ? 'Akun Guru' : 'Akun Siswa' }}
            </p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">{{ $account->username }}</h1>

            <form method="POST" action="{{ route('admin.users.update', $account) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Username</span>
                    <input type="text" name="username" value="{{ old('username', $account->username) }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Nama lengkap</span>
                    <input type="text" name="name"
                           value="{{ old('name', $account->student?->name ?? $account->teacher?->name) }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                @if ($account->role === 'teacher')
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">NIP <span class="font-normal">(opsional)</span></span>
                        <input type="text" name="nip" value="{{ old('nip', $account->teacher?->nip) }}"
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    </label>
                @else
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">NIS</span>
                        <input type="text" name="nis" value="{{ old('nis', $account->student?->nis) }}" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Kelas</span>
                        <select name="class_id" required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id', $account->student?->class_id) === (string) $class->id)>
                                    {{ $class->name }} — {{ $class->academic_year }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <input type="checkbox" name="is_active" value="1"
                           data-username="{{ $account->username }}"
                           @checked(old('is_active', $account->is_active))
                           onchange="if (!this.checked && !confirm('Nonaktifkan akun ' + this.dataset.username + '? Akun yang nonaktif tidak dapat login.')) { this.checked = true; }"
                           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-accent-lavender focus:ring-accent-lavender">
                    <span>
                        <span class="block text-xs font-semibold text-ink">Akun aktif</span>
                        <span class="block text-[11px] text-ink-muted">Hilangkan centang untuk menonaktifkan akun (konfirmasi akan muncul).</span>
                    </span>
                </label>

                <button type="submit"
                        class="w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <div class="mt-5 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:p-8">
            <h2 class="text-sm font-bold text-ink">Reset Password</h2>
            <p class="mt-1 text-[11px] font-medium text-ink-muted">Password lama tidak ditampilkan. Password baru tidak akan diperlihatkan lagi setelah disimpan.</p>

            <form method="POST" action="{{ route('admin.users.reset-password', $account) }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Password baru</span>
                    <input type="password" name="password" required autocomplete="new-password"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Konfirmasi password baru</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                <button type="submit"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-ink transition-colors hover:border-accent-lavender">
                    Reset Password
                </button>
            </form>
        </div>
    </main>
@endsection
