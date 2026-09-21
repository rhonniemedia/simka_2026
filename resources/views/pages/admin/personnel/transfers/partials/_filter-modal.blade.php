{{-- File: resources/views/pages/admin/personnel/transfers/partials/_filter-modal.blade.php --}}
@php
// x-ui.select membutuhkan daftar [['value' => ..., 'label' => ...]].
$yearSelectOptions = collect($yearOptions ?? [])
->map(fn($y) => ['value' => (string) $y, 'label' => (string) $y])
->values()
->all();

$statusSelectOptions = [
['value' => 'transferred', 'label' => 'Pindah'],
['value' => 'resigned', 'label' => 'Mengundurkan Diri'],
['value' => 'retired', 'label' => 'Pensiun'],
['value' => 'deceased', 'label' => 'Meninggal Dunia'],
['value' => 'dismissed', 'label' => 'Diberhentikan'],
];
@endphp

<x-ui.modal show="filterModalOpen" maxWidth="md">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <i data-lucide="sliders-horizontal" class="size-4 text-primary"></i>
            </div>
            <div>
                <h3 class="font-bold text-foreground text-base sm:text-lg">Filter Mutasi</h3>
                <p class="text-[11px] sm:text-xs text-secondary mt-0.5">Persempit daftar berdasarkan kriteria berikut</p>
            </div>
        </div>
        <button type="button" @click="filterModalOpen = false" class="size-8 rounded-lg border border-border flex items-center justify-center hover:bg-muted transition-colors">
            <i data-lucide="x" class="size-4 text-secondary"></i>
        </button>
    </div>

    {{-- Form filter.
         hx-include membawa isi kolom pencarian agar kata kunci tidak hilang saat filter diterapkan. --}}
    <form id="mutation-filter-form"
        hx-get="{{ route('admin.personnel.mutation.index') }}"
        hx-include="[name='search']"
        hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML"
        hx-push-url="true"
        @htmx:after-request="filterModalOpen = false"
        class="flex flex-col flex-1 min-h-0">

        <div class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 space-y-3">
            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary">
                <i data-lucide="arrow-right-left" class="size-3.5"></i>
                Mutasi
            </p>

            <div class="grid grid-cols-1 gap-3 sm:gap-4">
                <div>
                    <label class="block text-sm text-foreground mb-2">Tahun</label>
                    <x-ui.select
                        name="year"
                        :options="$yearSelectOptions"
                        value="{{ $year ?? '' }}"
                        placeholder="1 tahun terakhir (default)" />
                </div>
                <div>
                    <label class="block text-sm text-foreground mb-2">Status</label>
                    <x-ui.select
                        name="filter_status"
                        :options="$statusSelectOptions"
                        value="{{ $filterStatus ?? '' }}"
                        placeholder="Semua Status" />
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 sm:px-6 py-4 border-t border-border bg-gray-50/50 flex flex-col-reverse sm:flex-row items-center justify-between shrink-0 gap-3">
            {{-- Reset: kosongkan semua x-ui.select lewat event, lalu kirim ulang filter. --}}
            <button type="button"
                @click="
                    $dispatch('reset-filters');
                    setTimeout(() => htmx.trigger(document.getElementById('mutation-filter-form'), 'submit'), 50);
                "
                class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-xl border border-border bg-white text-secondary hover:bg-muted transition-colors cursor-pointer text-sm">
                <i data-lucide="rotate-ccw" class="size-3.5"></i>
                Reset Filter
            </button>

            <button type="submit"
                class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-5 py-2.5 bg-primary text-white hover:bg-primary-dark shadow-md text-sm font-bold rounded-xl transition-all cursor-pointer">
                <i data-lucide="check" class="size-4"></i>
                Terapkan Filter
            </button>
        </div>
    </form>

</x-ui.modal>