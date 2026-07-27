@props([
'title',
'value' => 0,
'icon' => 'activity',
'theme' => 'blue',
'valueClass' => 'text-3xl' // Tambahan properti untuk mengatur ukuran teks kustom
])

@php
// Mapping warna yang sudah diperbarui dengan slate dan pink
$themes = [
'success' => [
'hover_border' => 'hover:border-green-200',
'bg_circle' => 'bg-success',
'icon_bg' => 'bg-success/10',
'icon_color' => 'text-success',
'value_color' => 'text-foreground',
],
'purple' => [
'hover_border' => 'hover:border-purple-200',
'bg_circle' => 'bg-purple-400',
'icon_bg' => 'bg-primary/10',
'icon_color' => 'text-primary',
'value_color' => 'text-foreground',
],
'blue' => [
'hover_border' => 'hover:border-blue-200',
'bg_circle' => 'bg-blue-400',
'icon_bg' => 'bg-blue-500/10',
'icon_color' => 'text-blue-600',
'value_color' => 'text-blue-600', // Warna angka biru
],
'orange' => [
'hover_border' => 'hover:border-orange-200',
'bg_circle' => 'bg-orange-400',
'icon_bg' => 'bg-orange-500/10',
'icon_color' => 'text-orange-500',
'value_color' => 'text-foreground',
],
'pink' => [
'hover_border' => 'hover:border-pink-200',
'bg_circle' => 'bg-pink-400',
'icon_bg' => 'bg-pink-500/10',
'icon_color' => 'text-pink-600',
'value_color' => 'text-pink-600', // Warna angka pink
],
'slate' => [
'hover_border' => 'hover:border-slate-200',
'bg_circle' => 'bg-slate-300',
'icon_bg' => 'bg-slate-100',
'icon_color' => 'text-slate-600',
'value_color' => 'text-foreground',
],
'secondary' => [
'hover_border' => 'hover:border-gray-300',
'bg_circle' => 'bg-secondary',
'icon_bg' => 'bg-secondary/10',
'icon_color' => 'text-secondary',
'value_color' => 'text-foreground',
],
'teal' => [
'hover_border' => 'hover:border-teal-200',
'bg_circle' => 'bg-teal-500',
'icon_bg' => 'bg-teal-500/10',
'icon_color' => 'text-teal-600',
'value_color' => 'text-foreground',
],
'error' => [
'hover_border' => 'hover:border-error/30',
'bg_circle' => 'bg-error',
'icon_bg' => 'bg-error/10',
'icon_color' => 'text-error',
'value_color' => 'text-foreground',
],
];

$currentTheme = $themes[$theme] ?? $themes['blue'];
@endphp

<div class="relative overflow-hidden flex flex-col rounded-2xl border border-border p-5 gap-4 bg-white min-h-[130px] transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5 {{ $currentTheme['hover_border'] }} cursor-default">
    <div class="absolute -top-5 -right-5 w-20 h-20 rounded-full opacity-[0.07] {{ $currentTheme['bg_circle'] }}"></div>

    <div class="flex items-center gap-2">
        <div class="size-10 rounded-xl flex items-center justify-center shrink-0 {{ $currentTheme['icon_bg'] }}">
            <i data-lucide="{{ $icon }}" class="size-5 {{ $currentTheme['icon_color'] }}"></i>
        </div>
        <p class="font-medium text-xs text-secondary">{{ $title }}</p>
    </div>

    <div class="border-t border-dashed border-border pt-3">
        {{-- Implementasi valueClass dan value_color agar dinamis --}}
        <p class="font-bold {{ $valueClass }} {{ $currentTheme['value_color'] }}">
            {{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}
        </p>
    </div>
</div>