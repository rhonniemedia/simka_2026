{{-- Container khusus untuk tab Jenis Personel --}}
<div id="personnel-types-container" class="animate-fade-in"
    hx-get="{{ route('admin.master.personnel-types') }}"
    hx-trigger="refreshPersonnelTypes from:body"
    hx-swap="outerHTML"
    hx-push-url="true"
    data-no-loader>

    {{-- Header Area Konten --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-xl font-bold text-foreground">Data Jenis Personel</h2>
                <span class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold">
                    {{ $personnelTypes->count() }}
                </span>
            </div>
            <p class="text-sm text-secondary mt-0.5">Daftar referensi kode dan nama jenis personel.</p>
        </div>

        <button type="button"
            hx-get="{{ route('admin.master.personnel-types.create') }}"
            hx-target="#modal-container"
            hx-swap="innerHTML"
            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-semibold text-sm transition-all duration-300 cursor-pointer shadow-sm shadow-amber-600/30 whitespace-nowrap w-full sm:w-auto">
            <i data-lucide="plus" class="size-4 shrink-0"></i>
            <span>Tambah Jenis</span>
        </button>
    </div>

    @php
    $badgeColors = [
    ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
    ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
    ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
    ['bg' => 'bg-rose-100', 'text' => 'text-rose-700'],
    ];
    @endphp

    {{-- ============ DESKTOP TABLE ============ --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[45%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Jenis Personel
                        <div class="text-[11px] font-normal normal-case">Nama | Kode</div>
                    </th>
                    <th class="w-[45%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Informasi
                        <div class="text-[11px] font-normal normal-case">Alias</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Edit | Hapus</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($personnelTypes as $item)
                @php $color = $badgeColors[$loop->index % count($badgeColors)]; @endphp
                <tr id="row-personnel-type-{{ $item->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">

                    {{-- Kolom 1: Ikon & Nama --}}
                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <div class="size-10 rounded-xl {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center font-bold text-sm">
                                    {{ substr($item->code, 0, 2) }}
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm whitespace-nowrap">
                                    {{ $item->name }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    Kode: <span class="font-medium text-foreground">{{ $item->code }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: Alias --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $item->alias ?: '-' }}</div>
                    </td>

                    {{-- Kolom 3: Dropdown Aksi --}}
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
                                class="fixed z-[9999] w-48 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left origin-top-right">

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold tracking-wider text-secondary">Manajemen Data</p>

                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.master.personnel-types.edit', $item->id) }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                <div class="my-2 border-t border-border"></div>

                                <button type="button"
                                    @click="
                                        open = false;
                                        ShowConfirm({
                                            title: 'Hapus Jenis Personel?',
                                            message: 'Data ini bisa dipakai oleh data pegawai lain. Yakin ingin menghapus?',
                                            confirmText: 'Ya, Hapus',
                                            cancelText: 'Batal',
                                        }, () => {
                                            htmx.ajax('DELETE', '{{ route('admin.master.personnel-types.destroy', $item->id) }}', {
                                                swap: 'none',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                                                }
                                            });
                                        })
                                    "
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
                                    <i data-lucide="trash-2" class="size-4 pointer-events-none"></i> Hapus Data
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
                            <p class="font-medium">Belum ada data jenis personel.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ MOBILE CARDS ============ --}}
    <div class="md:hidden divide-y divide-border border-y border-border">
        @forelse ($personnelTypes as $item)
        @php $color = $badgeColors[$loop->index % count($badgeColors)]; @endphp
        <div id="card-personnel-type-{{ $item->id }}" class="py-3.5 flex items-center gap-3">
            <div class="size-9 rounded-xl {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center shrink-0 font-bold text-xs">
                {{ substr($item->code, 0, 2) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold text-foreground truncate">{{ $item->name }}</div>
                <div class="text-xs text-secondary mt-0.5 truncate">
                    Kode: <span class="font-medium text-foreground/80">{{ $item->code }}</span>
                    @if ($item->alias)
                    &middot; {{ $item->alias }}
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button"
                    hx-get="{{ route('admin.master.personnel-types.edit', $item->id) }}"
                    hx-target="#modal-container" hx-swap="innerHTML"
                    class="size-8 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:text-blue-600 hover:border-blue-600 transition-colors cursor-pointer focus:outline-none"
                    title="Edit Data">
                    <i data-lucide="file-pen-line" class="size-4"></i>
                </button>
                <button type="button"
                    @click="
                        ShowConfirm({
                            title: 'Hapus Jenis Personel?',
                            message: 'Data ini bisa dipakai oleh data pegawai lain. Yakin ingin menghapus?',
                            confirmText: 'Ya, Hapus',
                            cancelText: 'Batal',
                        }, () => {
                            htmx.ajax('DELETE', '{{ route('admin.master.personnel-types.destroy', $item->id) }}', {
                                swap: 'none',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                                }
                            });
                        })
                    "
                    class="size-8 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:text-error hover:border-error transition-colors cursor-pointer focus:outline-none"
                    title="Hapus Data">
                    <i data-lucide="trash-2" class="size-4"></i>
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center justify-center size-14 rounded-full bg-muted">
                    <i data-lucide="database" class="size-6 text-secondary/50"></i>
                </div>
                <p class="font-medium text-sm">Belum ada data jenis personel.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Re-inisialisasi Ikon Lucide untuk elemen yang baru dimuat via HTMX --}}
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>