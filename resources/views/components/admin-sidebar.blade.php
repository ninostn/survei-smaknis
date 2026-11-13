@php
    $user = auth()->user();

    $usersActive = request()->routeIs('admin.users.*');
    $roleFilter = request()->query('role');

    $navGroups = [
        'Utama' => [
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard', 'params' => [], 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Survei', 'icon' => 'poll', 'route' => 'surveys.index', 'params' => [], 'active' => request()->routeIs('surveys.*')],
            ['label' => 'Kategori Survei', 'icon' => 'category', 'route' => 'admin.survey-categories.index', 'params' => [], 'active' => request()->routeIs('admin.survey-categories.*')],
        ],
        'Data Master' => [
            ['label' => 'Guru', 'icon' => 'badge', 'route' => 'admin.users.index', 'params' => ['role' => 'teacher'], 'active' => $usersActive && $roleFilter === 'teacher'],
            ['label' => 'Siswa', 'icon' => 'school', 'route' => 'admin.users.index', 'params' => ['role' => 'student'], 'active' => $usersActive && $roleFilter !== 'teacher'],
            ['label' => 'Kelas', 'icon' => 'meeting_room', 'route' => 'admin.classes.index', 'params' => [], 'active' => request()->routeIs('admin.classes.*')],
            ['label' => 'Mata Pelajaran', 'icon' => 'menu_book', 'route' => 'admin.subjects.index', 'params' => [], 'active' => request()->routeIs('admin.subjects.*')],
            ['label' => 'Penugasan Guru', 'icon' => 'assignment_ind', 'route' => 'admin.teaching-assignments.index', 'params' => [], 'active' => request()->routeIs('admin.teaching-assignments.*')],
        ],
    ];
@endphp

<aside class="sticky top-0 z-30 hidden h-screen w-64 shrink-0 flex-col justify-between border-r border-slate-200/70 bg-white p-6 lg:flex">
    <div class="flex min-h-0 flex-col gap-8">
        <div class="flex shrink-0 items-center gap-3 px-1">
            <img alt="Logo SMK Nurul Islam Cianjur" class="h-9 w-9 shrink-0 object-contain" src="{{ asset('images/logo-smaknis.png') }}">
            <div class="flex min-w-0 flex-col">
                <span class="truncate text-sm font-bold leading-tight tracking-tight text-slate-800">SURVEI SMAKNIS</span>
                <span class="truncate text-[11px] font-medium text-slate-400">SMK NURUL ISLAM</span>
            </div>
        </div>

        <nav class="flex min-h-0 flex-col gap-6 overflow-y-auto" aria-label="Navigasi admin">
            @foreach ($navGroups as $groupLabel => $items)
                <div>
                    <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $groupLabel }}</p>
                    <div class="space-y-1">
                        @foreach ($items as $item)
                            <a href="{{ route($item['route'], $item['params']) }}"
                               @class([
                                   'flex items-center gap-3 rounded-xl bg-blue-50 px-3 py-2.5 text-sm font-semibold text-blue-600 transition-colors' => $item['active'],
                                   'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900' => ! $item['active'],
                               ])
                               @if ($item['active']) aria-current="page" @endif>
                                <span aria-hidden="true" class="material-symbols-outlined shrink-0 text-[20px] {{ $item['active'] ? 'text-blue-600 fill-icon' : 'text-slate-400' }}">{{ $item['icon'] }}</span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>

    <div class="mt-4 shrink-0 border-t border-slate-200/70 pt-4">
        <div class="flex items-center justify-between rounded-xl border border-slate-200/70 bg-slate-50/60 p-2 transition-colors hover:bg-slate-50">
            <div class="flex min-w-0 items-center gap-2.5 overflow-hidden">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white shadow-sm">{{ mb_strtoupper(mb_substr($user->username, 0, 1)) }}</div>
                <div class="flex min-w-0 flex-col">
                    <span class="truncate text-xs font-semibold leading-tight text-slate-800">{{ $user->username }}</span>
                    <span class="mt-0.5 text-[10px] font-medium uppercase tracking-wider text-slate-400">Admin</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" aria-label="Keluar" title="Keluar"
                        class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-500">
                    <span aria-hidden="true" class="material-symbols-outlined text-[18px]">logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>
