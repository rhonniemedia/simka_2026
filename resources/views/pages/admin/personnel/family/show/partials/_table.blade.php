{{-- File: resources/views/pages/admin/personnel/family/show/partials/_table.blade.php --}}
<div id="family-detail-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshFamilyDetail from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Anggota Keluarga
                        <div class="text-[11px] font-normal normal-case">Nama Lengkap | Status Hubungan</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Demografi
                        <div class="text-[11px] font-normal normal-case">Jenis Kelamin | Usia</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pendidikan & Pekerjaan
                        <div class="text-[11px] font-normal normal-case">Pendidikan | Profesi</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Lihat | Edit | Hapus</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($families as $member)
                @php
                $hubungan = $member->relationship ?? '-';
                $gender = $member->gender ?? '-';
                $usia = $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->age . ' Tahun' : '-';

                $hubLabel = match($hubungan) {
                'husband' => 'Suami',
                'wife' => 'Istri',
                'child' => 'Anak',
                'other' => 'Lainnya',
                default => '-',
                };

                $hubColor = match($hubungan) {
                'husband' => 'bg-blue-100 text-blue-700 border-blue-200',
                'wife' => 'bg-pink-100 text-pink-700 border-pink-200',
                'child' => 'bg-purple-100 text-purple-700 border-purple-200',
                default => 'bg-slate-100 text-slate-700 border-slate-200',
                };

                $iconColor = match($hubungan) {
                'husband', 'wife' => ['from' => 'from-rose-300', 'to' => 'to-rose-500', 'icon' => 'heart'],
                'child' => ['from' => 'from-indigo-300', 'to' => 'to-indigo-500', 'icon' => 'baby'],
                default => ['from' => 'from-slate-300', 'to' => 'to-slate-500', 'icon' => 'user'],
                };
                @endphp

                <tr id="row-family-{{ $member->id }}" class="group transition-colors hover:bg-muted/40">

                    {{-- Kolom 1: Anggota Keluarga --}}
                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                <div class="size-11 rounded-2xl bg-gradient-to-br {{ $iconColor['from'] }} {{ $iconColor['to'] }} text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10 transition-transform duration-200 group-hover:scale-[1.04]">
                                    <i data-lucide="{{ $iconColor['icon'] }}" class="size-5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm uppercase flex items-center gap-2 whitespace-nowrap">
                                    {{ $member->name ?? '-' }}
                                </div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $hubColor }} uppercase tracking-wider whitespace-nowrap">
                                        {{ $hubLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: Demografi --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap capitalize">
                            <i data-lucide="{{ strtolower($gender) === 'p' ? 'user-round-female' : 'user-round' }}" class="size-3.5 text-secondary/50"></i>
                            {{ $gender === 'P' ? 'Perempuan' : ($gender === 'L' ? 'Laki-Laki' : $gender) }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            Usia: {{ $usia }}
                        </div>
                    </td>

                    {{-- Kolom 3: Pendidikan & Pekerjaan --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            <i data-lucide="graduation-cap" class="size-3.5 text-secondary/50"></i>
                            {{ $member->education ?? '-' }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            {{ $member->occupation ?? '-' }}
                        </div>
                    </td>

                    {{-- Kolom 4: Aksi (Dropdown) --}}
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

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Aksi Keluarga</p>

                                @if ($member->document_file_id)
                                <button type="button" @click="open = false"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-text" class="size-4 pointer-events-none"></i> Lihat Berkas
                                </button>
                                @endif

                                <button type="button" @click="open = false"
                                    hx-get="#"
                                    hx-target="#modal-container" hx-swap="outerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                <button type="button"
                                    hx-delete="#"
                                    hx-target="#family-detail-container" hx-select="#family-detail-container" hx-swap="outerHTML"
                                    hx-confirm="Yakin ingin menghapus anggota keluarga ini?"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition-colors cursor-pointer text-left">
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
                                <i data-lucide="users" class="size-7 text-secondary/50"></i>
                            </div>
                            <p class="font-medium text-sm">Belum ada data keluarga untuk pegawai ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($families as $member)
        @php
        $hubungan = $member->relationship ?? '-';
        $gender = $member->gender ?? '-';
        $usia = $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->age . ' Tahun' : '-';

        $hubLabel = match($hubungan) {
        'husband' => 'Suami',
        'wife' => 'Istri',
        'child' => 'Anak',
        'other' => 'Lainnya',
        default => '-',
        };

        $hubColor = match($hubungan) {
        'husband' => 'bg-blue-100 text-blue-700 border-blue-200',
        'wife' => 'bg-pink-100 text-pink-700 border-pink-200',
        'child' => 'bg-purple-100 text-purple-700 border-purple-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
        };

        $iconColor = match($hubungan) {
        'husband', 'wife' => ['from' => 'from-rose-300', 'to' => 'to-rose-500', 'icon' => 'heart'],
        'child' => ['from' => 'from-indigo-300', 'to' => 'to-indigo-500', 'icon' => 'baby'],
        default => ['from' => 'from-slate-300', 'to' => 'to-slate-500', 'icon' => 'user'],
        };
        @endphp

        <div id="card-family-{{ $member->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="shrink-0">
                        <div class="size-11 rounded-2xl bg-gradient-to-br {{ $iconColor['from'] }} {{ $iconColor['to'] }} text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10">
                            <i data-lucide="{{ $iconColor['icon'] }}" class="size-5"></i>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-foreground text-sm uppercase truncate">
                            {{ $member->name ?? '-' }}
                        </div>
                        <p class="text-xs text-secondary mt-1 truncate flex items-center gap-1.5 capitalize">
                            <span class="inline-block size-1.5 rounded-full {{ strtolower($gender) === 'p' ? 'bg-pink-400' : 'bg-blue-400' }} shrink-0"></span>
                            {{ $gender === 'P' ? 'Perempuan' : ($gender === 'L' ? 'Laki-Laki' : $gender) }}
                        </p>
                    </div>
                </div>

                {{-- Status Hubungan --}}
                <div class="shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $hubColor }} uppercase tracking-wider">
                        {{ $hubLabel }}
                    </span>
                </div>
            </div>

            <div class="mt-3 border-y border-border divide-y divide-border text-xs">
                {{-- Baris Usia & Demografi --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="calendar" class="size-3.5 text-secondary/50"></i>
                        Usia
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $usia }}</p>
                    </div>
                </div>

                {{-- Baris Pendidikan & Pekerjaan --}}
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="briefcase" class="size-3.5 text-secondary/50"></i>
                        Pekerjaan
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $member->occupation ?? '-' }}</p>
                        <p class="text-secondary truncate mt-0.5">{{ $member->education ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Mobile --}}
            <div class="mt-3 flex items-center justify-end gap-2 pt-1">
                @if ($member->document_file_id)
                <button type="button"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-blue-200 bg-blue-50 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer" title="Lihat Berkas">
                    <i data-lucide="file-text" class="size-3.5"></i>
                    Lihat
                </button>
                @endif

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
                    hx-target="#family-detail-container"
                    hx-select="#family-detail-container"
                    hx-swap="outerHTML"
                    hx-confirm="Yakin ingin menghapus anggota keluarga ini?"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-red-200 bg-red-50 text-xs font-medium text-red-600 hover:bg-red-100 transition-colors cursor-pointer">
                    <i data-lucide="trash-2" class="size-3.5"></i>
                    Hapus
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center justify-center size-16 rounded-full bg-muted">
                    <i data-lucide="users" class="size-7 text-secondary/50"></i>
                </div>
                <p class="font-medium text-sm">Belum ada data keluarga untuk pegawai ini.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- ============ 3. PAGINATION ============ --}}
    <x-ui.pagination :paginator="$families" hxTarget="#family-detail-container" />

    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (!highlightId) return;
            const row = document.getElementById('row-family-' + highlightId);
            const card = document.getElementById('card-family-' + highlightId);
            const target = row || card;
            if (!target) return;
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            const highlightClasses = row ? ['bg-primary/10'] : ['ring-2', 'ring-primary', 'rounded-2xl', 'bg-primary/5'];
            target.classList.add(...highlightClasses, 'transition-colors', 'duration-700');
            setTimeout(() => target.classList.remove(...highlightClasses), 2500);

            params.delete('highlight');
            const query = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
        })();

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>