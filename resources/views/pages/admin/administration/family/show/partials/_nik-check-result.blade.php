@php
$relationLabels = [];
foreach ($existing->relations ?? [] as $relation) {
$enumLabel = \App\Enums\Staff\FamilyRelation::tryFrom($relation->relationship)?->label() ?? $relation->relationship;
$staffName = $relation->staff?->name ?? '-';
$relationLabels[] = "{$enumLabel} dari {$staffName}";
}

// Data dari staff_data_vault + riwayat pendidikan (kalau NIK cocok dengan
// seorang staff), dipakai untuk mengisi otomatis field-field identitas.
//
// CATATAN ASUMSI: education_level_id di bawah ini diambil dari
// $matchedStaff->highestEducation->education_level_id - saya asumsikan
// tabel riwayat pendidikan staff (EducationHistory) punya kolom
// education_level_id yang merujuk ke staff_education_levels, sama seperti
// staff_family_members. Kalau nama kolomnya beda, tolong dikoreksi.
$vaultBirthDate = $matchedVault?->dob ? \Carbon\Carbon::parse($matchedVault->dob)->format('Y-m-d') : '';
$vaultEducationLevelId = $matchedStaff?->highestEducation?->education_level_id ?? '';

// Base URL untuk muat ulang MODAL PENUH dari server (bukan menyuntik
// value ke field lewat JS lagi). Kalau sedang edit relasi tertentu,
// reload ke route edit relasi itu; kalau tambah baru, reload ke route
// create. Lihat FamilyController::resolvePrefillFromRequest().
$isEditingRelation = !empty($relation);
$reloadBaseUrl = $isEditingRelation
? route('admin.personnel.family.edit', [$staff->id, $relation->id])
: route('admin.personnel.family.create', $staff->id);

$autoFillFromStaffUrl = $matchedVault
? $reloadBaseUrl . '?' . http_build_query(['prefill_from_staff_nik' => $matchedVault->nik, 'refresh' => 1])
: null;

$linkToExistingUrl = $existing
? $reloadBaseUrl . '?' . http_build_query(['link_to_family_member_id' => $existing->id, 'refresh' => 1])
: null;
@endphp

{{--
    Catatan implementasi: tombol "Isi Otomatis dari Data Staff Ini" dan
    "Tautkan" TIDAK LAGI menyuntik .value ke field lewat JavaScript.
    Sebelumnya itu gagal untuk dropdown custom (x-ui.select /
    x-ui.searchable-select) - value aslinya kesimpan benar, tapi teks yang
    ditampilkan di tombol dropdown yang tertutup tidak ikut ter-update
    karena komponennya nyimpen teks tampilan terpisah dari value.

    Sekarang kedua tombol itu pakai hx-get untuk MUAT ULANG SELURUH MODAL
    dari server (target #modal-container), sama seperti mode Edit yang
    sudah pasti benar - field terisi lewat atribut :value Blade sejak
    render pertama, bukan diubah sesudahnya. Konsekuensinya: field lain
    yang sempat diketik manual sebelum klik tombol ini akan ikut ter-reset
    ke data hasil pencarian NIK.

    Tombol "Tetap Buat Baru" TETAP pakai JS biasa (cuma mengosongkan hidden
    input link_to_family_member_id) karena itu bukan field custom dan
    tidak butuh reload apa pun.
--}}

{{-- ============ Blok 1: NIK ini ternyata NIK staff lain (cek ke staff_data_vault) ============ --}}
@if ($matchedStaff)
<div class="mt-2 rounded-xl border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800">
    <div class="flex items-start gap-2">
        <i data-lucide="triangle-alert" class="size-4 shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <p class="font-semibold">NIK ini adalah NIK milik staff: {{ $matchedStaff->name }}</p>
            <p class="mt-0.5 text-amber-700">
                Kalau orang ini memang pasangan/kerabat yang juga pegawai di sini, cek Status Tunjangan di bawah supaya tidak dibayar dobel.
            </p>

            <button type="button"
                hx-get="{{ $autoFillFromStaffUrl }}"
                hx-target="#family-modal-content"
                hx-swap="innerHTML"
                hx-indicator="#nik-autofill-spinner-{{ $matchedStaff->id }}"
                class="mt-2 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-amber-400 bg-white text-amber-700 hover:bg-amber-100 text-xs font-semibold transition-colors cursor-pointer disabled:opacity-60">
                <i data-lucide="download" class="size-3.5"></i>
                <span>Isi Otomatis dari Data Staff Ini</span>
                <i id="nik-autofill-spinner-{{ $matchedStaff->id }}" data-lucide="loader-2" class="size-3.5 animate-spin htmx-indicator"></i>
            </button>
            <p class="mt-1.5 text-amber-700/80 text-[11px]">
                Semua kolom (kecuali Pekerjaan) akan terisi otomatis dan seluruh form dimuat ulang dari server, supaya dropdown Jenis Kelamin & Pendidikan ikut ter-pilih dengan benar.
                Kolom Pekerjaan tetap dikosongkan (data pekerjaan pegawai berbeda konteks dengan pekerjaan anggota keluarga) - silakan pilih manual.
                Isian lain yang sudah sempat diketik manual akan ikut ter-reset.
            </p>
        </div>
    </div>
</div>
@endif

{{-- ============ Blok 2: NIK ini sudah pernah didaftarkan sebagai anggota keluarga (cek ke staff_family_members) ============ --}}
@if ($existing && $alreadyLinkedToThisStaff)
<div class="mt-2 rounded-xl border border-error/30 bg-error/5 p-3 text-xs text-error flex items-start gap-2">
    <i data-lucide="alert-circle" class="size-4 shrink-0 mt-0.5"></i>
    <div>
        <p class="font-semibold">NIK ini sudah terdaftar sebagai anggota keluarga staff ini juga.</p>
        <p class="mt-0.5 text-error/80">Cek kembali daftar anggota keluarga di bawah, mungkin sudah pernah ditambahkan.</p>
    </div>
</div>
@elseif ($existing)
<div x-data="{ mode: null }" class="mt-2 rounded-xl border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800">
    <div class="flex items-start gap-2">
        <i data-lucide="info" class="size-4 shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <p class="font-semibold">NIK ini sudah terdaftar sebagai:</p>
            <ul class="mt-1 list-disc list-inside space-y-0.5">
                @foreach ($relationLabels as $label)
                <li>{{ $label }}</li>
                @endforeach
            </ul>

            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                <button type="button"
                    hx-get="{{ $linkToExistingUrl }}"
                    hx-target="#family-modal-content"
                    hx-swap="innerHTML"
                    @click="mode = 'link'"
                    :class="mode === 'link' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-amber-700 border-amber-300 hover:bg-amber-100'"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border text-xs font-semibold transition-colors cursor-pointer disabled:opacity-60">
                    <i data-lucide="link" class="size-3.5"></i>
                    Tautkan (pakai data ini)
                </button>
                <button type="button"
                    @click="mode = 'new'; $el.closest('form').querySelector('[name=link_to_family_member_id]').value = ''"
                    :class="mode === 'new' ? 'bg-slate-700 text-white border-slate-700' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-100'"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border text-xs font-semibold transition-colors cursor-pointer">
                    <i data-lucide="plus" class="size-3.5"></i>
                    Tetap Buat Baru
                </button>
            </div>

            <p x-show="mode === 'link'" x-cloak class="mt-2 text-amber-700">
                Seluruh form akan dimuat ulang dengan Nama, jenis kelamin, pekerjaan, pendidikan, tanggal lahir, dll otomatis memakai data yang sudah ada.
            </p>
        </div>
    </div>
</div>
@endif

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>