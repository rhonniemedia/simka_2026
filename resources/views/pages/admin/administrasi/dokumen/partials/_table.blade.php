<div id="documents-container" class="animate-fade-in"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshDocuments from:body"
    hx-target="this"
    hx-select="#documents-container"
    hx-swap="outerHTML"
    hx-push-url="true"
    data-no-loader>

    @php
    $verificationBadges = [
    'draft' => ['dot' => 'bg-slate-400', 'text' => 'text-slate-600', 'label' => 'Draft'],
    'verified' => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'label' => 'Terverifikasi'],
    'rejected' => ['dot' => 'bg-red-500', 'text' => 'text-red-700', 'label' => 'Ditolak'],
    ];
    @endphp

    {{-- ============ DESKTOP TABLE ============ --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Dokumen
                        <div class="text-[11px] font-normal normal-case">Nama & Nomor Dokumen</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pemilik
                        <div class="text-[11px] font-normal normal-case">Pegawai & Info Unggahan</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Informasi
                        <div class="text-[11px] font-normal normal-case">Kategori & Status Verifikasi</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Kelola Dokumen</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $item)
                @php $badge = $verificationBadges[$item->verification_status] ?? $verificationBadges['draft']; @endphp
                <tr class="border-b border-border hover:bg-muted/50 transition-colors">
                    {{-- Dokumen: ikon PDF + nama + tanggal --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="shrink-0 h-10 w-10 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center">
                                <i data-lucide="file-text" class="size-5 text-red-500 pointer-events-none"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-foreground text-sm truncate">{{ $item->document_name }}</div>
                                @if($item->document_number)
                                <div class="text-xs text-secondary mt-0.5 truncate">No. {{ $item->document_number }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Pemilik: nama pegawai + info pengunggah --}}
                    <td class="px-4 py-4">
                        <div class="min-w-0">
                            <div class="font-semibold text-foreground text-sm truncate">{{ $item->staff->name ?? '-' }}</div>
                            <div class="flex items-center gap-1.5 text-xs text-secondary mt-1 truncate">
                                <i data-lucide="upload" class="size-3 shrink-0 pointer-events-none"></i>
                                <span class="truncate">{{ $item->creator->name ?? $item->creator->username ?? 'Admin' }}</span>
                                <i data-lucide="calendar" class="size-3 shrink-0 pointer-events-none"></i>
                                <span class="truncate">{{ $item->document_date?->translatedFormat('d M Y') ?? '-' }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- Informasi: kategori + status --}}
                    <td class="px-4 py-4">
                        <div class="text-sm font-semibold text-foreground truncate">{{ $item->category->name ?? '-' }}</div>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="size-1.5 rounded-full {{ $badge['dot'] }}"></span>
                            <span class="text-xs font-bold {{ $badge['text'] }}">{{ $badge['label'] }}</span>
                        </div>
                    </td>

                    {{-- Aksi: dropdown lihat, unduh, opsi lainnya --}}
                    <td class="px-4 py-4">
                        <div x-data="{ open: false, menuX: 0, menuY: 0, toggle() { if(this.open) { this.open = false; return; } this.open = true; this.$nextTick(() => { const btn = this.$refs.button.getBoundingClientRect(); const menu = this.$refs.menu.getBoundingClientRect(); const spaceBelow = window.innerHeight - btn.bottom; const spaceAbove = btn.top; const dropUp = spaceBelow < menu.height && spaceAbove > menu.height; this.menuX = btn.right - menu.width; this.menuY = dropUp ? (btn.top - menu.height - 4) : (btn.bottom + 4); }); } }" @click.outside="open = false" @scroll.window="open = false" @resize.window="open = false" class="relative inline-block text-left">
                            <button x-ref="button" type="button" @click="toggle()" class="inline-flex items-center gap-2 h-9 px-3.5 rounded-lg border border-border bg-white text-secondary hover:bg-muted transition-all">
                                <span class="text-sm font-medium">Aksi</span>
                                <i data-lucide="chevron-down" class="size-4" :class="{ 'rotate-180': open }"></i>
                            </button>
                            <div x-ref="menu" x-show="open" x-cloak x-transition class="fixed z-[9999] w-48 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left" :style="`top: ${menuY}px; left: ${menuX}px;`">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold tracking-wider text-secondary">Dokumen</p>
                                <a href="{{ route('admin.personnel.documents.preview', $item->id) }}" target="_blank" rel="noopener" @click="open = false" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="eye" class="size-4 text-secondary"></i> Lihat Dokumen
                                </a>
                                <a href="{{ route('admin.personnel.documents.download', $item->id) }}" download @click="open = false" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="download" class="size-4 text-secondary"></i> Unduh Dokumen
                                </a>
                                <div class="my-2 border-t border-border"></div>
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold tracking-wider text-secondary">Manajemen Data</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.personnel.documents.edit', $item->id) }}"
                                    hx-target="#modal-container"
                                    hx-swap="innerHTML"
                                    hx-select="unset"
                                    hx-push-url="false"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary"></i> Edit Data
                                </button>
                                <button type="button" @click="open = false; ShowConfirm({ title: 'Hapus Dokumen?', message: 'Yakin ingin menghapus dokumen ini?', confirmText: 'Ya, Hapus', cancelText: 'Batal' }, () => { htmx.ajax('DELETE', '{{ route('admin.personnel.documents.destroy', $item->id) }}', { swap: 'none', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}' } }); })" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
                                    <i data-lucide="trash-2" class="size-4"></i> Hapus Data
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium">Belum ada data dokumen.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ MOBILE CARDS ============ --}}
    <div class="md:hidden divide-y divide-border border-y border-border mt-2 mb-4">
        @forelse ($documents as $item)
        @php $badge = $verificationBadges[$item->verification_status] ?? $verificationBadges['draft']; @endphp
        <div class="py-3.5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 h-10 w-10 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center">
                    <i data-lucide="file-text" class="size-5 text-red-500 pointer-events-none"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-foreground truncate">{{ $item->document_name }}</div>
                        <div x-data="{ open: false, menuX: 0, menuY: 0, toggle() { if(this.open) { this.open = false; return; } this.open = true; this.$nextTick(() => { const btn = this.$refs.button.getBoundingClientRect(); const menu = this.$refs.menu.getBoundingClientRect(); const spaceBelow = window.innerHeight - btn.bottom; const spaceAbove = btn.top; const dropUp = spaceBelow < menu.height && spaceAbove > menu.height; this.menuX = btn.right - menu.width; this.menuY = dropUp ? (btn.top - menu.height - 4) : (btn.bottom + 4); }); } }" @click.outside="open = false" @scroll.window="open = false" @resize.window="open = false" class="relative shrink-0">
                            <button x-ref="button" type="button" @click="toggle()" title="Aksi" class="p-1 -mr-1 text-secondary">
                                <i data-lucide="more-vertical" class="size-4 pointer-events-none"></i>
                            </button>
                            <div x-ref="menu" x-show="open" x-cloak x-transition class="fixed z-[9999] w-48 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left" :style="`top: ${menuY}px; left: ${menuX}px;`">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold tracking-wider text-secondary">Dokumen</p>
                                <a href="{{ route('admin.personnel.documents.preview', $item->id) }}" target="_blank" rel="noopener" @click="open = false" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="eye" class="size-4 text-secondary"></i> Lihat Dokumen
                                </a>
                                <a href="{{ route('admin.personnel.documents.download', $item->id) }}" download @click="open = false" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="download" class="size-4 text-secondary"></i> Unduh Dokumen
                                </a>
                                <div class="my-2 border-t border-border"></div>
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold tracking-wider text-secondary">Manajemen Data</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.personnel.documents.edit', $item->id) }}"
                                    hx-target="#modal-container"
                                    hx-swap="innerHTML"
                                    hx-select="unset"
                                    hx-push-url="false"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary"></i> Edit Data
                                </button>
                                <button type="button" @click="open = false; ShowConfirm({ title: 'Hapus Dokumen?', message: 'Yakin ingin menghapus dokumen ini?', confirmText: 'Ya, Hapus', cancelText: 'Batal' }, () => { htmx.ajax('DELETE', '{{ route('admin.personnel.documents.destroy', $item->id) }}', { swap: 'none', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}' } }); })" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
                                    <i data-lucide="trash-2" class="size-4"></i> Hapus Data
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-secondary mt-0.5 truncate">{{ $item->staff->name ?? '-' }} &bull; {{ $item->category->name ?? '-' }}</div>
                    <div class="flex items-center justify-between mt-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full {{ $badge['dot'] }}"></span>
                            <span class="text-[11px] font-bold {{ $badge['text'] }}">{{ $badge['label'] }}</span>
                        </div>
                        <span class="text-[11px] text-secondary">{{ $item->document_date?->translatedFormat('d M Y') ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <p class="font-medium text-sm">Belum ada data dokumen.</p>
        </div>
        @endforelse
    </div>

    {{-- Memanggil komponen pagination --}}
    <div class="mt-5">
        <x-ui.pagination :paginator="$documents" hxTarget="#documents-container" />
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>