@extends('layouts.main.admin')

@section('title', 'Kenaikan Gaji Berkala')
@section('page_title', 'Kenaikan Gaji Berkala')
@section('page_subtitle', 'Kelola riwayat kenaikan gaji berkala (KGB) staf dan pegawai')

@section('content')
{{-- PERBAIKAN: Mengubah px-4 menjadi px-5, dan md:p-8 menjadi md:px-8 md:py-8, ditambah w-full agar 100% presisi sejajar dengan Topbar --}}
<div class="w-full px-5 py-6 md:px-8 md:py-8"
    x-data="{ 
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterStatus) || !empty($filterYear)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const status = document.querySelector('[name=filter_verification_status]')?.value || '';
            const year = document.querySelector('[name=filter_year]')?.value || '';
            
            this.isFilterActive = (status !== '' || year !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Kenaikan Gaji Berkala</h1>
            <p class="text-sm text-secondary leading-relaxed">Kelola riwayat kenaikan gaji berkala (KGB) staf dan pegawai secara menyeluruh.</p>
        </div>

        {{-- Grup Tombol Aksi --}}
        <div class="flex items-center gap-2">
            {{-- Cetak PDF (dropdown pilih periode) --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open"
                    class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 ring-1 ring-border hover:ring-primary rounded-full text-foreground font-semibold text-sm transition-all bg-white cursor-pointer whitespace-nowrap">
                    <i data-lucide="printer" class="size-4 shrink-0"></i>
                    <span>Cetak PDF</span>
                </button>
                <form x-show="open" x-cloak x-transition
                    method="GET"
                    action="{{ route('admin.personnel.periodic-salary.pdf') }}"
                    target="_blank"
                    @submit="open = false"
                    class="absolute right-0 z-20 mt-2 w-64 rounded-2xl border border-border bg-white p-4 shadow-lg">
                    <label for="pdf-year" class="block text-xs font-semibold text-secondary mb-1.5">Periode (tahun TMT)</label>
                    <select id="pdf-year" name="year"
                        class="h-11 w-full rounded-xl border border-border bg-white px-3 text-sm text-foreground focus:outline-none focus:border-primary">
                        <option value="">Semua tahun</option>
                        @foreach ($yearOptions ?? [] as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    <div class="mt-3 flex gap-2">
                        <button type="submit"
                            class="flex-1 h-10 rounded-xl bg-primary text-white text-sm font-semibold cursor-pointer">
                            Tampilkan
                        </button>
                        <button type="submit" name="download" value="1"
                            class="h-10 px-3 rounded-xl ring-1 ring-border hover:ring-primary text-sm font-semibold text-foreground cursor-pointer">
                            Unduh
                        </button>
                    </div>
                </form>
            </div>

            {{-- Segarkan --}}
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

            {{-- Bagian Kiri: Ikon & Teks --}}
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                    <i data-lucide="wallet" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">Riwayat KGB</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-1 sm:mt-0.5 leading-relaxed">Gunakan fitur pencarian dan filter untuk merampingkan data.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Pencarian & Filter --}}
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
                        placeholder="Cari pegawai / No. SK..."
                        autocomplete="off"
                        hx-get="{{ route('admin.personnel.periodic-salary.index') }}"
                        hx-trigger="keyup changed delay:400ms, search"
                        hx-target="#periodic-salary-container"
                        hx-select="#periodic-salary-container"
                        hx-swap="outerHTML"
                        hx-include="#salary-filter-form"
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

        @include('pages.admin.career.salaries.partials._table', compact('histories'))

    </div>

    @include('pages.admin.career.salaries.partials._filter-modal', [
    'filterVerificationStatus' => $filterVerificationStatus ?? '',
    'filterYear' => $filterYear ?? '',
    ])

    <div id="modal-container"></div>

</div>
@endsection