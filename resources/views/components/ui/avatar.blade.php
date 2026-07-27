@props([
'name' => 'User',
'gender' => null,
'index' => null
])

@php
// 1. Ekstrak nilai string jika $gender adalah sebuah objek Enum
$genderValue = $gender instanceof \BackedEnum ? $gender->value : $gender;

// 2. Ekstrak 2 huruf pertama untuk inisial
$initials = strtoupper(substr(trim($name ?? 'U'), 0, 2));

// 3. Daftar variasi warna gradien
$colors = [
'linear-gradient(135deg, #6F1D1B, #D62828)',
'linear-gradient(135deg, #432818, #99582A)',
'linear-gradient(135deg, #99582A, #BB9457)',
'linear-gradient(135deg, #BB9457, #FFE6A7)',
'linear-gradient(135deg, #D62828, #BB9457)'
];

// 4. Menentukan urutan warna
$colorIndex = $index !== null ? ($index % 5) : (crc32($name) % 5);
$color = $colors[$colorIndex];

// 5. Penyesuaian Kontras Teks
$textColorClass = $colorIndex === 3 ? 'text-gray-900' : 'text-white';

// 6. Konfigurasi Lencana Gender (Gunakan $genderValue yang sudah diekstrak)
$isMale = $genderValue === 'L';
$isFemale = $genderValue === 'P';
$genderTitle = $isMale ? 'Laki-laki' : ($isFemale ? 'Perempuan' : 'Tidak diketahui');
$badgeBg = $isMale ? 'bg-blue-500' : 'bg-pink-500';
$badgeIcon = $isMale ? 'mars' : 'venus';
@endphp

<div class="relative shrink-0">
    {{-- Lingkaran Avatar --}}
    <div @style(["background: {$color}"])
        class="h-10 w-10 rounded-full flex items-center justify-center {{ $textColorClass }} text-sm font-bold shadow-sm">
        {{ $initials }}
    </div>

    {{-- Lencana Gender (Gunakan $genderValue untuk validasi) --}}
    @if(in_array($genderValue, ['L', 'P']))
    <span title="{{ $genderTitle }}"
        class="absolute -bottom-0.5 -right-0.5 flex items-center justify-center size-4 rounded-full border-2 border-white {{ $badgeBg }}">
        <i data-lucide="{{ $badgeIcon }}" class="size-2.5 text-white pointer-events-none"></i>
    </span>
    @endif
</div>