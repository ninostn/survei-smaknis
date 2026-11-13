@php
    $survey = $survey ?? null;
    $selectedClassIds = array_map('strval', (array) old('target_class_ids', $survey ? $survey->targetClasses->pluck('id')->all() : []));
    $studentTargetMode = old('student_target_mode', $survey?->student_target_mode ?? ($canTargetAllStudents ? 'all' : 'classes'));
    $majors = $classes->pluck('major')->filter()->unique()->sort()->values();
    $years = $classes->pluck('academic_year')->filter()->unique()->sortDesc()->values();
    $selectedMajors = (array) old('target_majors', []);
    $selectedYears = (array) old('target_academic_years', []);
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
      class="survey-editor mt-6 space-y-7">
    @csrf
    @if ($survey)
        @method('PUT')
    @endif

    <section aria-labelledby="survey-details-heading" class="space-y-4">
    <div>
        <h2 id="survey-details-heading" class="text-sm font-bold text-slate-900">Informasi survei</h2>
        <p class="mt-1 text-xs text-slate-500">Isi informasi yang akan dilihat responden.</p>
    </div>
    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-slate-700">Judul survei</span>
        <input type="text" name="title" value="{{ old('title', $survey?->title) }}" required maxlength="255"
               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
    </label>

    <div class="relative" data-category-picker>
        <label for="survey-category" class="mb-1.5 block text-xs font-semibold text-slate-700">Kategori</label>
        <select id="survey-category" name="category_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $survey?->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <details data-category-menu class="relative hidden">
            <summary aria-label="Kategori survei" class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 transition-colors hover:border-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">
                <span data-category-label>Pilih kategori</span>
                <svg aria-hidden="true" class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg" aria-label="Pilihan kategori">
                @foreach ($categories as $category)
                    <button type="button" data-category-value="{{ $category->id }}" class="block w-full rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 focus-visible:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-500">{{ $category->name }}</button>
                @endforeach
                @if ($categories->isEmpty())
                    <p class="px-3 py-2.5 text-sm text-slate-500">Belum ada kategori.</p>
                @endif
            </div>
        </details>
    </div>

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Deskripsi <span class="font-medium text-ink-muted">(opsional)</span></span>
        <textarea name="description" rows="4" maxlength="5000"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">{{ old('description', $survey?->description) }}</textarea>
    </label>

    </section>

    <section aria-labelledby="survey-targets-heading" class="space-y-4 border-t border-slate-200 pt-6">
    <div>
        <h2 id="survey-targets-heading" class="text-sm font-bold text-slate-900">Target responden</h2>
        <p class="mt-1 text-xs text-slate-500">Pilih siswa berdasarkan kelas, jurusan, atau angkatan.</p>
    </div>
    <div class="grid gap-5 md:grid-cols-3">
    <fieldset class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:col-span-3">
        <legend class="px-1 text-sm font-bold text-slate-900">Target siswa</legend>
        <p class="mt-1 text-xs text-slate-500">Tentukan siswa yang dapat mengisi survei ini.</p>

        <div class="mt-4 space-y-2">
            @if ($canTargetAllStudents)
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition-colors has-[:checked]:border-blue-300 has-[:checked]:bg-blue-50/60 hover:border-slate-300">
                    <input type="radio" name="student_target_mode" value="all" class="mt-0.5 size-4 accent-blue-600"
                           @checked($studentTargetMode === 'all')>
                    <span class="text-xs font-semibold text-slate-800">Semua siswa aktif</span>
                </label>
            @endif

            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition-colors has-[:checked]:border-blue-300 has-[:checked]:bg-blue-50/60 hover:border-slate-300">
                <input type="radio" name="student_target_mode" value="classes" class="mt-0.5 size-4 accent-blue-600"
                       @checked($studentTargetMode === 'classes')>
                <span class="text-xs font-semibold text-slate-800">Pilihan tertentu</span>
            </label>
        </div>

        @unless ($canTargetAllStudents)
            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[11px] font-medium leading-relaxed text-amber-700">Anda hanya dapat memilih kelas yang tercantum dalam penugasan mengajar Anda.</p>
        @endunless

        <div id="student-class-targets" @class(['mt-4 space-y-2 border-t border-slate-200 pt-4', 'hidden' => $studentTargetMode !== 'classes'])>
            <div class="grid gap-4 md:grid-cols-3">
            <div>
            <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Jurusan</p>
            @foreach ($majors as $major)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    <input type="checkbox" name="target_majors[]" value="{{ $major }}" class="size-4 rounded border-slate-300 accent-blue-600" @checked(in_array($major, $selectedMajors, true))>
                    <span><span class="font-semibold text-slate-900">{{ $major }}</span><span class="font-normal text-slate-500">: {{ \App\Models\SchoolClass::MAJORS[$major] ?? 'Jurusan belum dipetakan' }}</span></span>
                </label>
            @endforeach
            </div>
            <div>
            <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Angkatan</p>
            @foreach ($years as $year)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    <input type="checkbox" name="target_academic_years[]" value="{{ $year }}" class="size-4 rounded border-slate-300 accent-blue-600" @checked(in_array($year, $selectedYears, true))>
                    <span>{{ $year }}</span>
                </label>
            @endforeach
            </div>
            <div>
            <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Kelas</p>
            @forelse ($classes as $class)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50">
                    <input type="checkbox" name="target_class_ids[]" value="{{ $class->id }}" class="size-4 rounded border-slate-300 accent-blue-600"
                           @checked(in_array((string) $class->id, $selectedClassIds, true))>
                    <span>{{ $class->name }} <span class="font-normal text-slate-500">({{ $class->academic_year }})</span></span>
                </label>
            @empty
                <p class="text-[11px] font-medium text-ink-muted">Tidak ada kelas aktif yang dapat dipilih.</p>
            @endforelse
            </div>
            </div>
        </div>
    </fieldset>

    </div>
    </section>

    <div class="flex justify-end border-t border-slate-200 pt-5">
        <button type="submit"
                class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
            {{ $survey ? 'Simpan Perubahan' : 'Simpan Survei' }}
        </button>
    </div>
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

        const picker = document.querySelector('[data-category-picker]');
        const select = picker?.querySelector('select');
        const menu = picker?.querySelector('[data-category-menu]');
        const label = picker?.querySelector('[data-category-label]');
        if (select && menu && label) {
            const summary = menu.querySelector('summary');
            const syncCategory = () => {
                label.textContent = select.selectedOptions[0]?.textContent.trim() || 'Pilih kategori';
                menu.querySelectorAll('[data-category-value]').forEach((button) => {
                    button.setAttribute('aria-pressed', String(button.dataset.categoryValue === select.value));
                });
            };
            syncCategory();
            select.hidden = true;
            menu.classList.remove('hidden');
            menu.querySelectorAll('[data-category-value]').forEach((button) => {
                button.addEventListener('click', () => {
                    select.value = button.dataset.categoryValue;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncCategory();
                    menu.open = false;
                    summary.focus();
                });
            });
            select.addEventListener('invalid', (event) => {
                event.preventDefault();
                menu.open = true;
                summary.focus();
            });
            document.addEventListener('click', (event) => {
                if (!picker.contains(event.target)) menu.open = false;
            });
            menu.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    menu.open = false;
                    summary.focus();
                }
            });
        }
    })();
</script>
