@php
$family = $family ?? null;
$isEdit = !empty($family);

$levelOptions = $levels->map(function($lvl) {
return ['value' => $lvl->id, 'label' => $lvl->name . ' (' . $lvl->alias . ')'];
})->toArray();

$relationshipOptions = [
['value' => 'husband', 'label' => 'Suami'],
['value' => 'wife', 'label' => 'Istri'],
['value' => 'child', 'label' => 'Anak'],
['value' => 'other', 'label' => 'Lainnya']
];

$genderOptions = [
['value' => 'L', 'label' => 'Laki-Laki'],
['value' => 'P', 'label' => 'Perempuan']
];

$payrollOptions = [
['value' => 'included', 'label' => 'Masuk Tunjangan (Included)'],
['value' => 'excluded', 'label' => 'Tidak Masuk (Excluded)']
];

$studyingOptions = [
['value' => '1', 'label' => 'Ya, Masih Sekolah/Kuliah'],
['value' => '0', 'label' => 'Tidak']
];

$modalTitle = $isEdit ? 'Edit Keluarga' : 'Tambah Anggota Keluarga';
$actionUrl = $isEdit
? route('admin.personnel.family.update', [$staff->id, $family->id])
: route('admin.personnel.family.store', $staff->id);
$method = $isEdit ? 'hx-put' : 'hx-post';

// Formatting Tanggal untuk input type="date" (Dilengkapi anti-double-serialization)
$rawBirth = $family->birth_date ?? '';
if (is_string($rawBirth) && str_starts_with($rawBirth, 's:')) {
$rawBirth = @unserialize($rawBirth) ?: $rawBirth;
}
$birthDateValue = !empty($rawBirth) ? \Carbon\Carbon::parse($rawBirth)->format('Y-m-d') : '';

$rawMarriage = $family->marriage_date_encrypted ?? '';
if (is_string($rawMarriage) && str_starts_with($rawMarriage, 's:')) {
$rawMarriage = @unserialize($rawMarriage) ?: $rawMarriage;
}
$marriageDateValue = !empty($rawMarriage) ? \Carbon\Carbon::parse($rawMarriage)->format('Y-m-d') : '';
@endphp

<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="2xl">
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

            <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                    {{-- Nama & Status Hubungan --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nama Lengkap <span class="text-error">*</span></label>
                        <input type="text" name="name" value="{{ $family->name ?? '' }}" required placeholder="Nama lengkap anggota keluarga" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Status Hubungan <span class="text-error">*</span></label>
                        <x-ui.select name="relationship" :options="$relationshipOptions" :value="$family->relationship ?? ''" required placeholder="-- Pilih Hubungan --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Kelamin <span class="text-error">*</span></label>
                        <x-ui.select name="gender" :options="$genderOptions" :value="$family->gender ?? ''" required placeholder="-- Pilih Kelamin --" />
                    </div>

                    {{-- Data Identitas & Lahir --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NIK / Nomor Identitas</label>
                        <input type="text" name="nik" value="{{ $family->nik ?? '' }}" placeholder="Nomor Induk Kependudukan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Telepon</label>
                        <input type="text" name="telephone" value="{{ $family->telephone ?? '' }}" placeholder="Contoh: 0812..." class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tempat Lahir</label>
                        <input type="text" name="birth_place_encrypted" value="{{ $family->birth_place_encrypted ?? '' }}" placeholder="Kota kelahiran" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Lahir</label>
                        <input type="date" name="birth_date" value="{{ $birthDateValue }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Pendidikan & Pekerjaan --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Pendidikan Terakhir</label>
                        <x-ui.searchable-select name="education_level_id" :options="$levelOptions" :value="$family->education_level_id ?? ''" placeholder="-- Pilih Pendidikan --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Pekerjaan</label>
                        <input type="text" name="occupation" value="{{ $family->occupation ?? '' }}" placeholder="Profesi / Pekerjaan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Informasi Administrasi & Dapodik --}}
                    <div class="md:col-span-2 mt-2 p-4 rounded-xl border border-border bg-slate-50/50 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Administrasi Pegawai & Dapodik</h4>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Kode Relasi Keluarga (HDK)</label>
                            <input type="text" name="family_relation_code" value="{{ $family->family_relation_code ?? '' }}" placeholder="Kode Dapodik" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Tanggal Pernikahan (Jika Pasangan)</label>
                            <input type="date" name="marriage_date_encrypted" value="{{ $marriageDateValue }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Status Tunjangan (Gaji)</label>
                            <x-ui.select name="payroll_status" :options="$payrollOptions" :value="$family->payroll_status ?? ''" placeholder="-- Pilih Status --" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Masih Sekolah / Kuliah?</label>
                            <x-ui.select name="is_studying" :options="$studyingOptions" :value="isset($family->is_studying) ? (string)(int)$family->is_studying : ''" placeholder="-- Pilih Status --" />
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
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>