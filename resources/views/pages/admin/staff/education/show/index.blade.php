@extends('layouts.main.admin')

@section('title', 'Detail Pendidikan: ' . $staff->name)
@section('page_title', 'Riwayat Pendidikan Pegawai')
@section('page_subtitle', 'Detail riwayat pendidikan untuk ' . $staff->name)

@section('content')
<div class="p-8">

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.staff.education.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-secondary hover:text-primary transition-colors mb-2">
                <i data-lucide="arrow-left" class="size-4"></i>
                Kembali ke Daftar
            </a>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Riwayat Pendidikan</h1>
            <p class="text-sm text-secondary">Kelola riwayat pendidikan untuk <span class="font-bold text-foreground">{{ $staff->name }}</span>.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                title="Tambah Pendidikan"
                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-primary/30 whitespace-nowrap">
                <i data-lucide="plus" class="size-4 shrink-0"></i>
                <span>Tambah Data</span>
            </button>
        </div>
    </div>

    {{-- 2. PROFIL SINGKAT PEGAWAI --}}
    <div class="bg-white rounded-2xl border border-border p-5 mb-6 flex items-center gap-4">
        <x-ui.avatar :name="$staff->name" :gender="$staff->gender" />
        <div>
            <h2 class="text-lg font-bold text-foreground uppercase">{{ $staff->name }}</h2>
            <div class="flex items-center gap-4 text-sm text-secondary mt-1">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="credit-card" class="size-3.5"></i> NIK: {{ $staff->vault?->nik ?? '-' }}
                </span>
                <span class="flex items-center gap-1.5">
                    <i data-lucide="briefcase" class="size-3.5"></i> {{ $staff->employmentStatus?->name ?? '-' }}
                </span>
            </div>
        </div>
    </div>

    {{-- 3. TABEL RIWAYAT PENDIDIKAN --}}
    <div class="bg-white rounded-2xl border border-border p-5">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-foreground">Daftar Riwayat Pendidikan</h2>
        </div>

        @include('pages.admin.staff.education.show.partials._table', compact('staff'))
    </div>

</div>
@endsection