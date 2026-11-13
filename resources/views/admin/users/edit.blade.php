@extends('layouts.admin')

@section('title', 'Edit Akun')

@section('admin-content')
    <div class="w-full max-w-4xl flex flex-col gap-4">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div>
            <a href="{{ route('admin.users.index', ['role' => $account->role]) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali ke daftar {{ $account->role === 'teacher' ? 'guru' : 'siswa' }}</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    {{ $account->role === 'teacher' ? 'Akun Guru' : 'Akun Siswa' }}
                </p>
                <h1 class="mt-1 text-base font-bold text-slate-900">{{ $account->username }}</h1>

                @if ($errors->any())
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600" role="alert">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="p-6">
                <form method="POST" action="{{ route('admin.users.update', $account) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col gap-1.5">
                        <label for="account-username" class="text-xs font-semibold text-slate-700">Username</label>
                        <input id="account-username" type="text" name="username" value="{{ old('username', $account->username) }}" required
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="account-name" class="text-xs font-semibold text-slate-700">Nama lengkap</label>
                        <input id="account-name" type="text" name="name"
                               value="{{ old('name', $account->student?->name ?? $account->teacher?->name) }}" required
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    @if ($account->role === 'teacher')
                        <div class="flex flex-col gap-1.5">
                            <label for="account-nip" class="text-xs font-semibold text-slate-700">NIP <span class="font-medium text-slate-500">(opsional)</span></label>
                            <input id="account-nip" type="text" name="nip" value="{{ old('nip', $account->teacher?->nip) }}"
                                   class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                    @else
                        <div class="flex flex-col gap-1.5">
                            <label for="account-nis" class="text-xs font-semibold text-slate-700">NIS</label>
                            <input id="account-nis" type="text" name="nis" value="{{ old('nis', $account->student?->nis) }}" required
                                   class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="account-class" class="text-xs font-semibold text-slate-700">Kelas</label>
                            <div class="relative">
                                <select id="account-class" name="class_id" required
                                        class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" @selected((string) old('class_id', $account->student?->class_id) === (string) $class->id)>
                                            {{ $class->name }} — {{ $class->academic_year }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                            </div>
                        </div>
                    @endif

                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="checkbox" name="is_active" value="1"
                               data-username="{{ $account->username }}"
                               @checked(old('is_active', $account->is_active))
                               onchange="if (!this.checked && !confirm('Nonaktifkan akun ' + this.dataset.username + '? Akun yang nonaktif tidak dapat login.')) { this.checked = true; }"
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <span class="block text-xs font-semibold text-slate-900">Akun aktif</span>
                            <span class="block text-[11px] text-slate-500">Hilangkan centang untuk menonaktifkan akun (konfirmasi akan muncul).</span>
                        </span>
                    </label>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
                        <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm transition-all duration-150">
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Reset Password</h2>
                <p class="mt-0.5 text-xs text-slate-500">Password lama tidak ditampilkan. Password baru tidak akan diperlihatkan lagi setelah disimpan.</p>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('admin.users.reset-password', $account) }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="flex flex-col gap-1.5">
                        <label for="reset-password" class="text-xs font-semibold text-slate-700">Password baru</label>
                        <input id="reset-password" type="password" name="password" required autocomplete="new-password"
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="reset-password-confirmation" class="text-xs font-semibold text-slate-700">Konfirmasi password baru</label>
                        <input id="reset-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
                        <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition-colors">
                            <span>Reset Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
