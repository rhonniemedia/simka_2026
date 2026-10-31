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
'docs' => $s->retirementDocuments ?? collect(),
'processUrl' => route('admin.personnel.retirement.process', $s->id),
'editUrl' => route('admin.personnel.retirement.edit', $s->id),
'detailUrl' => route('admin.personnel.data.detail', $s->id),
'isRetired' => $calc['state'] === 'retired',
// Berkas SK pensiun (bila sudah diunggah) untuk menu "Lihat SK".
'sk' => ($s->retirementDocuments ?? collect())->first(),
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
                        @if ($v['calc']['state'] === 'retired')
                        @forelse ($v['docs'] as $doc)
                        <a href="{{ route('admin.personnel.documents.preview', $doc->id) }}" target="_blank" rel="noopener"
                            class="mt-1.5 flex items-center gap-1 text-[11px] font-medium text-blue-600 hover:underline whitespace-nowrap">
                            <i data-lucide="file-text" class="size-3"></i> Lihat {{ $doc->category?->name ?? 'SK Pensiun' }}
                        </a>
                        @empty
                        <p class="mt-1.5 flex items-center gap-1 text-[11px] text-amber-600 whitespace-nowrap"><i data-lucide="file-x" class="size-3"></i> SK belum diunggah</p>
                        @endforelse
                        @endif
                    </td>

                    {{-- 4. Aksi (14%) --}}
                    <td class="px-4 py-4 min-w-[120px]">
                        @if ($v['canProcess'] || $v['isRetired'])
                        <div x-data="{
                                open: false, menuX: 0, menuY: 0,
                                toggle() {
                                    if (this.open) { this.open = false; return; }
                                    this.open = true;
                                    this.$nextTick(() => {
                                        const btn = this.$refs.button.getBoundingClientRect();
                                        const menu = this.$refs.menu.getBoundingClientRect();
                                        const spaceBelow = window.innerHeight - btn.bottom;
                                        const spaceAbove = btn.top;
                                        const dropUp = spaceBelow < menu.height && spaceAbove > menu.height;
                                        this.menuX = btn.right - menu.width;
                                        this.menuY = dropUp ? (btn.top - menu.height - 4) : (btn.bottom + 4);
                                    });
                                }
                            }"
                            @click.outside="open = false" @scroll.window="open = false" @resize.window="open = false"
                            class="relative inline-block text-left">

                            <button x-ref="button" type="button" @click="toggle()" title="Aksi"
                                class="inline-flex items-center gap-2 h-9 px-3.5 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all focus:outline-none cursor-pointer whitespace-nowrap">
                                <span class="text-sm font-medium">Aksi</span>
                                <i data-lucide="chevron-down" class="size-4 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </button>

                            <div x-ref="menu" x-show="open" x-cloak
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                :style="`top: ${menuY}px; left: ${menuX}px;`"
                                class="fixed z-[9999] w-48 rounded-xl border border-border bg-white shadow-lg py-2 flex flex-col text-left origin-top-right">

                                @if ($v['canProcess'])
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Proses</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ $v['processUrl'] }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="user-minus" class="size-4 text-secondary pointer-events-none"></i> Proses Pensiun
                                </button>
                                @endif

                                @if ($v['isRetired'])
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Detail</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ $v['detailUrl'] }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="id-card" class="size-4 text-secondary pointer-events-none"></i> Detail Pegawai
                                </button>
                                @if ($v['sk'])
                                <a href="{{ route('admin.personnel.documents.preview', $v['sk']->id) }}" target="_blank" rel="noopener" @click="open = false"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-text" class="size-4 pointer-events-none"></i> Lihat SK
                                </a>
                                @endif

                                <div class="my-2 border-t border-border"></div>

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Edit & Delete</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ $v['editUrl'] }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                {{-- Hapus belum tersedia: sengaja dinonaktifkan --}}
                                <button type="button" disabled aria-disabled="true" title="Fitur hapus belum tersedia"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-red-600 opacity-40 cursor-not-allowed text-left">
                                    <i data-lucide="trash-2" class="size-4 pointer-events-none"></i> Hapus Data
                                </button>
                                @endif

                                @if (! $v['canProcess'] && ! $v['isRetired'])
                                <p class="px-4 py-2 text-xs text-secondary">Belum ada aksi tersedia.</p>
                                @endif
                            </div>
                        </div>
                        @else
                        <span class="text-secondary/50">–</span>
                        @endif
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

                    @if ($v['calc']['state'] === 'retired')
                    <div class="mt-2 text-xs">
                        @forelse ($v['docs'] as $doc)
                        <a href="{{ route('admin.personnel.documents.preview', $doc->id) }}" target="_blank" rel="noopener"
                            class="flex items-center gap-1 font-medium text-blue-600 hover:underline">
                            <i data-lucide="file-text" class="size-3"></i> Lihat {{ $doc->category?->name ?? 'SK Pensiun' }}
                        </a>
                        @empty
                        <p class="flex items-center gap-1 text-amber-600"><i data-lucide="file-x" class="size-3"></i> SK belum diunggah</p>
                        @endforelse
                    </div>
                    @endif

                    @if ($v['canProcess'] || $v['isRetired'])
                    <div class="mt-3 flex items-center justify-end gap-2 border-t border-border pt-3">
                        @if ($v['canProcess'])
                        <button type="button"
                            hx-get="{{ $v['processUrl'] }}"
                            hx-target="#modal-container"
                            hx-swap="innerHTML"
                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg bg-primary text-xs font-semibold text-white hover:bg-primary/90 shadow-sm shadow-primary/30 transition-colors cursor-pointer focus:outline-none">
                            <i data-lucide="user-minus" class="size-3.5 pointer-events-none"></i>
                            Proses
                        </button>
                        @endif

                        @if ($v['isRetired'])
                        <button type="button"
                            hx-get="{{ $v['detailUrl'] }}"
                            hx-target="#modal-container"
                            hx-swap="innerHTML"
                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer">
                            <i data-lucide="id-card" class="size-3.5 pointer-events-none"></i>
                            Detail
                        </button>

                        @if ($v['sk'])
                        <a href="{{ route('admin.personnel.documents.preview', $v['sk']->id) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-blue-200 bg-blue-50 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer" title="Lihat SK">
                            <i data-lucide="file-text" class="size-3.5"></i>
                            Lihat
                        </a>
                        @endif

                        <span class="h-5 w-px bg-border mx-0.5" aria-hidden="true"></span>

                        <button type="button"
                            hx-get="{{ $v['editUrl'] }}"
                            hx-target="#modal-container"
                            hx-swap="innerHTML"
                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer">
                            <i data-lucide="file-pen-line" class="size-3.5"></i>
                            Edit
                        </button>

                        <button type="button" disabled aria-disabled="true" title="Fitur hapus belum tersedia"
                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-red-200 bg-red-50 text-xs font-medium text-red-600 opacity-40 cursor-not-allowed">
                            <i data-lucide="trash-2" class="size-3.5"></i>
                            Hapus
                        </button>
                        @endif
                    </div>
                    @endif
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