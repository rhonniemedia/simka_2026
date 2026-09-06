@extends('layouts.main.admin')

@section('title', 'Dokumen Pegawai')
@section('page_title', 'Dokumen Pegawai')
@section('page_subtitle', 'Pantau kelengkapan dan status verifikasi dokumen pegawai')

@section('content')
<div class="px-5 py-8 md:p-8"
    x-data="{
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterCategory) || !empty($filterVerification) || !empty($filterDateFrom) || !empty($filterDateTo)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const category     = document.querySelector('[name=filter_category]')?.value || '';
            const verification = document.querySelector('[name=filter_verification]')?.value || '';
            const dateFrom     = document.querySelector('[name=filter_date_from]')?.value || '';
            const dateTo       = document.querySelector('[name=filter_date_to]')?.value || '';

            this.isFilterActive = (category !== '' || verification !== '' || dateFrom !== '' || dateTo !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Dokumen Pegawai</h1>
            <p class="text-sm text-secondary">Pantau kelengkapan dan status verifikasi dokumen pegawai.</p>
        </div>

        <button type="button"
            hx-get="{{ route('admin.personnel.documents.create') }}"
            hx-target="#modal-container"
            hx-swap="innerHTML"
            title="Tambah Dokumen"
            class="flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap w-full sm:w-auto">
            <i data-lucide="plus" class="size-4 shrink-0"></i>
            <span>Tambah Dokumen</span>
        </button>
    </div>

    {{-- Tabel Data --}}
    <div class="bg-white rounded-2xl border border-border p-5">

        {{-- Header Tabel --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">

            {{-- Bagian Kiri: Ikon & Teks --}}
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                    <i data-lucide="folder-open" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">Daftar Dokumen</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-1 sm:mt-0.5 leading-relaxed">Gunakan fitur pencarian dan filter untuk merampingkan data.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Pencarian & Filter --}}
            <div class="flex items-center gap-2 w-full sm:w-auto" x-data="{ searchQuery: '{{ $search ?? '' }}' }">

                {{-- INPUT PENCARIAN (berdasarkan nama pegawai) --}}
                <div class="relative flex-1 sm:flex-none w-56 md:w-64 flex items-center">
                    <i data-lucide="search" class="absolute left-3.5 size-4 transition-colors pointer-events-none"
                        :class="searchQuery.length > 0 ? 'text-primary' : 'text-secondary'"></i>

                    <input
                        x-ref="searchInput"
                        type="text"
                        name="search"
                        x-model="searchQuery"
                        placeholder="Cari nama pegawai..."
                        autocomplete="off"
                        hx-get="{{ route('admin.personnel.documents.index') }}"
                        hx-trigger="keyup changed delay:400ms, search"
                        hx-target="#documents-container"
                        hx-select="#documents-container"
                        hx-swap="outerHTML"
                        hx-include="#documents-filter-form"
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

        @include('pages.admin.personnel.document.partials._table', compact('documents'))

    </div>

    @include('pages.admin.personnel.document.partials._filter-modal', [
    'filterCategory' => $filterCategory ?? '',
    'filterVerification' => $filterVerification ?? '',
    'filterDateFrom' => $filterDateFrom ?? '',
    'filterDateTo' => $filterDateTo ?? '',
    'categoryOptions' => $categoryOptions ?? [],
    ])

    <div id="modal-container"></div>

</div>
@endsection