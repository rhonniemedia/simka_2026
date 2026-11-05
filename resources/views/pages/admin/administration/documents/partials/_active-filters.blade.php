{{--
    Ringkasan filter yang sedang aktif. Dirender server-side di dalam
    #documents-container, jadi selalu sama dengan hasil query (termasuk setelah
    refresh / tombol back / URL dibagikan).
--}}
@if (($activeFilterCount ?? 0) > 0)
@php
$verificationLabels = ['draft' => 'Draft', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'];
$linkLabels = ['linked' => 'Bertaut', 'unlinked' => 'Belum bertaut', 'standalone' => 'Mandiri'];
$fmtDate = fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y');

$chips = [];

if (filled($filterCategory ?? null)) {
$chips[] = ['Kategori', data_get($categoryOptions ?? [], $filterCategory, '-')];
}
if (filled($filterVerification ?? null)) {
$chips[] = ['Status', $verificationLabels[$filterVerification] ?? $filterVerification];
}
if (filled($filterLink ?? null)) {
$chips[] = ['Tautan', $linkLabels[$filterLink] ?? $filterLink];
}
if (($viewMode ?? 'all') === 'staff' && ($filterPresence ?? null) === 'missing') {
$chips[] = ['Pegawai', 'Belum punya dokumen sesuai filter'];
}
if (filled($filterDateFrom ?? null) || filled($filterDateTo ?? null)) {
$range = filled($filterDateFrom ?? null) && filled($filterDateTo ?? null)
? $fmtDate($filterDateFrom) . ' - ' . $fmtDate($filterDateTo)
: (filled($filterDateFrom ?? null) ? 'Mulai ' . $fmtDate($filterDateFrom) : 'Sampai ' . $fmtDate($filterDateTo));
$chips[] = ['Tanggal', $range];
}

// Hapus semua filter (tanpa menyentuh pencarian, view, back). Tautan biasa => halaman dimuat
// ulang dan isi modal filter ikut kembali bersih dari server.
$clearUrl = request()->fullUrlWithoutQuery([
'filter_category', 'filter_verification', 'filter_link', 'filter_date_from', 'filter_date_to', 'filter_presence', 'page',
]);
@endphp

<div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
    <span class="font-semibold text-secondary">Filter aktif:</span>

    @foreach ($chips as [$chipLabel, $chipValue])
    <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 font-medium text-primary">
        <span class="text-primary/70">{{ $chipLabel }}:</span>
        <span>{{ $chipValue }}</span>
    </span>
    @endforeach

    <a href="{{ $clearUrl }}"
        class="inline-flex items-center gap-1 rounded-full border border-border bg-white px-2.5 py-1 font-semibold text-secondary transition-colors hover:bg-muted hover:text-error">
        <i data-lucide="x" class="size-3"></i>
        Hapus filter
    </a>
</div>

@if (($viewMode ?? 'all') === 'staff')
<p class="mb-4 -mt-2 text-[11px] text-secondary">
    @if (($filterPresence ?? null) === 'missing')
    {{ $activeFilterCount > 1
        ? 'Menampilkan pegawai yang belum memiliki satu pun dokumen sesuai filter. "Total berkas" adalah seluruh dokumen pegawai tersebut.'
        : 'Menampilkan pegawai yang belum memiliki dokumen sama sekali.' }}
    @else
    Hanya pegawai yang memiliki dokumen sesuai filter yang ditampilkan. Angka pada tiap pegawai adalah jumlah dokumen yang cocok, bukan total berkasnya.
    @endif
</p>
@endif
@endif