@extends('layouts.admin')

@section('title', 'Manajemen Akun')

@section('admin-content')
    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Area Admin</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Manajemen Akun</h1>
                <p class="mt-1 text-xs font-medium text-ink-muted">Kelola akun guru dan siswa SMAKNIS.</p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                + Tambah Akun
            </a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.users.index') }}" class="mt-5 grid grid-cols-1 gap-3 rounded-3xl border border-white bg-white/90 p-4 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:grid-cols-2 lg:grid-cols-4">
            <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari username, nama, NIP, NIS..."
                   class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">

            <select name="role" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua peran</option>
                <option value="teacher" @selected($role === 'teacher')>Guru</option>
                <option value="student" @selected($role === 'student')>Siswa</option>
            </select>

            <select name="status" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                    Terapkan
                </button>
                <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-5 overflow-hidden rounded-3xl border border-white bg-white/90 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-ink-muted">
                            <th class="px-5 py-4 font-semibold">Username</th>
                            <th class="px-5 py-4 font-semibold">Nama</th>
                            <th class="px-5 py-4 font-semibold">Peran</th>
                            <th class="px-5 py-4 font-semibold">NIP / NIS</th>
                            <th class="px-5 py-4 font-semibold">Kelas</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-4 font-bold text-ink">{{ $user->username }}</td>
                                <td class="px-5 py-4 font-medium">
                                    {{ $user->student?->name ?? $user->teacher?->name ?? '-' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'inline-block rounded-full px-2.5 py-1 text-[11px] font-bold',
                                        'bg-accent-blue/15 text-sky-700' => $user->role === 'teacher',
                                        'bg-accent-lavender/15 text-indigo-600' => $user->role === 'student',
                                    ])>{{ $user->role === 'teacher' ? 'Guru' : 'Siswa' }}</span>
                                </td>
                                <td class="px-5 py-4 font-medium text-ink-muted">
                                    {{ $user->student?->nis ?? $user->teacher?->nip ?? '-' }}
                                </td>
                                <td class="px-5 py-4 font-medium text-ink-muted">
                                    {{ $user->student?->schoolClass?->name ?? '-' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'inline-block h-2.5 w-2.5 rounded-full',
                                        'bg-emerald-400' => $user->is_active,
                                        'bg-slate-300' => ! $user->is_active,
                                    ])></span>
                                    <span class="ml-1.5 font-semibold {{ $user->is_active ? 'text-emerald-600' : 'text-ink-muted' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-ink-muted">
                                    Tidak ada akun yang cocok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $users->links() }}
            </div>
        </div>
    </main>
@endsection
