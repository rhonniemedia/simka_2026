{{-- File: resources/views/pages/admin/personnel/positions/partials/_table.blade.php --}}
<div id="staff-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshStaffList from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | Nomor Induk Kependudukan</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Status Kepegawaian
                        <div class="text-[11px] font-normal normal-case">Status | Jumlah Riwayat</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Jabatan
                        <div class="text-[11px] font-normal normal-case">Jabatan ASN Aktif | TMT</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Tautan
                        <div class="text-[11px] font-normal normal-case">Dokumen</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($staffList as $staff)
                @php
                $nik = $staff->vault?->nik ?? '-';
                // Sesuaikan attribute ini dengan nama kolom file SK di model Staff/PositionHistory Anda
                $hasFile = $staff->decree_file_id ?? false;
                @endphp
                <tr id="row-staff-{{ $staff->id }}" class="group transition-colors hover:bg-muted/40">
                    {{-- Kolom 1: Pegawai --}}
                    <td class="px-5 py-4 min-w-[240px]">
                        <a href="{{ route('admin.personnel.positions.show', $staff->id) }}" class="flex items-center gap-3 cursor-pointer">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$staff->name" :gender="$staff->gender" :index="$loop->index" />
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm group-hover:text-primary transition-colors whitespace-nowrap">
                                    {{ $staff->name }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-1 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $nik }}
                                </div>
                            </div>
                        </a>
                    </td>

                    {{-- Kolom 2: Status Kepegawaian --}}
                    <td class="px-5 py-4 min-w-[180px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $staff->employmentStatus?->name ?? '-' }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            <span>{{ $staff->position_history_count ?? 0 }}</span> riwayat
                        </div>
                    </td>

                    {{-- Kolom 3: Jabatan --}}
                    <td class="px-5 py-4 min-w-[220px]">
                        @if ($staff->active_position_name)
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $staff->active_position_name }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            TMT: <span class="font-semibold">{{ $staff->active_position_effective_date ? \Carbon\Carbon::parse($staff->active_position_effective_date)->translatedFormat('d M Y') : '-' }}</span>
                        </div>
                        @else
                        <span class="text-xs text-secondary/70">Belum ada jabatan aktif</span>
                        @endif
                    </td>

                    {{-- Kolom 4: Dokumen --}}
                    <td class="px-5 py-4 min-w-[100px]">
                        <div class="flex items-center gap-3">
                            @if ($hasFile)
                            <button type="button" class="shrink-0 hover:opacity-80 transition-opacity" title="Lihat Dokumen SK">
                                <div class="size-8 rounded-lg bg-red-50 flex items-center justify-center border border-red-200">
                                    <i data-lucide="file-text" class="size-4 text-red-600"></i>
                                </div>
                            </button>
                            @else
                            <div class="size-8 rounded-lg bg-muted flex items-center justify-center border border-border" title="Dokumen Tidak Tersedia">
                                <i data-lucide="file-x-2" class="size-4 text-secondary/50"></i>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium text-sm">Tidak ada data pegawai yang cocok.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS (di bawah lg) ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($staffList as $staff)
        @php
        $nik = $staff->vault?->nik ?? '-';
        $hasFile = $staff->decree_file_id ?? false;
        @endphp
        <div id="card-staff-{{ $staff->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors">
            {{-- Bagian Header Card: Avatar & Informasi Kiri --}}
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.personnel.positions.show', $staff->id) }}" class="flex items-center gap-3 min-w-0 cursor-pointer group">
                    <div class="shrink-0">
                        <x-ui.avatar :name="$staff->name" :gender="$staff->gender" :index="$loop->index" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-foreground text-sm uppercase truncate group-hover:text-primary transition-colors">
                            {{ $staff->name }}
                        </div>
                        <p class="text-xs text-secondary mt-1 truncate flex items-center gap-1.5">
                            <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                            {{ $nik }}
                        </p>
                    </div>
                </a>
            </div>

            <div class="mt-3 border-y border-border divide-y divide-border text-xs">
                {{-- Baris Status Kepegawaian --}}
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="shield-check" class="size-3.5 text-secondary/50"></i>
                        Status Pegawai
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $staff->employmentStatus?->name ?? '-' }}</p>
                        <p class="text-secondary truncate mt-0.5">{{ $staff->position_history_count ?? 0 }} riwayat</p>
                    </div>
                </div>
                {{-- Baris Jabatan Aktif --}}
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="briefcase" class="size-3.5 text-secondary/50"></i>
                        Jabatan Aktif
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        @if ($staff->active_position_name)
                        <p class="font-medium text-foreground truncate">{{ $staff->active_position_name }}</p>
                        <p class="text-secondary truncate mt-0.5">
                            TMT: {{ $staff->active_position_effective_date ? \Carbon\Carbon::parse($staff->active_position_effective_date)->translatedFormat('d M Y') : '-' }}
                        </p>
                        @else
                        <p class="text-secondary/70 truncate">Belum ada</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-end gap-2">
                @if ($hasFile)
                <button type="button" title="Lihat SK" class="inline-flex items-center justify-center size-8 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors cursor-pointer">
                    <i data-lucide="file-text" class="size-4"></i>
                </button>
                @else
                <div class="inline-flex items-center justify-center size-8 rounded-lg border border-border bg-muted" title="Dokumen Tidak Tersedia">
                    <i data-lucide="file-x-2" class="size-4 text-secondary/50"></i>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Tidak ada data pegawai yang cocok.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Memanggil komponen pagination --}}
    <x-ui.pagination :paginator="$staffList" hxTarget="#staff-container" />

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