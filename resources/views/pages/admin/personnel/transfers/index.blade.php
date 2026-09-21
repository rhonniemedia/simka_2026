@extends('layouts.main.admin')

@section('title', 'Mutasi Pegawai')
@section('page_title', 'Mutasi Pegawai')
@section('page_subtitle', 'Kelola perubahan status pegawai: pindah, mengundurkan diri, meninggal, diberhentikan, hingga pensiun')

@section('content')
<div class="w-full px-5 py-6 md:px-8 md:py-8"
    x-data="{
        filterModalOpen: false,
        isFilterActive: {{ (!empty($filterStatus)) ? 'true' : 'false' }},
        checkFilterStatus() {
            const status = document.querySelector('[name=filter_status]')?.value || '';
            this.isFilterActive = (status !== '');
        }
    }"
    @htmx:after-request.document="checkFilterStatus()">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Mutasi Pegawai</h1>
            <p class="text-sm text-secondary leading-relaxed">Kelola perubahan status dan riwayat mutasi pegawai, termasuk perpindahan, pengunduran diri, pemberhentian, dan pengaktifan kembali.</p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
            <button type="button"
                hx-get="{{ route('admin.personnel.mutation.create') }}"
                hx-target="#modal-container" hx-swap="innerHTML"
                title="Mutasi Pegawai"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap">
                <i data-lucide="arrow-right-left" class="size-4 shrink-0"></i>
                <span>Mutasi</span>
            </button>

            <button type="button"
                hx-get="{{ route('admin.personnel.mutation.create-reaktivasi') }}"
                hx-target="#modal-container" hx-swap="innerHTML"
                title="Aktifkan Kembali Pegawai"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-emerald-600/30 whitespace-nowrap">
                <i data-lucide="rotate-ccw" class="size-4 shrink-0"></i>
                <span>Reaktivasi</span>
            </button>
        </div>
    </div>

    {{-- Tabel Data --}}
    <div class="bg-white rounded-2xl border border-border p-5">

        {{-- Header Tabel --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">

            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                    <i data-lucide="arrow-right-left" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">Daftar Pegawai Non-Aktif</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-1 sm:mt-0.5 leading-relaxed">Menampilkan data 1 tahun terakhir. Gunakan filter untuk melihat tahun lain.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Pencarian & Filter --}}
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button
                    type="button"
                    @click="filterModalOpen = true"
                    title="Filter"
                    class="relative flex h-11 items-center justify-center gap-2 px-4 shrink-0 rounded-xl border border-border bg-white hover:bg-muted transition-colors cursor-pointer focus:outline-none">
                    <i data-lucide="filter" class="size-4 text-secondary"></i>
                    <span class="text-sm font-semibold text-foreground">Filter</span>
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

        @include('pages.admin.personnel.transfers.partials._table', compact('staffList'))

    </div>

    @include('pages.admin.personnel.transfers.partials._filter-modal', [
    'year' => $year ?? '',
    'filterStatus' => $filterStatus ?? '',
    'yearOptions' => $yearOptions ?? [],
    ])

    <div id="modal-container"></div>

</div>
@endsection