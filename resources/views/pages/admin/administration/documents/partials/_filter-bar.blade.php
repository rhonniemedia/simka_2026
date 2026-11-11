{{--
    Filter bar bersama: [switch tampilan] + pencarian + tombol filter + modal filter.
    Dipakai di index (Semua Dokumen / Per Pegawai) dan di halaman detail pegawai.

    Param:
      $actionUrl          URL tujuan request HTMX (index, atau detail pegawai)
      $showViewSwitch     tampilkan dropdown "Semua Dokumen / Per Pegawai" (hanya index; butuh `viewMode` di x-data induk)
      $searchPlaceholder  placeholder kolom cari
      $persistParams      parameter tambahan yang harus ikut di setiap request (mis. ['back' => ...])

    Nilai filter, $search, $activeFilterCount, $categoryOptions diwarisi dari view induk (controller).

    Sumber kebenaran = URL / server. State Alpine di sini hanya turunan:
    jumlah filter dibaca dari atribut data-* pada #documents-container setiap kali isinya diganti.
--}}
@php
$showViewSwitch = $showViewSwitch ?? false;
$searchPlaceholder = $searchPlaceholder ?? 'Cari...';
$persistParams = $persistParams ?? [];
@endphp

<div class="contents"
    x-data="{
        filterModalOpen: false,
        viewDropdownOpen: false,
        searchQuery: @js($search ?? ''),
        filterCount: {{ (int) ($activeFilterCount ?? 0) }},

        // Baca keadaan terbaru dari container hasil (server). Pencarian hanya disinkronkan
        // saat init / back-forward, supaya tidak menimpa ketikan yang sedang berjalan.
        syncFromContainer(withSearch = false) {
            const el = document.getElementById('documents-container');
            if (!el) return;
            this.filterCount = parseInt(el.dataset.filterCount || '0', 10);
            if (withSearch) this.searchQuery = el.dataset.search ?? '';
        },

        // Reset = navigasi ke URL tanpa filter_*. Tidak bergantung pada event custom,
        // komponen select, atau setTimeout; isi modal ikut bersih karena halaman dimuat ulang.
        resetFilters(view) {
            const p = new URLSearchParams(@js($persistParams));
            if (view) p.set('view', view);
            if (this.searchQuery) p.set('search', this.searchQuery);
            const qs = p.toString();
            window.location.href = @js($actionUrl) + (qs ? '?' + qs : '');
        }
    }"
    x-init="syncFromContainer(true)"
    @htmx:after-swap.window="syncFromContainer()">

    <div class="flex items-center gap-2 w-full sm:w-auto">

        @if ($showViewSwitch)
        {{-- SWITCH TAMPILAN (DROPDOWN) --}}
        <div class="relative" @click.away="viewDropdownOpen = false">
            {{-- Mobile: hanya ikon + panah kecil. Layar sm ke atas: ikon + label. --}}
            <button type="button"
                @click="viewDropdownOpen = !viewDropdownOpen"
                :aria-label="viewMode === 'all' ? 'Semua Dokumen' : 'Per Pegawai'"
                :title="viewMode === 'all' ? 'Semua Dokumen' : 'Per Pegawai'"
                aria-haspopup="true"
                :aria-expanded="viewDropdownOpen"
                class="flex items-center justify-center sm:justify-between gap-1.5 sm:gap-3 px-3 sm:px-3.5 h-11 rounded-xl bg-white border border-border text-sm font-semibold text-foreground hover:bg-muted transition-all cursor-pointer focus:outline-none focus:border-primary">

                <div class="flex items-center gap-2">
                    <i data-lucide="files" x-show="viewMode === 'all'" class="size-4 text-primary shrink-0"></i>
                    <i data-lucide="users" x-show="viewMode === 'staff'" class="size-4 text-primary shrink-0" x-cloak></i>
                    <span class="hidden sm:inline" x-text="viewMode === 'all' ? 'Semua Dokumen' : 'Per Pegawai'"></span>
                </div>

                <i data-lucide="chevron-down" class="size-3.5 sm:size-4 text-secondary transition-transform duration-200" :class="viewDropdownOpen ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="viewDropdownOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                class="absolute left-0 sm:left-auto sm:right-0 mt-2 w-52 max-w-[calc(100vw-2rem)] rounded-xl bg-white border border-border shadow-lg p-1.5 z-50 flex flex-col gap-1">

                {{-- Pencarian & filter ikut terbawa (hx-include); `view` eksplisit lewat hx-vals. --}}
                <button type="button"
                    title="Semua Dokumen"
                    hx-get="{{ $actionUrl }}"
                    hx-vals='{"view":"all"}'
                    hx-include="#documents-filter-form, [name='search']"
                    hx-target="#documents-container" hx-select="#documents-container"
                    hx-swap="outerHTML" hx-push-url="true"
                    @click="viewMode = 'all'; viewDropdownOpen = false;"
                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors cursor-pointer text-left"
                    :class="viewMode === 'all' ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-muted hover:text-foreground'">
                    <i data-lucide="files" class="size-4 shrink-0"></i>
                    <span>Semua Dokumen</span>
                </button>

                <button type="button"
                    title="Per Pegawai"
                    hx-get="{{ $actionUrl }}"
                    hx-vals='{"view":"staff"}'
                    hx-include="#documents-filter-form, [name='search']"
                    hx-target="#documents-container" hx-select="#documents-container"
                    hx-swap="outerHTML" hx-push-url="true"
                    @click="viewMode = 'staff'; viewDropdownOpen = false;"
                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors cursor-pointer text-left"
                    :class="viewMode === 'staff' ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-muted hover:text-foreground'">
                    <i data-lucide="users" class="size-4 shrink-0"></i>
                    <span>Per Pegawai</span>
                </button>
            </div>
        </div>
        @endif

        {{-- INPUT PENCARIAN --}}
        <div class="relative flex-1 min-w-0 sm:flex-none sm:w-56 md:w-64 flex items-center">
            <i data-lucide="search" class="absolute left-3.5 size-4 transition-colors pointer-events-none"
                :class="searchQuery.length > 0 ? 'text-primary' : 'text-secondary'"></i>

            <input
                x-ref="searchInput"
                type="text"
                inputmode="search"
                enterkeyhint="search"
                name="search"
                x-model="searchQuery"
                placeholder="{{ $searchPlaceholder }}"
                autocomplete="off"
                hx-get="{{ $actionUrl }}"
                hx-trigger="keyup changed delay:400ms, search"
                hx-target="#documents-container"
                hx-select="#documents-container"
                hx-swap="outerHTML"
                hx-include="#documents-filter-form"
                hx-push-url="true"
                class="h-11 w-full min-w-0 bg-white border border-border rounded-xl pl-10 pr-10 text-base sm:text-sm truncate focus:outline-none focus:border-primary transition-all"
                :class="searchQuery.length > 0 ? 'border-primary/50 text-foreground font-medium' : 'border-border text-foreground'">

            <button
                type="button"
                x-show="searchQuery.length > 0"
                x-cloak
                @click="searchQuery = ''; $nextTick(() => $refs.searchInput.dispatchEvent(new Event('search')))"
                aria-label="Hapus pencarian"
                class="absolute right-2.5 flex items-center justify-center size-6 sm:size-5 rounded-full bg-slate-100 hover:bg-error/10 text-secondary hover:text-error transition-all cursor-pointer focus:outline-none">
                <i data-lucide="x" class="size-3"></i>
            </button>
        </div>

        {{-- TOMBOL FILTER (indikator dari server lewat data-filter-count) --}}
        <button
            type="button"
            @click="filterModalOpen = true"
            title="Filter"
            aria-label="Buka filter"
            class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border bg-white hover:bg-muted transition-colors cursor-pointer focus:outline-none"
            :class="filterCount > 0 ? 'border-primary/50' : 'border-border'">
            <i data-lucide="filter" class="size-4" :class="filterCount > 0 ? 'text-primary' : 'text-secondary'"></i>

            <span
                x-show="filterCount > 0"
                x-cloak
                x-text="filterCount"
                class="absolute -top-1.5 -right-1.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-none text-white border-2 border-white"></span>
        </button>
    </div>

    {{-- Modal filter (berbagi state `filterModalOpen` dengan bar ini) --}}
    @include('pages.admin.administration.documents.partials._filter-modal', [
    'actionUrl' => $actionUrl,
    'showViewSwitch' => $showViewSwitch,
    'persistParams' => $persistParams,
    ])
</div>