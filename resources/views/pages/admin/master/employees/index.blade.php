@extends('layouts.main.admin')

@section('title', 'Data Pegawai')
@section('page_title', 'Data Pegawai')
@section('page_subtitle', 'Kelola basis data staf dan pegawai secara menyeluruh')

@section('content')
<div class="px-5 py-8 md:p-8"
    x-data="{ 
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterEmploymentStatus) || !empty($filterPersonnel) || !empty($filterPosition) || !empty($filterGender)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const employment = document.querySelector('[name=filter_employment_status]')?.value || '';
            const personnel = document.querySelector('[name=filter_personnel]')?.value || '';
            const position = document.querySelector('[name=filter_position]')?.value || '';
            const gender = document.querySelector('[name=filter_gender]')?.value || '';
            
            this.isFilterActive = (employment !== '' || personnel !== '' || position !== '' || gender !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Data Pegawai</h1>
            <p class="text-sm text-secondary">Kelola basis data staf dan pegawai secara menyeluruh.</p>
        </div>

        {{-- Grup Tombol Aksi --}}
        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
            <button type="button"
                hx-get="{{ route('admin.personnel.data.create') }}"
                hx-target="#modal-container"
                hx-swap="innerHTML"
                title="Tambah Data Pegawai"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap">
                <i data-lucide="plus" class="size-4 shrink-0"></i>
                <span>Tambah Data</span>
            </button>

            {{-- Laporan PDF (Dropdown Accordion Mobile-Friendly) --}}
            @php
            $reports = [
            [
            'key' => 'seniority',
            'label' => 'Daftar Urut Kepangkatan',
            'url' => route('admin.personnel.data.pdf.seniority'),
            'filter' => null,
            ],
            [
            'key' => 'employees',
            'label' => 'Daftar Pegawai',
            'url' => route('admin.personnel.data.pdf.employees'),
            'filter' => [
            'param' => 'employment',
            'placeholder' => 'Semua status kepegawaian',
            'options' => collect($employmentAliasOptions ?? [])
            ->map(fn ($label, $id) => ['value' => (string) $id, 'label' => $label])
            ->values()
            ->prepend(['value' => '', 'label' => 'Semua status kepegawaian'])
            ->all(),
            ],
            ],
            ];
            @endphp
            <div class="relative"
                x-data="{
                    open: false,
                    activeAccordion: null,
                    filters: { employees: '' },
                    openPdf(url, download = false, param = null, value = '') {
                        if (!url || url === '#') { this.open = false; return; }

                        const target = new URL(url, window.location.origin);
                        if (param && value) target.searchParams.set(param, value);
                        if (download) target.searchParams.set('download', '1');

                        window.open(target.toString(), '_blank', 'noopener');
                        this.open = false;
                    }
                }"
                {{-- Daftar <x-ui.select> di-teleport ke body, jadi klik pada opsinya dianggap "di luar" menu ini.
                     Klik di dalam daftar tersebut (fixed z-[9999]) diabaikan agar menu tidak tertutup. --}}
                @click.window="if (open && !$el.contains($event.target) && !$event.target.closest('.fixed.z-\\[9999\\]')) open = false"
                @keydown.escape.window="open = false">

                <button type="button" @click="open = !open"
                    title="Cetak laporan"
                    class="flex w-full items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-blue-600/30 whitespace-nowrap">
                    <i data-lucide="printer" class="size-4 shrink-0"></i>
                    <span>Laporan</span>
                    <i data-lucide="chevron-down" class="size-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="open" x-cloak x-transition
                    class="absolute right-0 z-20 mt-2 w-80 sm:w-96 rounded-2xl border border-border bg-white p-3 shadow-xl max-h-[80vh] overflow-y-auto">
                    <div class="px-2 pb-2 mb-2 border-b border-border/60 text-xs font-semibold text-secondary uppercase tracking-wider">
                        Pilih Menu Laporan
                    </div>

                    <div class="space-y-2">
                        @foreach ($reports as $report)
                        <div class="rounded-xl border border-border/60 bg-slate-50/50 overflow-hidden transition-all">
                            {{-- Accordion Header --}}
                            <button type="button"
                                @click="activeAccordion = (activeAccordion === '{{ $report['key'] }}' ? null : '{{ $report['key'] }}')"
                                class="w-full flex items-center justify-between gap-2 px-3.5 py-3 text-left hover:bg-muted/60 transition-colors cursor-pointer">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="size-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <i data-lucide="file-text" class="size-4"></i>
                                    </div>
                                    <span class="text-xs sm:text-sm font-semibold text-foreground truncate">{{ $report['label'] }}</span>
                                </div>
                                <i data-lucide="chevron-down" class="size-4 shrink-0 text-secondary transition-transform duration-200"
                                    :class="activeAccordion === '{{ $report['key'] }}' ? 'rotate-180 text-primary' : ''"></i>
                            </button>

                            {{-- Accordion Body (Filter, Tampilkan & Unduh) --}}
                            <div x-show="activeAccordion === '{{ $report['key'] }}'" x-cloak x-collapse
                                class="px-3.5 pb-3.5 pt-1 border-t border-border/40 bg-white space-y-3">

                                @if ($report['filter'])
                                <div @change="filters[@js($report['key'])] = $event.target.value"
                                    @reset-filters.window="filters[@js($report['key'])] = ''">
                                    <label class="block text-[11px] font-medium text-secondary mb-1">Filter Kategori:</label>
                                    <x-ui.select
                                        :name="'report_filter_' . $report['key']"
                                        :options="$report['filter']['options']"
                                        :placeholder="$report['filter']['placeholder']" />
                                </div>
                                @endif

                                {{-- Grid Tombol Aksi: Tampilkan & Download --}}
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <button type="button"
                                        @click="openPdf(@js($report['url']), false, @js($report['filter']['param'] ?? null), filters[@js($report['key'])] ?? '')"
                                        class="flex items-center justify-center gap-1.5 px-3 py-2 bg-primary/10 hover:bg-primary text-primary hover:text-white rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                                        <i data-lucide="eye" class="size-3.5 shrink-0"></i>
                                        <span>Lihat PDF</span>
                                    </button>

                                    <button type="button"
                                        @click="openPdf(@js($report['url']), true, @js($report['filter']['param'] ?? null), filters[@js($report['key'])] ?? '')"
                                        class="flex items-center justify-center gap-1.5 px-3 py-2 ring-1 ring-border hover:ring-primary text-foreground hover:text-primary bg-white rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                                        <i data-lucide="download" class="size-3.5 shrink-0"></i>
                                        <span>Download</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.personnel.data.index') }}"
                title="Segarkan halaman"
                onclick="document.getElementById('refresh-icon').classList.add('animate-spin');"
                class="hidden sm:flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 ring-1 ring-border hover:ring-primary rounded-full text-foreground font-semibold text-sm transition-all bg-white cursor-pointer whitespace-nowrap">
                <i id="refresh-icon" data-lucide="refresh-cw" class="size-4 shrink-0"></i>
                <span>Segarkan</span>
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    @include('pages.admin.master.employees.partials._stats-cards', [
    'totalStats' => $totalStats ?? 0,
    'activeStats' => $activeStats ?? 0,
    'inactiveStats' => $inactiveStats ?? 0,
    'retiredStats' => $retiredStats ?? 0,
    'resignedStats' => $resignedStats ?? 0,
    ])

    {{-- Tabel Data --}}
    <div class="bg-white rounded-2xl border border-border p-5">

        {{-- Header Tabel --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">

            {{-- Bagian Kiri: Ikon & Teks --}}
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                    <i data-lucide="users" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">Daftar Pegawai</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-1 sm:mt-0.5 leading-relaxed">Gunakan fitur pencarian dan filter untuk merampingkan data.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Pencarian & Filter --}}
            <div class="flex items-center gap-2 w-full sm:w-auto" x-data="{ searchQuery: '{{ $search ?? '' }}' }">

                {{-- INPUT PENCARIAN --}}
                <div class="relative flex-1 sm:flex-none w-56 md:w-64 flex items-center">
                    <i data-lucide="search" class="absolute left-3.5 size-4 transition-colors pointer-events-none"
                        :class="searchQuery.length > 0 ? 'text-primary' : 'text-secondary'"></i>

                    <input
                        x-ref="searchInput"
                        type="text"
                        name="search"
                        x-model="searchQuery"
                        placeholder="Cari pegawai..."
                        autocomplete="off"
                        hx-get="{{ route('admin.personnel.data.index') }}"
                        hx-trigger="keyup changed delay:400ms, search"
                        hx-target="#staff-container"
                        hx-select="#staff-container"
                        hx-swap="outerHTML"
                        hx-include="#staff-filter-form"
                        hx-push-url="true"
                        class="h-11 w-full bg-white border border-border rounded-xl pl-10 pr-10 text-sm focus:outline-none focus:border-primary transition-all"
                        :class="searchQuery.length > 0 ? 'border-primary/50 text-foreground font-medium' : 'border-border text-foreground'">

                    <button
                        type="button"
                        x-show="searchQuery.length > 0"
                        x-cloak
                        @click="searchQuery = ''; $nextTick(() => $refs.searchInput.dispatchEvent(new Event('search')))"
                        class="absolute right-3 flex items-center justify-center size-5 rounded-full bg-slate-100 hover:bg-error/10 text-secondary hover:text-error transition-all cursor-pointer focus:outline-none">
                        <i data-lucide="x" class="size-3"></i>
                    </button>
                </div>

                {{-- TOMBOL FILTER --}}
                <button
                    type="button"
                    @click="filterModalOpen = true"
                    title="Filter"
                    class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-white hover:bg-muted transition-colors cursor-pointer focus:outline-none">
                    <i data-lucide="filter" class="size-4 text-secondary"></i>

                    {{-- Ping Indikator Aktif --}}
                    <span
                        x-show="isFilterActive"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200 transform"
                        x-transition:enter-start="opacity-0 scale-50"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150 transform"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-50"
                        class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary border-2 border-white"></span>
                    </span>
                </button>
            </div>
        </div>

        @include('pages.admin.master.employees.partials._table', compact('staff'))

    </div>

    @include('pages.admin.master.employees.partials._filter-modal', [
    'filterEmploymentStatus' => $filterEmploymentStatus ?? '',
    'filterPersonnel' => $filterPersonnel ?? '',
    'filterPosition' => $filterPosition ?? '',
    'filterGender' => $filterGender ?? '',
    'employmentOptions' => $employmentOptions ?? [],
    'personnelOptions' => $personnelOptions ?? [],
    'positionOptions' => $positionOptions ?? [],
    ])

    <div id="modal-container"></div>

</div>
@endsection