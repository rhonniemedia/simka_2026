@props(['width' => '', 'sub' => null])
<th {{ $attributes->class(['px-4 py-3 text-sm font-bold text-secondary tracking-wider align-top', $width]) }}>
    <div class="truncate">{{ $slot }}</div>
    @if ($sub)
    <div class="truncate text-[11px] font-normal normal-case">{{ $sub }}</div>
    @endif
</th>