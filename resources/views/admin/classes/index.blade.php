@extends('layouts.admin')

@section('title', 'Manajemen Kelas')
@section('admin-subtitle', 'Kelola kelas per tahun akademik.')

@section('admin-content')
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- FILTER BAR --}}
        <form method="GET" action="{{ route('admin.classes.index') }}"
              class="bg-white p-3.5 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-3.5">
            <div class="flex flex-col items-center gap-2.5 w-full lg:w-auto sm:flex-row sm:w-full lg:flex-1 lg:max-w-xl">
                <div class="relative flex-1 max-w-sm min-w-[220px] w-full">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
                    <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari nama kelas..."
                           autocomplete="off"
                           class="w-full h-10 pl-9 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0 self-end lg:self-auto">
                <x-filter-modal title="Filter Kelas" :reset-url="route('admin.classes.index')"
                                :active-count="($year ? 1 : 0) + ($status ? 1 : 0)">
                    <div class="flex flex-col gap-1.5">
                        <label for="class-filter-year" class="text-xs font-semibold text-slate-700">Tahun Akademik</label>
                        <div class="relative">
                            <select id="class-filter-year" name="academic_year"
                                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Tahun Akademik</option>
                                @foreach ($years as $option)
                                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="class-filter-status" class="text-xs font-semibold text-slate-700">Status Kelas</label>
                        <div class="relative">
                            <select id="class-filter-status" name="status"
                                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Status</option>
                                <option value="active" @selected($status === 'active')>Aktif</option>
                                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                        </div>
                    </div>
                </x-filter-modal>

                <a href="{{ route('admin.classes.index') }}"
                   class="h-10 flex items-center gap-1.5 px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold border border-slate-200 transition-colors">
                    <span>Reset</span>
                </a>
                <x-admin-add-link :href="route('admin.classes.create')" label="Tambah Kelas" data-create-dialog />
            </div>
        </form>

        {{-- DATA TABLE --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 text-center w-12 font-semibold" scope="col">No</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Kelas</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Jurusan</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Tahun Akademik</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Siswa</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Penugasan</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Status</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($classes as $class)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center font-medium text-slate-400 text-xs">{{ $classes->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-5 font-semibold text-slate-900">{{ $class->name }}</td>
                                <td class="py-3 px-5 text-slate-700 font-medium">
                                    <span class="font-semibold text-slate-900">{{ $class->major }}</span>
                                </td>
                                <td class="py-3 px-5 text-slate-700 font-medium">{{ $class->academic_year }}</td>
                                <td class="py-3 px-5 text-center font-semibold text-slate-700">{{ $class->students_count }}</td>
                                <td class="py-3 px-5 text-center font-semibold text-slate-700">{{ $class->teaching_assignments_count }}</td>
                                <td class="py-3 px-5 text-center">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold',
                                        'bg-emerald-50 text-emerald-700' => $class->is_active,
                                        'bg-slate-100 text-slate-600' => ! $class->is_active,
                                    ])>
                                        {{ $class->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.classes.edit', $class) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-colors">
                                            <span class="material-symbols-outlined text-sm text-slate-500">edit</span>
                                            <span>Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('admin.classes.toggle', $class) }}"
                                              onsubmit="return confirm('Ubah status kelas ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ $class->is_active ? 'text-slate-500 hover:text-red-600 hover:bg-red-50' : 'text-slate-500 hover:text-emerald-600 hover:bg-emerald-50' }} border border-slate-200 transition-colors">
                                                <span class="material-symbols-outlined text-sm">{{ $class->is_active ? 'toggle_off' : 'toggle_on' }}</span>
                                                <span>{{ $class->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 px-5 text-center text-sm text-slate-500">Belum ada kelas yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION FOOTER --}}
        <div class="bg-white px-6 py-4 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
            <p class="text-xs text-slate-400">
                Menampilkan <span class="font-semibold text-slate-700">{{ $classes->firstItem() ?? 0 }} &ndash; {{ $classes->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-700">{{ $classes->total() }}</span> Kelas
            </p>
            {{ $classes->links('vendor.pagination.stitch') }}
        </div>
    </div>
    <x-admin-create-dialog title="Tambah Kelas" form="admin.classes._form" />
@endsection
