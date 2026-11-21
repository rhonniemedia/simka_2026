{{-- File: resources/views/pages/admin/personnel/positions/partials/_filter-modal.blade.php --}}
@php
// Format opsi untuk <x-ui.select>: [['value' => ..., 'label' => ...], ...]
    // Opsi pertama (value '') dipakai agar filter bisa dikembalikan ke "Semua".
    $employmentSelectOptions = $employmentOptions
    ->map(fn ($status) => ['value' => $status->id, 'label' => $status->name])
    ->prepend(['value' => '', 'label' => 'Semua status'])
    ->values()
    ->all();

    $positionSelectOptions = [
    ['value' => '', 'label' => 'Semua pegawai'],
    ['value' => 'has', 'label' => 'Sudah punya jabatan aktif'],
    ['value' => 'none', 'label' => 'Belum punya jabatan aktif'],
    ];
    @endphp

    <x-ui.modal show="filterModalOpen" maxWidth="md">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-gray-50/50">
            <div class="flex items-center gap-3 min-w-0">
                <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="filter" class="size-4 text-primary"></i>
                </div>
                <div>
                    <h3 class="font-bold text-foreground text-base sm:text-lg">Filter Jabatan ASN</h3>
                    <p class="text-[11px] sm:text-xs text-secondary mt-0.5">Persempit daftar posisi pegawai</p>
                </div>
            </div>
            <button type="button" @click="filterModalOpen = false"
                class="size-8 rounded-lg border border-border flex items-center justify-center hover:bg-muted transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 text-secondary"></i>
            </button>
        </div>

        {{-- Form filter --}}
        <form id="staff-filter-form"
            hx-get="{{ route('admin.personnel.positions.index') }}"
            hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML"
            hx-include="[name=search]"
            hx-push-url="true"
            @htmx:after-request="filterModalOpen = false"
            class="flex flex-col flex-1 overflow-hidden">

            {{-- Body Content --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-sm text-foreground mb-2 font-medium">Status Kepegawaian</label>
                    <x-ui.select
                        name="filter_employment_status"
                        :options="$employmentSelectOptions"
                        :value="$filterEmploymentStatus"
                        placeholder="Semua status" />
                </div>

                <div>
                    <label class="block text-sm text-foreground mb-2 font-medium">Jabatan ASN Aktif</label>
                    <x-ui.select
                        name="filter_position"
                        :options="$positionSelectOptions"
                        :value="$filterPosition"
                        placeholder="Semua pegawai" />
                    <p class="text-xs text-secondary mt-1.5">Berguna untuk menemukan pegawai yang datanya belum dilengkapi.</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-gray-50/50 flex flex-col-reverse sm:flex-row items-center sm:justify-end shrink-0 gap-3">
                {{-- Komponen select mendengarkan event "reset-filters" untuk mengosongkan pilihannya --}}
                <button type="button"
                    @click="
                    $dispatch('reset-filters');
                    $nextTick(() => htmx.trigger($el.closest('form'), 'submit'));
                "
                    class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                    <i data-lucide="rotate-ccw" class="size-3.5"></i>
                    Reset Filter
                </button>
                <button type="submit"
                    class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all shadow-sm shadow-primary/30 cursor-pointer">
                    <i data-lucide="check" class="size-4"></i>
                    Terapkan Filter
                </button>
            </div>
        </form>

    </x-ui.modal>