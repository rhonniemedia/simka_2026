<x-ui.modal show="filterModalOpen" maxWidth="lg">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <i data-lucide="sliders-horizontal" class="size-4 text-primary"></i>
            </div>
            <div>
                <h3 class="font-bold text-foreground text-base sm:text-lg">Filter Data Pegawai</h3>
                <p class="text-[11px] sm:text-xs text-secondary mt-0.5">Persempit daftar berdasarkan kriteria berikut</p>
            </div>
        </div>
        <button @click="filterModalOpen = false" class="size-8 rounded-lg border border-border flex items-center justify-center hover:bg-muted transition-colors">
            <i data-lucide="x" class="size-4 text-secondary"></i>
        </button>
    </div>

    {{-- Form filter --}}
    <div id="staff-filter-form" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 sm:space-y-6">

        @php
        $empOptions = [];
        if(isset($employmentOptions)) {
        foreach ($employmentOptions as $id => $name) {
        $empOptions[] = ['value' => $id, 'label' => $name];
        }
        }

        $persOptions = [];
        if(isset($personnelOptions)) {
        foreach ($personnelOptions as $id => $name) {
        $persOptions[] = ['value' => $id, 'label' => $name];
        }
        }

        $posOptions = [];
        if(isset($positionOptions)) {
        foreach ($positionOptions as $id => $name) {
        $posOptions[] = ['value' => $id, 'label' => $name];
        }
        }

        $genderOptions = [
        ['value' => 'L', 'label' => 'Laki-laki'],
        ['value' => 'P', 'label' => 'Perempuan'],
        ];
        @endphp

        {{-- Grup: Kepegawaian --}}
        <div class="space-y-3">
            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary">
                <i data-lucide="briefcase" class="size-3.5"></i>
                Kepegawaian
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-sm text-foreground mb-2">Status</label>
                    <x-ui.select
                        name="filter_employment_status"
                        :options="$empOptions"
                        value="{{ $filterEmploymentStatus ?? '' }}"
                        placeholder="Semua Status" />
                </div>
                <div>
                    <label class="block text-sm text-foreground mb-2">Jenis Pegawai</label>
                    <x-ui.select
                        name="filter_personnel"
                        :options="$persOptions"
                        value="{{ $filterPersonnel ?? '' }}"
                        placeholder="Semua Jenis" />
                </div>
            </div>
        </div>

        {{-- Grup: Jabatan & Demografi --}}
        <div class="space-y-3 pt-1 border-t border-border/70">
            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary pt-3">
                <i data-lucide="award" class="size-3.5"></i>
                Jabatan & Demografi
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div class="col-span-1 sm:col-span-2">
                    <label class="block text-sm text-foreground mb-2">Jabatan</label>
                    <x-ui.select
                        name="filter_position"
                        :options="$posOptions"
                        value="{{ $filterPosition ?? '' }}"
                        placeholder="Semua Jabatan" />
                </div>
                <div class="col-span-1 sm:col-span-2">
                    <label class="block text-sm text-foreground mb-2">Jenis Kelamin</label>
                    <x-ui.select
                        name="filter_gender"
                        :options="$genderOptions"
                        value="{{ $filterGender ?? '' }}"
                        placeholder="Semua Gender" />
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
            hx-get="{{ route('admin.personnel.data.index') }}"
            hx-include="#staff-filter-form, [name='search']"
            hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML" hx-push-url="true"
            @click="filterModalOpen = false"
            class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-5 py-2.5 bg-primary text-white hover:bg-primary-dark shadow-md text-sm font-bold rounded-xl transition-all cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            Terapkan Filter
        </button>
    </div>

</x-ui.modal>