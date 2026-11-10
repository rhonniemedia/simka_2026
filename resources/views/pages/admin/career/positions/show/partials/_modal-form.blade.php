{{-- File: resources/views/pages/admin/personnel/positions/show/partials/_modal-form.blade.php --}}
@php
$history = $history ?? null;
$isEdit = !empty($history) && $history->exists;
$modalTitle = $isEdit ? 'Edit Riwayat Jabatan ASN' : 'Tambah Riwayat Jabatan ASN';
$actionUrl = $isEdit
? route('admin.personnel.positions.update', [$staff->id, $history->id])
: route('admin.personnel.positions.store', $staff->id);
$method = 'hx-post';

// Opsi untuk searchable-select, dikelompokkan lewat key 'group'.
// Urut per jenis jabatan, lalu nama.
$positionTypeLabels = [
'fungsional_keahlian' => 'Fungsional Keahlian',
'fungsional_keterampilan' => 'Fungsional Keterampilan',
'pelaksana' => 'Pelaksana',
];
$positionSelectOptions = [];
foreach ($positionTypeLabels as $type => $typeLabel) {
foreach ($positionOptions->where('position_type', $type)->sortBy('name') as $p) {
$positionSelectOptions[] = [
'value' => (string) $p->id,
'label' => $p->name,
'group' => $typeLabel,
];
}
}

$positionIdValue = old('staff_asn_position_id', $history->staff_asn_position_id ?? '');
$decreeNumberValue = old('decree_number', $history->decree_number ?? '');
$decreeDateValue = old('decree_date', !empty($history->decree_date) ? \Carbon\Carbon::parse($history->decree_date)->format('Y-m-d') : '');
$effectiveDateValue = old('effective_date', !empty($history->effective_date) ? \Carbon\Carbon::parse($history->effective_date)->format('Y-m-d') : '');
$isActiveValue = old('is_active', $history->is_active ?? true);

// Berkas SK jabatan yang sudah terunggah (hanya saat edit)
$existingSk = $isEdit
? $history->documents()->whereHas('category', fn ($c) => $c->where('code', 'sk_jabatan'))->latest()->first()
: null;

// Opsi status dokumen (sama dengan form di menu Dokumen)
$verificationOptions = [
['value' => 'draft', 'label' => 'Draft'],
['value' => 'verified', 'label' => 'Terverifikasi'],
['value' => 'rejected', 'label' => 'Ditolak'],
];
@endphp

<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="xl">
        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full {{ $isEdit ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'briefcase-business' }}" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $modalTitle }}</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate uppercase">
                        {{ $staff->name }}
                    </p>
                </div>
            </div>

            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="position-history-form"
            {!! $method !!}="{{ $actionUrl }}"
            hx-encoding="multipart/form-data"
            hx-target="#position-history-container"
            hx-select="#position-history-container"
            hx-swap="outerHTML"
            novalidate
            x-data="{ saving: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @if ($isEdit)
            @method('PUT')
            @endif

            <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                    {{-- Jabatan ASN --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jabatan ASN <span class="text-error">*</span></label>
                        <x-ui.searchable-select
                            name="staff_asn_position_id"
                            :options="$positionSelectOptions"
                            :value="$positionIdValue"
                            placeholder="Cari jabatan..." />
                        @error('staff_asn_position_id')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nomor SK --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nomor SK <span class="text-error">*</span></label>
                        <input type="text" name="decree_number" value="{{ $decreeNumberValue }}"
                            placeholder="mis. 821/123/SK/2026"
                            class="w-full rounded-xl border {{ $errors->has('decree_number') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('decree_number')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tanggal SK --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal SK <span class="text-error">*</span></label>
                        <input type="date" name="decree_date" value="{{ $decreeDateValue }}"
                            class="w-full rounded-xl border {{ $errors->has('decree_date') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('decree_date')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- TMT (Effective Date) --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">TMT <span class="text-error">*</span></label>
                        <input type="date" name="effective_date" value="{{ $effectiveDateValue }}"
                            class="w-full rounded-xl border {{ $errors->has('effective_date') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('effective_date')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Berkas SK & Status Dokumen --}}
                    <div class="md:col-span-2" x-data="{ fileError: '', hasFile: false, hasSk: {{ $existingSk ? 'true' : 'false' }} }">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Berkas SK <span class="text-secondary font-normal">(PDF, maksimal 1 MB)</span></label>
                        @if ($existingSk)
                        <p class="text-xs text-secondary mb-2 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                            <i data-lucide="paperclip" class="size-3.5 shrink-0"></i>
                            <span>Berkas saat ini:</span>
                            <a href="{{ route('admin.personnel.documents.preview', $existingSk->id) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all">{{ $existingSk->original_filename ?? 'Lihat berkas' }}</a>
                            <span>- unggah berkas baru untuk menggantinya.</span>
                        </p>
                        @endif
                        <input type="file" name="file" accept="application/pdf,.pdf"
                            @change="fileError = ''; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileError = 'Format berkas harus PDF.'; $event.target.value = ''; } else if (f && f.size > 1048576) { fileError = 'Ukuran berkas maksimal 1 MB.'; $event.target.value = ''; } hasFile = $event.target.files.length > 0;"
                            class="w-full rounded-xl border {{ $errors->has('file') ? 'border-error' : 'border-border' }} px-3.5 py-2 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/15 cursor-pointer">
                        <p x-show="fileError" x-cloak x-text="fileError" class="text-xs text-error mt-1"></p>
                        @error('file')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror

                        <div class="mt-3">
                            <label class="block text-sm font-medium text-foreground mb-1.5">Status Dokumen</label>
                            <fieldset :disabled="!hasFile && !hasSk" class="min-w-0 border-0 p-0 m-0 disabled:opacity-60">
                                <x-ui.select name="verification_status" :options="$verificationOptions" :value="$existingSk?->verification_status ?? 'draft'" placeholder="-- Pilih Status --" />
                            </fieldset>
                            <p class="text-[11px] text-secondary mt-1" x-show="!hasFile && !hasSk" x-cloak>Pilih berkas SK terlebih dahulu; status disimpan bersama berkas.</p>
                            @error('verification_status')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Status Aktif --}}
                    <div class="md:col-span-2">
                        <label class="flex items-start gap-3 rounded-2xl border border-border p-4 cursor-pointer hover:bg-muted/50 transition-colors">
                            <input type="checkbox" name="is_active" value="1" @checked($isActiveValue)
                                class="mt-0.5 size-4 rounded border-border text-primary focus:ring-primary/30">
                            <span>
                                <span class="block text-sm font-semibold text-foreground">Jadikan jabatan aktif saat ini</span>
                                <span class="block text-xs text-secondary mt-0.5">Riwayat aktif sebelumnya untuk pegawai ini akan otomatis dinonaktifkan.</span>
                            </span>
                        </label>
                        @error('is_active')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 sm:gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    <i data-lucide="undo-2" class="size-4"></i>
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
                        <span>Memproses...</span>
                    </div>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>