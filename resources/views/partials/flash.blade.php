@php
    $flashSuccess = session('success') ?? session('ok');
    $flashError = session('error');
@endphp

@if ($flashSuccess)
    <x-alert type="success">{{ $flashSuccess }}</x-alert>
@endif

@if ($flashError)
    <x-alert type="danger">{{ $flashError }}</x-alert>
@endif

@if ($errors->any())
    <x-alert type="danger">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </x-alert>
@endif
