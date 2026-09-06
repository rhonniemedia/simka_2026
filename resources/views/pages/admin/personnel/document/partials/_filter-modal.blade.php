<x-ui.modal show="filterModalOpen" maxWidth="lg">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <i data-lucide="sliders-horizontal" class="size-4 text-primary"></i>
            </div>
            <div>
                <h3 class="font-bold text-foreground text-base sm:text-lg">Filter Dokumen</h3>
                <p class="text-[11px] sm:text-xs text-secondary mt-0.5">Persempit daftar berdasarkan kriteria berikut</p>
            </div>
        </div>
        <button @click="filterModalOpen = false" class="size-8 rounded-lg border border-border flex items-center justify-center hover:bg-muted transition-colors">
            <i data-lucide="x" class="size-4 text-secondary"></i>
        </button>
    </div>

    {{-- Form filter --}}
    <div id="documents-filter-form" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 sm:space-y-6">

        @php
        $catOptions = [];
        if (isset($categoryOptions)) {
        foreach ($categoryOptions as $id => $name) {
        $catOptions[] = ['value' => $id, 'label' => $name];
        }
        }

        $verificationOptions = [
        ['value' => 'draft', 'label' => 'Draft'],
        ['value' => 'verified', 'label' => 'Terverifikasi'],
        ['value' => 'rejected', 'label' => 'Ditolak'],
        ];
        @endphp

        {{-- Grup: Kategori & Status --}}
        <div class="space-y-3">
            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary">
                <i data-lucide="folder-open" class="size-3.5"></i>
                Kategori & Status
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-sm text-foreground mb-2">Kategori Dokumen</label>
                    <x-ui.select
                        name="filter_category"
                        :options="$catOptions"
                        value="{{ $filterCategory ?? '' }}"
                        placeholder="Semua Kategori" />
                </div>
                <div>
                    <label class="block text-sm text-foreground mb-2">Status Verifikasi</label>
                    <x-ui.select
                        name="filter_verification"
                        :options="$verificationOptions"
                        value="{{ $filterVerification ?? '' }}"
                        placeholder="Semua Status" />
                </div>
            </div>
        </div>

        {{-- Grup: Rentang Tanggal --}}
        <div class="space-y-3 pt-1 border-t border-border/70">
            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary pt-3">
                <i data-lucide="calendar" class="size-3.5"></i>
                Rentang Tanggal Dokumen
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-sm text-foreground mb-2">Dari Tanggal</label>
                    <input type="date" name="filter_date_from" value="{{ $filterDateFrom ?? '' }}"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm text-foreground mb-2">Sampai Tanggal</label>
                    <input type="date" name="filter_date_to" value="{{ $filterDateTo ?? '' }}"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="px-4 sm:px-6 py-4 border-t border-border bg-gray-50/50 flex flex-col-reverse sm:flex-row items-center justify-between shrink-0 gap-3">
        <button type="button"
            @click="
                $dispatch('reset-filters');
                setTimeout(() => { document.getElementById('btn-apply-document-filter').click(); }, 50);
            "
            class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-xl border border-border bg-white text-secondary hover:bg-muted transition-colors cursor-pointer text-sm">
            <i data-lucide="rotate-ccw" class="size-3.5"></i>
            Reset Filter
        </button>

        <button type="button"
            id="btn-apply-document-filter"
            hx-get="{{ route('admin.personnel.documents.index') }}"
            hx-include="#documents-filter-form, [name='search']"
            hx-target="#documents-container" hx-select="#documents-container" hx-swap="outerHTML" hx-push-url="true"
            @click="filterModalOpen = false"
            class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-5 py-2.5 bg-primary text-white hover:bg-primary-dark shadow-md text-sm font-bold rounded-xl transition-all cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            Terapkan Filter
        </button>
    </div>

</x-ui.modal>