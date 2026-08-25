@props([
'show',
'maxWidth' => 'lg'
])

@php
// PERBAIKAN 1: Tambahkan prefix 'sm:' di setiap class agar max-width HANYA aktif di desktop.
// Di mobile, modal akan murni w-full sehingga jarak p-4 (16px) di kiri-kanan-atas-bawah akan presisi sama.
$maxWidthClass = [
'sm' => 'sm:max-w-sm',
'md' => 'sm:max-w-md',
'lg' => 'sm:max-w-lg',
'xl' => 'sm:max-w-xl',
'2xl' => 'sm:max-w-2xl',
'3xl' => 'sm:max-w-3xl',
'4xl' => 'sm:max-w-4xl',
'full' => 'sm:max-w-full',
][$maxWidth] ?? 'sm:max-w-lg';
@endphp

<div x-show="{{ $show }}" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">

    {{-- Backdrop (Latar Belakang Hitam Transparan) --}}
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

    {{-- Panel Modal Utama --}}
    {{-- PERBAIKAN 2: Pastikan class ini menggunakan 'rounded-none sm:rounded-2xl' --}}
    <div x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-12"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-12"
        class="relative z-10 bg-white rounded-none sm:rounded-2xl w-full {{ $maxWidthClass }} h-full sm:h-auto sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl">
        {{ $slot }}
    </div>

</div>