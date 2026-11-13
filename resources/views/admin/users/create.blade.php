@extends('layouts.admin')

@section('title', $role === 'teacher' ? 'Tambah Guru' : 'Tambah Siswa')

@section('admin-content')
    <div class="w-full max-w-4xl flex flex-col gap-4">
        <div>
            <a href="{{ route('admin.users.index', ['role' => $role]) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali ke daftar {{ $role === 'teacher' ? 'guru' : 'siswa' }}</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h1 class="text-base font-bold text-slate-900">Tambah {{ $role === 'teacher' ? 'Guru' : 'Siswa' }}</h1>
                <p class="mt-0.5 text-xs text-slate-500">Buat akun dan profil {{ $role === 'teacher' ? 'guru' : 'siswa' }}.</p>
            </div>

            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600" role="alert">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST"
                      action="{{ $role === 'teacher' ? route('admin.users.teachers.store') : route('admin.users.students.store') }}"
                      class="space-y-5">
                    @csrf

                    <div class="flex flex-col gap-1.5"> 
                        <label for="account-username" class="text-xs font-semibold text-slate-700">Username</label>
                        <input id="account-username" type="text" name="username" value="{{ old('username') }}" required
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="account-name" class="text-xs font-semibold text-slate-700">Nama lengkap</label>
                        <input id="account-name" type="text" name="name" value="{{ old('name') }}" required
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    @if ($role === 'teacher')
                        <div class="flex flex-col gap-1.5">
                            <label for="account-nip" class="text-xs font-semibold text-slate-700">NIP <span class="font-medium text-slate-500">(opsional)</span></label>
                            <input id="account-nip" type="text" name="nip" value="{{ old('nip') }}"
                                   class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                    @else
                        <div class="flex flex-col gap-1.5">
                            <label for="account-nis" class="text-xs font-semibold text-slate-700">NIS</label>
                            <input id="account-nis" type="text" name="nis" value="{{ old('nis') }}" required
                                   class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="account-class" class="text-xs font-semibold text-slate-700">Kelas</label>
                            <div class="relative">
                                <select id="account-class" name="class_id" required
                                        class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="">Pilih kelas aktif</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>
                                            {{ $class->name }} — {{ $class->academic_year }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col gap-1.5">
                        <label for="account-password" class="text-xs font-semibold text-slate-700">Password awal</label>
                        <input id="account-password" type="password" name="password" required autocomplete="new-password"
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="account-password-confirmation" class="text-xs font-semibold text-slate-700">Konfirmasi password</label>
                        <input id="account-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
                        <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm transition-all duration-150">
                            <span>Simpan Akun {{ $role === 'teacher' ? 'Guru' : 'Siswa' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
