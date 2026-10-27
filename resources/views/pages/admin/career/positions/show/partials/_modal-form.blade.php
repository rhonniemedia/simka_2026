{{-- File: resources/views/pages/admin/personnel/positions/show/partials/_modal-form.blade.php --}}
@php
$isEdit = $history->exists;
$actionUrl = $isEdit
? route('admin.personnel.positions.update', [$staff->id, $history->id])
: route('admin.personnel.positions.store', $staff->id);

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

// Berkas SK jabatan yang sudah terunggah (hanya saat edit)
$existingSk = $isEdit
? $history->documents()->whereHas('category', fn ($c) => $c->where('code', 'sk_jabatan'))->latest()->first()
: null;
@endphp

<div id="modal-container"
    x-data="{
        open: false,
        errors: {},
        fileError: '',
        hasErrors: false,
        staff_asn_position_id: '{{ old('staff_asn_position_id', $history->staff_asn_position_id) }}',
        decree_number: '{{ old('decree_number', $history->decree_number) }}',
        decree_date: '{{ old('decree_date', optional($history->decree_date)->format('Y-m-d')) }}',
        effective_date: '{{ old('effective_date', optional($history->effective_date)->format('Y-m-d')) }}',
        close() {
            this.open = false;
            setTimeout(() => document.getElementById('modal-container').innerHTML = '', 300);
        },
        setErrors(xhr) {
            if (xhr.status === 422) {
                try {
                    const body = JSON.parse(xhr.response);
                    this.errors = body.errors ?? {};
                    this.hasErrors = Object.keys(this.errors).length > 0;
                } catch (e) {
                    this.errors = {};
                    this.hasErrors = false;
                }
            } else {
                this.errors = {};
                this.hasErrors = false;
            }
        },
        err(field) {
            return this.errors[field]?.[0] ?? null;
        }
    }"
    x-init="setTimeout(() => open = true, 50)"
    @close-modal.window="close()">

    <x-ui.modal show="open" maxWidth="lg">
        <div class="flex flex-col flex-1 h-full w-full min-h-0 bg-white overflow-hidden">

            {{-- HEADER --}}
            <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-border bg-slate-50/50 shrink-0">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <div class="size-11 sm:size-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 shadow-sm">
                        <i data-lucide="briefcase-business" class="size-5 sm:size-6"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">
                            {{ $isEdit ? 'Edit Riwayat Jabatan ASN' : 'Tambah Riwayat Jabatan ASN' }}
                        </h3>
                        <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate uppercase">{{ $staff->name }}</p>
                    </div>
                </div>
                <button type="button" @click="close()"
                    class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                    <i data-lucide="x" class="size-4 pointer-events-none"></i>
                </button>
            </div>

            <form hx-post="{{ $actionUrl }}"
                hx-encoding="multipart/form-data"
                hx-target="#position-history-container" hx-select="#position-history-container" hx-swap="outerHTML"
                @htmx:after-request="
                    const xhr = $event.detail.xhr;
                    if (xhr.status === 422) {
                        setErrors(xhr);
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                ">
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="flex-1 min-h-0 p-4 sm:p-6 space-y-4 overflow-y-auto [scrollbar-gutter:stable]">

                    {{-- Jabatan ASN --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">Jabatan ASN <span class="text-error">*</span></label>
                        <div class="mt-1.5" @change="staff_asn_position_id = $event.target.value">
                            <x-ui.searchable-select
                                name="staff_asn_position_id"
                                :options="$positionSelectOptions"
                                :value="$history->staff_asn_position_id"
                                placeholder="Cari jabatan..." />
                        </div>
                        <p class="text-xs text-error font-medium mt-1" x-show="err('staff_asn_position_id')" x-cloak x-text="err('staff_asn_position_id')"></p>
                    </div>

                    {{-- Nomor SK --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">Nomor SK <span class="text-error">*</span></label>
                        <input type="text" name="decree_number" x-model="decree_number"
                            class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            placeholder="mis. 821/123/SK/2026">
                        <p class="text-xs text-error font-medium mt-1" x-show="err('decree_number')" x-cloak x-text="err('decree_number')"></p>
                    </div>

                    {{-- Tanggal SK & TMT --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold text-secondary uppercase tracking-wider">Tanggal SK <span class="text-error">*</span></label>
                            <input type="date" name="decree_date" x-model="decree_date"
                                class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <p class="text-xs text-error font-medium mt-1" x-show="err('decree_date')" x-cloak x-text="err('decree_date')"></p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-secondary uppercase tracking-wider">TMT <span class="text-error">*</span></label>
                            <input type="date" name="effective_date" x-model="effective_date"
                                class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <p class="text-xs text-error font-medium mt-1" x-show="err('effective_date')" x-cloak x-text="err('effective_date')"></p>
                        </div>
                    </div>

                    {{-- Berkas SK --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">Berkas SK <span class="text-secondary font-normal normal-case tracking-normal">(PDF, maksimal 1 MB)</span></label>
                        @if ($existingSk)
                        <p class="mt-1.5 text-xs text-secondary flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                            <i data-lucide="paperclip" class="size-3.5 shrink-0"></i>
                            <span>Berkas saat ini:</span>
                            <a href="{{ route('admin.personnel.documents.preview', $existingSk->id) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all">{{ $existingSk->original_filename ?? 'Lihat berkas' }}</a>
                            <span>- unggah berkas baru untuk menggantinya.</span>
                        </p>
                        @endif
                        <input type="file" name="file" accept="application/pdf,.pdf" x-ref="sk"
                            @change="fileError = ''; errors.file = null; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileError = 'Format berkas harus PDF.'; $event.target.value = ''; } else if (f && f.size > 1048576) { fileError = 'Ukuran berkas maksimal 1 MB.'; $event.target.value = ''; }"
                            class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/15 cursor-pointer">
                        <p x-show="fileError" x-cloak class="text-xs text-error font-medium mt-1">
                            <span x-text="fileError"></span>
                            Pilih berkas lain, atau
                            <button type="button" class="underline cursor-pointer" @click="$refs.sk.value = ''; fileError = ''">hapus pilihan</button>
                            untuk menyimpan tanpa berkas.
                        </p>
                        <p class="text-xs text-error font-medium mt-1" x-show="err('file')" x-cloak x-text="err('file')"></p>
                    </div>

                    {{-- Status aktif --}}
                    <label class="flex items-start gap-3 rounded-2xl border border-border p-4 cursor-pointer hover:bg-muted/50 transition-colors">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $history->is_active))
                        class="mt-0.5 size-4 rounded border-border text-primary focus:ring-primary/30">
                        <span>
                            <span class="block text-sm font-semibold text-foreground">Jadikan jabatan aktif saat ini</span>
                            <span class="block text-xs text-secondary mt-0.5">Riwayat aktif sebelumnya untuk pegawai ini akan otomatis dinonaktifkan.</span>
                        </span>
                    </label>
                </div>

                {{-- FOOTER --}}
                <div class="mt-auto px-4 sm:px-6 py-3.5 sm:py-4 border-t border-border bg-slate-50/50 flex items-center justify-end gap-3 shrink-0 sm:rounded-b-2xl">
                    <button type="button" @click="close()"
                        class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                        x-data="{ saving: false }"
                        @htmx:before-request="saving = true"
                        @htmx:after-request="saving = false"
                        :disabled="saving || fileError !== '' || !staff_asn_position_id || !decree_number.trim() || !decree_date || !effective_date"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all shadow-sm shadow-primary/30 disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer">
                        <i data-lucide="save" class="size-4" x-show="!saving"></i>
                        <i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin text-white" x-show="saving" x-cloak></i>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>