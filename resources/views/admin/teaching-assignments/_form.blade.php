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
    <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ $assignment ? route('admin.teaching-assignments.update', $assignment) : route('admin.teaching-assignments.store') }}"
      class="mt-6 space-y-4">
    @csrf
    @if ($assignment)
        @method('PUT')
    @endif

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Guru</span>
        <select name="teacher_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
            <option value="">Pilih guru</option>
            @foreach ($teacherOptions as $teacher)
                <option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $assignment?->teacher_id) === (string) $teacher->id)>
                    {{ $teacher->name }}{{ $teacher->user?->is_active ? '' : ' (akun nonaktif)' }}
                </option>
            @endforeach
        </select>
        @if ($teacherOptions->isEmpty())
            <span class="mt-1 block text-[11px] text-ink-muted">Belum ada guru dengan akun aktif. Buat akun guru terlebih dahulu.</span>
        @endif
    </label>

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Kelas</span>
        <select name="class_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
            <option value="">Pilih kelas aktif</option>
            @foreach ($classOptions as $class)
                <option value="{{ $class->id }}" @selected((string) old('class_id', $assignment?->class_id) === (string) $class->id)>
                    {{ $class->name }} — {{ $class->academic_year }}{{ $class->is_active ? '' : ' (nonaktif)' }}
                </option>
            @endforeach
        </select>
        <span class="mt-1 block text-[11px] text-ink-muted">Tahun akademik penugasan otomatis mengikuti kelas yang dipilih.</span>
    </label>

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Mata pelajaran</span>
        <select name="subject_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
            <option value="">Pilih mata pelajaran</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected((string) old('subject_id', $assignment?->subject_id) === (string) $subject->id)>
                    {{ $subject->code }} — {{ $subject->name }}
                </option>
            @endforeach
        </select>
    </label>

    <button type="submit"
            class="w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
        {{ $assignment ? 'Simpan Perubahan' : 'Simpan Penugasan' }}
    </button>
</form>
