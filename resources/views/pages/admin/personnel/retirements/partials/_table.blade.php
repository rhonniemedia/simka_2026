{{-- File: resources/views/pages/admin/personnel/retirements/partials/_table.blade.php --}}
@php
// Tampilan badge per status pensiun.
$stateMeta = [
'retired' => ['label' => 'Pensiun', 'class' => 'bg-slate-100 text-slate-700', 'dot' => 'bg-slate-500'],
'reached' => ['label' => 'Sudah mencapai batas', 'class' => 'bg-error/10 text-error', 'dot' => 'bg-error'],
'soon' => ['label' => 'Segera pensiun', 'class' => 'bg-amber-100 text-amber-700', 'dot' => 'bg-amber-500'],
'not_yet' => ['label' => 'Belum pensiun', 'class' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
'unknown' => ['label' => 'Tanggal lahir belum valid', 'class' => 'bg-slate-100 text-secondary', 'dot' => 'bg-slate-400'],
];

$positionTypeLabels = [
'fungsional_keahlian' => 'Fungsional Keahlian',
'fungsional_keterampilan' => 'Fungsional Keterampilan',
'pelaksana' => 'Pelaksana',
];

// Format tanggal Indonesia dari DateTimeImmutable (null -> '-').
$fmt = fn($date, $pattern = 'D MMM Y') => $date
? \Carbon\Carbon::parse($date->format('Y-m-d'))->locale('id')->isoFormat($pattern)
: '-';

// Siapkan data tampilan satu baris
$present = function (array $r) use ($stateMeta, $positionTypeLabels, $fmt) {
$s = $r['staff'];
$calc = $r['calc'];
$pos = $r['position'];
$nip = $s->vault?->nip;

return [
'staff' => $s,
'calc' => $calc,
'meta' => $stateMeta[$calc['state']],
'nip' => (!empty($nip) && strcasecmp($nip, 'tidak ada') !== 0) ? $nip : null,
'statusAlias' => $s->employmentStatus->alias ?? '-',
'positionName' => $pos?->name,
'positionType' => $pos ? ($positionTypeLabels[$pos->position_type] ?? $pos->position_type) : null,
'dob' => $fmt($calc['dob']),
'age' => $calc['age'] !== null ? $calc['age'] . ' tahun' : null,
'tmt' => $fmt($r['tmt']),
'note' => match ($calc['state']) {
'reached' => $calc['limit_date'] ? 'Sejak ' . $fmt($calc['limit_date']) : null,
'soon' => '± ' . $calc['months_left'] . ' bulan lagi',
default => null,
},
'canProcess' => $calc['can_process'],
'processUrl' => route('admin.personnel.retirement.process', $s->id),
];
};
@endphp

<div id="retirement-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshRetirementData from:body"
    hx-swap="outerHTML"
    data-no-loader>

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | Jabatan ASN</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pensiun
                        <div class="text-[11px] font-normal normal-case">TMT | Batas usia</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Status
                        <div class="text-[11px] font-normal normal-case">Status | TMT</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Detil | Proses</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                @php $v = $present($r); @endphp

                <tr id="row-retirement-{{ $v['staff']->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                    {{-- 1. Data Pegawai (30%) --}}
                    <td class="px-4 py-4 pr-6 truncate">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$v['staff']->name" :gender="$v['staff']->gender" :index="$loop->index" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-foreground text-sm truncate" title="{{ $v['staff']->name }}">{{ $v['staff']->name }}</div>
                                <div class="text-xs text-secondary mt-0.5 truncate" title="{{ $v['positionName'] ?? 'Belum diisi' }}">
                                    @if ($v['positionName'])
                                    {{ $v['positionName'] }}
                                    @else
                                    <span class="italic">Jabatan ASN belum diisi</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- 2. Pensiun (30%) --}}
                    <td class="px-4 py-4 truncate">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $v['tmt'] }}</div>
                        <div class="text-xs text-secondary mt-0.5 whitespace-nowrap">
                            Batas {{ $v['calc']['limit_age'] }} tahun
                        </div>
                    </td>

                    {{-- 3. Status (30%) --}}
                    <td class="px-4 py-4 truncate">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $v['meta']['class'] }}">
                                <span class="size-1.5 rounded-full {{ $v['meta']['dot'] }}"></span>
                                {{ $v['meta']['label'] }}
                            </span>
                        </div>
                        <div class="text-[11px] text-secondary mt-1.5 whitespace-nowrap">
                            {{ $v['note'] ?? $v['tmt'] }}
                        </div>
                    </td>

                    {{-- 4. Aksi (10%) --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            {{-- Tombol Detil --}}
                            <a href="#" class="inline-flex items-center justify-center size-9 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors" title="Lihat Detil">
                                <i data-lucide="eye" class="size-4"></i>
                            </a>

                            {{-- Tombol Proses --}}
                            @if ($v['canProcess'])
                            <button type="button"
                                hx-get="{{ $v['processUrl'] }}"
                                hx-target="#modal-container"
                                hx-swap="innerHTML"
                                class="inline-flex items-center justify-center size-9 rounded-lg bg-primary text-white hover:bg-primary/90 shadow-sm shadow-primary/30 transition-colors cursor-pointer focus:outline-none" title="Proses">
                                <i data-lucide="user-minus" class="size-4 pointer-events-none"></i>
                            </button>
                            @else
                            <div class="size-9"></div> {{-- Placeholder if no process --}}
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium">Tidak ada data pegawai ditemukan</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS (di bawah lg) ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border bg-white -mx-5 mb-5 mt-2">
        @forelse ($rows as $r)
        @php $v = $present($r); @endphp

        <div id="card-retirement-{{ $v['staff']->id }}" class="px-5 py-4 border-border hover:bg-muted/40 active:bg-muted/60 transition-colors">
            <div class="flex items-start gap-3">
                <x-ui.avatar :name="$v['staff']->name" :gender="$v['staff']->gender" :index="$loop->index" />

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground text-sm truncate block">{{ $v['staff']->name }}</p>
                            <p class="text-xs text-secondary mt-0.5 truncate">
                                {{ $v['positionName'] ?? 'Jabatan ASN belum diisi' }}
                            </p>
                        </div>

                        <span class="shrink-0 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold {{ $v['meta']['class'] }}">
                            <span class="size-1.5 rounded-full {{ $v['meta']['dot'] }}"></span>
                            {{ $v['meta']['label'] }}
                        </span>
                    </div>

                    <div class="mt-3 border-t border-b border-border divide-y divide-border text-xs">
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="calendar-clock" class="size-3 text-slate-400"></i>
                                TMT Pensiun
                            </p>
                            <p class="text-right font-medium text-foreground">
                                {{ $v['tmt'] }} <span class="text-secondary font-normal">(batas {{ $v['calc']['limit_age'] }} th)</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <a href="#" class="flex-1 inline-flex items-center justify-center gap-1.5 h-10 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200 transition-colors">
                            <i data-lucide="eye" class="size-4"></i> Detil
                        </a>

                        @if ($v['canProcess'])
                        <button type="button"
                            hx-get="{{ $v['processUrl'] }}"
                            hx-target="#modal-container"
                            hx-swap="innerHTML"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 h-10 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-colors cursor-pointer focus:outline-none">
                            <i data-lucide="user-minus" class="size-4 pointer-events-none"></i>
                            Proses
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Tidak ada data pegawai ditemukan</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Memanggil komponen pagination --}}
    <x-ui.pagination :paginator="$rows" hxTarget="#retirement-container" />

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>