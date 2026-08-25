{{-- File: resources/views/pages/admin/personnel/education/partials/_table.blade.php --}}
<div id="education-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshEducationData from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pendidikan
                        <div class="text-[11px] font-normal normal-case">Jenjang & Jurusan | Gelar</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Ijazah
                        <div class="text-[11px] font-normal normal-case">Tahun Lulus | Nomor Ijazah</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Satuan Pendidikan
                        <div class="text-[11px] font-normal normal-case">Institusi | Provinsi</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Edit | Delete</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($educations as $edu)
                @php
                // Palet warna pastel lembut per jenjang pendidikan
                $alias = $edu->level->alias ?? 'S1';
                $colorMap = [
                'S3' => ['from' => 'from-violet-300', 'to' => 'to-purple-400', 'ring' => 'ring-violet-300/40'],
                'S2' => ['from' => 'from-sky-300', 'to' => 'to-blue-400', 'ring' => 'ring-sky-300/40'],
                'S1' => ['from' => 'from-teal-300', 'to' => 'to-emerald-400', 'ring' => 'ring-teal-300/40'],
                'D4' => ['from' => 'from-cyan-300', 'to' => 'to-sky-400', 'ring' => 'ring-cyan-300/40'],
                'D3' => ['from' => 'from-amber-300', 'to' => 'to-orange-400', 'ring' => 'ring-amber-300/40'],
                'SMA' => ['from' => 'from-pink-300', 'to' => 'to-rose-400', 'ring' => 'ring-pink-300/40'],
                'SMK' => ['from' => 'from-pink-300', 'to' => 'to-rose-400', 'ring' => 'ring-pink-300/40'],
                ];
                $color = $colorMap[$alias] ?? ['from' => 'from-slate-300', 'to' => 'to-slate-400', 'ring' => 'ring-slate-300/40'];
                @endphp
                <tr id="row-edu-{{ $edu->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                {{-- Avatar BULAT dengan gradient pastel --}}
                                <div class="size-10 rounded-full bg-gradient-to-br {{ $color['from'] }} {{ $color['to'] }} text-white font-bold text-xs flex items-center justify-center border border-white/60 ring-1 {{ $color['ring'] }} uppercase shadow-sm">
                                    {{ $alias }}
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm whitespace-nowrap">
                                    {{ $edu->level->alias ?? 'Tidak Diketahui' }} <span class="font-normal text-secondary px-0.5">|</span> {{ $edu->major ?? '-' }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $edu->degree_name ?? '-' }} ({{ $edu->degree_abbreviation ?? '-' }})
                                </div>
                            </div>
                        </div>
                    </td>
                    {{-- Kolom Ijazah: Tahun (Atas) & Nomor (Bawah) --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">
                            {{ $edu->graduation_date ? \Carbon\Carbon::parse($edu->graduation_date)->translatedFormat('Y (d F Y)') : '-' }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            Nomor: {{ $edu->certificate_number ?? '-' }}
                        </div>
                    </td>
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $edu->institution_name }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            {{ $edu->province ?? '-' }}
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
                                class="fixed z-[9999] w-48 rounded-xl border border-border bg-white shadow-lg py-2 flex flex-col text-left origin-top-right">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Aksi Pendidikan</p>
                                <button type="button" @click="open = false"
                                    hx-get="#"
                                    hx-target="#modal-container" hx-swap="outerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>
                                <button type="button"
                                    hx-delete="#"
                                    hx-target="#education-container" hx-select="#education-container" hx-swap="outerHTML"
                                    hx-confirm="Yakin ingin menghapus riwayat pendidikan ini? Tindakan ini tidak dapat dibatalkan."
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer text-left">
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
                            <i data-lucide="graduation-cap" class="size-10 text-border"></i>
                            <p class="font-medium">Belum ada riwayat pendidikan untuk pegawai ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS (di bawah lg) ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border bg-white -mx-5 mb-5 mt-2">
        @forelse ($educations as $edu)
        @php
        $alias = $edu->level->alias ?? 'S1';
        $colorMap = [
        'S3' => ['from' => 'from-violet-300', 'to' => 'to-purple-400', 'ring' => 'ring-violet-300/40'],
        'S2' => ['from' => 'from-sky-300', 'to' => 'to-blue-400', 'ring' => 'ring-sky-300/40'],
        'S1' => ['from' => 'from-teal-300', 'to' => 'to-emerald-400', 'ring' => 'ring-teal-300/40'],
        'D4' => ['from' => 'from-cyan-300', 'to' => 'to-sky-400', 'ring' => 'ring-cyan-300/40'],
        'D3' => ['from' => 'from-amber-300', 'to' => 'to-orange-400', 'ring' => 'ring-amber-300/40'],
        'SMA' => ['from' => 'from-pink-300', 'to' => 'to-rose-400', 'ring' => 'ring-pink-300/40'],
        'SMK' => ['from' => 'from-pink-300', 'to' => 'to-rose-400', 'ring' => 'ring-pink-300/40'],
        ];
        $color = $colorMap[$alias] ?? ['from' => 'from-slate-300', 'to' => 'to-slate-400', 'ring' => 'ring-slate-300/40'];
        @endphp
        <div id="card-edu-{{ $edu->id }}" class="px-5 py-4 hover:bg-muted/40 active:bg-muted/60 transition-colors">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    {{-- Avatar BULAT dengan gradient pastel --}}
                    <div class="size-10 rounded-full bg-gradient-to-br {{ $color['from'] }} {{ $color['to'] }} text-white font-bold text-xs flex items-center justify-center border border-white/60 ring-1 {{ $color['ring'] }} uppercase shadow-sm mt-0.5">
                        {{ $alias }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold text-foreground text-sm truncate block">
                                {{ $edu->level->alias ?? 'Tidak Diketahui' }} <span class="font-normal text-secondary px-0.5">|</span> {{ $edu->major ?? '-' }}
                            </div>
                            <p class="text-xs text-secondary mt-0.5 truncate flex items-center gap-1.5">
                                <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                {{ $edu->degree_name ?? '-' }} ({{ $edu->degree_abbreviation ?? '-' }})
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 border-t border-b border-border divide-y divide-border text-xs">
                        {{-- Baris Ijazah: Tahun (Atas) & Nomor (Bawah) --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="file-text" class="size-3 text-slate-400"></i>
                                Ijazah
                            </p>
                            <div class="text-right min-w-0 flex-1">
                                <p class="font-medium text-foreground truncate">
                                    {{ $edu->graduation_date ? \Carbon\Carbon::parse($edu->graduation_date)->translatedFormat('Y (d F Y)') : '-' }}
                                </p>
                                <p class="text-secondary truncate mt-0.5">
                                    {{ $edu->certificate_number ?? '-' }}
                                </p>
                            </div>
                        </div>
                        {{-- Baris Institusi --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="building" class="size-3 text-slate-400"></i>
                                Institusi
                            </p>
                            <div class="text-right min-w-0 flex-1">
                                <p class="font-medium text-foreground truncate">{{ $edu->institution_name }}</p>
                                <p class="text-secondary truncate mt-0.5">{{ $edu->province ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Action Buttons Mobile --}}
            <div class="mt-3 flex items-center justify-end gap-2">
                <button type="button"
                    hx-get="#"
                    hx-target="#modal-container"
                    hx-swap="outerHTML"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer">
                    <i data-lucide="file-pen-line" class="size-3.5"></i>
                    Edit
                </button>
                <button type="button"
                    hx-delete="#"
                    hx-target="#education-container"
                    hx-select="#education-container"
                    hx-swap="outerHTML"
                    hx-confirm="Yakin ingin menghapus riwayat pendidikan ini? Tindakan ini tidak dapat dibatalkan."
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-error/20 bg-error/5 text-xs font-medium text-error hover:bg-error/10 transition-colors cursor-pointer">
                    <i data-lucide="trash-2" class="size-3.5"></i>
                    Hapus
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="graduation-cap" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Belum ada riwayat pendidikan untuk pegawai ini.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- ============ 3. PAGINATION (Selalu tampil) ============ --}}
    <x-ui.pagination :paginator="$educations" hxTarget="#education-container" />

    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (!highlightId) return;
            const row = document.getElementById('row-edu-' + highlightId);
            const card = document.getElementById('card-edu-' + highlightId);
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