@extends('layouts.main.admin')

@section('title', 'Dokumen Pegawai')
@section('page_title', 'Dokumen Pegawai')
@section('page_subtitle', 'Pantau kelengkapan dan status verifikasi dokumen pegawai')

@section('content')
<div class="px-5 py-8 md:p-8"
    x-data="{
        viewMode: '{{ $viewMode }}',

        // Mode tampilan mengikuti server: dibaca dari atribut data-view pada #documents-container
        // setiap kali isinya diganti (termasuk saat back/forward), bukan dari state sendiri.
        syncView() {
            const el = document.getElementById('documents-container');
            if (el?.dataset.view) this.viewMode = el.dataset.view;
        }
    }"
    x-init="syncView()"
    @htmx:after-swap.window="syncView()">

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

            {{-- Bagian Kanan: Switch Tampilan, Pencarian & Filter (komponen bersama) --}}
            @include('pages.admin.administration.documents.partials._filter-bar', [
            'actionUrl' => route('admin.personnel.documents.index'),
            'showViewSwitch' => true,
            'searchPlaceholder' => 'Cari nama pegawai...',
            ])

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

    <div id="modal-container"></div>

</div>
@endsection