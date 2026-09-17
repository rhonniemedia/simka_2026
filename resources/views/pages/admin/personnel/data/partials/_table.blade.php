{{-- File: resources/views/pages/admin/staff/data/partials/_table.blade.php --}}
<div id="staff-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshStaffData from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[32%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | NIK</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Kepegawaian
                        <div class="text-[11px] font-normal normal-case">Status | Nomor Induk Pegawai</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Kontak
                        <div class="text-[11px] font-normal normal-case">Telepon | Email</div>
                    </th>
                    <th class="w-[8%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Detail | Edit</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $r)
                @php
                $nik = $r->vault?->nik ?? '-';
                $nip = $r->vault?->nip;
                $status = $r->employmentStatus->alias ?? '-';
                $telepon = $r->vault?->phone_number ?? '-';
                $email = $r->vault?->email ?? '-';
                @endphp

                <tr id="row-staff-{{ $r->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$r->name" :gender="$r->gender" :index="$loop->index" />
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm whitespace-nowrap">
                                    {{ $r->name }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $nik }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $status }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-0.5">
                            @if(empty($nip) || strcasecmp($nip, 'tidak ada') === 0)
                            -
                            @else
                            <span class="font-semibold">NIP </span>{{ $nip }}
                            @endif
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $telepon }}</div>
                        <div class="flex items-center gap-1 text-xs text-secondary whitespace-nowrap mt-1">
                            <i data-lucide="mail" class="size-3 shrink-0"></i>
                            <span class="leading-none mt-[1px]">{{ $email }}</span>
                        </div>
                    </td>

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
                                class="fixed z-[9999] w-56 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left origin-top-right">

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Detail</p>
                                <button type="button" @click="open = false" hx-get="{{ route('admin.personnel.data.detail-personal', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="user" class="size-4 text-secondary pointer-events-none"></i> Data Pegawai
                                </button>
                                <button type="button" @click="open = false" hx-get="{{ route('admin.personnel.data.detail-employment', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="briefcase" class="size-4 text-secondary pointer-events-none"></i> Detail Kepegawaian
                                </button>

                                <div class="my-2 border-t border-border"></div>

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Edit & Delete</p>
                                <button type="button" @click="open = false" hx-get="{{ route('admin.personnel.data.edit-personal', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                <button type="button" hx-delete="{{ route('admin.personnel.data.destroy', $r->id) }}" hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML" hx-confirm="Yakin ingin menghapus data {{ $r->name }}? Tindakan ini tidak dapat dibatalkan." class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
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
        @forelse ($staff as $r)
        @php
        $nik = $r->vault?->nik ?? '-';
        $nip = $r->vault?->nip;
        $status = $r->employmentStatus->alias ?? '-';
        $telepon = $r->vault?->phone_number ?? '-';
        $email = $r->vault?->email ?? '-';
        @endphp

        <div id="card-staff-{{ $r->id }}" class="px-5 py-4 border-border hover:bg-muted/40 active:bg-muted/60 transition-colors">
            <div class="flex items-start gap-3">
                <x-ui.avatar :name="$r->name" :gender="$r->gender" :index="$loop->index" />

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground text-sm uppercase truncate block">
                                {{ $r->name_with_title }}
                            </p>
                            <p class="text-xs text-secondary mt-0.5 truncate flex items-center gap-1.5" title="NIK">
                                <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                {{ $nik }}
                            </p>
                        </div>

                        <span class="shrink-0 inline-flex px-2 py-1 rounded-md text-[10px] font-bold bg-primary/10 text-primary">
                            {{ $status }}
                        </span>
                    </div>

                    <div class="mt-3 border-t border-b border-border divide-y divide-border text-xs">
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="hash" class="size-3 text-slate-400"></i>
                                NIP
                            </p>
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                @if(!empty($nip) && strcasecmp($nip, 'tidak ada') !== 0)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-500/10 text-teal-700 whitespace-nowrap">{{ $nip }}</span>
                                @else
                                <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>

                        {{-- Baris Telepon --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="phone" class="size-3 text-slate-400"></i>
                                Telepon
                            </p>
                            <div class="text-right min-w-0">
                                <p class="text-foreground truncate">{{ $telepon }}</p>
                            </div>
                        </div>

                        {{-- Baris Email --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="mail" class="size-3 text-slate-400"></i>
                                Email
                            </p>
                            <div class="text-right min-w-0 flex-1">
                                <p class="text-foreground truncate" title="{{ $email }}">{{ $email }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 flex justify-end">
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
                        class="inline-flex items-center gap-2 h-8 px-3 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all focus:outline-none cursor-pointer whitespace-nowrap">
                        <span class="text-xs font-medium">Aksi</span>
                        <i data-lucide="chevron-down" class="size-3 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>

                    <div x-ref="menu" x-show="open" x-cloak
                        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        :style="`top: ${menuY}px; left: ${menuX}px;`"
                        class="fixed z-[9999] w-56 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left origin-top-right">

                        <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Detail</p>
                        <button type="button" @click="open = false" hx-get="{{ route('admin.personnel.data.detail-personal', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                            <i data-lucide="user" class="size-4 text-secondary pointer-events-none"></i> Data Pegawai
                        </button>
                        <button type="button" @click="open = false" hx-get="{{ route('admin.personnel.data.detail-employment', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                            <i data-lucide="briefcase" class="size-4 text-secondary pointer-events-none"></i> Detail Kepegawaian
                        </button>

                        <div class="my-2 border-t border-border"></div>

                        <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Edit & Delete</p>
                        <button type="button" @click="open = false" hx-get="{{ route('admin.personnel.data.edit-personal', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML" class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                            <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                        </button>

                        <button type="button" hx-delete="{{ route('admin.personnel.data.destroy', $r->id) }}" hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML" hx-confirm="Yakin ingin menghapus data {{ $r->name }}? Tindakan ini tidak dapat dibatalkan." class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
                            <i data-lucide="trash-2" class="size-4 pointer-events-none"></i> Hapus Data
                        </button>
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
    <x-ui.pagination :paginator="$staff" hxTarget="#staff-container" />

    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (!highlightId) return;

            const row = document.getElementById('row-staff-' + highlightId);
            const card = document.getElementById('card-staff-' + highlightId);
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