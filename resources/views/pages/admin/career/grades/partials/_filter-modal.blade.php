<x-ui.modal show="filterModalOpen" maxWidth="lg">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <i data-lucide="sliders-horizontal" class="size-4 text-primary"></i>
            </div>
            <div>
                <h3 class="font-bold text-foreground text-base sm:text-lg">Filter Riwayat Kepangkatan</h3>
                <p class="text-[11px] sm:text-xs text-secondary mt-0.5">Persempit daftar riwayat berdasarkan kriteria</p>
            </div>
        </div>
        <button type="button" @click="filterModalOpen = false" class="size-8 rounded-lg border border-border flex items-center justify-center hover:bg-muted transition-colors cursor-pointer">
            <i data-lucide="x" class="size-4 text-secondary"></i>
        </button>
    </div>

    {{-- Form filter --}}
    <form id="promotion-filter-form"
        hx-get="{{ route('admin.personnel.promotions.index') }}"
        hx-target="#promotions-container" hx-select="#promotions-container" hx-swap="outerHTML"
        hx-include="[name=search]"
        hx-push-url="true"
        @htmx:after-request="filterModalOpen = false"
        class="flex flex-col flex-1 overflow-hidden">

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 sm:space-y-6">
            <div class="space-y-3">
                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary">
                    <i data-lucide="filter" class="size-3.5"></i>
                    Kriteria Kepangkatan
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm text-foreground mb-2 font-medium">Golongan / Pangkat</label>
                        <select name="filter_grade_id" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 bg-white">
                            <option value="">Semua Golongan</option>
                            @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(($filterGradeId ?? '' )==$grade->id)>
                                {{ $grade->grade_name }} ({{ $grade->grade_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-foreground mb-2 font-medium">Tahun TMT</label>
                        <select name="filter_year" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 bg-white">
                            <option value="">Semua Tahun</option>
                            @foreach ($yearOptions as $year)
                            <option value="{{ $year }}" @selected(($filterYear ?? '' )==$year)>
                                {{ $year }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 sm:px-6 py-4 border-t border-border bg-gray-50/50 flex flex-col-reverse sm:flex-row items-center sm:justify-end shrink-0 gap-3">
            <button type="button"
                @click="
                    $el.closest('form').querySelectorAll('select').forEach(el => { el.selectedIndex = 0; });
                    htmx.trigger($el.closest('form'), 'submit');
                "
                class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                <i data-lucide="rotate-ccw" class="size-3.5"></i>
                Reset Filter
            </button>

            <button type="submit"
                id="btn-apply-filter"
                class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/95 transition-all shadow-sm cursor-pointer">
                <i data-lucide="check" class="size-4"></i>
                Terapkan Filter
            </button>
        </div>
    </form>

</x-ui.modal>