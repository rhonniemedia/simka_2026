@extends('layouts.main.admin')

@section('title', 'Mutasi Pegawai')
@section('page_title', 'Mutasi Pegawai')
@section('page_subtitle', 'Kelola perubahan status pegawai: pindah, mengundurkan diri, meninggal, diberhentikan, hingga pensiun')

@section('content')
<div class="w-full min-w-0 max-w-[1600px] mx-auto px-4 py-5 md:px-6 md:py-6 2xl:px-8 2xl:py-8"
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
    {{-- Layout menyamping baru dipakai di xl (>=1280px) karena di 14 inci area konten sudah terpotong sidebar --}}
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
        <div class="flex flex-row items-center justify-between gap-3 mb-4">

            <div class="flex items-center gap-3 min-w-0">
                <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="arrow-right-left" class="size-5 text-primary"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-base sm:text-lg 2xl:text-xl font-bold text-foreground">Daftar Pegawai Non-Aktif</h2>
                    <p class="text-xs 2xl:text-sm text-secondary mt-0.5 leading-snug">Menampilkan data 1 tahun terakhir. Gunakan filter untuk melihat tahun lain.</p>
                </div>
            </div>

            {{-- Bagian Kanan: Filter --}}
            <div class="flex items-center shrink-0">
                <button
                    type="button"
                    @click="filterModalOpen = true"
                    title="Filter"
                    class="relative flex h-10 items-center justify-center gap-2 px-4 shrink-0 rounded-xl border border-border bg-white hover:bg-muted transition-colors cursor-pointer focus:outline-none">
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

        {{-- Wrapper scroll horizontal: tabel tidak menjebol layout di layar sempit --}}
        <div class="w-full min-w-0 overflow-x-auto">
            @include('pages.admin.personnel.transfers.partials._table', compact('staffList'))
        </div>

    </div>

    @include('pages.admin.personnel.transfers.partials._filter-modal', [
    'year' => $year ?? '',
    'filterStatus' => $filterStatus ?? '',
    'yearOptions' => $yearOptions ?? [],
    ])

    <div id="modal-container"></div>

</div>
@endsection