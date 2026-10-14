@extends('layouts.main.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Ringkasan data kepegawaian SIMKA')


@section('content')
@php
/*
|--------------------------------------------------------------------------
| Helper tampilan
|--------------------------------------------------------------------------
*/

// Total untuk persentase bar status kepegawaian
$employmentTotal = max(1, (int) $employmentBreakdown->sum('total'));
$employmentColors = ['bg-primary', 'bg-blue-500', 'bg-amber-400', 'bg-emerald-500', 'bg-violet-500', 'bg-slate-300'];

// Hitung sisa waktu menuju tanggal jatuh tempo + warna sesuai urgensi
$countdown = function ($date) {
$due = \Illuminate\Support\Carbon::parse($date)->startOfDay();
$days = (int) round(now()->startOfDay()->diffInDays($due, false));

$red = 'bg-red-50 text-red-600 ring-red-100';
$amber = 'bg-amber-50 text-amber-700 ring-amber-100';
$calm = 'bg-emerald-50 text-emerald-700 ring-emerald-100';

if ($days < 0) return ['Terlewat ' . abs($days) . ' hari', $red];
    if ($days===0) return ['Hari ini', $red];
    if ($days <=30) return [$days . ' hari lagi' , $red];
    if ($days <=90) return [floor($days / 30) . ' bulan lagi' , $amber];
    if ($days < 365) return [floor($days / 30) . ' bulan lagi' , $calm];

    return [floor($days / 365) . ' tahun lagi' , $calm];
    };

    // Konfigurasi dua daftar tunggu (dirender dengan satu template)
    $waitlists=[
    [ 'title'=> 'Daftar Tunggu Pensiun',
    'subtitle' => 'Pegawai yang paling dekat memasuki masa pensiun',
    'icon' => 'user-minus',
    'tile' => 'bg-red-50 text-red-600',
    'dateLabel'=> 'Tanggal pensiun',
    'items' => $retirementWaitlist ?? collect(),
    'url' => route('admin.personnel.retirement.index'),
    'empty' => 'Belum ada pegawai dalam daftar tunggu pensiun',
    ],
    [
    'title' => 'Daftar Tunggu Berkala',
    'subtitle' => 'Pegawai yang akan menerima kenaikan gaji berkala',
    'icon' => 'calendar-clock',
    'tile' => 'bg-blue-50 text-blue-600',
    'dateLabel'=> 'Jatuh tempo',
    'items' => $periodicWaitlist ?? collect(),
    // Ganti nama route jika berbeda di proyek Anda
    'url' => \Illuminate\Support\Facades\Route::has('admin.personnel.periodic.index')
    ? route('admin.personnel.periodic.index')
    : '#',
    'empty' => 'Belum ada pegawai dalam daftar tunggu berkala',
    ],
    ];
    @endphp
    <div class="w-full px-5 py-6 md:px-8 md:py-8 space-y-6">

        {{-- 1. HERO SAMBUTAN --}}
        <section class="relative overflow-hidden rounded-3xl bg-primary text-white px-6 py-7 md:px-10 md:py-9">
            {{-- dekorasi cincin --}}
            <span class="pointer-events-none absolute -right-16 -top-24 size-72 rounded-full border-[28px] border-white/10"></span>
            <span class="pointer-events-none absolute right-24 -bottom-28 size-64 rounded-full border-[22px] border-white/10"></span>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="max-w-xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur">
                        <i data-lucide="calendar" class="size-3.5"></i>
                        {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                    </span>

                    <h1 class="mt-4 text-2xl md:text-4xl font-bold tracking-tight leading-tight">
                        Selamat datang, {{ Auth::user()->username ?? 'Admin' }}
                    </h1>

                    <p class="mt-2 text-sm md:text-base text-white/85 leading-relaxed">
                        @if ($retirementSoonCount > 0)
                        Ada <span class="font-semibold text-white">{{ $retirementSoonCount }} pegawai</span> yang mendekati masa pensiun.
                        Cek daftar tunggu di bawah untuk menyiapkan prosesnya.
                        @else
                        Belum ada pegawai yang mendekati masa pensiun. Semua data kepegawaian terpantau aman.
                        @endif
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 md:flex md:items-center md:gap-3">
                    <a href="{{ route('admin.personnel.data.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-bold text-foreground transition hover:bg-white/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        <i data-lucide="users" class="size-4"></i>
                        Data Pegawai
                    </a>
                    <a href="{{ route('admin.personnel.mutation.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-white/15 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        <i data-lucide="arrow-right-left" class="size-4"></i>
                        Catat Mutasi
                    </a>
                </div>
            </div>
        </section>

        {{-- 2. KARTU STATISTIK --}}
        @php
        $stats = [
        ['label' => 'Pegawai Aktif', 'value' => $activeStaffCount, 'icon' => 'users', 'tile' => 'bg-emerald-50 text-emerald-600', 'url' => route('admin.personnel.data.index')],
        ['label' => 'Jabatan ASN Aktif', 'value' => $activePositionCount, 'icon' => 'briefcase-business', 'tile' => 'bg-blue-50 text-blue-600', 'url' => route('admin.personnel.positions.index')],
        ['label' => 'Mutasi Tahun ' . now()->year, 'value' => $mutationThisYearCount, 'icon' => 'arrow-right-left', 'tile' => 'bg-amber-50 text-amber-600', 'url' => route('admin.personnel.mutation.index')],
        ['label' => 'Segera Pensiun', 'value' => $retirementSoonCount, 'icon' => 'user-minus', 'tile' => 'bg-red-50 text-red-600', 'url' => route('admin.personnel.retirement.index')],
        ];
        @endphp

        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
            @foreach ($stats as $stat)
            <a href="{{ $stat['url'] }}"
                class="group flex flex-col gap-4 rounded-2xl border border-border bg-white p-5 transition-all duration-300 hover:border-primary/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary">
                <div class="flex items-center justify-between">
                    <span class="size-11 rounded-xl {{ $stat['tile'] }} flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $stat['icon'] }}" class="size-5"></i>
                    </span>
                    <i data-lucide="arrow-up-right" class="size-4 text-secondary opacity-0 -translate-x-1 translate-y-1 transition-all duration-300 group-hover:opacity-100 group-hover:translate-x-0 group-hover:translate-y-0"></i>
                </div>
                <div>
                    <p class="text-3xl md:text-[32px] font-bold leading-none tracking-tight text-foreground tabular-nums">{{ number_format($stat['value'], 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm font-medium text-secondary">{{ $stat['label'] }}</p>
                </div>
            </a>
            @endforeach
        </section>


        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- 3. AKTIVITAS TERBARU (TIMELINE) --}}
            <div class="lg:col-span-2 rounded-2xl border border-border bg-white p-5 md:p-6">
                <div class="flex items-center gap-3 mb-6">
                    <span class="size-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <i data-lucide="history" class="size-5"></i>
                    </span>
                    <div>
                        <h2 class="text-base md:text-lg font-bold text-foreground">Aktivitas Terbaru</h2>
                        <p class="text-xs sm:text-sm text-secondary mt-0.5">Perubahan status pegawai terakhir (mutasi, reaktivasi, pensiun)</p>
                    </div>
                </div>

                @if ($recentActivities->isNotEmpty())
                <ol class="relative space-y-1 before:absolute before:left-[21px] before:top-4 before:bottom-4 before:w-px before:bg-border">
                    @foreach ($recentActivities as $activity)
                    <li class="relative flex items-start gap-4 rounded-xl py-3 pr-2 transition-colors hover:bg-muted/50">
                        <div class="relative z-10 shrink-0 rounded-full bg-white p-0.5">
                            <x-ui.avatar :name="$activity->staff?->name ?? '-'" :gender="$activity->staff?->gender" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-foreground">{{ $activity->staff?->name ?? 'Pegawai tidak ditemukan' }}</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs">
                                <span class="rounded-md bg-muted px-2 py-0.5 text-secondary">
                                    {{ \App\Http\Controllers\Admin\Personnel\TransferController::STATUS_LABELS[$activity->from_status] ?? $activity->from_status }}
                                </span>
                                <i data-lucide="arrow-right" class="size-3 text-secondary"></i>
                                <span class="rounded-md bg-primary/10 px-2 py-0.5 font-semibold text-primary">
                                    {{ \App\Http\Controllers\Admin\Personnel\TransferController::STATUS_LABELS[$activity->to_status] ?? $activity->to_status }}
                                </span>
                            </div>
                        </div>

                        <div class="shrink-0 text-right">
                            <p class="text-xs font-medium text-foreground">{{ $activity->effective_date?->translatedFormat('d M Y') }}</p>
                            <p class="mt-0.5 text-[11px] text-secondary">oleh {{ $activity->creator?->username ?? '-' }}</p>
                        </div>
                    </li>
                    @endforeach
                </ol>
                @else
                <div class="py-16 text-center text-secondary">
                    <div class="flex flex-col items-center gap-3">
                        <i data-lucide="inbox" class="size-10 text-border"></i>
                        <p class="font-medium text-sm">Belum ada aktivitas tercatat</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="flex flex-col gap-6">

                {{-- 4. STATUS KEPEGAWAIAN --}}
                <div class="rounded-2xl border border-border bg-white p-5 md:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="size-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="pie-chart" class="size-5"></i>
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-foreground">Status Kepegawaian</h2>
                            <p class="text-xs text-secondary mt-0.5">{{ number_format($employmentBreakdown->sum('total'), 0, ',', '.') }} pegawai tercatat</p>
                        </div>
                    </div>

                    @if ($employmentBreakdown->isNotEmpty())
                    {{-- bar proporsi --}}
                    <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-muted gap-0.5" role="img" aria-label="Proporsi status kepegawaian">
                        @foreach ($employmentBreakdown as $item)
                        <span class="{{ $employmentColors[$loop->index % count($employmentColors)] }} first:rounded-l-full last:rounded-r-full"
                            style="width: {{ round(($item->total / $employmentTotal) * 100, 2) }}%"></span>
                        @endforeach
                    </div>

                    <ul class="mt-5 space-y-3">
                        @foreach ($employmentBreakdown as $item)
                        <li class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-2.5 min-w-0">
                                <span class="size-2.5 shrink-0 rounded-full {{ $employmentColors[$loop->index % count($employmentColors)] }}"></span>
                                <span class="truncate text-sm text-foreground">{{ $item->name }}</span>
                            </span>
                            <span class="flex items-baseline gap-2 shrink-0 tabular-nums">
                                <span class="text-sm font-bold text-foreground">{{ number_format($item->total, 0, ',', '.') }}</span>
                                <span class="w-10 text-right text-xs text-secondary">{{ round(($item->total / $employmentTotal) * 100) }}%</span>
                            </span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-sm text-secondary/70">Belum ada data</p>
                    @endif
                </div>

                {{-- 5. MENU CEPAT --}}
                <div class="rounded-2xl border border-border bg-white p-5 md:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="size-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="layout-grid" class="size-5"></i>
                        </span>
                        <h2 class="text-base font-bold text-foreground">Menu Cepat</h2>
                    </div>

                    @php
                    $shortcuts = [
                    ['Data Pegawai', 'users', route('admin.personnel.data.index')],
                    ['Jabatan ASN', 'briefcase-business', route('admin.personnel.positions.index')],
                    ['Mutasi', 'arrow-right-left', route('admin.personnel.mutation.index')],
                    ['Pensiun', 'user-minus', route('admin.personnel.retirement.index')],
                    ];
                    @endphp

                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($shortcuts as [$label, $icon, $url])
                        <a href="{{ $url }}"
                            class="group flex flex-col items-center justify-center gap-2.5 rounded-xl border border-border p-4 text-center transition-all duration-300 hover:border-primary/40 hover:bg-primary/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary">
                            <span class="size-10 rounded-full bg-muted text-secondary flex items-center justify-center transition-colors duration-300 group-hover:bg-primary group-hover:text-white">
                                <i data-lucide="{{ $icon }}" class="size-[18px]"></i>
                            </span>
                            <span class="text-xs font-semibold text-foreground">{{ $label }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- 6. DAFTAR TUNGGU PENSIUN & BERKALA --}}
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            @foreach ($waitlists as $list)
            <div class="flex flex-col rounded-2xl border border-border bg-white">
                <div class="flex items-start justify-between gap-3 p-5 pb-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="size-11 rounded-xl {{ $list['tile'] }} flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $list['icon'] }}" class="size-5"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-foreground">{{ $list['title'] }}</h2>
                            <p class="text-xs text-secondary mt-0.5 truncate">{{ $list['subtitle'] }}</p>
                        </div>
                    </div>
                    <a href="{{ $list['url'] }}"
                        class="shrink-0 inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-semibold text-primary ring-1 ring-border transition hover:ring-primary">
                        Lihat semua
                        <i data-lucide="chevron-right" class="size-3.5"></i>
                    </a>
                </div>

                <div class="px-2 pb-2">
                    @forelse ($list['items']->take(5) as $item)
                    @php [$countLabel, $countClass] = $countdown($item->due_date); @endphp
                    <div class="flex items-center gap-3 rounded-xl px-3 py-3 transition-colors hover:bg-muted/60">
                        <div class="shrink-0">
                            <x-ui.avatar :name="$item->staff?->name ?? '-'" :gender="$item->staff?->gender" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-foreground">{{ $item->staff?->name ?? 'Pegawai tidak ditemukan' }}</p>
                            <p class="mt-0.5 truncate text-xs text-secondary tabular-nums">
                                {{ $item->staff?->nip ? 'NIP ' . $item->staff->nip : 'NIP belum diisi' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-xs font-medium text-foreground">
                                {{ \Illuminate\Support\Carbon::parse($item->due_date)->translatedFormat('d M Y') }}
                            </p>
                            <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $countClass }}">
                                {{ $countLabel }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="py-14 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="check-circle-2" class="size-10 text-border"></i>
                            <p class="text-sm font-medium">{{ $list['empty'] }}</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </section>
    </div>
    @endsection