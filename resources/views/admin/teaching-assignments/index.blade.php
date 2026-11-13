@extends('layouts.admin')

@section('title', 'Penugasan Mengajar')
@section('admin-subtitle', 'Dasar pembatasan kelas yang dapat ditargetkan guru.')

@section('admin-content')
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- FILTER BAR --}}
        <form method="GET" action="{{ route('admin.teaching-assignments.index') }}"
              class="bg-white p-3.5 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-3.5">
            <div class="flex items-center gap-2">
                <x-filter-modal title="Filter Penugasan" :reset-url="route('admin.teaching-assignments.index')"
                                :active-count="($teacherId ? 1 : 0) + ($classId ? 1 : 0) + ($subjectId ? 1 : 0) + ($year ? 1 : 0)">
                    <div class="flex flex-col gap-1.5">
                        <label for="assignment-filter-teacher" class="text-xs font-semibold text-slate-700">Guru</label>
                        <div class="relative">
                            <select id="assignment-filter-teacher" name="teacher_id"
                                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Guru</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected($teacherId === $teacher->id)>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="assignment-filter-class" class="text-xs font-semibold text-slate-700">Kelas</label>
                        <div class="relative">
                            <select id="assignment-filter-class" name="class_id"
                                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Kelas</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }} &mdash; {{ $class->academic_year }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="assignment-filter-subject" class="text-xs font-semibold text-slate-700">Mata Pelajaran</label>
                        <div class="relative">
                            <select id="assignment-filter-subject" name="subject_id"
                                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Mata Pelajaran</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->code }} &mdash; {{ $subject->name }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="assignment-filter-year" class="text-xs font-semibold text-slate-700">Tahun Akademik</label>
                        <div class="relative">
                            <select id="assignment-filter-year" name="academic_year"
                                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Tahun Akademik</option>
                                @foreach ($years as $option)
                                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                        </div>
                    </div>
                </x-filter-modal>

                <a href="{{ route('admin.teaching-assignments.index') }}"
                   class="h-10 flex items-center gap-1.5 px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold border border-slate-200 transition-colors">
                    <span>Reset</span>
                </a>
                <x-admin-add-link :href="route('admin.teaching-assignments.create')" label="Tambah Penugasan" />
            </div>
        </form>

        {{-- DATA TABLE --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 text-center w-12 font-semibold" scope="col">No</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Guru</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Kelas</th>
                            <th class="py-3 px-5 font-semibold" scope="col">Mata Pelajaran</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Tahun Akademik</th>
                            <th class="py-3 px-5 text-center font-semibold" scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($assignments as $assignment)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center font-medium text-slate-400 text-xs">{{ $assignments->firstItem() + $loop->index }}</td>
                                <td class="py-3 px-5 font-semibold text-slate-900">{{ $assignment->teacher?->name ?? '&mdash;' }}</td>
                                <td class="py-3 px-5 text-slate-700 font-medium">
                                    {{ $assignment->schoolClass?->name ?? '&mdash;' }}
                                    @if ($assignment->schoolClass && ! $assignment->schoolClass->is_active)
                                        <span class="ml-1 rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-3 px-5 text-slate-700 font-medium">
                                    {{ $assignment->subject?->code ?? '&mdash;' }} &mdash; {{ $assignment->subject?->name ?? '' }}
                                </td>
                                <td class="py-3 px-5 text-center font-semibold text-slate-700">{{ $assignment->academic_year }}</td>
                                <td class="py-3 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.teaching-assignments.edit', $assignment) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-colors">
                                            <span class="material-symbols-outlined text-sm text-slate-500">edit</span>
                                            <span>Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('admin.teaching-assignments.destroy', $assignment) }}"
                                              onsubmit="return confirm('Hapus penugasan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition-colors">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 px-5 text-center text-sm text-slate-500">Belum ada penugasan yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION FOOTER --}}
        <div class="bg-white px-6 py-4 rounded-2xl border border-slate-200/70 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
            <p class="text-xs text-slate-400">
                Menampilkan <span class="font-semibold text-slate-700">{{ $assignments->firstItem() ?? 0 }} &ndash; {{ $assignments->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-700">{{ $assignments->total() }}</span> Penugasan
            </p>
            {{ $assignments->links('vendor.pagination.stitch') }}
        </div>
    </div>
@endsection
