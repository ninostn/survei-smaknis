@props(['title', 'form'])

<dialog id="admin-create-dialog" aria-labelledby="admin-create-title" data-reopen="{{ old('_create_dialog') === '1' && $errors->any() ? 'true' : 'false' }}"
        class="m-auto max-h-[90vh] w-[min(92vw,32rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <h2 id="admin-create-title" class="text-base font-bold">{{ $title }}</h2>
        <button type="button" data-create-close aria-label="Tutup" class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100"><span aria-hidden="true" class="material-symbols-outlined">close</span></button>
    </div>
    <div class="p-6">
        @include($form, ['inDialog' => true, 'class' => null, 'subject' => null, 'category' => null])
    </div>
</dialog>
