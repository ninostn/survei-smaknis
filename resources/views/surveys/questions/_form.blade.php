@php
    $types = [
        'single_choice' => 'Pilihan tunggal',
        'multiple_choice' => 'Pilihan ganda',
        'rating' => 'Rating 1-5',
        'short_text' => 'Teks singkat',
        'long_text' => 'Teks panjang',
    ];
    $optionTypes = ['single_choice', 'multiple_choice'];
    $selectedType = old('type', $question?->type ?? 'single_choice');
    $initialOptions = old('option_texts', $question ? $question->options->pluck('option_text')->all() : ['', '']);
    $initialOptions = is_array($initialOptions) ? array_values($initialOptions) : [];
    if ($initialOptions === [] && in_array($selectedType, $optionTypes, true)) {
        $initialOptions = ['', ''];
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
      action="{{ $question ? route('surveys.questions.update', [$survey, $question]) : route('surveys.questions.store', $survey) }}"
      class="mt-6 space-y-5">
    @csrf
    @if ($question)
        @method('PUT')
    @endif

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Teks pertanyaan</span>
        <textarea name="question_text" rows="3" required maxlength="2000"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">{{ old('question_text', $question?->question_text) }}</textarea>
    </label>

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Tipe pertanyaan</span>
        <select name="type" id="question-type" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label class="flex items-center gap-2 text-xs font-semibold text-ink">
        <input type="checkbox" name="is_required" value="1" class="accent-accent-lavender"
               @checked((bool) old('is_required', $question?->is_required ?? false))>
        <span>Wajib dijawab</span>
    </label>

    <fieldset id="options-section" class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4">
        <legend class="px-1 text-xs font-bold text-ink">Opsi jawaban</legend>
        <p class="mt-1 text-[11px] font-medium text-ink-muted">Minimal dua opsi untuk pertanyaan pilihan. Opsi tidak dipakai pada tipe rating atau teks.</p>

        <div id="option-rows" class="mt-3 space-y-2">
            @foreach ($initialOptions as $option)
                <div data-option-row class="flex items-center gap-2">
                    <input type="text" name="option_texts[]" value="{{ $option }}" maxlength="255"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-accent-lavender focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                    <button type="button" data-remove-option
                            class="shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold text-ink-muted transition-colors hover:border-rose-300 hover:text-rose-600">
                        Hapus
                    </button>
                </div>
            @endforeach
        </div>

        <button type="button" id="add-option"
                class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
            + Tambah opsi
        </button>
    </fieldset>

    <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
        <a href="{{ route('surveys.questions.index', $survey) }}"
           class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
            Batal
        </a>
        <button type="submit"
                class="rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
            {{ $question ? 'Simpan Perubahan' : 'Simpan Pertanyaan' }}
        </button>
    </div>
</form>

<script>
    (function () {
        const optionSection = document.getElementById('options-section');
        const typeSelect = document.getElementById('question-type');
        const optionRows = document.getElementById('option-rows');
        const addOption = document.getElementById('add-option');
        const optionTypes = ['single_choice', 'multiple_choice'];

        function toggleOptions() {
            optionSection.classList.toggle('hidden', !optionTypes.includes(typeSelect.value));
        }

        function bindRemove(button) {
            button.addEventListener('click', function () {
                const row = button.closest('[data-option-row]');
                if (row) {
                    row.remove();
                }
            });
        }

        typeSelect.addEventListener('change', toggleOptions);
        toggleOptions();

        optionRows.querySelectorAll('[data-remove-option]').forEach(bindRemove);

        addOption.addEventListener('click', function () {
            const row = document.createElement('div');
            row.setAttribute('data-option-row', '');
            row.className = 'flex items-center gap-2';
            row.innerHTML = '<input type="text" name="option_texts[]" maxlength="255" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-accent-lavender focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">'
                + '<button type="button" data-remove-option class="shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold text-ink-muted transition-colors hover:border-rose-300 hover:text-rose-600">Hapus</button>';
            optionRows.appendChild(row);
            bindRemove(row.querySelector('[data-remove-option]'));
        });
    })();
</script>
