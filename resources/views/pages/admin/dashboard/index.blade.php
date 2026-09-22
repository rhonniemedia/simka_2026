@extends('layouts.main.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Ringkasan data kepegawaian SIMKA')

@section('content')
<div class="w-full px-5 py-6 md:px-8 md:py-8">

    {{-- 1. HEADER SAMBUTAN --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">
                Selamat datang, {{ Auth::user()->username ?? 'Admin' }}
            </h1>
            <p class="text-sm text-secondary leading-relaxed">
                {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </p>
        </div>
    </div>

    {{-- 2. KARTU STATISTIK UTAMA --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="rounded-2xl border border-border bg-white p-4 flex items-center gap-3">
            <span class="size-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <i data-lucide="users" class="size-5"></i>
            </span>
            <div class="min-w-0">
                <p class="text-xl font-bold text-foreground leading-tight">{{ $activeStaffCount }}</p>
                <p class="text-xs text-secondary">Pegawai Aktif</p>
            </div>
        </div>

        <div class="rounded-2xl border border-border bg-white p-4 flex items-center gap-3">
            <span class="size-11 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="briefcase-business" class="size-5"></i>
            </span>
            <div class="min-w-0">
                <p class="text-xl font-bold text-foreground leading-tight">{{ $activePositionCount }}</p>
                <p class="text-xs text-secondary">Jabatan ASN Aktif</p>
            </div>
        </div>

        <div class="rounded-2xl border border-border bg-white p-4 flex items-center gap-3">
            <span class="size-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="arrow-right-left" class="size-5"></i>
            </span>
            <div class="min-w-0">
                <p class="text-xl font-bold text-foreground leading-tight">{{ $mutationThisYearCount }}</p>
                <p class="text-xs text-secondary">Mutasi Tahun {{ now()->year }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-border bg-white p-4 flex items-center gap-3">
            <span class="size-11 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                <i data-lucide="user-minus" class="size-5"></i>
            </span>
            <div class="min-w-0">
                <p class="text-xl font-bold text-foreground leading-tight">{{ $retirementSoonCount }}</p>
                <p class="text-xs text-secondary">Segera Pensiun</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- 3. AKTIVITAS TERBARU --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-border p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="size-10 sm:size-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="history" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-foreground">Aktivitas Terbaru</h2>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5">Perubahan status pegawai terakhir (mutasi, reaktivasi, pensiun)</p>
                </div>
            </div>

            <div class="divide-y divide-border">
                @forelse ($recentActivities as $activity)
                <div class="py-3 flex items-center gap-3">
                    <div class="shrink-0">
                        <x-ui.avatar :name="$activity->staff?->name ?? '-'" :gender="$activity->staff?->gender" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-foreground truncate">{{ $activity->staff?->name ?? 'Pegawai tidak ditemukan' }}</p>
                        <p class="text-xs text-secondary mt-0.5">
                            {{ \App\Http\Controllers\Admin\Personnel\TransferController::STATUS_LABELS[$activity->from_status] ?? $activity->from_status }}
                            <i data-lucide="arrow-right" class="inline size-3"></i>
                            <span class="font-medium text-foreground">{{ \App\Http\Controllers\Admin\Personnel\TransferController::STATUS_LABELS[$activity->to_status] ?? $activity->to_status }}</span>
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs font-medium text-foreground">{{ $activity->effective_date?->translatedFormat('d M Y') }}</p>
                        <p class="text-[11px] text-secondary mt-0.5">{{ $activity->creator?->username ?? '-' }}</p>
                    </div>
                </div>
                @empty
                <div class="py-16 text-center text-secondary">
                    <div class="flex flex-col items-center gap-3">
                        <i data-lucide="inbox" class="size-10 text-border"></i>
                        <p class="font-medium text-sm">Belum ada aktivitas tercatat</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-6">

            {{-- 4. RINGKASAN STATUS KEPEGAWAIAN --}}
            <div class="bg-white rounded-2xl border border-border p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                        <i data-lucide="pie-chart" class="size-5 text-primary"></i>
                    </div>
                    <h2 class="text-base font-bold text-foreground">Status Kepegawaian</h2>
                </div>

                <div class="space-y-3">
                    @forelse ($employmentBreakdown as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-foreground">{{ $item->name }}</span>
                        <span class="text-sm font-bold text-foreground">{{ $item->total }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-secondary/70">Belum ada data</p>
                    @endforelse
                </div>
            </div>

            {{-- 5. MENU CEPAT --}}
            <div class="bg-white rounded-2xl border border-border p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                        <i data-lucide="layout-grid" class="size-5 text-primary"></i>
                    </div>
                    <h2 class="text-base font-bold text-foreground">Menu Cepat</h2>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('admin.personnel.data.index') }}"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-border hover:bg-muted transition-colors text-center">
                        <i data-lucide="users" class="size-5 text-primary"></i>
                        <span class="text-xs font-semibold text-foreground">Data Pegawai</span>
                    </a>
                    <a href="{{ route('admin.personnel.positions.index') }}"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-border hover:bg-muted transition-colors text-center">
                        <i data-lucide="briefcase-business" class="size-5 text-primary"></i>
                        <span class="text-xs font-semibold text-foreground">Jabatan ASN</span>
                    </a>
                    <a href="{{ route('admin.personnel.mutation.index') }}"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-border hover:bg-muted transition-colors text-center">
                        <i data-lucide="arrow-right-left" class="size-5 text-primary"></i>
                        <span class="text-xs font-semibold text-foreground">Mutasi</span>
                    </a>
                    <a href="{{ route('admin.personnel.retirement.index') }}"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-border hover:bg-muted transition-colors text-center">
                        <i data-lucide="user-minus" class="size-5 text-primary"></i>
                        <span class="text-xs font-semibold text-foreground">Pensiun</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection