@extends('layouts.main.admin')

@section('title', 'Riwayat Kepangkatan')
@section('page_title', 'Riwayat Kepangkatan')
@section('page_subtitle', 'Kelola data riwayat kepangkatan dan golongan pegawai')

@section('content')
<div class="w-full px-5 py-6 md:px-8 md:py-8"
    x-data="{ 
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterGradeId) || !empty($filterYear)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const gradeId = document.querySelector('[name=filter_grade_id]')?.value || '';
            const year = document.querySelector('[name=filter_year]')?.value || '';
            this.isFilterActive = (gradeId !== '' || year !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Riwayat Kepangkatan</h1>
            <p class="text-sm text-secondary leading-relaxed">Kelola data riwayat kepangkatan dan golongan pegawai secara menyeluruh.</p>
        </div>

        {{-- Grup Tombol Aksi --}}
        <div class="flex items-center">
            <a href="{{ route('admin.personnel.periodic-salary.index') }}"
                title="Segarkan halaman"
                onclick="document.getElementById('refresh-icon').classList.add('animate-spin');"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 ring-1 ring-border hover:ring-primary rounded-full text-foreground font-semibold text-sm transition-all bg-white cursor-pointer whitespace-nowrap">
                <i id="refresh-icon" data-lucide="refresh-cw" class="size-4 shrink-0"></i>
                <span>Segarkan</span>
            </a>
        </div>
    </div>

    {{-- Tabel Data --}}
    <div class="bg-white rounded-2xl border border-border p-5">

        {{-- Header Tabel --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                    <i data-lucide="user-cog" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">Data Kepangkatan</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-1 sm:mt-0.5 leading-relaxed">Gunakan fitur pencarian untuk merampingkan data.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Pencarian & Tombol Filter --}}
            <div class="flex items-center gap-2 w-full sm:w-auto" x-data="{ searchQuery: '{{ $search ?? '' }}' }">

                {{-- INPUT PENCARIAN --}}
                <div class="relative flex-1 sm:w-56 md:w-64 flex items-center">
                    <i data-lucide="search" class="absolute left-3.5 size-4 transition-colors pointer-events-none"
                        :class="searchQuery.length > 0 ? 'text-primary' : 'text-secondary'"></i>

                    <input
                        x-ref="searchInput"
                        type="text"
                        name="search"
                        x-model="searchQuery"
                        placeholder="Cari pegawai / golongan..."
                        autocomplete="off"
                        hx-get="{{ route('admin.personnel.promotions.index') }}"
                        hx-trigger="keyup changed delay:400ms, search"
                        hx-target="#promotions-container"
                        hx-select="#promotions-container"
                        hx-swap="outerHTML"
                        hx-include="#promotion-filter-form"
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

                {{-- TOMBOL FILTER (Menyerupai Referensi) --}}
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

        @include('pages.admin.career.grades.partials._table', compact('histories'))

    </div>

    {{-- MODAL FILTER --}}
    @include('pages.admin.career.grades.partials._filter-modal')

    <div id="modal-container"></div>
</div>
@endsection