{{-- File: resources/views/pages/admin/career/transfers/partials/_form-modal.blade.php
     Dipakai untuk Mutasi, Reaktivasi (create) dan Edit data SK (kirim $staff + $history untuk mode edit). --}}
@php
// Mode edit: controller mengirim $staff & $history (riwayat status terakhir pegawai).
$history = $history ?? null;
$staff = $staff ?? null;
$isEdit = $history !== null && $staff !== null;
$isReaktivasi = !$isEdit && ($mode ?? null) === 'reaktivasi';

$modalTitle = $isEdit ? 'Edit Data Mutasi' : ($isReaktivasi ? 'Aktifkan Kembali Pegawai' : 'Mutasi Pegawai');
$modalDesc = $isEdit
? $staff->name
: ($isReaktivasi ? 'Pulihkan status kepegawaian pegawai' : 'Ubah status kepegawaian pegawai');

if ($isEdit) {
$formUrl = route('admin.personnel.mutation.update', $staff->id);
$editStatusLabel = \App\Http\Controllers\Admin\Career\TransferController::STATUS_LABELS[$history->to_status] ?? $history->to_status;
$currentSk = $history->documents->first();
$fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '';
} else {
$formUrl = route('admin.personnel.mutation.store');
$currentSk = null;
}

// x-ui.select / x-ui.searchable-select membutuhkan daftar [['value' => ..., 'label' => ...]].
$staffSelectOptions = collect($staffOptions ?? [])
->map(fn($name, $id) => ['value' => (string) $id, 'label' => (string) $name])
->values()
->all();

