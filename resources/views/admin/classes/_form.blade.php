@php($class = $class ?? null)

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
      action="{{ $class ? route('admin.classes.update', $class) : route('admin.classes.store') }}"
      class="space-y-5">
    @csrf
    @if ($inDialog ?? false)
        <input type="hidden" name="_create_dialog" value="1">
    @endif
    @if ($class)
        @method('PUT')
    @endif

    <div class="relative flex flex-col gap-1.5" data-major-picker>
        <label for="class-major" class="text-xs font-semibold text-slate-700">Jurusan</label>
        <select id="class-major" name="major" required
                class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <option value="">Pilih jurusan</option>
            @foreach (\App\Models\SchoolClass::MAJORS as $code => $name)
                <option value="{{ $code }}" @selected(old('major', $class?->major) === $code)>{{ $code }}: {{ $name }}</option>
            @endforeach
        </select>
        <details data-major-menu class="relative hidden">
            <summary aria-label="Pilih jurusan" class="flex min-h-10 cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-800 transition-colors hover:border-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">
                <span data-major-label>Pilih jurusan</span>
                <svg aria-hidden="true" class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="absolute left-0 top-full z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                @foreach (\App\Models\SchoolClass::MAJORS as $code => $name)
                    <button type="button" data-major-value="{{ $code }}" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 focus-visible:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-500">
                        <span class="font-semibold">{{ $code }}</span><span class="text-slate-500">: {{ $name }}</span>
                    </button>
                @endforeach
            </div>
        </details>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="class-name" class="text-xs font-semibold text-slate-700">Nama kelas</label>
        <input id="class-name" type="text" name="name" value="{{ old('name', $class?->name) }}" required
               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="class-year" class="text-xs font-semibold text-slate-700">Tahun akademik</label>
        <input id="class-year" type="text" name="academic_year" value="{{ old('academic_year', $class?->academic_year) }}"
               placeholder="2025/2026" required
               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
    </div>

    @if ($class)
        <p class="text-[11px] text-slate-500">Status aktif diubah lewat tombol di daftar kelas.</p>
    @endif

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
        <button type="submit"
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm transition-all duration-150">
            <span>{{ $class ? 'Simpan Perubahan' : 'Simpan Kelas' }}</span>
        </button>
    </div>
</form>

<script>
    (() => {
        const picker = document.querySelector('[data-major-picker]');
        const select = picker?.querySelector('select');
        const menu = picker?.querySelector('[data-major-menu]');
        const label = picker?.querySelector('[data-major-label]');
        const summary = menu?.querySelector('summary');

        if (!select || !menu || !label || !summary) return;

        const sync = () => {
            label.textContent = select.selectedOptions[0]?.textContent.trim() || 'Pilih jurusan';
            menu.querySelectorAll('[data-major-value]').forEach((button) => {
                button.setAttribute('aria-pressed', String(button.dataset.majorValue === select.value));
            });
        };

        select.hidden = true;
        menu.classList.remove('hidden');
        sync();

        menu.querySelectorAll('[data-major-value]').forEach((button) => {
            button.addEventListener('click', () => {
                select.value = button.dataset.majorValue;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                sync();
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
    })();
</script>
