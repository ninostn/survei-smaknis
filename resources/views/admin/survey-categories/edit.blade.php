@extends('layouts.admin')

@section('title', 'Edit Kategori Survei')

@section('admin-content')
    <div class="w-full max-w-4xl flex flex-col gap-4">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div>
            <a href="{{ route('admin.survey-categories.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali ke daftar kategori</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h1 class="text-base font-bold text-slate-900">Edit Kategori Survei</h1>
                <p class="mt-0.5 text-xs text-slate-500">{{ $category->name }}</p>
            </div>
            <div class="p-6">
                @include('admin.survey-categories._form', ['category' => $category])
            </div>
        </div>
    </div>
@endsection
