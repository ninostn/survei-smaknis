@extends('layouts.admin')

@section('title', 'Mata Pelajaran')
@section('admin-subtitle', 'Daftar mata pelajaran yang dipakai pada penugasan mengajar.')

@section('admin-content')
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- FILTER BAR --}}
        <form method="GET" action="{{ route('admin.subjects.index') }}"
              class="bg-white p-3.5 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-3.5">
            <div class="relative flex-1 max-w-sm min-w-[220px] w-full lg:w-auto">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
                <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari kode atau nama..."
                       autocomplete="off"
                       class="w-full h-10 pl-9 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            </div>

            <div class="flex items-center gap-2 shrink-0 self-end lg:self-auto">
                <a href="{{ route('admin.subjects.index') }}"
                   class="h-10 flex items-center gap-1.5 px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold border border-slate-200 transition-colors">
                    <span>Reset</span>
                </a>
                <x-admin-add-link :href="route('admin.subjects.create')" label="Tambah Mata Pelajaran" data-create-dialog />
            </div>
        </form>

        {{-- DATA TABLE --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 text-center w-12 font-semibold" scope="col">No</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Kode</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Nama Mata Pelajaran</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Penugasan</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($subjects as $subject)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center font-medium text-slate-400 text-xs">{{ $subjects->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-5 font-semibold text-slate-900">{{ $subject->code }}</td>
                                <td class="py-3 px-5 text-slate-700 font-medium">{{ $subject->name }}</td>
                                <td class="py-3 px-5 text-center font-semibold text-slate-700">{{ $subject->teaching_assignments_count }}</td>
                                <td class="py-3 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.subjects.edit', $subject) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-colors">
                                            <span class="material-symbols-outlined text-sm text-slate-500">edit</span>
                                            <span>Edit</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 px-5 text-center text-sm text-slate-500">Belum ada mata pelajaran yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION FOOTER --}}
        <div class="bg-white px-6 py-4 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
            <p class="text-xs text-slate-400">
                Menampilkan <span class="font-semibold text-slate-700">{{ $subjects->firstItem() ?? 0 }} &ndash; {{ $subjects->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-700">{{ $subjects->total() }}</span> Mata Pelajaran
            </p>
            {{ $subjects->links('vendor.pagination.stitch') }}
        </div>
    </div>
    <x-admin-create-dialog title="Tambah Mata Pelajaran" form="admin.subjects._form" />
@endsection
