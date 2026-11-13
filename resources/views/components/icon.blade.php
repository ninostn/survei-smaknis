@props(['name'])

@php
    $icons = [
        'dashboard' => '<path d="M4 4h6.5v6.5H4zM13.5 4H20v6.5h-6.5zM4 13.5h6.5V20H4zM13.5 13.5H20V20h-6.5z"/>',
        'survey' => '<rect x="4.5" y="4.5" width="15" height="15" rx="2"/><path d="M8.5 15.5v-3M12 15.5V8.5M15.5 15.5v-5"/>',
        'category' => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h5.1l9.4 9.4-6.6 6.6-9.4-9.4V5.5z"/><path d="M7.5 7.5h.01"/>',
        'users' => '<circle cx="9" cy="8" r="3.25"/><path d="M3.5 19.5c.7-3 2.9-4.75 5.5-4.75s4.8 1.75 5.5 4.75"/><circle cx="16.75" cy="9.5" r="2.5"/><path d="M16.2 14.9c2.2.25 3.9 1.7 4.5 3.85"/>',
        'student' => '<path d="M12 4.5 2.5 9l9.5 4.5L21.5 9 12 4.5z"/><path d="M6.5 11.3v4.2c0 1.5 2.46 3 5.5 3s5.5-1.5 5.5-3v-4.2"/><path d="M21.5 9v5.5"/>',
        'badge' => '<rect x="3.5" y="5" width="17" height="14" rx="2"/><circle cx="9" cy="10.5" r="1.75"/><path d="M6.5 15.5c.4-1.4 1.3-2.1 2.5-2.1s2.1.7 2.5 2.1"/><path d="M14.5 9.5h3M14.5 12.5h3"/>',
        'door' => '<path d="M6 20V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v15"/><path d="M3.5 20h17"/><path d="M14 11.5h.01"/>',
        'assign' => '<rect x="4" y="3.5" width="16" height="17" rx="2"/><circle cx="12" cy="9" r="2"/><path d="M8.5 15.5c.7-1.7 2-2.5 3.5-2.5s2.8.8 3.5 2.5"/>',
        'school' => '<path d="M4 20V6.5L12 3l8 3.5V20"/><path d="M4 20h16"/><path d="M9.5 20v-5.5h5V20"/><path d="M8 9.5h.01M12 9.5h.01M16 9.5h.01"/>',
        'book' => '<path d="M12 6.5C10.5 5 8 4.5 5.5 4.5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1c2.5 0 5 .5 6.5 2 1.5-1.5 4-2 6.5-2a1 1 0 0 0 1-1v-12a1 1 0 0 0-1-1C16 4.5 13.5 5 12 6.5z"/><path d="M12 6.5v14"/>',
        'assignment' => '<path d="M16.5 3.5l4 4L9 19H5v-4L16.5 3.5z"/>',
        'logout' => '<path d="M15.5 8.5V6.75A1.75 1.75 0 0 0 13.75 5h-6A1.75 1.75 0 0 0 6 6.75v10.5A1.75 1.75 0 0 0 7.75 19h6a1.75 1.75 0 0 0 1.75-1.75V15.5"/><path d="M11 12h9.5m0 0-2.75-2.75M20.5 12l-2.75 2.75"/>',
        'calendar' => '<rect x="4" y="5.5" width="16" height="15" rx="2"/><path d="M4 10.5h16M8.5 3.5v4M15.5 3.5v4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'chart' => '<path d="M5 20v-9M12 20V4.5M19 20v-6.5"/>',
        'star' => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.2 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.2 5.9-.9L12 3.5z"/>',
        'user-check' => '<circle cx="9.5" cy="8" r="3.25"/><path d="M4 19.5c.7-3 2.9-4.75 5.5-4.75 1.2 0 2.3.35 3.2 1"/><path d="m13.5 17 2 2 4-4.5"/>',
        'clipboard' => '<path d="M9 4.5H7.5A1.5 1.5 0 0 0 6 6v13a1.5 1.5 0 0 0 1.5 1.5h9A1.5 1.5 0 0 0 18 19V6a1.5 1.5 0 0 0-1.5-1.5H15"/><rect x="9" y="3" width="6" height="3.5" rx="1"/>',
        'edit' => '<path d="M16.5 3.5l4 4L9 19H5v-4L16.5 3.5z"/>',
    ];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
     stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
     {{ $attributes->merge(['class' => 'h-5 w-5']) }}
>{!! $icons[$name] !!}</svg>
