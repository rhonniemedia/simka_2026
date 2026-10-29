{{-- File: resources/views/pages/admin/administration/family/show/partials/_modal-form.blade.php --}}
@php
use App\Enums\Staff\FamilyRelation as FamilyRelationEnum;
use App\Enums\Staff\Gender;
use App\Enums\Staff\Profession;

$relation = $relation ?? null;
$prefill = $prefill ?? null;
$linkToFamilyMemberId = $linkToFamilyMemberId ?? null;

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

$professionOptions = Profession::optionsForSelect();

$studyingOptions = [
['value' => '1', 'label' => 'Ya, Masih Sekolah/Kuliah'],
['value' => '0', 'label' => 'Tidak'],
];

$modalTitle = $isEdit ? 'Edit Keluarga' : 'Tambah Anggota Keluarga';
$actionUrl = $isEdit
? route('admin.personnel.family.update', [$staff->id, $relation->id])
: route('admin.personnel.family.store', $staff->id);
$checkNikUrl = route('admin.personnel.family.check-nik', $staff->id);
$method = 'hx-post';

$birthDateValue = $familyMember?->birth_date ? \Carbon\Carbon::parse($familyMember->birth_date)->format('Y-m-d') : '';
$marriageDateValue = $relation?->marriage_date_encrypted ? \Carbon\Carbon::parse($relation->marriage_date_encrypted)->format('Y-m-d') : '';

$isShared = $familyMember instanceof \App\Models\FamilyMember
&& ($familyMember->relations_count ?? $familyMember->relations()->count()) > 1;

$existingAkta = $isEdit
? $relation->documents()->with('category')
->whereHas('category', fn ($c) => $c->whereIn('code', ['akta_nikah', 'akta_kelahiran_anak']))
->latest()->first()
: null;
$documentNumberValue = $existingAkta?->document_number ?? '';
$documentDateValue = $existingAkta?->document_date?->format('Y-m-d') ?? '';

