{{-- File: resources/views/pages/admin/personnel/positions/show/partials/_table.blade.php --}}
<div id="position-history-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshPositionHistoryData from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Jabatan ASN
                        <div class="text-[11px] font-normal normal-case">Nama | Jenis</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        SK & TMT
                        <div class="text-[11px] font-normal normal-case">Nomor SK | Tanggal SK</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Status
                        <div class="text-[11px] font-normal normal-case">Status | TMT</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Lihat | Edit | Delete</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($histories as $h)
                @php
                $positionTypeLabel = [
                'fungsional_keahlian' => 'Fungsional Keahlian',
                'fungsional_keterampilan' => 'Fungsional Keterampilan',
                'pelaksana' => 'Pelaksana',
                ][$h->asnPosition?->position_type] ?? '-';
                $sk = $h->documents->first(fn ($d) => $d->category?->code === 'sk_jabatan');
                @endphp

                <tr id="row-position-history-{{ $h->id }}" class="group transition-colors hover:bg-muted/40">
                    {{-- Kolom 1: Jabatan ASN (dengan avatar ikon) --}}
                    <td class="px-5 py-4 min-w-[220px]">
                        <div class="flex items-center gap-3">
                            <div class="size-11 shrink-0 rounded-2xl bg-gradient-to-br from-blue-300 to-blue-500 text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10 transition-transform duration-200 group-hover:scale-[1.04]">
                                <i data-lucide="briefcase-business" class="size-5"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-foreground truncate">{{ $h->asnPosition?->name ?? '-' }}</p>
                                <p class="text-xs text-secondary mt-1">{{ $positionTypeLabel }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: SK & TMT (Nomor SK & Tanggal SK) --}}
                    <td class="px-5 py-4 min-w-[200px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            @if ($sk)
                            <i data-lucide="file-text" class="size-4 text-emerald-600 shrink-0" title="Berkas SK tersedia"></i>
                            @else
                            <i data-lucide="file-x" class="size-4 text-amber-500 shrink-0" title="SK belum diunggah"></i>
                            @endif
                            <span>{{ $h->decree_number ?? '-' }}</span>
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5.5">
                            Tgl. {{ optional($h->decree_date)->translatedFormat('d F Y') ?? '-' }}
                        </div>
                    </td>

                    {{-- Kolom 3: Status (badge & TMT) --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div>
                            @if ($h->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span> Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-secondary">
                                Nonaktif
                            </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-secondary mt-1.5 whitespace-nowrap">
                            <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                            TMT. {{ optional($h->effective_date)->translatedFormat('d F Y') ?? '-' }}
                        </div>
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

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Aksi Jabatan</p>

                                @if ($sk)
                                <a href="{{ route('admin.personnel.documents.preview', $sk->id) }}" target="_blank" rel="noopener" @click="open = false"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer text-left w-[calc(100%-1rem)]">
                                    <i data-lucide="file-text" class="size-4 pointer-events-none"></i> Lihat SK
                                </a>
                                @endif

                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.personnel.positions.edit', [$staff->id, $h->id]) }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left w-[calc(100%-1rem)]">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                <button type="button"
                                    @click="
                                        open = false;
                                        ShowConfirm({
                                            title: 'Hapus Riwayat Jabatan?',
                                            message: 'Yakin ingin menghapus riwayat jabatan ini? Tindakan ini tidak dapat dibatalkan.',
                                            confirmText: 'Ya, Hapus',
                                            cancelText: 'Batal',
                                        }, () => {
                                            htmx.ajax('DELETE', '{{ route('admin.personnel.positions.destroy', [$staff->id, $h->id]) }}', {
                                                target: '#position-history-container',
                                                swap: 'outerHTML',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                                                }
                                            });
                                        })
                                    "
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition-colors cursor-pointer text-left w-[calc(100%-1rem)] mt-1">
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
                                <i data-lucide="briefcase-business" class="size-7 text-secondary/50"></i>
                            </div>
                            <p class="font-medium text-sm">Belum ada riwayat jabatan ASN untuk pegawai ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS (di bawah lg) ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($histories as $h)
        @php
        $positionTypeLabel = [
        'fungsional_keahlian' => 'Fungsional Keahlian',
        'fungsional_keterampilan' => 'Fungsional Keterampilan',
        'pelaksana' => 'Pelaksana',
        ][$h->asnPosition?->position_type] ?? '-';
        $sk = $h->documents->first(fn ($d) => $d->category?->code === 'sk_jabatan');
        @endphp

        <div id="card-position-history-{{ $h->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <div class="size-11 rounded-2xl bg-gradient-to-br from-blue-300 to-blue-500 text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10">
                        <i data-lucide="briefcase-business" class="size-5"></i>
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-foreground text-sm truncate">
                        {{ $h->asnPosition?->name ?? '-' }}
                    </div>
                    <p class="text-xs text-secondary mt-1 truncate">
                        {{ $positionTypeLabel }}
                    </p>
                </div>
                <div class="shrink-0">
                    @if ($h->is_active)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Aktif
                    </span>
                    @else
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-secondary">
                        Nonaktif
                    </span>
                    @endif
                </div>
            </div>

            <div class="mt-3 border-t border-border divide-y divide-border text-xs">
                {{-- Baris Surat Keputusan --}}
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
                        <p class="font-medium text-foreground truncate">{{ $h->decree_number ?? '-' }}</p>
                        <p class="text-secondary truncate mt-0.5">Tgl. {{ optional($h->decree_date)->translatedFormat('d F Y') ?? '-' }}</p>
                    </div>
                </div>

                {{-- Baris TMT --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                        TMT Jabatan
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ optional($h->effective_date)->translatedFormat('d F Y') ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Mobile --}}
            <div class="mt-3 flex items-center justify-end gap-2 border-t border-border pt-3">
                @if ($sk)
                <a href="{{ route('admin.personnel.documents.preview', $sk->id) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-blue-200 bg-blue-50 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer shadow-sm" title="Lihat SK">
                    <i data-lucide="file-text" class="size-3.5"></i>
                    Lihat
                </a>
                @endif

                <button type="button"
                    hx-get="{{ route('admin.personnel.positions.edit', [$staff->id, $h->id]) }}"
                    hx-target="#modal-container"
                    hx-swap="innerHTML"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer shadow-sm">
                    <i data-lucide="file-pen-line" class="size-3.5"></i>
                    Edit
                </button>
                <button type="button"
                    @click="
                        ShowConfirm({
                            title: 'Hapus Riwayat Jabatan?',
                            message: 'Yakin ingin menghapus riwayat jabatan ini? Tindakan ini tidak dapat dibatalkan.',
                            confirmText: 'Ya, Hapus',
                            cancelText: 'Batal',
                        }, () => {
                            htmx.ajax('DELETE', '{{ route('admin.personnel.positions.destroy', [$staff->id, $h->id]) }}', {
                                target: '#position-history-container',
                                swap: 'outerHTML',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                                }
                            });
                        })
                    "
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-red-200 bg-red-50 text-xs font-medium text-red-600 hover:bg-red-100 hover:border-red-300 transition-colors cursor-pointer shadow-sm">
                    <i data-lucide="trash-2" class="size-3.5"></i>
                    Hapus
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center justify-center size-16 rounded-full bg-muted">
                    <i data-lucide="briefcase-business" class="size-7 text-secondary/50"></i>
                </div>
                <p class="font-medium text-sm">Belum ada riwayat jabatan ASN untuk pegawai ini.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- ============ 3. PAGINATION ============ --}}
    <x-ui.pagination :paginator="$histories" hxTarget="#position-history-container" />

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>