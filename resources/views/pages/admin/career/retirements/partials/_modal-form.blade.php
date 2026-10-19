{{-- File: resources/views/pages/admin/personnel/retirements/partials/_modal-form.blade.php --}}
@php
$staff = $row['staff'];

// TMT pensiun hasil perhitungan (tanggal 1 pada bulan setelah bulan ulang tahun batas usia).
$tmtText = $row['tmt']
? \Carbon\Carbon::parse($row['tmt']->format('Y-m-d'))->locale('id')->isoFormat('D MMMM Y')
: '-';

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

    <x-ui.modal show="open" maxWidth="md">

        {{-- ============================ HEADER ============================ --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="user-minus" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">Proses Pensiun</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">{{ $staff->name }}</p>
                </div>
            </div>
            <button type="button" @click="close()" title="Tutup" aria-label="Tutup"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form: status & TMT dihitung ulang di server, jadi yang dikirim hanya Nomor SK dan Catatan.
             Status "Menyimpan..." dan pembacaan error 422 dipasang di <form> karena event htmx
             dikirim ke elemen pemilik hx-post. --}}
        <form hx-post="{{ route('admin.personnel.retirement.process.store', $staff->id) }}"
            hx-swap="none"
            @htmx:before-request="saving = true"
            @htmx:after-request="
                saving = false;
                setErrors($event.detail.xhr);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            "
            class="flex flex-col flex-1 min-h-0">
            @csrf

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
                        <label class="{{ $labelClass }}">TMT Pensiun</label>
                        <input type="text" value="{{ $tmtText }}" readonly tabindex="-1" class="{{ $readonlyClass }}">
                    </div>
                </div>

                {{-- Nomor SK --}}
                <div>
                    <label class="{{ $labelClass }}">Nomor SK <span class="text-error">*</span></label>
                    <input type="text" name="decree_number"
                        placeholder="mis. 821/123/SK/2026"
                        autocomplete="off"
                        class="{{ $inputClass }}">
                    <p class="{{ $errorClass }}" x-show="err('decree_number')" x-cloak x-text="err('decree_number')"></p>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="{{ $labelClass }}">Catatan</label>
                    <textarea name="note" rows="3"
                        placeholder="Opsional"
                        class="{{ $inputClass }} resize-none"></textarea>
                    <p class="{{ $errorClass }}" x-show="err('note')" x-cloak x-text="err('note')"></p>
                </div>

                <p class="flex items-start gap-2 rounded-xl bg-amber-50 px-3.5 py-3 text-xs leading-relaxed text-amber-800">
                    <i data-lucide="info" class="size-4 shrink-0 mt-px"></i>
                    <span>Status pegawai akan diubah menjadi Pensiun terhitung mulai TMT di atas, dan dicatat pada riwayat status pegawai.</span>
                </p>
            </div>

            {{-- ============================ FOOTER ============================ --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center sm:justify-end gap-2.5 sm:gap-3 shrink-0">
                <button type="button" @click="close()" :disabled="saving"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <i data-lucide="x-circle" class="size-4"></i>
                    <span>Batal</span>
                </button>
                <button type="submit" :disabled="saving"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-wait">
                    <span class="flex" x-show="!saving"><i data-lucide="check" class="size-4"></i></span>
                    <span class="flex" x-show="saving" x-cloak><i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i></span>
                    <span x-text="saving ? 'Memproses...' : 'Proses Pensiun'">Proses Pensiun</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>