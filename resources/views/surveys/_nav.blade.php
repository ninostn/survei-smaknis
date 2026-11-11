@php
    $user = auth()->user();
    $dashboardRoute = match ($user->role) {
        'admin' => route('admin.dashboard'),
        'teacher' => route('teacher.dashboard'),
        default => route('student.dashboard'),
    };
@endphp
<header class="border-b border-white/60 bg-white/50 backdrop-blur">
    <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-1 gap-y-2 px-4 py-3">
        <a href="{{ $dashboardRoute }}" class="mr-3 flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-tr from-accent-lavender to-accent-lavender-soft text-white">
                <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 18h4V6L4 18zm6 0h4V6l-4 12zm6-12v12h4L16 6z"></path>
                </svg>
            </span>
            <span class="text-sm font-bold tracking-tight text-ink">SMAKNIS <span class="font-medium text-ink-muted">Survei</span></span>
        </a>

        <nav class="flex flex-1 flex-wrap items-center gap-1">
            <a href="{{ $dashboardRoute }}"
               class="rounded-xl px-3 py-1.5 text-xs font-semibold text-ink-muted transition-colors hover:bg-white/70 hover:text-ink">
                Dashboard
            </a>

            @if ($user->role !== 'student')
                <a href="{{ route('surveys.index') }}"
                   @class([
                       'rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors',
                       'bg-white text-ink shadow-sm ring-1 ring-slate-200/70' => request()->routeIs('surveys.*'),
                       'text-ink-muted hover:bg-white/70 hover:text-ink' => ! request()->routeIs('surveys.*'),
                   ])>
                    Survei
                </a>
            @endif

            @if ($user->role !== 'admin')
                <a href="{{ route('responses.index') }}"
                   @class([
                       'rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors',
                       'bg-white text-ink shadow-sm ring-1 ring-slate-200/70' => request()->routeIs('responses.*'),
                       'text-ink-muted hover:bg-white/70 hover:text-ink' => ! request()->routeIs('responses.*'),
                   ])>
                    Isi Survei
                </a>
            @endif

            @if ($user->role === 'admin')
                <a href="{{ route('admin.survey-categories.index') }}"
                   class="rounded-xl px-3 py-1.5 text-xs font-semibold text-ink-muted transition-colors hover:bg-white/70 hover:text-ink">
                    Kategori Survei
                </a>
            @endif
        </nav>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                Keluar
            </button>
        </form>
    </div>
</header>
