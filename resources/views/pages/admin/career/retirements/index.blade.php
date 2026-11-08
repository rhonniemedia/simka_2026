@extends('layouts.main.admin')

@section('title', 'Pensiun')
@section('page_title', 'Pensiun')
@section('page_subtitle', 'Pantau batas usia pensiun pegawai dan proses pensiun')

@section('content')
@php
$pdfPeriodOptions = [
['value' => 'all', 'label' => 'Semua'],
['value' => 'one_year', 'label' => '1 tahun sebelum pensiun'],
];
$pdfStatusOptions = collect($retirementStatusOptions ?? [])
->map(fn ($o) => ['value' => (string) $o['value'], 'label' => $o['label']])
->values()
->prepend(['value' => '', 'label' => 'Semua status'])
->all();
@endphp
<div class="px-5 py-8 md:p-8"
    x-data="{
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterRetirementStatus) || !empty($filterEmploymentStatus) || !empty($filterPositionType) || !empty($filterYear) || !empty($filterGender)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const names = ['filter_retirement_status', 'filter_employment_status', 'filter_position_type', 'filter_year', 'filter_gender'];
            this.isFilterActive = names.some(n => (document.querySelector(`[name=${n}]`)?.value || '') !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Pensiun</h1>
            <p class="text-sm text-secondary">Pantau batas usia pensiun pegawai dan proses pensiun bagi yang telah mencapai batas.</p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
            {{-- Cetak PDF (dropdown pilih periode & status) --}}
            <div class="relative"
                x-data="{
        open: false,
        period: 'all',
        status: '',
        openPdf(download = false) {
            const params = new URLSearchParams({ period: this.period, status: this.status });
            if (download) params.set('download', '1');

            window.open('{{ route('admin.personnel.retirement.pdf') }}?' + params.toString(), '_blank', 'noopener');
            this.open = false;
        }
    }"
                {{-- Daftar <x-ui.select> di-teleport ke body, jadi klik pada opsinya dianggap "di luar" menu ini.
                     Klik di dalam daftar tersebut (fixed z-[9999]) diabaikan agar menu tidak tertutup. --}}
                @click.window="if (open && !$el.contains($event.target) && !$event.target.closest('.fixed.z-\\[9999\\]')) open = false"
                @keydown.escape.window="open = false">

                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-center gap-2 px-3 py-2.5 sm:px-4 ring-1 ring-border hover:ring-primary rounded-full text-foreground font-semibold text-sm transition-all bg-white cursor-pointer whitespace-nowrap">
                    <i data-lucide="printer" class="size-4 shrink-0"></i>
                    <span>Cetak PDF</span>
                </button>

                <div x-show="open" x-cloak x-transition
                    class="absolute left-0 sm:left-auto sm:right-0 z-20 mt-2 w-72 max-w-[calc(100vw-2.5rem)] rounded-2xl border border-border bg-white p-4 shadow-lg space-y-3">

                    <div @change="period = $event.target.value"
                        @reset-filters.window="period = 'all'">
                        <label class="block text-xs font-semibold text-secondary mb-1.5">Periode</label>
                        <x-ui.select
                            name="pdf_period"
                            :options="$pdfPeriodOptions"
                            value="all"
                            placeholder="Semua" />
                    </div>

                    <div @change="status = $event.target.value"
                        @reset-filters.window="status = ''">
                        <label class="block text-xs font-semibold text-secondary mb-1.5">Status pensiun</label>
                        <x-ui.select
                            name="pdf_status"
                            :options="$pdfStatusOptions"
                            placeholder="Semua status" />
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="openPdf()"
                            class="flex-1 h-10 inline-flex items-center justify-center gap-1.5 rounded-xl bg-primary text-white text-sm font-semibold cursor-pointer">
                            <i data-lucide="eye" class="size-4 shrink-0"></i>
                            Tampilkan
                        </button>
                        <button type="button" @click="openPdf(true)"
                            class="h-10 px-3 inline-flex items-center justify-center gap-1.5 rounded-xl ring-1 ring-border hover:ring-primary text-sm font-semibold text-foreground cursor-pointer">
                            <i data-lucide="download" class="size-4 shrink-0"></i>
                            Unduh
                        </button>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.personnel.retirement.index') }}"
                title="Segarkan halaman"
                onclick="document.getElementById('refresh-icon').classList.add('animate-spin');"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 ring-1 ring-border hover:ring-primary rounded-full text-foreground font-semibold text-sm transition-all bg-white cursor-pointer whitespace-nowrap">
                <i id="refresh-icon" data-lucide="refresh-cw" class="size-4 shrink-0"></i>
                <span>Segarkan</span>
            </a>
        </div>
    </div>

    {{-- 2. TABEL DATA --}}
    <div class="bg-white rounded-2xl border border-border p-5">

        {{-- Header Tabel --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">

            {{-- Bagian Kiri: Ikon & Teks --}}
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                    <i data-lucide="user-minus" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">Daftar Pensiun Pegawai</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-1 sm:mt-0.5 leading-relaxed">Pegawai PNS, PPPK, dan PPPK Paruh Waktu, diurutkan dari batas usia yang terdekat.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Pencarian & Filter --}}
            <div class="flex items-center gap-2 w-full sm:w-auto" x-data="{ searchQuery: @js($search ?? '') }">

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
                        hx-get="{{ route('admin.personnel.retirement.index') }}"
                        hx-trigger="keyup changed delay:400ms, search"
                        hx-target="#retirement-container"
                        hx-select="#retirement-container"
                        hx-swap="outerHTML"
                        hx-include="#retirement-filter-form"
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

        @include('pages.admin.career.retirements.partials._table', ['rows' => $rows])

    </div>

    @include('pages.admin.career.retirements.partials._filter-modal', [
    'filterRetirementStatus' => $filterRetirementStatus ?? '',
    'filterEmploymentStatus' => $filterEmploymentStatus ?? '',
    'filterPositionType' => $filterPositionType ?? '',
    'filterYear' => $filterYear ?? '',
    'filterGender' => $filterGender ?? '',
    'retirementStatusOptions' => $retirementStatusOptions ?? [],
    'employmentOptions' => $employmentOptions ?? [],
    'positionTypeOptions' => $positionTypeOptions ?? [],
    'yearOptions' => $yearOptions ?? [],
    ])

    <div id="modal-container"></div>

</div>
@endsection