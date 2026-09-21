{{-- File: resources/views/pages/admin/personnel/transfers/partials/_table.blade.php --}}
<div id="staff-container">

    @php
    $statusBadge = [
    'transferred' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'dot' => 'bg-blue-500'],
    'resigned' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
    'retired' => ['bg' => 'bg-violet-100', 'text' => 'text-violet-700', 'dot' => 'bg-violet-500'],
    'deceased' => ['bg' => 'bg-slate-200', 'text' => 'text-slate-700', 'dot' => 'bg-slate-500'],
    'dismissed' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'dot' => 'bg-red-500'],
    ];
    @endphp

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[34%] px-5 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | NIK</div>
                    </th>
                    <th class="w-[33%] px-5 py-3 text-sm font-bold text-secondary tracking-wider">
                        Status
                        <div class="text-[11px] font-normal normal-case">Status | Nomor SK</div>
                    </th>
                    <th class="w-[33%] px-5 py-3 text-sm font-bold text-secondary tracking-wider">
                        TMT
                        <div class="text-[11px] font-normal normal-case">Tanggal Efektif | Catatan</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staffList as $staff)
                @php $badge = $statusBadge[$staff->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-secondary', 'dot' => 'bg-slate-400']; @endphp
                <tr class="border-b border-border hover:bg-muted/50 transition-colors">
                    {{-- Kolom 1: Pegawai --}}
                    <td class="px-5 py-4 min-w-[220px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$staff->name" :gender="$staff->gender" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-foreground text-sm truncate">{{ $staff->name }}</p>
                                <p class="text-xs text-secondary mt-0.5 font-mono tracking-wide">{{ $staff->vault?->nik ?? '-' }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: Status --}}
                    <td class="px-5 py-4 min-w-[200px]">
                        <span class="inline-flex items-center gap-1.5 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }} px-2.5 py-1 text-xs font-semibold">
                            <span class="size-1.5 rounded-full {{ $badge['dot'] }}"></span>
                            {{ \App\Http\Controllers\Admin\Personnel\TransferController::STATUS_LABELS[$staff->status] ?? $staff->status }}
                        </span>
                        <p class="text-xs text-secondary mt-1.5">
                            {{ $staff->latest_decree_number ?? '-' }}
                        </p>
                    </td>

                    {{-- Kolom 3: TMT --}}
                    <td class="px-5 py-4 min-w-[200px]">
                        <p class="flex items-center gap-1.5 text-sm font-medium text-foreground">
                            <i data-lucide="calendar" class="size-3.5 shrink-0 text-secondary"></i>
                            {{ $staff->status_effective_date ? \Carbon\Carbon::parse($staff->status_effective_date)->translatedFormat('d M Y') : '-' }}
                        </p>
                        @if ($staff->latest_note)
                        <p class="text-xs text-secondary mt-1 line-clamp-2">{{ $staff->latest_note }}</p>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium text-sm">Tidak ada data mutasi pada periode ini</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARD (di bawah lg) ============ --}}
    <div class="lg:hidden flex flex-col gap-3 p-3">
        @forelse ($staffList as $staff)
        @php $badge = $statusBadge[$staff->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-secondary', 'dot' => 'bg-slate-400']; @endphp
        <div class="rounded-2xl border border-border p-4">
            <div class="flex items-center gap-3">
                <div class="shrink-0">
                    <x-ui.avatar :name="$staff->name" :gender="$staff->gender" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-foreground text-sm truncate">{{ $staff->name }}</p>
                    <p class="text-xs text-secondary mt-0.5 font-mono tracking-wide">{{ $staff->vault?->nik ?? '-' }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }} px-2.5 py-1 text-[11px] font-semibold">
                    <span class="size-1.5 rounded-full {{ $badge['dot'] }}"></span>
                    {{ \App\Http\Controllers\Admin\Personnel\TransferController::STATUS_LABELS[$staff->status] ?? $staff->status }}
                </span>
            </div>

            <div class="mt-3 pt-3 border-t border-border/60 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <p class="text-[10px] font-bold text-secondary uppercase tracking-widest mb-0.5">Nomor SK</p>
                    <p class="font-medium text-foreground">{{ $staff->latest_decree_number ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-secondary uppercase tracking-widest mb-0.5">TMT</p>
                    <p class="flex items-center gap-1 font-medium text-foreground">
                        <i data-lucide="calendar" class="size-3 shrink-0"></i>
                        {{ $staff->status_effective_date ? \Carbon\Carbon::parse($staff->status_effective_date)->translatedFormat('d M Y') : '-' }}
                    </p>
                </div>
            </div>
            @if ($staff->latest_note)
            <p class="text-[11px] text-secondary mt-2 line-clamp-2">{{ $staff->latest_note }}</p>
            @endif
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Tidak ada data mutasi pada periode ini</p>
            </div>
        </div>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$staffList" hxTarget="#staff-container" />

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>