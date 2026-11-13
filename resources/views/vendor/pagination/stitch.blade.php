@if ($paginator->hasPages())
    <div class="flex items-center gap-1.5">
        {{-- Halaman sebelumnya --}}
        @if (! $paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"
               class="p-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </a>
        @else
            <button type="button" disabled
                    class="p-1.5 rounded-lg border border-slate-200 text-slate-400 hover:text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition-colors">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </button>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-slate-400 text-xs">{{ $element }}</span>
            @elseif ($element === $paginator->currentPage())
                <span aria-current="page"
                      class="w-7 h-7 rounded-lg bg-blue-600 text-white text-xs font-semibold flex items-center justify-center shadow-sm">{{ $element }}</span>
            @else
                <a href="{{ $paginator->url($element) }}" aria-label="Ke halaman {{ $element }}"
                   class="w-7 h-7 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-medium flex items-center justify-center transition-colors">{{ $element }}</a>
            @endif
        @endforeach

        {{-- Halaman berikutnya --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"
               class="p-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        @else
            <button type="button" disabled
                    class="p-1.5 rounded-lg border border-slate-200 text-slate-400 hover:text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition-colors">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </button>
        @endif
    </div>
@endif
