@extends('layouts.admin')

@section('title', $role === 'teacher' ? 'Data Master Guru' : ($role === 'student' ? 'Data Master Siswa' : 'Manajemen Akun'))
@section('admin-subtitle', match ($role) {
    'teacher' => 'Kelola data tenaga pendidik, NIP, status mengajar, dan akun instrumen survei.',
    'student' => 'Kelola data siswa, NIS, rombongan belajar, dan akun instrumen survei.',
    default => 'Kelola akun guru dan siswa SMAKNIS.',
})

@section('admin-content')
    @php
        $buttonLabel = match ($role) {
            'teacher' => 'Tambah Guru',
            'student' => 'Tambah Siswa',
            default => 'Tambah Akun',
        };

        $unitLabel = match ($role) {
            'teacher' => 'Guru',
            'student' => 'Siswa',
            default => 'Akun',
        };

        $listUrl = route('admin.users.index', $role ? ['role' => $role] : []);
        $createUrl = route('admin.users.create', $role ? ['role' => $role] : []);
    @endphp

    <div class="flex flex-col gap-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FILTER BAR --}}
        <form method="GET" action="{{ route('admin.users.index') }}"
              class="bg-white p-3.5 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-3.5">
            @if ($role)
                <input type="hidden" name="role" value="{{ $role }}">
            @endif

            <div class="flex flex-col items-center gap-2.5 w-full lg:w-auto sm:flex-row sm:w-full lg:flex-1 lg:max-w-xl">
                <div class="relative flex-1 max-w-sm min-w-[220px] w-full">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
                    <input type="search" name="search" value="{{ e($search) }}"
                           placeholder="{{ $role === 'student' ? 'Cari nama siswa atau NIS...' : ($role === 'teacher' ? 'Cari nama guru atau NIP...' : 'Cari nama atau NIP/NIS...') }}"
                           autocomplete="off"
                           class="w-full h-10 pl-9 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0 self-end lg:self-auto">
                <x-filter-modal title="Filter Akun" :reset-url="$listUrl" :active-count="$status ? 1 : 0">
                    @unless ($role)
                        <div class="flex flex-col gap-1.5">
                            <label for="account-filter-role" class="text-xs font-semibold text-slate-700">Peran</label>
                            <div class="relative">
                                <select id="account-filter-role" name="role"
                                        class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                    <option value="">Semua Peran</option>
                                    <option value="teacher" @selected($role === 'teacher')>Guru</option>
                                    <option value="student" @selected($role === 'student')>Siswa</option>
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                            </div>
                        </div>
                    @endunless

                    <div class="flex flex-col gap-1.5">
                        <label for="account-filter-status" class="text-xs font-semibold text-slate-700">Status Akun</label>
                        <div class="relative">
                            <select id="account-filter-status" name="status"
                                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Status</option>
                                <option value="active" @selected($status === 'active')>Aktif</option>
                                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                        </div>
                    </div>
                </x-filter-modal>

                <a href="{{ $listUrl }}"
                   class="h-10 flex items-center gap-1.5 px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold border border-slate-200 transition-colors">
                    <span>Reset</span>
                </a>
                <x-admin-add-link :href="$createUrl" :label="$buttonLabel" />
            </div>
        </form>

        {{-- DATA TABLE --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        @if ($role === 'teacher')
                            <tr>
                                <th class="py-3 px-4 text-center w-12 font-semibold" scope="col">No</th>
                                <th class="py-3 px-5 font-semibold" scope="col">Nama Guru &amp; NIP</th>
                                <th class="py-3 px-5 font-semibold" scope="col">Mapel</th>
                                <th class="py-3 px-5 text-center font-semibold" scope="col">Rombel</th>
                                <th class="py-3 px-5 text-center font-semibold" scope="col">Aksi</th>
                            </tr>
                        @elseif ($role === 'student')
                            <tr>
                                <th class="py-3 px-4 text-center w-12 font-semibold" scope="col">No</th>
                                <th class="py-3 px-5 font-semibold" scope="col">Nama Siswa &amp; NIS</th>
                                <th class="py-3 px-5 font-semibold" scope="col">Kelas</th>
                                <th class="py-3 px-5 text-center font-semibold" scope="col">Status</th>
                                <th class="py-3 px-5 text-center font-semibold" scope="col">Aksi</th>
                            </tr>
                        @else
                            <tr>
                                <th class="py-3 px-4 text-center w-12 font-semibold" scope="col">No</th>
                                <th class="py-3 px-5 font-semibold" scope="col">Nama Akun</th>
                                <th class="py-3 px-5 font-semibold" scope="col">NIP / NIS</th>
                                <th class="py-3 px-5 font-semibold" scope="col">Kelas</th>
                                <th class="py-3 px-5 text-center font-semibold" scope="col">Status</th>
                                <th class="py-3 px-5 text-center font-semibold" scope="col">Aksi</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($users as $user)
                            @php
                                $profile = $user->student ?? $user->teacher;
                                $profileName = $profile?->name ?? $user->username;

                                $words = preg_split('/\s+/', trim($profileName)) ?: [];
                                $meaningful = array_values(array_filter($words, fn ($word) => $word !== '' && ! str_ends_with($word, '.')));
                                $initials = mb_strtoupper(implode('', array_map(fn ($word) => mb_substr($word, 0, 1), array_slice($meaningful, 0, 2))));
                                if ($initials === '') {
                                    $initials = mb_strtoupper(mb_substr($profileName, 0, 1));
                                }

                                $code = $user->teacher?->nip ?? $user->student?->nis;
                                $codeLabel = $user->teacher && $user->teacher->nip ? 'NIP' : ($user->student && $user->student->nis ? 'NIS' : null);

                                $subjects = $user->teacher
                                    ? $user->teacher->teachingAssignments->pluck('subject.name')->unique()->sort()->values()->implode(', ')
                                    : null;
                                $rombelCount = $user->teacher ? $user->teacher->teachingAssignments->count() : null;
                            @endphp

                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center font-medium text-slate-400 text-xs">{{ $users->firstItem() + $loop->index }}</td>

                                <td class="py-3 px-5">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0 border border-slate-200">{{ $initials }}</div>
                                        <div class="min-w-0 flex flex-col justify-center gap-1">
                                            <div class="font-semibold text-slate-900 leading-snug truncate">{{ $profileName }}</div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                @if ($codeLabel && $code)
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px] font-medium border border-slate-200/60">
                                                        <span class="font-bold text-[10px] text-slate-400 uppercase tracking-wider">{{ $codeLabel }}</span>
                                                        <span>{{ $code }}</span>
                                                    </span>
                                                @else
                                                    <span class="text-[11px] font-medium text-slate-500">{{ $user->username }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                @if ($role === 'teacher')
                                    <td class="py-3 px-5 text-slate-700 font-medium">{{ $subjects !== '' ? $subjects : '&mdash;' }}</td>
                                    <td class="py-3 px-5 text-center font-semibold text-slate-700">{{ $rombelCount }}</td>
                                @elseif ($role === 'student')
                                    <td class="py-3 px-5 text-slate-700 font-medium">{{ $user->student?->schoolClass?->name ?? '&mdash;' }}</td>
                                @else
                                    <td class="py-3 px-5 text-slate-700 font-medium">{{ $code ?? '&mdash;' }}</td>
                                    <td class="py-3 px-5 text-slate-700 font-medium">{{ $user->student?->schoolClass?->name ?? '&mdash;' }}</td>
                                @endif

                                @if ($role !== 'teacher')
                                    <td class="py-3 px-5 text-center">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold',
                                            'bg-emerald-50 text-emerald-700' => $user->is_active,
                                            'bg-slate-100 text-slate-600' => ! $user->is_active,
                                        ])>
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                @endif

                                <td class="py-3 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-colors">
                                            <span class="material-symbols-outlined text-sm text-slate-500">edit</span>
                                            <span>Edit</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $role ? 5 : 6 }}" class="py-10 px-5 text-center text-sm text-slate-500">
                                    Tidak ada akun yang cocok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION FOOTER --}}
        <div class="bg-white px-6 py-4 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
            <p class="text-xs text-slate-400">
                Menampilkan <span class="font-semibold text-slate-700">{{ $users->firstItem() ?? 0 }} &ndash; {{ $users->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-700">{{ $users->total() }}</span> {{ $unitLabel }}
            </p>
            {{ $users->links('vendor.pagination.stitch') }}
        </div>
    </div>
@endsection
