<div id="asn-positions-container" class="animate-fade-in"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshAsnPositions from:body"
    hx-swap="outerHTML"
    hx-push-url="true"
    data-no-loader>

    {{-- Header Area Konten --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-xl font-bold text-foreground">Data Jabatan Kepegawaian</h2>
                <span class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold">
                    {{ $asnPositions->total() }}
                </span>
            </div>
            <p class="text-sm text-secondary mt-0.5">Daftar referensi jabatan kepegawaian non-manajerial (Fungsional & Pelaksana).</p>
        </div>

        <button type="button"
            hx-get="{{ route('admin.master.asn-positions.create') }}"
            hx-target="#modal-container"
            hx-swap="innerHTML"
            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap w-full sm:w-auto">
            <i data-lucide="plus" class="size-4 shrink-0"></i>
            <span>Tambah Jabatan</span>
        </button>
    </div>

    @php
    $badgeColors = [
    ['bg' => 'bg-cyan-100', 'text' => 'text-cyan-700'],
    ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    ['bg' => 'bg-sky-100', 'text' => 'text-sky-700'],
    ];

    // PENTING: gunakan closure lokal (bukan `function namaFungsi(){}` global).
    // Deklarasi fungsi global di dalam file Blade akan menyebabkan
    // "Fatal error: Cannot redeclare function" jika file ini ter-include
    // lebih dari sekali dalam satu request, atau jika partial master data
    // lain memakai nama fungsi yang sama.
    $positionTypeLabels = [
    'fungsional_keahlian' => 'Fungsional Keahlian',
    'fungsional_keterampilan' => 'Fungsional Keterampilan',
    'pelaksana' => 'Pelaksana',
    ];

    $eligibilityLabels = [
    'pns' => 'PNS',
    'pppk' => 'PPPK',
    'both' => 'PNS & PPPK',
    ];

    $formatPositionType = fn ($type) => $positionTypeLabels[$type] ?? $type;
    $formatEligibility = fn ($elig) => $eligibilityLabels[$elig] ?? $elig;
    @endphp

    {{-- ============ DESKTOP TABLE ============ --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[50%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Nama Jabatan
                        <div class="text-[11px] font-normal normal-case">Detail Jabatan Kepegawaian</div>
                    </th>
                    <th class="w-[40%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Kategori & Peruntukan
                        <div class="text-[11px] font-normal normal-case">Tipe | Status Kelayakan</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Edit | Hapus</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asnPositions as $item)
                @php $color = $badgeColors[$loop->index % count($badgeColors)]; @endphp
                <tr id="row-asn-position-{{ $item->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">

                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <div class="size-10 rounded-xl {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center font-bold text-sm">
                                    <i data-lucide="badge-check" class="size-4"></i>
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm whitespace-nowrap">
                                    {{ $item->name }}
                                </div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <i data-lucide="hourglass" class="size-3 text-secondary shrink-0"></i>
                                    <span class="text-[11px] text-secondary">BUP {{ $item->retirement_age }} tahun</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="flex flex-col items-start gap-1">
                            <span class="inline-flex px-2 py-1 rounded-md text-xs font-bold bg-primary/10 text-primary">
                                {{ $formatPositionType($item->position_type) }}
                            </span>
                            <span class="text-[11px] text-secondary font-medium ml-1">
                                Kelayakan: <span class="text-foreground">{{ $formatEligibility($item->eligibility) }}</span>
                            </span>
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[120px]">
                        <div x-data="{ open: false, menuX: 0, menuY: 0, toggle() { if(this.open) { this.open = false; return; } this.open = true; this.$nextTick(() => { const btn = this.$refs.button.getBoundingClientRect(); const menu = this.$refs.menu.getBoundingClientRect(); const spaceBelow = window.innerHeight - btn.bottom; const spaceAbove = btn.top; const dropUp = spaceBelow < menu.height && spaceAbove > menu.height; this.menuX = btn.right - menu.width; this.menuY = dropUp ? (btn.top - menu.height - 4) : (btn.bottom + 4); }); } }" @click.outside="open = false" @scroll.window="open = false" @resize.window="open = false" class="relative inline-block text-left">
                            <button x-ref="button" type="button" @click="toggle()" class="inline-flex items-center gap-2 h-9 px-3.5 rounded-lg border border-border bg-white text-secondary hover:bg-muted transition-all">
                                <span class="text-sm font-medium">Aksi</span>
                                <i data-lucide="chevron-down" class="size-4" :class="{ 'rotate-180': open }"></i>
                            </button>
                            <div x-ref="menu" x-show="open" x-cloak x-transition class="fixed z-[9999] w-48 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left" :style="`top: ${menuY}px; left: ${menuX}px;`">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold tracking-wider text-secondary">Manajemen Data</p>
                                <button type="button" @click="open = false" hx-get="{{ route('admin.master.asn-positions.edit', $item->id) }}" hx-target="#modal-container" hx-swap="innerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary"></i> Edit Data
                                </button>
                                <div class="my-2 border-t border-border"></div>
                                <button type="button" @click="open = false; ShowConfirm({ title: 'Hapus Jabatan?', message: 'Yakin ingin menghapus jabatan ini?', confirmText: 'Ya, Hapus', cancelText: 'Batal' }, () => { htmx.ajax('DELETE', '{{ route('admin.master.asn-positions.destroy', $item->id) }}', { swap: 'none', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}' } }); })" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
                                    <i data-lucide="trash-2" class="size-4"></i> Hapus Data
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium">Belum ada data jabatan kepegawaian.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ MOBILE CARDS ============ --}}
    <div class="md:hidden divide-y divide-border border-y border-border mt-2 mb-4">
        @forelse ($asnPositions as $item)
        @php $color = $badgeColors[$loop->index % count($badgeColors)]; @endphp
        <div class="py-3.5 flex items-center gap-3">
            <div class="size-9 rounded-xl {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center shrink-0">
                <i data-lucide="badge-check" class="size-4"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold text-foreground truncate">{{ $item->name }}</div>
                <div class="text-xs text-secondary mt-0.5 truncate">
                    <span class="font-medium text-foreground">{{ $formatPositionType($item->position_type) }}</span>
                    &bull; {{ $formatEligibility($item->eligibility) }}
                </div>
                <div class="flex items-center gap-1 mt-1">
                    <i data-lucide="hourglass" class="size-3 text-secondary shrink-0"></i>
                    <span class="text-[11px] text-secondary">BUP {{ $item->retirement_age }} tahun</span>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button" hx-get="{{ route('admin.master.asn-positions.edit', $item->id) }}" hx-target="#modal-container" hx-swap="innerHTML" class="size-8 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:text-blue-600 hover:border-blue-600 transition-colors">
                    <i data-lucide="file-pen-line" class="size-4"></i>
                </button>
                <button type="button" @click="ShowConfirm({ title: 'Hapus?', message: 'Yakin hapus jabatan ini?', confirmText: 'Ya', cancelText: 'Batal' }, () => { htmx.ajax('DELETE', '{{ route('admin.master.asn-positions.destroy', $item->id) }}', { swap: 'none', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}' } }); })" class="size-8 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:text-error hover:border-error transition-colors">
                    <i data-lucide="trash-2" class="size-4"></i>
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <p class="font-medium text-sm">Belum ada data jabatan kepegawaian.</p>
        </div>
        @endforelse
    </div>

    {{-- Memanggil komponen pagination --}}
    <div class="mt-5">
        <x-ui.pagination :paginator="$asnPositions" hxTarget="#asn-positions-container" />
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>