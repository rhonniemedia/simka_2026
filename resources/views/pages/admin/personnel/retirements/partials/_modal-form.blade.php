{{-- File: resources/views/pages/admin/personnel/retirements/partials/_modal-form.blade.php --}}
@php
$staff = $row['staff'];

// TMT pensiun hasil perhitungan (tanggal 1 pada bulan setelah bulan ulang tahun batas usia).
$tmtText = $row['tmt']
? \Carbon\Carbon::parse($row['tmt']->format('Y-m-d'))->locale('id')->isoFormat('D MMMM Y')
: '-';

$fieldClass = 'w-full bg-muted/50 border border-border rounded-xl px-3.5 py-2.5 text-sm text-foreground cursor-default focus:outline-none';
@endphp

<div x-data="{
        open: false,
        submitting: false,
        close() {
            const container = this.$root.closest('#modal-container');
            this.open = false;
            setTimeout(() => { if (container) container.innerHTML = ''; }, 150);
        }
    }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="close()"
    @keydown.escape.window="if (open && !submitting) close()">

    <x-ui.modal show="open" maxWidth="md">

        {{-- Header --}}
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

        {{-- Form: status & TMT dihitung ulang di server, jadi tidak ada input yang dikirim selain token. --}}
        <form hx-post="{{ route('admin.personnel.retirement.process.store', $staff->id) }}"
            hx-swap="none"
            @htmx:before-request="submitting = true"
            @htmx:after-request="submitting = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf

            {{-- Body --}}
            <div class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-sm text-foreground mb-2">Nama</label>
                    <input type="text" value="{{ $staff->name }}" readonly tabindex="-1" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="block text-sm text-foreground mb-2">Status</label>
                    <input type="text" value="Pensiun" readonly tabindex="-1" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="block text-sm text-foreground mb-2">TMT Pensiun</label>
                    <input type="text" value="{{ $tmtText }}" readonly tabindex="-1" class="{{ $fieldClass }}">
                </div>

                <p class="flex items-start gap-2 rounded-xl bg-amber-50 px-3.5 py-3 text-xs leading-relaxed text-amber-800">
                    <i data-lucide="info" class="size-4 shrink-0 mt-px"></i>
                    <span>Status pegawai akan diubah menjadi Pensiun terhitung mulai TMT di atas.</span>
                </p>
            </div>

            {{-- Footer --}}
            <div class="shrink-0 border-t border-border bg-white px-4 sm:px-6 py-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                <button type="button" @click="close()" :disabled="submitting"
                    class="w-full sm:w-auto flex items-center justify-center px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:text-foreground transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                    Batal
                </button>
                <button type="submit" :disabled="submitting"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2 disabled:opacity-70 disabled:cursor-wait">
                    <i data-lucide="check" class="size-4"></i>
                    <span x-text="submitting ? 'Memproses...' : 'Proses Pensiun'">Proses Pensiun</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>