@extends('layouts.admin')

@section('title', 'Edit Penugasan Mengajar')

@section('admin-content')
    <div class="w-full max-w-4xl flex flex-col gap-4">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div>
            <a href="{{ route('admin.teaching-assignments.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali ke daftar penugasan</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h1 class="text-base font-bold text-slate-900">Edit Penugasan Mengajar</h1>
                <p class="mt-0.5 text-xs text-slate-500">{{ $assignment->teacher?->name ?? '—' }} &middot; {{ $assignment->schoolClass?->name ?? '—' }} &middot; {{ $assignment->subject?->name ?? '—' }}</p>
            </div>
            <div class="p-6">
                @include('admin.teaching-assignments._form', ['assignment' => $assignment])
            </div>
        </div>
    </div>
@endsection
