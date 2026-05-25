@props(['title'])

@php
    $current = request()->path();
    $nav = [
        ['path' => 'propose', 'label' => '材料から'],
        ['path' => 'search', 'label' => '料理から'],
        ['path' => 'inventory', 'label' => '冷蔵庫'],
        ['path' => 'history', 'label' => '履歴'],
        ['path' => 'settings', 'label' => '設定'],
    ];
@endphp

<header class="space-y-3">
    <h1 class="text-2xl font-semibold text-gray-900">{{ $title }}</h1>
    <nav class="flex flex-wrap gap-1.5 text-xs">
        @foreach($nav as $item)
            @php $active = $current === $item['path']; @endphp
            <a href="/{{ $item['path'] }}"
                class="rounded-full px-3 py-1.5 transition
                    {{ $active
                        ? 'bg-rose-200 text-rose-900 font-semibold'
                        : 'bg-white text-gray-700 ring-1 ring-rose-100 hover:bg-rose-50' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</header>
