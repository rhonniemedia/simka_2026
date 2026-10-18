@php
use App\Enums\Staff\FamilyRelation as FamilyRelationEnum;
use App\Enums\Staff\Gender;
use App\Enums\Staff\Profession;

$relation = $relation ?? null;
$prefill = $prefill ?? null;
$linkToFamilyMemberId = $linkToFamilyMemberId ?? null;

// Prioritas data yang ditampilkan: data prefill (hasil klik
// "Tautkan"/"Isi Otomatis", termasuk saat sedang Edit) lebih diutamakan
// daripada familyMember tersimpan, supaya tombol itu tetap kelihatan
// efeknya walau modal ini dibuka dalam mode Edit. Karena $prefill datang
// dari server (bukan disuntik JS), semua komponen select custom
// (x-ui.select, x-ui.searchable-select) otomatis tampil benar lewat
// atribut :value di bawah - sama seperti mode Edit biasa.
$familyMember = $prefill ?? $relation->familyMember ?? null;
$isEdit = !empty($relation);

$levelOptions = \App\Models\EducationLevel::orderByRaw('CAST(level AS UNSIGNED) ASC')->get()->map(function ($lvl) {
return ['value' => $lvl->id, 'label' => $lvl->name . ' (' . $lvl->alias . ')'];
})->toArray();

$relationshipOptions = array_map(
fn (FamilyRelationEnum $r) => ['value' => $r->value, 'label' => $r->label()],
FamilyRelationEnum::cases()
);

$genderOptions = array_map(
fn (Gender $g) => ['value' => $g->value, 'label' => $g->label()],
Gender::cases()
);

$professionOptions = array_map(
fn (Profession $p) => ['value' => $p->value, 'label' => $p->label()],
Profession::cases()
);

$studyingOptions = [
['value' => '1', 'label' => 'Ya, Masih Sekolah/Kuliah'],
['value' => '0', 'label' => 'Tidak'],
];

$modalTitle = $isEdit ? 'Edit Keluarga' : 'Tambah Anggota Keluarga';
$actionUrl = $isEdit
? route('admin.personnel.family.update', [$staff->id, $relation->id])
: route('admin.personnel.family.store', $staff->id);
$checkNikUrl = route('admin.personnel.family.check-nik', $staff->id);
$method = $isEdit ? 'hx-put' : 'hx-post';

$birthDateValue = $familyMember?->birth_date ? \Carbon\Carbon::parse($familyMember->birth_date)->format('Y-m-d') : '';
$marriageDateValue = $relation?->marriage_date_encrypted ? \Carbon\Carbon::parse($relation->marriage_date_encrypted)->format('Y-m-d') : '';

// Kalau sedang edit/tautkan dan orang ini ditautkan lebih dari 1 staff,
// tampilkan peringatan. Hanya berlaku untuk FamilyMember yang benar-benar
// tersimpan - prefill dari "Isi Otomatis dari Data Staff Ini" berupa
// stdClass biasa (bukan model), jadi tidak punya relasi apa pun.
$isShared = $familyMember instanceof \App\Models\FamilyMember
&& ($familyMember->relations_count ?? $familyMember->relations()->count()) > 1;
@endphp

{{-- Modal Header --}}
<div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
        <div class="size-10 sm:size-12 rounded-full {{ $isEdit ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center shrink-0 shadow-sm">
            <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'users' }}" class="size-5 sm:size-6"></i>
        </div>
        <div class="min-w-0">
            <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $modalTitle }}</h3>
            <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                Keluarga {{ $staff->name }}
            </p>
        </div>
    </div>
    <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
        class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
        <i data-lucide="x" class="size-4 pointer-events-none"></i>
    </button>
</div>

