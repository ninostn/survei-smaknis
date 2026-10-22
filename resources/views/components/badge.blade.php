@props(['status'])

@php
    $map = [
        'aktif' => ['Aktif', 'border-ok/30 bg-ok/10 text-ok'],
        'nonaktif' => ['Nonaktif', 'border-muted/30 bg-muted/10 text-muted'],
        'admin' => ['Admin', 'border-accent/30 bg-accent/10 text-accent'],
        'guru' => ['Guru', 'border-accent/30 bg-accent/10 text-accent'],
        'murid' => ['Murid', 'border-accent/30 bg-accent/10 text-accent'],
        'likert' => ['Likert', 'border-accent/30 bg-accent/10 text-accent'],
        'pg' => ['Pilihan ganda', 'border-line bg-raised text-white'],
        'essay' => ['Esai', 'border-line bg-raised text-white'],
        'sudah' => ['Sudah diisi', 'border-ok/30 bg-ok/10 text-ok'],
        'belum' => ['Belum diisi', 'border-muted/30 bg-muted/10 text-muted'],
    ];

    [$label, $style] = $map[$status] ?? [$status, 'border-line bg-raised text-muted'];
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[12px] font-medium {{ $style }}">
    {{ $label }}
</span>
