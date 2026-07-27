@props([
'show',
'maxWidth' => 'lg'
])

@php
$maxWidthClass = [
'sm' => 'max-w-sm',
'md' => 'max-w-md',
'lg' => 'max-w-lg',
'xl' => 'max-w-xl',
'2xl' => 'max-w-2xl',
'3xl' => 'max-w-3xl', // Tambahan baru
'4xl' => 'max-w-4xl', // Tambahan baru
'full' => 'max-w-full',
][$maxWidth] ?? 'max-w-lg';
@endphp

<div x-show="{{ $show }}" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- 1. Backdrop (Latar Belakang Hitam Transparan) --}}
    {{-- Animasi: Fade-in & Fade-out biasa (durasi 300ms) --}}
    <div x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50"
        @click="{{ $show }} = false">
    </div>

    {{-- 2. Panel Modal Utama --}}
    {{-- Animasi: Slide-down (-translate-y-12 setara 48px, sangat mirip Bootstrap 50px) --}}
    <div x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-12"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-12"
        class="relative z-10 bg-white rounded-2xl w-full {{ $maxWidthClass }} max-h-[90vh] flex flex-col overflow-hidden shadow-2xl">
        {{ $slot }}
    </div>

</div>