@php
    $assignment = $assignment ?? null;

    // Saat edit, opsi hanya berisi data aktif. Sertakan nilai saat ini agar
    // select tidak diam-diam berubah ke opsi pertama bila sudah nonaktif.
    $teacherOptions = $teachers;
    if ($assignment && $assignment->teacher && ! $teacherOptions->contains('id', $assignment->teacher_id)) {
        $teacherOptions = $teacherOptions->push($assignment->teacher);
    }

    $classOptions = $classes;
    if ($assignment && $assignment->schoolClass && ! $classOptions->contains('id', $assignment->class_id)) {
        $classOptions = $classOptions->push($assignment->schoolClass);
    }
@endphp

@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600" role="alert">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ $assignment ? route('admin.teaching-assignments.update', $assignment) : route('admin.teaching-assignments.store') }}"
      class="space-y-5">
    @csrf
    @if ($assignment)
        @method('PUT')
    @endif

    <div class="flex flex-col gap-1.5">
        <label for="assignment-teacher" class="text-xs font-semibold text-slate-700">Guru</label>
        <div class="relative">
            <select id="assignment-teacher" name="teacher_id" required
                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Pilih guru</option>
                @foreach ($teacherOptions as $teacher)
                    <option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $assignment?->teacher_id) === (string) $teacher->id)>
                        {{ $teacher->name }}{{ $teacher->user?->is_active ? '' : ' (akun nonaktif)' }}
                    </option>
                @endforeach
            </select>
            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
        </div>
        @if ($teacherOptions->isEmpty())
            <span class="text-[11px] text-slate-500">Belum ada guru dengan akun aktif. Buat akun guru terlebih dahulu.</span>
        @endif
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="assignment-class" class="text-xs font-semibold text-slate-700">Kelas</label>
        <div class="relative">
            <select id="assignment-class" name="class_id" required
                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Pilih kelas aktif</option>
                @foreach ($classOptions as $class)
                    <option value="{{ $class->id }}" @selected((string) old('class_id', $assignment?->class_id) === (string) $class->id)>
                        {{ $class->name }} — {{ $class->academic_year }}{{ $class->is_active ? '' : ' (nonaktif)' }}
                    </option>
                @endforeach
            </select>
            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
        </div>
        <span class="text-[11px] text-slate-500">Tahun akademik penugasan otomatis mengikuti kelas yang dipilih.</span>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="assignment-subject" class="text-xs font-semibold text-slate-700">Mata pelajaran</label>
        <div class="relative">
            <select id="assignment-subject" name="subject_id" required
                    class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Pilih mata pelajaran</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((string) old('subject_id', $assignment?->subject_id) === (string) $subject->id)>
                        {{ $subject->code }} — {{ $subject->name }}
                    </option>
                @endforeach
            </select>
            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
        <button type="submit"
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm transition-all duration-150">
            <span>{{ $assignment ? 'Simpan Perubahan' : 'Simpan Penugasan' }}</span>
        </button>
    </div>
</form>
