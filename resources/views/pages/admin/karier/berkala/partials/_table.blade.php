{{-- File: resources/views/pages/admin/personnel/periodic-salary/partials/_table.blade.php --}}
<div id="periodic-salary-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshPeriodicSalary from:body"
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
                        Berkala Terakhir
                        <div class="text-[11px] font-normal normal-case">TMT | Masa Kerja</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Berkala Berikutnya
                        <div class="text-[11px] font-normal normal-case">TMT Berikutnya | Tahun Ke</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Tautan
                        <div class="text-[11px] font-normal normal-case">Dokumen</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($histories as $r)
                @php
                $staff = $r->staff;
                $nik = $staff?->vault?->nik ?? '-';

                $tmt = $r->effective_date ? \Carbon\Carbon::parse($r->effective_date)->translatedFormat('d F Y') : '-';
                $tmtBerikut = $r->tmt_berikut ? \Carbon\Carbon::parse($r->tmt_berikut)->translatedFormat('d F Y') : '-';

                $statusColor = match($r->verification_status) {
                'verified' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'rejected' => 'bg-red-100 text-red-700 border-red-200',
                default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $statusLabel = match($r->verification_status) {
                'verified' => 'Terverifikasi',
                'rejected' => 'Ditolak',
                default => 'Draf',
                };
                @endphp

                <tr id="row-salary-{{ $r->id }}" class="group transition-colors hover:bg-muted/40">
                    {{-- Kolom 1: Pegawai --}}
                    <td class="px-5 py-4 min-w-[240px]">
                        {{-- Jadikan div flex ini sebagai anchor <a> --}}
                        <a href="{{ route('admin.personnel.periodic-salary.show', $staff->id) }}" class="flex items-center gap-3 cursor-pointer">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$staff?->name ?? 'Unknown'" :gender="$staff?->gender" :index="$loop->index" />
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm group-hover:text-primary transition-colors whitespace-nowrap">
                                    {{ $staff?->name ?? 'Data Tidak Ditemukan' }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-1 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $nik }}
                                </div>
                            </div>
                        </a>
                    </td>

                    {{-- Kolom 2: Berkala Terakhir --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $tmt }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            MK. <span>{{ $r->masa_kerja }}</span>
                        </div>
                    </td>

                    {{-- Kolom 3: Berkala Berikutnya --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $tmtBerikut }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            Tahun ke. <span class="font-semibold">{{ $r->tahun_ke ?? '-' }}</span>
                        </div>
                    </td>

                    {{-- Kolom 4: Dokumen --}}
                    <td class="px-5 py-4 min-w-[100px]">
                        <div class="flex items-center gap-3">
                            @if ($r->decree_file_id)
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
                            <p class="font-medium text-sm">Tidak ada data riwayat gaji berkala ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS (di bawah lg) ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($histories as $r)
        @php
        $staff = $r->staff;
        $nik = $staff?->vault?->nik ?? '-';

        $tmt = ($r->effective_date && $r->effective_date !== '-')
        ? \Carbon\Carbon::parse($r->effective_date)->translatedFormat('d F Y')
        : '-';

        $tmtBerikut = ($r->tmt_berikut && $r->tmt_berikut !== '-')
        ? \Carbon\Carbon::parse($r->tmt_berikut)->translatedFormat('d F Y')
        : '-';

        $statusColor = match($r->verification_status) {
        'verified' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'rejected' => 'bg-red-100 text-red-700 border-red-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
        $statusLabel = match($r->verification_status) {
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
        default => 'Draf',
        };
        @endphp

        <div id="card-salary-{{ $r->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors">

            {{-- Bagian Header Card: Avatar & Informasi Kiri | Status Kanan --}}
            <div class="flex items-center justify-between gap-3">
                {{-- Jadikan div flex ini sebagai anchor <a> --}}
                <a href="{{ route('admin.personnel.periodic-salary.show', $staff->id) }}" class="flex items-center gap-3 min-w-0 cursor-pointer group">
                    <div class="shrink-0">
                        <x-ui.avatar :name="$staff?->name ?? 'Unknown'" :gender="$staff?->gender" :index="$loop->index" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-foreground text-sm uppercase truncate group-hover:text-primary transition-colors">
                            {{ $staff?->name ?? 'Data Tidak Ditemukan' }}
                        </div>
                        <p class="text-xs text-secondary mt-1 truncate flex items-center gap-1.5">
                            <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                            {{ $nik }}
                        </p>
                    </div>
                </a>

                {{-- Status Verifikasi --}}
                <div class="shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border {{ $statusColor }} uppercase tracking-wider">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <div class="mt-3 border-y border-border divide-y divide-border text-xs">
                {{-- Baris TMT & Masa Kerja --}}
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                        Berkala Terakhir
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $tmt }}</p>
                        <p class="text-secondary truncate mt-0.5">MK. <span class="font-medium">{{ $r->masa_kerja }}</span></p>
                    </div>
                </div>

                {{-- Baris Berkala Berikutnya --}}
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="calendar-clock" class="size-3.5 text-secondary/50"></i>
                        Estimasi Berikutnya
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $tmtBerikut }}</p>
                        <p class="text-secondary truncate mt-0.5">Tahun ke. <span class="font-medium">{{ $r->tahun_ke ?? '-' }}</span></p>
                    </div>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-end gap-2">
                @if ($r->decree_file_id)
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
                <p class="font-medium text-sm">Tidak ada data riwayat gaji berkala ditemukan.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Memanggil komponen pagination --}}
    <x-ui.pagination :paginator="$histories" hxTarget="#periodic-salary-container" />

    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (!highlightId) return;

            const row = document.getElementById('row-salary-' + highlightId);
            const card = document.getElementById('card-salary-' + highlightId);
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