$statusSelectOptions = collect($statusOptions ?? [])
->map(fn($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
->values()
->all();

// Gaya field: sama dengan form edit pegawai.
$labelClass = 'block text-sm font-medium text-foreground mb-1.5';
$inputClass = 'w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary';
$errorClass = 'text-[11px] text-error mt-1.5 font-medium';
@endphp

<div id="modal-container"
    x-data="{
        open: false,
        saving: false,
        errors: {},
        fileError: '',
        staff_id: '{{ $isEdit ? $staff->id : old('staff_id') }}',
        to_status: '{{ $isEdit ? $history->to_status : old('to_status') }}',
        decree_number: @js($isEdit ? (string) $history->decree_number : (string) old('decree_number')),
        decree_date: '{{ $isEdit ? $fmtDate($history->decree_date) : old('decree_date') }}',
        effective_date: '{{ $isEdit ? $fmtDate($history->effective_date) : old('effective_date') }}',
        isReaktivasi: {{ $isReaktivasi ? 'true' : 'false' }},
        close() {
            this.open = false;
            setTimeout(() => document.getElementById('modal-container').innerHTML = '', 300);
        },
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
    x-init="setTimeout(() => open = true, 50)"
    @close-modal.window="close()"
    @keydown.escape.window="if (open && !saving) close()">

    <x-ui.modal show="open" maxWidth="lg">

        {{-- ============================ HEADER ============================ --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : ($isReaktivasi ? 'rotate-ccw' : 'arrow-right-left') }}" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $modalTitle }}</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">{{ $modalDesc }}</p>
                </div>
            </div>
            <button type="button" @click="close()" title="Tutup" aria-label="Tutup"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form: status "Menyimpan..." dan pembacaan error 422 dipasang di <form> --}}
        <form hx-post="{{ $formUrl }}"
            hx-encoding="multipart/form-data"
            hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML"
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
            @else
            <input type="hidden" name="mode" value="{{ $mode }}">
            @endif

            {{-- ============================ BODY ============================ --}}
            <div class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 space-y-4 [scrollbar-gutter:stable]">

                @if ($isEdit)
                {{-- Mode edit: pegawai & status tujuan tidak dapat diubah --}}
                <div>
                    <label class="{{ $labelClass }}">Pegawai</label>
                    <input type="text" value="{{ $staff->name }}" readonly tabindex="-1"
                        class="w-full bg-muted/50 border border-border rounded-xl px-3.5 py-2.5 text-sm text-foreground cursor-default focus:outline-none">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Status Tujuan</label>
                    <input type="text" value="{{ $editStatusLabel }}" readonly tabindex="-1"
                        class="w-full bg-muted/50 border border-border rounded-xl px-3.5 py-2.5 text-sm text-foreground cursor-default focus:outline-none">
                </div>
                @else
                {{-- Pegawai (dapat dicari) --}}
                <div @change="staff_id = $event.target.value">
                    <label class="{{ $labelClass }}">Pegawai <span class="text-error">*</span></label>
                    <x-ui.searchable-select
                        name="staff_id"
                        :options="$staffSelectOptions"
                        placeholder="-- Pilih Pegawai --" />
                    @if (empty($staffSelectOptions))
                    <p class="text-[11px] text-secondary mt-1.5">
                        {{ $isReaktivasi ? 'Tidak ada pegawai berstatus Pindah/Mengundurkan Diri.' : 'Tidak ada pegawai aktif.' }}
                    </p>
                    @endif
                    <p class="{{ $errorClass }}" x-show="err('staff_id')" x-cloak x-text="err('staff_id')"></p>
                </div>

                {{-- Status tujuan (khusus mode Mutasi) --}}
                @if (!$isReaktivasi)
                <div @change="to_status = $event.target.value">
                    <label class="{{ $labelClass }}">Status Tujuan <span class="text-error">*</span></label>
                    <x-ui.select
                        name="to_status"
                        :options="$statusSelectOptions"
                        placeholder="-- Pilih Status --" />
                    <p class="{{ $errorClass }}" x-show="err('to_status')" x-cloak x-text="err('to_status')"></p>
                </div>
                @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="size-4 text-emerald-600 shrink-0"></i>
                    <p class="text-xs font-medium text-emerald-800">Status tujuan: <strong>Aktif</strong></p>
                </div>
                @endif
                @endif

                {{-- Nomor SK --}}
                <div>
                    <label class="{{ $labelClass }}">Nomor SK <span class="text-error">*</span></label>
                    <input type="text" name="decree_number" x-model="decree_number"
                        placeholder="mis. 821/123/SK/2026"
                        class="{{ $inputClass }}">
                    <p class="{{ $errorClass }}" x-show="err('decree_number')" x-cloak x-text="err('decree_number')"></p>
                </div>

                {{-- Tanggal SK & TMT --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Tanggal SK <span class="text-error">*</span></label>
                        <input type="date" name="decree_date" x-model="decree_date"
                            class="{{ $inputClass }}">
                        <p class="{{ $errorClass }}" x-show="err('decree_date')" x-cloak x-text="err('decree_date')"></p>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">TMT SK (Tanggal Efektif) <span class="text-error">*</span></label>
                        <input type="date" name="effective_date" x-model="effective_date"
                            placeholder="YYYY-MM-DD"
                            class="{{ $inputClass }}">
                        <p class="{{ $errorClass }}" x-show="err('effective_date')" x-cloak x-text="err('effective_date')"></p>
                    </div>
                </div>

                {{-- Berkas SK --}}
                <div>
                    <label class="{{ $labelClass }}">Berkas SK <span class="text-secondary font-normal">(PDF, maksimal 1 MB, opsional)</span></label>
                    @if ($isEdit)
                    @if ($currentSk)
                    <p class="mb-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-secondary">
                        <a href="{{ route('admin.personnel.documents.preview', $currentSk->id) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 font-medium text-blue-600 hover:underline">
                            <i data-lucide="file-text" class="size-3.5"></i> Lihat berkas saat ini
                        </a>
                        <span>· Pilih berkas baru untuk menggantinya.</span>
                    </p>
                    @else
                    <p class="mb-2 flex items-center gap-1 text-xs text-amber-600">
                        <i data-lucide="file-x" class="size-3.5"></i> SK belum diunggah.
                    </p>
                    @endif
                    @endif
                    <input type="file" name="file" accept="application/pdf,.pdf" x-ref="sk"
                        @change="fileError = ''; errors.file = null; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileError = 'Format berkas harus PDF.'; $event.target.value = ''; } else if (f && f.size > 1048576) { fileError = 'Ukuran berkas maksimal 1 MB.'; $event.target.value = ''; }"
                        class="w-full rounded-xl border border-border px-3.5 py-2 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/15 cursor-pointer">
                    <p class="text-[11px] text-secondary mt-1.5">
                        @if ($isReaktivasi)
                        Jenis berkas: SK Reaktivasi.
                        @else
                        Jenis berkas mengikuti Status Tujuan: Pindah = SK Mutasi, Mengundurkan Diri = SK Pengunduran Diri, Diberhentikan = SK Pemberhentian, Meninggal Dunia = Surat Kematian.
                        @endif
                    </p>
                    <p x-show="fileError" x-cloak class="{{ $errorClass }}">
                        <span x-text="fileError"></span>
                        Pilih berkas lain, atau
                        <button type="button" class="underline cursor-pointer" @click="$refs.sk.value = ''; fileError = ''">hapus pilihan</button>
                        {{ $isEdit ? 'untuk mempertahankan berkas saat ini.' : 'untuk menyimpan tanpa berkas.' }}
                    </p>
                    <p class="{{ $errorClass }}" x-show="err('file')" x-cloak x-text="err('file')"></p>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="{{ $labelClass }}">Catatan</label>
                    <textarea name="note" rows="3"
                        placeholder="Opsional, mis. instansi tujuan untuk kasus pindah"
                        class="{{ $inputClass }} resize-none">{{ old('note', $isEdit ? $history->note : null) }}</textarea>
                    <p class="{{ $errorClass }}" x-show="err('note')" x-cloak x-text="err('note')"></p>
                </div>
            </div>

            {{-- ============================ FOOTER ============================ --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center sm:justify-end gap-2.5 sm:gap-3 shrink-0">
                <button type="button" @click="close()" :disabled="saving"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <i data-lucide="x-circle" class="size-4"></i>
                    <span>Batal</span>
                </button>
                <button type="submit"
                    :disabled="saving || fileError !== '' || !staff_id || (!isReaktivasi && !to_status) || !decree_number.trim() || !decree_date || !effective_date"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                    <span class="flex" x-show="!saving"><i data-lucide="save" class="size-4"></i></span>
                    <span class="flex" x-show="saving" x-cloak><i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i></span>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan'">Simpan</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>