{{-- Form HTMX --}}
<form id="family-form"
    {!! $method !!}="{{ $actionUrl }}"
    hx-target="#family-detail-container"
    hx-swap="outerHTML"
    x-data="{ saving: false }"
    @htmx:before-request="saving = true"
    @htmx:after-request="saving = false"
    class="flex flex-col flex-1 min-h-0">
    @csrf

    {{-- Dipakai controller untuk menentukan: tautkan ke orang yang
        sudah ada, atau buat data orang baru. Diisi dari server
        (query string ?link_to_family_member_id=...) saat konten ini
        di-reload lewat tombol "Tautkan" - lihat
        _nik-check-result.blade.php & FamilyController::resolvePrefillFromRequest(). --}}
    <input type="hidden" name="link_to_family_member_id" value="{{ $linkToFamilyMemberId }}">

    @if ($isEdit)
    <input type="hidden" name="ignore_relation_id" value="{{ $relation->id }}">
    @endif

    <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">

        @if ($isShared)
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800 flex items-start gap-2">
            <i data-lucide="users" class="size-4 shrink-0 mt-0.5"></i>
            <div>
                <p class="font-semibold">Data orang ini dipakai bersama {{ $familyMember->relations_count ?? $familyMember->relations()->count() }} staff lain.</p>
                <p class="mt-0.5 text-amber-700">Mengubah Nama/NIK/Tanggal Lahir/dll di sini akan ikut berubah untuk staff lain yang menautkan orang yang sama. Kolom Status Hubungan &amp; Status Tunjangan di bawah hanya berlaku untuk staff ini.</p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
            :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

            {{-- NIK - kolom inti, ditaruh paling atas & 1 baris penuh
                karena semua pengecekan (tautkan/deteksi staff ganda)
                dimulai dari sini. --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-foreground mb-1.5">NIK / Nomor Identitas</label>
                <input type="text" name="nik"
                    value="{{ $familyMember->nik ?? '' }}"
                    placeholder="Nomor Induk Kependudukan"
                    hx-post="{{ $checkNikUrl }}"
                    hx-trigger="keyup changed delay:600ms, blur"
                    hx-target="#nik-check-result"
                    hx-swap="innerHTML"
                    hx-include="[name='nik'], [name='ignore_relation_id']"
                    @input="$el.closest('form').querySelector('[name=link_to_family_member_id]').value = ''"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <div id="nik-check-result"></div>
            </div>

            {{-- Nama & Status Hubungan --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-foreground mb-1.5">Nama Lengkap <span class="text-error">*</span></label>
                <input type="text" name="name" value="{{ $familyMember->name ?? '' }}" required placeholder="Nama lengkap anggota keluarga" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Status Hubungan <span class="text-error">*</span></label>
                <x-ui.select name="relationship" :options="$relationshipOptions" :value="$relation->relationship ?? ''" required placeholder="-- Pilih Hubungan --" />
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Kelamin <span class="text-error">*</span></label>
                <x-ui.select name="gender" :options="$genderOptions" :value="$familyMember->gender ?? ''" required placeholder="-- Pilih Kelamin --" />
            </div>

            {{-- Data Kontak & Lahir --}}
            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Telepon</label>
                <input type="text" name="telephone" value="{{ $familyMember->telephone ?? '' }}" placeholder="Contoh: 0812..." class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Tempat Lahir</label>
                <input type="text" name="birth_place" value="{{ $familyMember->birth_place ?? '' }}" placeholder="Kota kelahiran" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Lahir</label>
                <input type="date" name="birth_date" value="{{ $birthDateValue }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            {{-- Pendidikan & Pekerjaan --}}
            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Pendidikan Terakhir</label>
                <x-ui.searchable-select name="education_level_id" :options="$levelOptions" :value="$familyMember->education_level_id ?? ''" placeholder="-- Pilih Pendidikan --" />
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Pekerjaan</label>
                <x-ui.searchable-select name="occupation" :options="$professionOptions" :value="$familyMember->occupation ?? ''" placeholder="-- Pilih Pekerjaan --" />
            </div>

            {{-- Informasi Tambahan --}}
            <div class="md:col-span-2 mt-2 p-4 rounded-xl border border-border bg-slate-50/50 space-y-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Informasi Tambahan</h4>

                {{-- Status Tunjangan (Gaji) sengaja DIHAPUS dari sini -
                    perhitungan masuk/tidaknya ke daftar gaji pakai kriteria
                    tersendiri di modul lain, bukan diisi manual di form ini. --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-foreground mb-1.5">Tanggal Pernikahan (Jika Pasangan)</label>
                        <input type="date" name="marriage_date_encrypted" value="{{ $marriageDateValue }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-foreground mb-1.5">Masih Sekolah / Kuliah?</label>
                        <x-ui.select name="is_studying" :options="$studyingOptions" :value="isset($familyMember->is_studying) ? (string)(int)$familyMember->is_studying : ''" placeholder="-- Pilih Status --" />
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Footer Modal --}}
    <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 sm:gap-2 shrink-0">
        <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
            class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
            <i data-lucide="x-circle" class="size-4"></i>
            <span>Batal</span>
        </button>
        <button type="submit" :disabled="saving"
            class="w-full sm:w-auto flex items-center justify-center min-w-[140px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
            <div x-show="!saving" class="flex items-center gap-1.5">
                <i data-lucide="save" class="size-4"></i>
                <span>Simpan Data</span>
            </div>
            <div x-show="saving" x-cloak class="flex items-center gap-1.5">
                <i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i>
                <span>Menyimpan...</span>
            </div>
        </button>
    </div>
</form>

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>