@php
    $survey = $survey ?? null;
    $selectedClassIds = array_map('strval', (array) old('target_class_ids', $survey ? $survey->targetClasses->pluck('id')->all() : []));
    $selectedTeacherIds = array_map('strval', (array) old('target_teacher_ids', $survey ? $survey->targetTeachers->pluck('id')->all() : []));
    $studentTargetMode = old('student_target_mode', $survey?->student_target_mode ?? 'none');
    $teacherTargetMode = old('teacher_target_mode', $survey?->teacher_target_mode ?? 'none');
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
      action="{{ $survey ? route('surveys.update', $survey) : route('surveys.store') }}"
      class="mt-6 space-y-5">
    @csrf
    @if ($survey)
        @method('PUT')
    @endif

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Judul survei</span>
        <input type="text" name="title" value="{{ old('title', $survey?->title) }}" required maxlength="255"
               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
    </label>

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Kategori</span>
        <select name="category_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $survey?->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Deskripsi <span class="font-medium text-ink-muted">(opsional)</span></span>
        <textarea name="description" rows="4" maxlength="5000"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">{{ old('description', $survey?->description) }}</textarea>
    </label>

    <fieldset class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4">
        <legend class="px-1 text-xs font-bold text-ink">Target siswa</legend>

        <div class="mt-1 space-y-2">
            <label class="flex items-start gap-2 text-xs font-medium text-ink">
                <input type="radio" name="student_target_mode" value="none" class="mt-0.5 accent-accent-lavender"
                       @checked($studentTargetMode === 'none')>
                <span>Tidak ada <span class="font-normal text-ink-muted">(target siswa kosong)</span></span>
            </label>

            @if ($canTargetAllStudents)
                <label class="flex items-start gap-2 text-xs font-medium text-ink">
                    <input type="radio" name="student_target_mode" value="all" class="mt-0.5 accent-accent-lavender"
                           @checked($studentTargetMode === 'all')>
                    <span>Semua siswa aktif</span>
                </label>
            @endif

            <label class="flex items-start gap-2 text-xs font-medium text-ink">
                <input type="radio" name="student_target_mode" value="classes" class="mt-0.5 accent-accent-lavender"
                       @checked($studentTargetMode === 'classes')>
                <span>Kelas tertentu</span>
            </label>
        </div>

        @unless ($canTargetAllStudents)
            <p class="mt-2 text-[11px] font-medium text-ink-muted">Anda hanya dapat memilih kelas yang tercantum dalam penugasan mengajar Anda.</p>
        @endunless

        <div id="student-class-targets" @class(['mt-3 space-y-2 border-t border-slate-200 pt-3', 'hidden' => $studentTargetMode !== 'classes'])>
            @forelse ($classes as $class)
                <label class="flex items-start gap-2 text-xs font-medium text-ink">
                    <input type="checkbox" name="target_class_ids[]" value="{{ $class->id }}" class="mt-0.5 accent-accent-lavender"
                           @checked(in_array((string) $class->id, $selectedClassIds, true))>
                    <span>{{ $class->name }} <span class="font-normal text-ink-muted">({{ $class->academic_year }})</span></span>
                </label>
            @empty
                <p class="text-[11px] font-medium text-ink-muted">Tidak ada kelas aktif yang dapat dipilih.</p>
            @endforelse
        </div>
    </fieldset>

    <fieldset class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4">
        <legend class="px-1 text-xs font-bold text-ink">Target guru</legend>

        <div class="mt-1 space-y-2">
            <label class="flex items-start gap-2 text-xs font-medium text-ink">
                <input type="radio" name="teacher_target_mode" value="none" class="mt-0.5 accent-accent-lavender"
                       @checked($teacherTargetMode === 'none')>
                <span>Tidak ada <span class="font-normal text-ink-muted">(target guru kosong)</span></span>
            </label>

            <label class="flex items-start gap-2 text-xs font-medium text-ink">
                <input type="radio" name="teacher_target_mode" value="all" class="mt-0.5 accent-accent-lavender"
                       @checked($teacherTargetMode === 'all')>
                <span>Semua guru aktif</span>
            </label>

            <label class="flex items-start gap-2 text-xs font-medium text-ink">
                <input type="radio" name="teacher_target_mode" value="selected" class="mt-0.5 accent-accent-lavender"
                       @checked($teacherTargetMode === 'selected')>
                <span>Guru tertentu</span>
            </label>
        </div>

        <div id="teacher-targets" @class(['mt-3 space-y-2 border-t border-slate-200 pt-3', 'hidden' => $teacherTargetMode !== 'selected'])>
            @forelse ($teachers as $teacher)
                <label class="flex items-start gap-2 text-xs font-medium text-ink">
                    <input type="checkbox" name="target_teacher_ids[]" value="{{ $teacher->id }}" class="mt-0.5 accent-accent-lavender"
                           @checked(in_array((string) $teacher->id, $selectedTeacherIds, true))>
                    <span>
                        {{ $teacher->name }}
                        @if ($teacher->user?->username)
                            <span class="font-normal text-ink-muted">({{ $teacher->user->username }})</span>
                        @endif
                    </span>
                </label>
            @empty
                <p class="text-[11px] font-medium text-ink-muted">Tidak ada guru aktif yang dapat dipilih.</p>
            @endforelse
        </div>
    </fieldset>

    <button type="submit"
            class="w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
        {{ $survey ? 'Simpan Perubahan' : 'Simpan Survei' }}
    </button>
</form>

<script>
    (() => {
        const syncPanel = (radioName, activeValue, panelId) => {
            const selected = document.querySelector(`input[name="${radioName}"]:checked`);
            const panel = document.getElementById(panelId);

            if (panel) {
                const hidden = !selected || selected.value !== activeValue;

                panel.classList.toggle('hidden', hidden);
                panel.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                    checkbox.disabled = hidden;
                });
            }
        };

        const refresh = () => {
            syncPanel('student_target_mode', 'classes', 'student-class-targets');
            syncPanel('teacher_target_mode', 'selected', 'teacher-targets');
        };

        document
            .querySelectorAll('input[name="student_target_mode"], input[name="teacher_target_mode"]')
            .forEach((input) => input.addEventListener('change', refresh));

        refresh();
    })();
</script>
