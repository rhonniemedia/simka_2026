{{-- File: resources/views/pages/admin/career/retirements/partials/_modal-form.blade.php
     Dipakai untuk proses pensiun (create) dan edit data SK (kirim $history untuk mode edit). --}}
@php
$staff = $row['staff'];

// Mode: create (proses pensiun baru) atau edit (ubah data SK pensiun yang sudah diproses).
// $history hanya dikirim controller pada mode edit.
$history = $history ?? null;
$isEdit = $history !== null;

// TMT pensiun: mode edit memakai TMT yang sudah tersimpan, mode create memakai hasil perhitungan
// (tanggal 1 pada bulan setelah bulan ulang tahun batas usia).
$tmtText = $isEdit
? ($history->effective_date ? \Carbon\Carbon::parse($history->effective_date)->locale('id')->isoFormat('D MMMM Y') : '-')
: ($row['tmt']
? \Carbon\Carbon::parse($row['tmt']->format('Y-m-d'))->locale('id')->isoFormat('D MMMM Y')
: '-');

// Nilai awal field (mode edit).
$oldDecreeNumber = $isEdit ? $history->decree_number : null;
$oldDecreeDate = $isEdit && $history->decree_date ? \Carbon\Carbon::parse($history->decree_date)->format('Y-m-d') : null;
$oldNote = $isEdit ? $history->note : null;
$currentSk = $isEdit
? $history->documents->first(fn($d) => $d->category?->code === 'sk_pensiun')
: null;

$formUrl = $isEdit
? route('admin.personnel.retirement.update', $staff->id)
: route('admin.personnel.retirement.process.store', $staff->id);
$title = $isEdit ? 'Edit Data Pensiun' : 'Proses Pensiun';
$submitLabel = $isEdit ? 'Simpan Perubahan' : 'Proses Pensiun';
$loadingLabel = $isEdit ? 'Menyimpan...' : 'Memproses...';

// Gaya field: sama dengan form edit pegawai & form mutasi.
$labelClass = 'block text-sm font-medium text-foreground mb-1.5';
$inputClass = 'w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary';
$readonlyClass = 'w-full bg-muted/50 border border-border rounded-xl px-3.5 py-2.5 text-sm text-foreground cursor-default focus:outline-none';
$errorClass = 'text-[11px] text-error mt-1.5 font-medium';
@endphp

<div x-data="{
        open: false,
        saving: false,
        errors: {},
        fileError: '',
        close() {
            const container = this.$root.closest('#modal-container');
            this.open = false;
            setTimeout(() => { if (container) container.innerHTML = ''; }, 150);
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
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="close()"
    @keydown.escape.window="if (open && !saving) close()">

    <x-ui.modal show="open" maxWidth="2xl">

        {{-- ============================ HEADER ============================ --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'user-minus' }}" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $title }}</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">{{ $staff->name }}</p>
                </div>
            </div>
            <button type="button" @click="close()" title="Tutup" aria-label="Tutup"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form: status & TMT tidak dikirim dari form (create: dihitung ulang di server; edit: tidak berubah).
             Mode edit dikirim sebagai POST + _method=PUT agar multipart (berkas) terbaca Laravel.
             Status "Menyimpan..." dan pembacaan error 422 dipasang di <form> karena event htmx
             dikirim ke elemen pemilik hx-post. --}}
        <form hx-post="{{ $formUrl }}"
            hx-encoding="multipart/form-data"
            hx-swap="none"
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

            {{-- ============================ BODY ============================ --}}
            <div class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 space-y-4 [scrollbar-gutter:stable]">

                {{-- Baca-saja --}}
                <div>
                    <label class="{{ $labelClass }}">Nama</label>
                    <input type="text" value="{{ $staff->name }}" readonly tabindex="-1" class="{{ $readonlyClass }}">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Status</label>
                        <input type="text" value="Pensiun" readonly tabindex="-1" class="{{ $readonlyClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">TMT Pensiun <span class="text-error">*</span></label>
                        <input type="text" value="{{ $tmtText }}" readonly tabindex="-1" class="{{ $readonlyClass }}">
                        <p class="text-[11px] text-secondary mt-1.5">Dihitung otomatis dari batas usia.</p>
                    </div>
                </div>

                {{-- Nomor SK --}}
                <div>
                    <label class="{{ $labelClass }}">Nomor SK <span class="text-secondary font-normal">(opsional)</span></label>
                    <input type="text" name="decree_number" value="{{ $oldDecreeNumber }}"
                        placeholder="mis. 821/123/SK/2026"
                        autocomplete="off"
                        class="{{ $inputClass }}">
                    <p class="{{ $errorClass }}" x-show="err('decree_number')" x-cloak x-text="err('decree_number')"></p>
                </div>

                {{-- Tanggal SK --}}
                <div>
                    <label class="{{ $labelClass }}">Tanggal SK <span class="text-secondary font-normal">(opsional)</span></label>
                    <input type="date" name="decree_date" value="{{ $oldDecreeDate }}" class="{{ $inputClass }}">
                    <p class="{{ $errorClass }}" x-show="err('decree_date')" x-cloak x-text="err('decree_date')"></p>
                </div>

                {{-- Berkas SK --}}
                <div>
                    <label class="{{ $labelClass }}">Berkas SK Pensiun <span class="text-secondary font-normal">(PDF, maksimal 1 MB, opsional)</span></label>
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
                        @change="fileError = ''; errors.file = null; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileError = 'Format berkas harus PDF.'; } else if (f && f.size > 1048576) { fileError = 'Ukuran berkas maksimal 1 MB.'; }"
                        class="w-full rounded-xl border border-border px-3.5 py-2 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/15 cursor-pointer">
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
                        placeholder="Opsional"
                        class="{{ $inputClass }} resize-none">{{ $oldNote }}</textarea>
                    <p class="{{ $errorClass }}" x-show="err('note')" x-cloak x-text="err('note')"></p>
                </div>

                <p class="flex items-start gap-2 rounded-xl bg-amber-50 px-3.5 py-3 text-xs leading-relaxed text-amber-800">
                    <i data-lucide="info" class="size-4 shrink-0 mt-px"></i>
                    @if ($isEdit)
                    <span>Hanya data SK yang diubah. Status Pensiun dan TMT di atas tidak berubah.</span>
                    @else
                    <span>Status pegawai akan diubah menjadi Pensiun terhitung mulai TMT di atas, dan dicatat pada riwayat status pegawai.</span>
                    @endif
                </p>
            </div>

            {{-- ============================ FOOTER ============================ --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center sm:justify-end gap-2.5 sm:gap-3 shrink-0">
                <button type="button" @click="close()" :disabled="saving"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <i data-lucide="x-circle" class="size-4"></i>
                    <span>Batal</span>
                </button>
                <button type="submit" :disabled="saving || fileError !== ''"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-wait">
                    <span class="flex" x-show="!saving"><i data-lucide="check" class="size-4"></i></span>
                    <span class="flex" x-show="saving" x-cloak><i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i></span>
                    <span x-text="saving ? '{{ $loadingLabel }}' : '{{ $submitLabel }}'">{{ $submitLabel }}</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>