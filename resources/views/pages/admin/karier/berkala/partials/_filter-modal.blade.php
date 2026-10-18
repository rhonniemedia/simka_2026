<x-ui.modal show="filterModalOpen" maxWidth="lg">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <i data-lucide="sliders-horizontal" class="size-4 text-primary"></i>
            </div>
            <div>
                <h3 class="font-bold text-foreground text-base sm:text-lg">Filter Gaji Berkala</h3>
                <p class="text-[11px] sm:text-xs text-secondary mt-0.5">Persempit daftar riwayat berdasarkan kriteria</p>
            </div>
        </div>
        <button @click="filterModalOpen = false" class="size-8 rounded-lg border border-border flex items-center justify-center hover:bg-muted transition-colors">
            <i data-lucide="x" class="size-4 text-secondary"></i>
        </button>
    </div>

    {{-- Form filter --}}
    <div id="salary-filter-form" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 sm:space-y-6">

        @php
        $statusOptions = [
        ['value' => 'verified', 'label' => 'Terverifikasi'],
        ['value' => 'draft', 'label' => 'Draf / Menunggu'],
        ['value' => 'rejected', 'label' => 'Ditolak'],
        ];

        // Contoh membuat array tahun 5 tahun terakhir
        $yearOptions = [];
        $currentYear = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $yearOptions[]=['value'=> $currentYear - $i, 'label' => $currentYear - $i];
            }
            @endphp

            <div class="space-y-3">
                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary">
                    <i data-lucide="filter" class="size-3.5"></i>
                    Kriteria KGB
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm text-foreground mb-2">Status Verifikasi</label>
                        <x-ui.select
                            name="filter_verification_status"
                            :options="$statusOptions"
                            value="{{ $filterVerificationStatus ?? '' }}"
                            placeholder="Semua Status" />
                    </div>
                    <div>
                        <label class="block text-sm text-foreground mb-2">Tahun TMT</label>
                        <x-ui.select
                            name="filter_year"
                            :options="$yearOptions"
                            value="{{ $filterYear ?? '' }}"
                            placeholder="Semua Tahun" />
                    </div>
                </div>
            </div>

    </div>

    {{-- Footer --}}
    <div class="px-4 sm:px-6 py-4 border-t border-border bg-gray-50/50 flex flex-col-reverse sm:flex-row items-center justify-between shrink-0 gap-3">
        <button type="button"
            @click="
                $dispatch('reset-filters');
                setTimeout(() => { document.getElementById('btn-apply-filter').click(); }, 50);
            "
            class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-xl border border-border bg-white text-secondary hover:bg-muted transition-colors cursor-pointer text-sm">
            <i data-lucide="rotate-ccw" class="size-3.5"></i>
            Reset Filter
        </button>

        <button type="button"
            id="btn-apply-filter"
            hx-get="#"
            hx-include="#salary-filter-form, [name='search']"
            hx-target="#salary-container" hx-select="#salary-container" hx-swap="outerHTML" hx-push-url="true"
            @click="filterModalOpen = false"
            class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-5 py-2.5 bg-primary text-white hover:bg-primary-dark shadow-md text-sm font-bold rounded-xl transition-all cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            Terapkan Filter
        </button>
    </div>

</x-ui.modal>