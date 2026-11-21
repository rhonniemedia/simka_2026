@props(['text'])
<div title="{{ $text }}" {{ $attributes->class('truncate') }}>{{ $text }}</div>