$labelClass = 'block text-sm font-medium text-foreground mb-1.5';
$errorClass = 'text-[11px] text-error mt-1.5 font-medium';
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
    hx-encoding="multipart/form-data"
    hx-target="#family-detail-container"
    hx-swap="outerHTML"
    x-data="{ 
        saving: false, 
        fileError: '', 
        errors: {},
        name: '{{ old('name', $familyMember->name ?? '') }}',
        relationship: '{{ old('relationship', $relation->relationship ?? '') }}',
        gender: '{{ old('gender', $familyMember->gender ?? '') }}',
        setErrors(xhr) {
            this.errors = {};
            if (xhr && xhr.status === 422) {
                try {
                    this.errors = JSON.parse(xhr.response).errors ?? {};
                } catch (e) {
                    this.errors = {};
                }
            }
        },
        err(field) {
            return this.errors[field]?.[0] ?? null;
        }
    }"
    @htmx:before-request="saving = true"
    @htmx:after-request="
        saving = false;
        setErrors($event.detail.xhr);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    "
    class="flex flex-col flex-1 min-h-0">
    @csrf
    @if ($isEdit)
    @method('PUT')
    @endif

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

            {{-- NIK --}}
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}">NIK / Nomor Identitas</label>
                <input type="text" name="nik"
                    value="{{ old('nik', $familyMember->nik ?? '') }}"
                    placeholder="Nomor Induk Kependudukan"
                    hx-post="{{ $checkNikUrl }}"
                    hx-trigger="keyup changed delay:600ms, blur"
                    hx-target="#nik-check-result"
                    hx-swap="innerHTML"
                    hx-include="[name='nik'], [name='ignore_relation_id']"
                    @input="$el.closest('form').querySelector('[name=link_to_family_member_id]').value = ''"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <div id="nik-check-result"></div>
                <p class="{{ $errorClass }}" x-show="err('nik')" x-cloak x-text="err('nik')"></p>
            </div>

            {{-- Nama Lengkap --}}
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}">Nama Lengkap <span class="text-error">*</span></label>
                <input type="text" name="name" x-model="name" placeholder="Nama lengkap anggota keluarga" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <p class="{{ $errorClass }}" x-show="err('name')" x-cloak x-text="err('name')"></p>
            </div>

            {{-- Status Hubungan --}}
            <div @change="relationship = $event.target.value">
                <label class="{{ $labelClass }}">Status Hubungan <span class="text-error">*</span></label>
                <x-ui.select name="relationship" :options="$relationshipOptions" :value="old('relationship', $relation->relationship ?? '')" placeholder="-- Pilih Hubungan --" />
                <p class="{{ $errorClass }}" x-show="err('relationship')" x-cloak x-text="err('relationship')"></p>
            </div>

            {{-- Jenis Kelamin --}}
            <div @change="gender = $event.target.value">
                <label class="{{ $labelClass }}">Jenis Kelamin <span class="text-error">*</span></label>
                <x-ui.select name="gender" :options="$genderOptions" :value="old('gender', $familyMember->gender ?? '')" placeholder="-- Pilih Kelamin --" />
                <p class="{{ $errorClass }}" x-show="err('gender')" x-cloak x-text="err('gender')"></p>
            </div>

            {{-- Nomor Telepon --}}
            <div>
                <label class="{{ $labelClass }}">Nomor Telepon</label>
                <input type="text" name="telephone" value="{{ old('telephone', $familyMember->telephone ?? '') }}" placeholder="Contoh: 0812..." class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <p class="{{ $errorClass }}" x-show="err('telephone')" x-cloak x-text="err('telephone')"></p>
            </div>

            {{-- Tempat Lahir --}}
            <div>
                <label class="{{ $labelClass }}">Tempat Lahir</label>
                <input type="text" name="birth_place" value="{{ old('birth_place', $familyMember->birth_place ?? '') }}" placeholder="Kota kelahiran" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <p class="{{ $errorClass }}" x-show="err('birth_place')" x-cloak x-text="err('birth_place')"></p>
            </div>

            {{-- Tanggal Lahir --}}
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}">Tanggal Lahir</label>
                <input type="date" name="birth_date" value="{{ old('birth_date', $birthDateValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <p class="{{ $errorClass }}" x-show="err('birth_date')" x-cloak x-text="err('birth_date')"></p>
            </div>

            {{-- Pendidikan Terakhir --}}
            <div>
                <label class="{{ $labelClass }}">Pendidikan Terakhir</label>
                <x-ui.searchable-select name="education_level_id" :options="$levelOptions" :value="old('education_level_id', $familyMember->education_level_id ?? '')" placeholder="-- Pilih Pendidikan --" />
                <p class="{{ $errorClass }}" x-show="err('education_level_id')" x-cloak x-text="err('education_level_id')"></p>
            </div>

            {{-- Pekerjaan --}}
            <div>
                <label class="{{ $labelClass }}">Pekerjaan</label>
                <x-ui.searchable-select name="occupation" :options="$professionOptions" :value="old('occupation', $familyMember->occupation ?? '')" placeholder="-- Pilih Pekerjaan --" />
                <p class="{{ $errorClass }}" x-show="err('occupation')" x-cloak x-text="err('occupation')"></p>
            </div>

            {{-- Informasi Tambahan --}}
            <div class="md:col-span-2 mt-2 p-4 rounded-xl border border-border bg-slate-50/50 space-y-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Informasi Tambahan</h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-foreground mb-1.5">Tanggal Pernikahan (Jika Pasangan)</label>
                        <input type="date" name="marriage_date_encrypted" value="{{ old('marriage_date_encrypted', $marriageDateValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="{{ $errorClass }}" x-show="err('marriage_date_encrypted')" x-cloak x-text="err('marriage_date_encrypted')"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-foreground mb-1.5">Masih Sekolah / Kuliah?</label>
                        <x-ui.select name="is_studying" :options="$studyingOptions" :value="old('is_studying', isset($familyMember->is_studying) ? (string)(int)$familyMember->is_studying : '')" placeholder="-- Pilih Status --" />
                        <p class="{{ $errorClass }}" x-show="err('is_studying')" x-cloak x-text="err('is_studying')"></p>
                    </div>
                </div>
            </div>

            {{-- Berkas Akta --}}
            <div class="md:col-span-2 p-4 rounded-xl border border-border bg-slate-50/50 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Berkas Akta <span class="font-normal normal-case tracking-normal">(PDF, maksimal 1 MB, opsional)</span></h4>
                <p class="text-[11px] text-secondary">Jenis berkas mengikuti Status Hubungan: Suami/Istri menjadi Akta Nikah, Anak menjadi Akta Kelahiran Anak.</p>

                @if ($existingAkta)
                <p class="text-xs text-secondary flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                    <i data-lucide="paperclip" class="size-3.5 shrink-0"></i>
                    <span>{{ $existingAkta->category?->name ?? 'Berkas' }} saat ini:</span>
                    <a href="{{ route('admin.personnel.documents.preview', $existingAkta->id) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all">{{ $existingAkta->original_filename ?? 'Lihat berkas' }}</a>
                    <span>- unggah berkas baru untuk menggantinya.</span>
                </p>
                @endif

                <div>
                    <input type="file" name="file" accept="application/pdf,.pdf" x-ref="sk"
                        @change="fileError = ''; errors.file = null; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileError = 'Format berkas harus PDF.'; $event.target.value = ''; } else if (f && f.size > 1048576) { fileError = 'Ukuran berkas maksimal 1 MB.'; $event.target.value = ''; }"
                        class="w-full rounded-xl border border-border px-3.5 py-2 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/15 cursor-pointer">
                    <p x-show="fileError" x-cloak class="{{ $errorClass }}">
                        <span x-text="fileError"></span>
                        Pilih berkas lain, atau
                        <button type="button" class="underline font-medium cursor-pointer" @click="$refs.sk.value = ''; fileError = ''">hapus pilihan</button>
                        untuk menyimpan tanpa berkas.
                    </p>
                    <p class="{{ $errorClass }}" x-show="err('file')" x-cloak x-text="err('file')"></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-foreground mb-1.5">Nomor Akta</label>
                        <input type="text" name="document_number" value="{{ old('document_number', $documentNumberValue) }}" placeholder="Wajib untuk Akta Nikah" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="{{ $errorClass }}" x-show="err('document_number')" x-cloak x-text="err('document_number')"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-foreground mb-1.5">Tanggal Akta</label>
                        <input type="date" name="document_date" value="{{ old('document_date', $documentDateValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="{{ $errorClass }}" x-show="err('document_date')" x-cloak x-text="err('document_date')"></p>
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
        <button type="submit"
            :disabled="saving || fileError !== '' || !name.trim() || !relationship || !gender"
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