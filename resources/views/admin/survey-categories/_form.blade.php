@php($category = $category ?? null)

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
      action="{{ $category ? route('admin.survey-categories.update', $category) : route('admin.survey-categories.store') }}"
      class="space-y-5">
    @csrf
    @if ($inDialog ?? false)
        <input type="hidden" name="_create_dialog" value="1">
    @endif
    @if ($category)
        @method('PUT')
    @endif

    <div class="flex flex-col gap-1.5">
        <label for="category-name" class="text-xs font-semibold text-slate-700">Nama kategori</label>
        <input id="category-name" type="text" name="name" value="{{ old('name', $category?->name) }}" required maxlength="255"
               class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="category-description" class="text-xs font-semibold text-slate-700">Deskripsi <span class="font-medium text-slate-500">(opsional)</span></label>
        <textarea id="category-description" name="description" rows="4" maxlength="1000"
                  class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('description', $category?->description) }}</textarea>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
        <button type="submit"
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm transition-all duration-150">
            <span>{{ $category ? 'Simpan Perubahan' : 'Simpan Kategori' }}</span>
        </button>
    </div>
</form>
