{{-- File: resources/views/pages/admin/personnel/transfers/partials/_form-modal.blade.php --}}
@php
$isReaktivasi = $mode === 'reaktivasi';
$modalTitle = $isReaktivasi ? 'Aktifkan Kembali Pegawai' : 'Mutasi Pegawai';
$modalDesc = $isReaktivasi
? 'Kembalikan status pegawai yang sebelumnya Pindah/Mengundurkan Diri menjadi Aktif.'
: 'Ubah status pegawai aktif menjadi Pindah, Mengundurkan Diri, Meninggal Dunia, atau Diberhentikan.';
@endphp

<div id="modal-container"
    x-data="{
        open: false,
        errors: {},
        hasErrors: false,
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
                    <div class="size-11 sm:size-12 rounded-full {{ $isReaktivasi ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center shrink-0 shadow-sm">
                        <i data-lucide="{{ $isReaktivasi ? 'rotate-ccw' : 'arrow-right-left' }}" class="size-5 sm:size-6"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $modalTitle }}</h3>
                        <p class="text-xs sm:text-sm text-secondary mt-0.5">{{ $modalDesc }}</p>
                    </div>
                </div>
                <button type="button" @click="close()"
                    class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                    <i data-lucide="x" class="size-4 pointer-events-none"></i>
                </button>
            </div>

            <form hx-post="{{ route('admin.personnel.mutation.store') }}"
                hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML"
                @htmx:after-request="
                    const xhr = $event.detail.xhr;
                    if (xhr.status === 422) {
                        setErrors(xhr);
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                ">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode }}">

                <div class="flex-1 min-h-0 p-4 sm:p-6 space-y-4 overflow-y-auto [scrollbar-gutter:stable]">

                    {{-- Pegawai --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">
                            Pegawai <span class="text-error">*</span>
                        </label>
                        <select name="staff_id" class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">— Pilih pegawai —</option>
                            @forelse ($staffOptions as $id => $name)
                            <option value="{{ $id }}" @selected(old('staff_id')==$id)>{{ $name }}</option>
                            @empty
                            <option value="" disabled>
                                {{ $isReaktivasi ? 'Tidak ada pegawai berstatus Pindah/Mengundurkan Diri' : 'Tidak ada pegawai aktif' }}
                            </option>
                            @endforelse
                        </select>
                        <p class="text-xs text-error font-medium mt-1" x-show="err('staff_id')" x-cloak x-text="err('staff_id')"></p>
                    </div>

                    {{-- Status tujuan (khusus mode Mutasi) --}}
                    @if (!$isReaktivasi)
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">
                            Status Tujuan <span class="text-error">*</span>
                        </label>
                        <select name="to_status" class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">— Pilih status —</option>
                            @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('to_status')==$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-error font-medium mt-1" x-show="err('to_status')" x-cloak x-text="err('to_status')"></p>
                    </div>
                    @else
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center gap-2.5">
                        <i data-lucide="check-circle-2" class="size-4 text-emerald-600 shrink-0"></i>
                        <p class="text-xs font-medium text-emerald-800">Status tujuan: <strong>Aktif</strong></p>
                    </div>
                    @endif

                    {{-- Nomor SK --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">Nomor SK <span class="text-error">*</span></label>
                        <input type="text" name="decree_number" value="{{ old('decree_number') }}"
                            class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            placeholder="mis. 821/123/SK/2026">
                        <p class="text-xs text-error font-medium mt-1" x-show="err('decree_number')" x-cloak x-text="err('decree_number')"></p>
                    </div>

                    {{-- TMT --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">TMT (Tanggal Efektif) <span class="text-error">*</span></label>
                        <input type="date" name="effective_date" value="{{ old('effective_date') }}"
                            class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <p class="text-xs text-error font-medium mt-1" x-show="err('effective_date')" x-cloak x-text="err('effective_date')"></p>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">Catatan</label>
                        <textarea name="note" rows="3"
                            class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            placeholder="Opsional, mis. instansi tujuan untuk kasus pindah">{{ old('note') }}</textarea>
                        <p class="text-xs text-error font-medium mt-1" x-show="err('note')" x-cloak x-text="err('note')"></p>
                    </div>
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
                        :disabled="saving"
                        class="inline-flex items-center gap-2 px-6 py-2.5 {{ $isReaktivasi ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30' : 'bg-primary hover:bg-primary/90 shadow-primary/30' }} text-white text-sm font-bold rounded-xl transition-all shadow-sm disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer">
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