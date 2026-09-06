@php
use App\Enums\Staff\FamilyRelation;
use App\Enums\Staff\Profession;
@endphp

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
                $person = $member->familyMember;
                $hubungan = $member->relationship ?? '-';
                $gender = $person->gender ?? '-';
                $rawBirthDate = $person->birth_date ?? null;
                $usia = $rawBirthDate ? \Carbon\Carbon::parse($rawBirthDate)->age . ' Tahun' : '-';

                $hubLabel = FamilyRelation::tryFrom($hubungan)?->label() ?? '-';
                $occupationLabel = Profession::tryFrom($person->occupation ?? '')?->label() ?? ($person->occupation ?? '-');
                $isSharedPerson = ($person->relations_count ?? 0) > 1;
                $isLinkedStaff = (bool) ($person->linked_staff_id ?? null);

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
                                    {{ $person->name ?? '-' }}
                                </div>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $hubColor }} uppercase tracking-wider whitespace-nowrap">
                                        {{ $hubLabel }}
                                    </span>
                                    @if ($isSharedPerson)
                                    <span title="Data orang ini dipakai bersama staff lain" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border bg-slate-100 text-slate-600 border-slate-200 uppercase tracking-wider whitespace-nowrap">
                                        <i data-lucide="users" class="size-3"></i> Bersama
                                    </span>
                                    @endif
                                    @if ($isLinkedStaff)
                                    <span title="Orang ini juga tercatat sebagai staff - cek status tunjangan" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border bg-amber-100 text-amber-700 border-amber-200 uppercase tracking-wider whitespace-nowrap">
                                        <i data-lucide="triangle-alert" class="size-3"></i> Staff
                                    </span>
                                    @endif
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
                            {{ $person->educationLevel?->alias ?? '-' }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            {{ $occupationLabel }}
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

                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.personnel.family.edit', [$staff->id, $member->id]) }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                <button type="button"
                                    @click="
                                        open = false;
                                        ShowConfirm({
                                            title: 'Hapus Anggota Keluarga?',
                                            message: 'Yakin ingin menghapus anggota keluarga ini? Tindakan ini tidak dapat dibatalkan.',
                                            confirmText: 'Ya, Hapus',
                                            cancelText: 'Batal',
                                        }, () => {
                                            htmx.ajax('DELETE', '{{ route('admin.personnel.family.destroy', ['staff_id' => $staff->id, 'family_id' => $member->id]) }}', {
                                                target: '#family-detail-container',
                                                swap: 'outerHTML',
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
        $person = $member->familyMember;
        $hubungan = $member->relationship ?? '-';
        $gender = $person->gender ?? '-';
        $rawBirthDate = $person->birth_date ?? null;
        $usia = $rawBirthDate ? \Carbon\Carbon::parse($rawBirthDate)->age . ' Tahun' : '-';

        $hubLabel = FamilyRelation::tryFrom($hubungan)?->label() ?? '-';
        $occupationLabel = Profession::tryFrom($person->occupation ?? '')?->label() ?? ($person->occupation ?? '-');
        $isSharedPerson = ($person->relations_count ?? 0) > 1;
        $isLinkedStaff = (bool) ($person->linked_staff_id ?? null);

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
                            {{ $person->name ?? '-' }}
                        </div>
                        <p class="text-xs text-secondary mt-1 truncate flex items-center gap-1.5 capitalize">
                            <span class="inline-block size-1.5 rounded-full {{ strtolower($gender) === 'p' ? 'bg-pink-400' : 'bg-blue-400' }} shrink-0"></span>
                            {{ $gender === 'P' ? 'Perempuan' : ($gender === 'L' ? 'Laki-Laki' : $gender) }}
                        </p>
                        <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                            @if ($isSharedPerson)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border bg-slate-100 text-slate-600 border-slate-200 uppercase tracking-wider">
                                <i data-lucide="users" class="size-3"></i> Bersama
                            </span>
                            @endif
                            @if ($isLinkedStaff)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border bg-amber-100 text-amber-700 border-amber-200 uppercase tracking-wider">
                                <i data-lucide="triangle-alert" class="size-3"></i> Staff
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $hubColor }} uppercase tracking-wider">
                        {{ $hubLabel }}
                    </span>
                </div>
            </div>

            <div class="mt-3 border-y border-border divide-y divide-border text-xs">
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="calendar" class="size-3.5 text-secondary/50"></i>
                        Usia
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $usia }}</p>
                    </div>
                </div>

                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="briefcase" class="size-3.5 text-secondary/50"></i>
                        Pekerjaan
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $occupationLabel }}</p>
                        <p class="text-secondary truncate mt-0.5">{{ $person->educationLevel?->alias ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Mobile --}}
            <div class="mt-3 flex items-center justify-end gap-2 pt-1">
                <button type="button"
                    hx-get="{{ route('admin.personnel.family.edit', [$staff->id, $member->id]) }}"
                    hx-target="#modal-container"
                    hx-swap="innerHTML"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer">
                    <i data-lucide="file-pen-line" class="size-3.5"></i>
                    Edit
                </button>
                <button type="button"
                    @click="
                        ShowConfirm({
                            title: 'Hapus Anggota Keluarga?',
                            message: 'Yakin ingin menghapus anggota keluarga ini? Tindakan ini tidak dapat dibatalkan.',
                            confirmText: 'Ya, Hapus',
                            cancelText: 'Batal',
                        }, () => {
                            htmx.ajax('DELETE', '{{ route('admin.personnel.family.destroy', ['staff_id' => $staff->id, 'family_id' => $member->id]) }}', {
                                target: '#family-detail-container',
                                swap: 'outerHTML',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                                }
                            });
                        })
                    "
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-error/20 bg-error/5 text-xs font-medium text-error hover:bg-error/10 transition-colors cursor-pointer">
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

        // Listener untuk menangani error saat request HTMX khusus keluarga
        if (!window.__familyErrorHandlerAttached) {
            window.__familyErrorHandlerAttached = true;

            document.body.addEventListener('htmx:responseError', function(evt) {
                const path = evt.detail?.requestConfig?.path || '';
                if (!path.includes('family')) return;

                const status = evt.detail?.xhr?.status;
                let text = 'Terjadi kesalahan saat memproses permintaan.';
                if (status === 419) {
                    text = 'Sesi Anda kedaluwarsa (token CSRF tidak valid). Silakan muat ulang halaman lalu coba lagi.';
                } else if (status === 404) {
                    text = 'Data tidak ditemukan. Coba muat ulang halaman.';
                } else if (status === 500) {
                    text = 'Terjadi kesalahan pada server saat memproses data.';
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire('Gagal!', text, 'error');
                } else {
                    alert(text);
                }
            });

            document.body.addEventListener('htmx:sendError', function(evt) {
                const path = evt.detail?.requestConfig?.path || '';
                if (!path.includes('family')) return;

                const text = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Gagal!', text, 'error');
                } else {
                    alert(text);
                }
            });
        }
    </script>
</div>