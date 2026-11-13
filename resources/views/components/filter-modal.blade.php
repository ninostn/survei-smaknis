@props([
    'title',
    'resetUrl',
    'activeCount' => 0,
])

<button type="button" data-filter-open
        class="h-10 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-semibold shadow-sm transition-all duration-150 shrink-0">
    <span class="material-symbols-outlined text-slate-500 text-base">tune</span>
    <span>Filter</span>
    @if ($activeCount > 0)
        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
    @endif
</button>

<div data-filter-modal class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-base">tune</span>
                </div>
                <h3 class="text-base font-bold text-slate-900">{{ $title }}</h3>
            </div>
            <button type="button" data-filter-close
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <span class="material-symbols-outlined text-lg leading-none">close</span>
            </button>
        </div>

        <div class="p-6 flex flex-col gap-5 text-sm">
            {{ $slot }}
        </div>

        <div class="flex items-center justify-between px-6 py-4 bg-slate-50/80 border-t border-slate-100">
            <a href="{{ $resetUrl }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">Reset Filter</a>
            <div class="flex items-center gap-2">
                <button type="button" data-filter-close
                        class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-sm transition-colors">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors">Terapkan Filter</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.querySelector('[data-filter-modal]');
        if (!modal) return;

        modal.querySelectorAll('select').forEach(function (select) {
            var wrapper = select.parentElement;
            var menu = document.createElement('details');
            menu.className = 'relative w-full';

            var summary = document.createElement('summary');
            summary.className = 'flex h-10 cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm font-medium text-slate-800 transition-colors hover:border-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500';
            summary.setAttribute('aria-label', 'Pilih filter');

            var label = document.createElement('span');
            var icon = document.createElement('span');
            icon.className = 'text-slate-400';
            icon.textContent = '⌄';
            summary.append(label, icon);

            var options = document.createElement('div');
            options.className = 'absolute left-0 top-full z-30 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg';

            Array.from(select.options).forEach(function (option) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 focus-visible:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-500';
                button.textContent = option.textContent.trim();
                button.dataset.value = option.value;
                button.addEventListener('click', function () {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    sync();
                    menu.open = false;
                    summary.focus();
                });
                options.append(button);
            });

            function sync() {
                label.textContent = select.selectedOptions[0]?.textContent.trim() || 'Pilih';
                options.querySelectorAll('button').forEach(function (button) {
                    button.setAttribute('aria-pressed', String(button.dataset.value === select.value));
                });
            }

            menu.append(summary, options);
            select.hidden = true;
            wrapper.querySelectorAll('.material-symbols-outlined').forEach(function (element) {
                element.hidden = true;
            });
            wrapper.append(menu);
            sync();

            document.addEventListener('click', function (event) {
                if (!menu.contains(event.target)) menu.open = false;
            });
        });

        var openBtn = document.querySelector('[data-filter-open]');
        var closeBtns = document.querySelectorAll('[data-filter-close]');

        function showModal() {
            modal.classList.remove('hidden');
        }

        function hideModal() {
            modal.classList.add('hidden');
        }

        if (openBtn) openBtn.addEventListener('click', showModal);
        closeBtns.forEach(function (btn) {
            btn.addEventListener('click', hideModal);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) hideModal();
        });
    })();
</script>
