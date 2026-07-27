@extends('layouts.main.admin')

@section('title', 'Pendidikan Pegawai')
@section('page_title', 'Pendidikan Pegawai')
@section('page_subtitle', 'Kelola basis data riwayat pendidikan staf dan pegawai secara menyeluruh')

@section('content')
<div class="p-8"
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
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Pendidikan Pegawai</h1>
            <p class="text-sm text-secondary">Kelola basis data riwayat pendidikan staf dan pegawai secara menyeluruh.</p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
            <button type="button"
                title="Tambah Data Pegawai"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap">
                <i data-lucide="plus" class="size-4 shrink-0"></i>
                <span>Tambah Data</span>
            </button>

            <button type="button"
                title="Download data Excel"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-emerald-600/30 whitespace-nowrap">
                <i data-lucide="file-box" class="size-4 shrink-0"></i>
                <span>Download</span>
            </button>

            <button type="button"
                title="Cetak laporan"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-blue-600/30 whitespace-nowrap">
                <i data-lucide="printer" class="size-4 shrink-0"></i>
                <span>Laporan</span>
            </button>

            <a href="{{ route('admin.staff.data.index') }}"
                title="Segarkan halaman"
                onclick="document.getElementById('refresh-icon').classList.add('animate-spin');"
                class="flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 ring-1 ring-border hover:ring-primary rounded-full text-foreground font-semibold text-sm transition-all bg-white cursor-pointer whitespace-nowrap">
                <i id="refresh-icon" data-lucide="refresh-cw" class="size-4 shrink-0"></i>
                <span>Segarkan</span>
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    @include('pages.admin.staff.data.partials._stats-cards', [
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
            <div>
                <h2 class="text-lg font-bold text-foreground">Daftar Pegawai</h2>
                <p class="text-sm text-secondary mt-1">Gunakan fitur pencarian dan filter untuk merampingkan data.</p>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-secondary"></i>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Cari pegawai..."
                        hx-get="{{ route('admin.staff.data.index') }}"
                        hx-trigger="keyup changed delay:400ms, search"
                        hx-target="#staff-container"
                        hx-select="#staff-container"
                        hx-swap="outerHTML"
                        hx-include="#staff-filter-form"
                        hx-push-url="true"
                        class="h-11 w-full sm:w-56 md:w-64 bg-white border border-border rounded-xl pl-10 pr-4 text-sm focus:outline-none focus:border-primary transition-all">
                </div>

                <button
                    type="button"
                    @click="filterModalOpen = true"
                    title="Filter"
                    class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-white hover:bg-muted transition-colors cursor-pointer focus:outline-none">
                    <i data-lucide="filter" class="size-4 text-secondary"></i>

                    {{-- Container untuk Titik Merah --}}
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

        @include('pages.admin.staff.data.partials._table', compact('staff'))

    </div>

    @include('pages.admin.staff.data.partials._filter-modal', [
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