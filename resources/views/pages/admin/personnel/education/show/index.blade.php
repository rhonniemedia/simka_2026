@extends('layouts.main.admin')
@section('title', 'Detail Pendidikan: ' . $staff->name)
@section('page_title', 'Riwayat Pendidikan Pegawai')
@section('page_subtitle', 'Detail riwayat pendidikan untuk ' . $staff->name)
@section('content')
<div class="px-5 py-8 md:p-8">
    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Riwayat Pendidikan</h1>
            <p class="text-sm text-secondary">Kelola riwayat pendidikan untuk <span class="font-bold text-foreground">{{ $staff->name }}</span>.</p>
        </div>
        {{-- Urutan tombol: Tambah Data di kiri, Tombol Back di kanan --}}
        <div class="flex items-center gap-2 sm:gap-3 w-full md:w-auto mt-2 md:mt-0">
            {{-- Tombol Tambah --}}
            <button type="button"
                title="Tambah Pendidikan"
                class="flex flex-1 md:flex-none items-center justify-center gap-2 px-3 py-2.5 sm:px-5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap h-[42px]">
                <i data-lucide="plus" class="size-4 shrink-0"></i>
                <span>Tambah Data</span>
            </button>
            {{-- Tombol Kembali (Berada di sebelah kanan tombol tambah, bentuk lingkaran, hover hijau) --}}
            <a href="{{ route('admin.personnel.education.index') }}"
                title="Kembali"
                class="flex items-center justify-center size-[42px] shrink-0 rounded-full bg-white border border-border text-secondary hover:text-green-500 hover:border-green-500 transition-colors duration-300 cursor-pointer">
                <i data-lucide="arrow-left" class="size-4 sm:size-5"></i>
            </a>
        </div>
    </div>

    {{-- 2. PROFIL SINGKAT PEGAWAI (Card dengan warna berbeda - gradient premium) --}}
    <div class="bg-gradient-to-br from-primary/5 via-slate-50/50 to-white rounded-2xl border border-primary/15 p-5 mb-6">
        <div class="flex items-center gap-4">
            {{-- Avatar --}}
            <div class="shrink-0">
                <x-ui.avatar :name="$staff->name" :gender="$staff->gender" />
            </div>
            {{-- Info Pegawai --}}
            <div class="min-w-0 flex-1">
                <h2 class="text-base sm:text-lg font-bold text-foreground uppercase truncate">
                    {{ $staff->name }}
                </h2>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mt-2 text-sm text-secondary">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <i data-lucide="credit-card" class="size-3.5 shrink-0 text-primary/70"></i>
                        <span class="truncate">
                            <span class="hidden sm:inline">NIK:</span>
                            <span class="font-medium text-foreground/80">{{ $staff->vault?->nik ?? '-' }}</span>
                        </span>
                    </span>
                    <span class="hidden sm:block size-1 rounded-full bg-primary/30"></span>
                    <span class="flex items-center gap-1.5 min-w-0">
                        <i data-lucide="briefcase" class="size-3.5 shrink-0 text-primary/70"></i>
                        <span class="truncate">{{ $staff->employmentStatus?->name ?? '-' }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. TABEL RIWAYAT PENDIDIKAN --}}
    <div class="bg-white rounded-2xl border border-border p-5">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-foreground">Daftar Riwayat Pendidikan</h2>
        </div>
        @include('pages.admin.personnel.education.show.partials._table', compact('staff'))
    </div>
</div>
@endsection