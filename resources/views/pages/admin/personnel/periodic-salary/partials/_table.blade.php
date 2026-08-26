{{-- File: resources/views/pages/admin/personnel/periodic-salary/partials/_table.blade.php --}}
<div id="periodic-salary-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshPeriodicSalary from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto mb-4">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | NIK</div>
                    </th>
                    <th class="w-[25%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Berkala Terakhir
                        <div class="text-[11px] font-normal normal-case">TMT | Masa Kerja</div>
                    </th>
                    <th class="w-[25%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Berkala Berikutnya
                        <div class="text-[11px] font-normal normal-case">TMT Berikutnya | Tahun Ke</div>
                    </th>
                    <th class="w-[20%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi & Informasi
                        <div class="text-[11px] font-normal normal-case">Dokumen SK | Edit Data</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($histories as $r)
                @php
                $staff = $r->staff;
                $nik = $staff?->vault?->nik ?? '-';

                $tmt = $r->effective_date ? \Carbon\Carbon::parse($r->effective_date)->translatedFormat('d F Y') : '-';
                $tmtBerikut = $r->tmt_berikut ? \Carbon\Carbon::parse($r->tmt_berikut)->translatedFormat('d F Y') : '-';

                $statusColor = match($r->verification_status) {
                'verified' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'rejected' => 'bg-red-100 text-red-700 border-red-200',
                default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $statusLabel = match($r->verification_status) {
                'verified' => 'Terverifikasi',
                'rejected' => 'Ditolak',
                default => 'Draf',
                };
                @endphp

                <tr id="row-salary-{{ $r->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                    {{-- Kolom 1: Pegawai --}}
                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3 group transition-all">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$staff?->name ?? 'Unknown'" :gender="$staff?->gender" :index="$loop->index" />
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm uppercase group-hover:text-primary transition-colors whitespace-nowrap">
                                    {{ $staff?->name ?? 'Data Tidak Ditemukan' }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $nik }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: Berkala Terakhir --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $tmt }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            MK. <span>{{ $r->masa_kerja }}</span>
                        </div>
                    </td>

                    {{-- Kolom 3: Berkala Berikutnya --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $tmtBerikut }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            Tahun ke. <span class="font-semibold">{{ $r->tahun_ke ?? '-' }}</span>
                        </div>
                    </td>

                    {{-- Kolom 4: Aksi & Informasi --}}
                    <td class="px-5 py-4 min-w-[140px]">
                        <div class="flex items-center gap-3">
                            {{-- Indikator Status & Tautan Dokumen (jika ada file) --}}
                            @if ($r->decree_file_id)
                            <button type="button" class="shrink-0 hover:opacity-80 transition-opacity" title="Lihat Dokumen SK">
                                <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center border border-primary/20">
                                    <i data-lucide="file-text" class="size-4 text-primary"></i>
                                </div>
                            </button>
                            @else
                            <div class="size-8 rounded-lg bg-muted flex items-center justify-center border border-border" title="Dokumen Tidak Tersedia">
                                <i data-lucide="file-x-2" class="size-4 text-secondary/50"></i>
                            </div>
                            @endif

                            {{-- Dropdown Aksi --}}
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
                                    class="fixed z-[9999] w-56 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left origin-top-right">

                                    <div class="px-4 pb-2 mb-2 border-b border-border">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1.5">Status Verifikasi</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border {{ $statusColor }} uppercase tracking-wider">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Manajemen Data</p>
                                    <button type="button" @click="open = false" hx-get="#" hx-target="#modal-container" hx-swap="outerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                        <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                    </button>
                                    <button type="button" hx-delete="#" hx-target="#periodic-salary-container" hx-select="#periodic-salary-container" hx-swap="outerHTML" hx-confirm="Yakin ingin menghapus data riwayat ini?" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
                                        <i data-lucide="trash-2" class="size-4 pointer-events-none"></i> Hapus Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium">Tidak ada data riwayat gaji berkala ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS (di bawah lg) ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($histories as $r)
        @php
        $staff = $r->staff;
        $nik = $staff?->vault?->nik ?? '-';

        $tmt = ($r->effective_date && $r->effective_date !== '-')
        ? \Carbon\Carbon::parse($r->effective_date)->translatedFormat('d F Y')
        : '-';

        $tmtBerikut = ($r->tmt_berikut && $r->tmt_berikut !== '-')
        ? \Carbon\Carbon::parse($r->tmt_berikut)->translatedFormat('d F Y')
        : '-';

        $statusColor = match($r->verification_status) {
        'verified' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'rejected' => 'bg-red-100 text-red-700 border-red-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
        $statusLabel = match($r->verification_status) {
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
        default => 'Draf',
        };
        @endphp

        <div id="card-salary-{{ $r->id }}" class="px-5 py-4 border-border active:bg-muted/40 transition-colors">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <x-ui.avatar :name="$staff?->name ?? 'Unknown'" :gender="$staff?->gender" :index="$loop->index" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-foreground text-sm uppercase truncate">
                        {{ $staff?->name ?? 'Data Tidak Ditemukan' }}
                    </div>
                    <p class="text-xs text-secondary mt-1 truncate flex items-center gap-1.5">
                        <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        {{ $nik }}
                    </p>
                </div>
            </div>

            <div class="mt-3 border-t border-b border-border divide-y divide-border text-xs">
                {{-- Baris Status & Dokumen --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="shield-check" class="size-3.5 text-secondary/50"></i>
                        Status & Dokumen
                    </p>
                    <div class="text-right min-w-0 flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border {{ $statusColor }} uppercase tracking-wider">
                            {{ $statusLabel }}
                        </span>
                        @if ($r->decree_file_id)
                        <button class="text-primary hover:underline font-medium">Lihat SK</button>
                        @endif
                    </div>
                </div>

                {{-- Baris TMT & Masa Kerja (Berkala Saat Ini) --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                        Berkala Terakhir
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $tmt }}</p>
                        <p class="text-secondary truncate mt-0.5">MK. <span class="font-medium">{{ $r->masa_kerja }}</span></p>
                    </div>
                </div>

                {{-- Baris Berkala Berikutnya --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="calendar-clock" class="size-3.5 text-secondary/50"></i>
                        Estimasi Berikutnya
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $tmtBerikut }}</p>
                        <p class="text-secondary truncate mt-0.5">Tahun ke. <span class="font-medium">{{ $r->tahun_ke ?? '-' }}</span></p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Mobile --}}
            <div class="mt-3 flex items-center justify-end gap-2 pt-3 border-t border-border">
                <button type="button" hx-get="#" hx-target="#modal-container" hx-swap="outerHTML" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-muted text-foreground rounded-lg hover:bg-muted/80 transition-colors">
                    <i data-lucide="file-pen-line" class="size-3.5"></i> Edit
                </button>
                <button type="button" hx-delete="#" hx-target="#periodic-salary-container" hx-select="#periodic-salary-container" hx-swap="outerHTML" hx-confirm="Yakin ingin menghapus data riwayat ini?" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-error/10 text-error rounded-lg hover:bg-error/20 transition-colors">
                    <i data-lucide="trash-2" class="size-3.5"></i> Hapus
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Tidak ada data riwayat gaji berkala ditemukan.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Memanggil komponen pagination --}}
    <x-ui.pagination :paginator="$histories" hxTarget="#periodic-salary-container" />

    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (!highlightId) return;

            const row = document.getElementById('row-salary-' + highlightId);
            const card = document.getElementById('card-salary-' + highlightId);
            const target = row || card;
            if (!target) return;

            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            const highlightClasses = row ? ['bg-primary/10'] : ['ring-2', 'ring-primary', 'rounded-xl', 'bg-primary/5'];

            target.classList.add(...highlightClasses, 'transition-colors', 'duration-700');

            setTimeout(() => {
                target.classList.remove(...highlightClasses);
            }, 2500);

            params.delete('highlight');
            const query = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
        })();

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>