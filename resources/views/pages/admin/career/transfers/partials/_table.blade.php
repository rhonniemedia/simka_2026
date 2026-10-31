{{-- File: resources/views/pages/admin/career/transfers/partials/_table.blade.php --}}
<div id="staff-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshStaffTransferData from:body"
    hx-swap="outerHTML">

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
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | NIK</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Surat Keputusan & Status
                        <div class="text-[11px] font-normal normal-case">Nomor SK | Status</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Penetapan
                        <div class="text-[11px] font-normal normal-case">Tanggal Efektif (TMT) | Catatan</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Lihat | Edit | Hapus</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($staffList as $staff)
                @php
                $badge = $statusBadge[$staff->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-secondary', 'dot' => 'bg-slate-400'];
                $docs = $staff->latestStatusDocuments ?? collect();
                $sk = $docs->first();
                // Pensiun diubah lewat menu Pensiun.
                $canEdit = $staff->status !== 'retired';
                @endphp
                <tr id="row-staff-transfer-{{ $staff->id }}" class="group transition-colors hover:bg-muted/40">
                    {{-- Kolom 1: Pegawai --}}
                    <td class="px-5 py-4 min-w-[220px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$staff->name" :gender="$staff->gender" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-foreground text-sm truncate">{{ $staff->name }}</p>
                                <p class="text-xs text-secondary mt-1 font-mono tracking-wide">{{ $staff->vault?->nik ?? '-' }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: Surat Keputusan & Status --}}
                    <td class="px-5 py-4 min-w-[220px]">
                        {{-- Nomor SK; ikon menandai ada/tidaknya berkas --}}
                        <div class="flex items-start gap-2">
                            @if ($sk)
                            <i data-lucide="file-check-2" class="size-4 mt-0.5 text-emerald-600 shrink-0" title="Berkas SK tersedia"></i>
                            @else
                            <i data-lucide="file-x" class="size-4 mt-0.5 text-amber-500 shrink-0" title="SK belum diunggah"></i>
                            @endif

                            @if (filled($staff->latest_decree_number))
                            <p class="text-sm font-semibold text-foreground leading-snug break-words">{{ $staff->latest_decree_number }}</p>
                            @else
                            <p class="text-sm italic text-secondary/60 leading-snug">Nomor SK belum diisi</p>
                            @endif
                        </div>

                        {{-- Status: teks berwarna dengan titik, tanpa badge --}}
                        <p class="mt-1.5 pl-6 flex items-center gap-2 text-xs font-semibold {{ $badge['text'] }}">
                            <span class="size-2 rounded-full {{ $badge['dot'] }}"></span>
                            {{ \App\Http\Controllers\Admin\Career\TransferController::STATUS_LABELS[$staff->status] ?? $staff->status }}
                        </p>
                    </td>

                    {{-- Kolom 3: TMT & Catatan --}}
                    <td class="px-5 py-4 min-w-[200px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                            TMT. {{ $staff->status_effective_date ? \Carbon\Carbon::parse($staff->status_effective_date)->translatedFormat('d F Y') : '-' }}
                        </div>
                        @if ($staff->latest_note)
                        <p class="text-xs text-secondary mt-1 line-clamp-2 pl-5">{{ $staff->latest_note }}</p>
                        @endif
                    </td>

                    {{-- Kolom 4: Aksi (Dropdown Tergabung) --}}
                    <td class="px-5 py-4 min-w-[120px]">
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

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Detail</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.personnel.data.detail', $staff->id) }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="id-card" class="size-4 text-secondary pointer-events-none"></i> Detail Pegawai
                                </button>
                                @if ($sk)
                                <a href="{{ route('admin.personnel.documents.preview', $sk->id) }}" target="_blank" rel="noopener" @click="open = false"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer text-left w-[calc(100%-1rem)]">
                                    <i data-lucide="file-text" class="size-4 pointer-events-none"></i> Lihat SK
                                </a>
                                @endif

                                <div class="my-2 border-t border-border"></div>

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Edit & Delete</p>
                                @if ($canEdit)
                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.personnel.mutation.edit', $staff->id) }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>
                                @else
                                <button type="button" disabled aria-disabled="true" title="Data pensiun diubah lewat menu Pensiun"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground opacity-40 cursor-not-allowed text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>
                                @endif

                                {{-- Hapus belum tersedia: sengaja dinonaktifkan --}}
                                <button type="button" disabled aria-disabled="true" title="Fitur hapus belum tersedia"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-red-600 opacity-40 cursor-not-allowed text-left">
                                    <i data-lucide="trash-2" class="size-4 pointer-events-none"></i> Hapus Data
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex items-center justify-center size-16 rounded-full bg-muted">
                                <i data-lucide="arrow-right-left" class="size-7 text-secondary/50"></i>
                            </div>
                            <p class="font-medium text-sm">Tidak ada data mutasi pada periode ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARD (di bawah lg) ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($staffList as $staff)
        @php
        $badge = $statusBadge[$staff->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-secondary', 'dot' => 'bg-slate-400'];
        $docs = $staff->latestStatusDocuments ?? collect();
        $sk = $docs->first();
        $canEdit = $staff->status !== 'retired';
        @endphp
        <div id="card-staff-transfer-{{ $staff->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <x-ui.avatar :name="$staff->name" :gender="$staff->gender" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-foreground text-sm truncate">{{ $staff->name }}</p>
                    <p class="text-xs text-secondary mt-1 font-mono tracking-wide">{{ $staff->vault?->nik ?? '-' }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }} px-2.5 py-1 text-[11px] font-semibold">
                    <span class="size-1.5 rounded-full {{ $badge['dot'] }}"></span>
                    {{ \App\Http\Controllers\Admin\Career\TransferController::STATUS_LABELS[$staff->status] ?? $staff->status }}
                </span>
            </div>

            <div class="mt-3 border-t border-border divide-y divide-border text-xs">
                {{-- Baris Nomor SK --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        @if ($sk)
                        <i data-lucide="file-text" class="size-4 text-emerald-600 shrink-0" title="Berkas SK tersedia"></i>
                        @else
                        <i data-lucide="file-x" class="size-4 text-amber-500 shrink-0" title="SK belum diunggah"></i>
                        @endif
                        Nomor SK
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $staff->latest_decree_number ?? '-' }}</p>
                    </div>
                </div>

                {{-- Baris TMT & Catatan --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                        TMT Mutasi
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">
                            {{ $staff->status_effective_date ? \Carbon\Carbon::parse($staff->status_effective_date)->translatedFormat('d F Y') : '-' }}
                        </p>
                        @if ($staff->latest_note)
                        <p class="text-secondary truncate mt-0.5">{{ $staff->latest_note }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Mobile --}}
            <div class="mt-3 flex items-center justify-end gap-2 border-t border-border pt-3">
                <button type="button"
                    hx-get="{{ route('admin.personnel.data.detail', $staff->id) }}"
                    hx-target="#modal-container"
                    hx-swap="innerHTML"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer">
                    <i data-lucide="id-card" class="size-3.5 pointer-events-none"></i>
                    Detail
                </button>
                @if ($sk)
                <a href="{{ route('admin.personnel.documents.preview', $sk->id) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-blue-200 bg-blue-50 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer shadow-sm" title="Lihat SK">
                    <i data-lucide="file-text" class="size-3.5"></i>
                    Lihat
                </a>
                @endif
                <span class="h-5 w-px bg-border mx-0.5" aria-hidden="true"></span>
                @if ($canEdit)
                <button type="button"
                    hx-get="{{ route('admin.personnel.mutation.edit', $staff->id) }}"
                    hx-target="#modal-container"
                    hx-swap="innerHTML"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer">
                    <i data-lucide="file-pen-line" class="size-3.5"></i>
                    Edit
                </button>
                @else
                <button type="button" disabled aria-disabled="true" title="Data pensiun diubah lewat menu Pensiun"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary opacity-40 cursor-not-allowed">
                    <i data-lucide="file-pen-line" class="size-3.5"></i>
                    Edit
                </button>
                @endif
                <button type="button" disabled aria-disabled="true" title="Fitur hapus belum tersedia"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-red-200 bg-red-50 text-xs font-medium text-red-600 opacity-40 cursor-not-allowed">
                    <i data-lucide="trash-2" class="size-3.5"></i>
                    Hapus
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center justify-center size-16 rounded-full bg-muted">
                    <i data-lucide="arrow-right-left" class="size-7 text-secondary/50"></i>
                </div>
                <p class="font-medium text-sm">Tidak ada data mutasi pada periode ini.</p>
            </div>
        </div>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$staffList" hxTarget="#staff-container" />

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>