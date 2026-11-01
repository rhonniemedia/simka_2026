@extends('layouts.main.admin')

@section('title', 'Mutasi Pegawai')
@section('page_title', 'Mutasi Pegawai')
@section('page_subtitle', 'Kelola perubahan status pegawai: pindah, mengundurkan diri, meninggal, diberhentikan, hingga pensiun')

@section('content')
<div class="w-full px-5 py-6 md:px-8 md:py-8"
    x-data="{
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterStatus) || !empty($year) || !empty($filterEmploymentStatus)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const status = document.querySelector('[name=filter_status]')?.value || '';
            const year = document.querySelector('[name=year]')?.value || '';
            const employment = document.querySelector('[name=filter_employment_status]')?.value || '';
            this.isFilterActive = (status !== '' || year !== '' || employment !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3 xl:gap-6 mb-5">
        <div class="min-w-0">
            <h1 class="text-xl md:text-2xl 2xl:text-3xl font-bold text-foreground mb-1">Mutasi Pegawai</h1>
            <p class="text-sm text-secondary leading-relaxed max-w-3xl">Kelola perubahan status dan riwayat mutasi pegawai, termasuk perpindahan, pengunduran diri, pemberhentian, dan pengaktifan kembali.</p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center sm:gap-3 shrink-0">
            <button type="button"
                hx-get="{{ route('admin.personnel.mutation.create') }}"
                hx-target="#modal-container" hx-swap="innerHTML"
                title="Mutasi Pegawai"
                class="flex items-center justify-center gap-2 px-4 py-2 2xl:px-5 2xl:py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap">
                <i data-lucide="arrow-right-left" class="size-4 shrink-0"></i>
                <span>Mutasi</span>
            </button>

            <button type="button"
                hx-get="{{ route('admin.personnel.mutation.create-reaktivasi') }}"
                hx-target="#modal-container" hx-swap="innerHTML"
                title="Aktifkan Kembali Pegawai"
                class="flex items-center justify-center gap-2 px-4 py-2 2xl:px-5 2xl:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-emerald-600/30 whitespace-nowrap">
                <i data-lucide="rotate-ccw" class="size-4 shrink-0"></i>
                <span>Reaktivasi</span>
            </button>
        </div>
    </div>

    {{-- Tabel Data --}}
    <div class="bg-white rounded-2xl border border-border p-4 2xl:p-5 min-w-0">

        {{-- Header Tabel --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">

            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                    <i data-lucide="arrow-right-left" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">Daftar Pegawai Non-Aktif</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-1 sm:mt-0.5 leading-relaxed">Menampilkan data 1 tahun terakhir. Gunakan pencarian dan filter untuk merampingkan data.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Pencarian & Tombol Filter --}}
            <div class="flex items-center gap-2 w-full sm:w-auto" x-data="{ searchQuery: @js($search ?? '') }">

                {{-- INPUT PENCARIAN --}}
                <div class="relative flex-1 sm:w-56 md:w-64 flex items-center">
                    <i data-lucide="search" class="absolute left-3.5 size-4 transition-colors pointer-events-none"
                        :class="searchQuery.length > 0 ? 'text-primary' : 'text-secondary'"></i>

                    <input
                        x-ref="searchInput"
                        type="text"
                        name="search"
                        x-model="searchQuery"
                        placeholder="Cari nama pegawai..."
                        autocomplete="off"
                        hx-get="{{ route('admin.personnel.mutation.index') }}"
                        hx-trigger="input changed delay:400ms, search"
                        hx-sync="this:replace"
                        hx-target="#staff-container"
                        hx-select="#staff-container"
                        hx-swap="outerHTML"
                        hx-include="#mutation-filter-form"
                        hx-push-url="true"
                        class="h-11 w-full bg-white border rounded-xl pl-10 pr-10 text-sm focus:outline-none focus:border-primary transition-all"
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
                        class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary border-2 border-white"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- Wrapper scroll horizontal --}}
        <div class="w-full min-w-0 overflow-x-auto">
            @include('pages.admin.career.transfers.partials._table', compact('staffList'))
        </div>

    </div>

    @include('pages.admin.career.transfers.partials._filter-modal', [
    'year' => $year ?? '',
    'filterStatus' => $filterStatus ?? '',
    'filterEmploymentStatus' => $filterEmploymentStatus ?? '',
    'yearOptions' => $yearOptions ?? [],
    'employmentOptions' => $employmentOptions ?? [],
    ])

    <div id="modal-container"></div>

</div>
@endsection