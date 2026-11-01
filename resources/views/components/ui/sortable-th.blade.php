@props(['column', 'target', 'sort' => null, 'direction' => 'asc'])

@php
$active = $sort === $column;
$nextDirection = ($active && $direction === 'asc') ? 'desc' : 'asc';
$url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => 1]);
$icon = ! $active ? 'arrow-up-down' : ($direction === 'asc' ? 'arrow-up' : 'arrow-down');
$ariaSort = ! $active ? 'none' : ($direction === 'asc' ? 'ascending' : 'descending');
@endphp

<th {{ $attributes->merge(['class' => 'px-4 py-3 text-sm font-bold text-secondary tracking-wider cursor-pointer hover:text-primary transition-colors']) }}
    aria-sort="{{ $ariaSort }}"
    hx-get="{{ $url }}"
    hx-target="{{ $target }}"
    hx-swap="outerHTML">
    <div class="flex items-center justify-between gap-1.5">
        <div>{{ $slot }}</div>
        <i data-lucide="{{ $icon }}" class="size-4 shrink-0 {{ $active ? 'text-primary' : 'text-secondary/40' }}"></i>
    </div>
</th>