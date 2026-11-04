@extends('layouts.main.admin')

@section('title', 'Dokumen Pegawai')
@section('page_title', 'Dokumen Pegawai')
@section('page_subtitle', 'Pantau kelengkapan dan status verifikasi dokumen pegawai')

@section('content')
<div class="px-5 py-8 md:p-8"
    x-data="{
        viewMode: '{{ request('view', 'all') }}',
        viewDropdownOpen: false,
        searchQuery: '{{ $search ?? '' }}',
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterCategory) || !empty($filterVerification) || !empty($filterLink) || !empty($filterDateFrom) || !empty($filterDateTo)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const category     = document.querySelector('[name=filter_category]')?.value || '';
            const verification = document.querySelector('[name=filter_verification]')?.value || '';
            const link         = document.querySelector('[name=filter_link]')?.value || '';
            const dateFrom     = document.querySelector('[name=filter_date_from]')?.value || '';
            const dateTo       = document.querySelector('[name=filter_date_to]')?.value || '';

            this.isFilterActive = (category !== '' || verification !== '' || link !== '' || dateFrom !== '' || dateTo !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Dokumen Pegawai</h1>
            <p class="text-sm text-secondary">Pantau kelengkapan dan status verifikasi dokumen pegawai.</p>
        </div>

        {{-- Tombol Tambah dikontrol instan oleh Alpine.js via x-show="viewMode === 'all'" --}}
        <button type="button"
            x-show="viewMode === 'all'"
            x-cloak
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

            {{-- Bagian Kanan: Switch Tampilan, Pencarian & Filter --}}
            <div class="flex items-center gap-2 w-full sm:w-auto">

                {{-- SWITCH TAMPILAN (DROPDOWN) --}}
                <div class="relative" @click.away="viewDropdownOpen = false">

                    {{-- Tombol Pemicu Dropdown --}}
                    <button type="button"
                        @click="viewDropdownOpen = !viewDropdownOpen"
                        class="flex items-center justify-between gap-3 px-3.5 h-11 rounded-xl bg-white border border-border text-sm font-semibold text-foreground hover:bg-muted transition-all cursor-pointer focus:outline-none focus:border-primary">

                        <div class="flex items-center gap-2">
                            <i data-lucide="files" x-show="viewMode === 'all'" class="size-4 text-primary shrink-0"></i>
                            <i data-lucide="users" x-show="viewMode === 'staff'" class="size-4 text-primary shrink-0" x-cloak></i>

                            <span x-text="viewMode === 'all' ? 'Semua Dokumen' : 'Per Pegawai'"></span>
                        </div>

                        <i data-lucide="chevron-down" class="size-4 text-secondary transition-transform duration-200" :class="viewDropdownOpen ? 'rotate-180' : ''"></i>
                    </button>

                    {{-- Panel Menu Dropdown --}}
                    <div x-show="viewDropdownOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                        class="absolute left-0 sm:left-auto sm:right-0 mt-2 w-52 rounded-xl bg-white border border-border shadow-lg p-1.5 z-50 flex flex-col gap-1">

                        {{-- Opsi: Semua Dokumen --}}
                        <button type="button"
                            title="Semua Dokumen"
                            hx-get="{{ route('admin.personnel.documents.index') }}"
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

                        {{-- Opsi: Per Pegawai --}}
                        <button type="button"
                            title="Per Pegawai"
                            hx-get="{{ route('admin.personnel.documents.index') }}"
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

                {{-- INPUT PENCARIAN --}}
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
                        class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary border-2 border-white"></span>
                    </span>
                </button>
            </div>

            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
                document.addEventListener('htmx:afterSwap', () => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            </script>
            @endpush
        </div>

        {{-- Partial tabel dinamis tergantung mode tampilan --}}
        @if (($viewMode ?? 'all') === 'staff')
        @include('pages.admin.administration.documents.partials._staff-list', ['staffList' => $staffList])
        @else
        @include('pages.admin.administration.documents.partials._table', compact('documents'))
        @endif

    </div>

    {{-- Modal Filter --}}
    @include('pages.admin.administration.documents.partials._filter-modal', [
    'filterCategory' => $filterCategory ?? '',
    'filterVerification' => $filterVerification ?? '',
    'filterLink' => $filterLink ?? '',
    'filterDateFrom' => $filterDateFrom ?? '',
    'filterDateTo' => $filterDateTo ?? '',
    'categoryOptions' => $categoryOptions ?? [],
    ])

    <div id="modal-container"></div>

</div>
@endsection