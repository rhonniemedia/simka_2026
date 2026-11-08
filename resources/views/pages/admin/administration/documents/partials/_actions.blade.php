{{-- Param: $item (Document), $compact (true = ikon titik tiga untuk mobile) --}}
@php $compact = $compact ?? false; @endphp
<div x-data="{ open: false, menuX: 0, menuY: 0, toggle() { if(this.open) { this.open = false; return; } this.open = true; this.$nextTick(() => { const btn = this.$refs.button.getBoundingClientRect(); const menu = this.$refs.menu.getBoundingClientRect(); const spaceBelow = window.innerHeight - btn.bottom; const spaceAbove = btn.top; const dropUp = spaceBelow < menu.height && spaceAbove > menu.height; this.menuX = btn.right - menu.width; this.menuY = dropUp ? (btn.top - menu.height - 4) : (btn.bottom + 4); }); } }"
    @click.outside="open = false" @scroll.window="open = false" @resize.window="open = false"
    class="relative {{ $compact ? 'shrink-0' : 'inline-block text-left' }}">

    @if ($compact)
    <button x-ref="button" type="button" @click="toggle()" title="Aksi" class="p-1 -mr-1 text-secondary">
        <i data-lucide="more-vertical" class="size-4 pointer-events-none"></i>
    </button>
    @else
    <button x-ref="button" type="button" @click="toggle()" class="inline-flex items-center gap-2 h-9 px-3.5 rounded-lg border border-border bg-white text-secondary hover:bg-muted transition-all">
        <span class="text-sm font-medium">Aksi</span>
        <i data-lucide="chevron-down" class="size-4" :class="{ 'rotate-180': open }"></i>
    </button>
    @endif

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
            hx-target="#modal-container" hx-swap="innerHTML" hx-select="unset" hx-push-url="false"
            class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
            <i data-lucide="file-pen-line" class="size-4 text-secondary"></i> Edit Data
        </button>
        <button type="button" @click="open = false; ShowConfirm({ title: 'Hapus Dokumen?', message: 'Yakin ingin menghapus dokumen ini?', confirmText: 'Ya, Hapus', cancelText: 'Batal' }, () => { htmx.ajax('DELETE', '{{ route('admin.personnel.documents.destroy', $item->id) }}', { swap: 'none', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}' } }); })" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
            <i data-lucide="trash-2" class="size-4"></i> Hapus Data
        </button>
    </div>
</div>