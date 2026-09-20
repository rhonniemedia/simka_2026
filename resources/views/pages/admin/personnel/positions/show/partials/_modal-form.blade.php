{{-- File: resources/views/pages/admin/personnel/positions/show/partials/_modal-form.blade.php --}}
@php
$isEdit = $history->exists;
$actionUrl = $isEdit
? route('admin.personnel.positions.update', [$staff->id, $history->id])
: route('admin.personnel.positions.store', $staff->id);
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
                        <select name="staff_asn_position_id" class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">— Pilih jabatan —</option>
                            @foreach (['fungsional_keahlian' => 'Fungsional Keahlian', 'fungsional_keterampilan' => 'Fungsional Keterampilan', 'pelaksana' => 'Pelaksana'] as $type => $label)
                            @php $group = $positionOptions->where('position_type', $type); @endphp
                            @if ($group->isNotEmpty())
                            <optgroup label="{{ $label }}">
                                @foreach ($group as $p)
                                <option value="{{ $p->id }}" @selected(old('staff_asn_position_id', $history->staff_asn_position_id) == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </optgroup>
                            @endif
                            @endforeach
                        </select>
                        <p class="text-xs text-error font-medium mt-1" x-show="err('staff_asn_position_id')" x-cloak x-text="err('staff_asn_position_id')"></p>
                    </div>

                    {{-- Nomor SK --}}
                    <div>
                        <label class="text-xs font-bold text-secondary uppercase tracking-wider">Nomor SK <span class="text-error">*</span></label>
                        <input type="text" name="decree_number" value="{{ old('decree_number', $history->decree_number) }}"
                            class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            placeholder="mis. 821/123/SK/2026">
                        <p class="text-xs text-error font-medium mt-1" x-show="err('decree_number')" x-cloak x-text="err('decree_number')"></p>
                    </div>

                    {{-- Tanggal SK & TMT --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold text-secondary uppercase tracking-wider">Tanggal SK <span class="text-error">*</span></label>
                            <input type="date" name="decree_date" value="{{ old('decree_date', optional($history->decree_date)->format('Y-m-d')) }}"
                                class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <p class="text-xs text-error font-medium mt-1" x-show="err('decree_date')" x-cloak x-text="err('decree_date')"></p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-secondary uppercase tracking-wider">TMT <span class="text-error">*</span></label>
                            <input type="date" name="effective_date" value="{{ old('effective_date', optional($history->effective_date)->format('Y-m-d')) }}"
                                class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <p class="text-xs text-error font-medium mt-1" x-show="err('effective_date')" x-cloak x-text="err('effective_date')"></p>
                        </div>
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
                        :disabled="saving"
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