{{-- File: resources/views/pages/admin/personnel/promotions/partials/_table.blade.php --}}
<div id="promotions-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshPromotions from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | NIK</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Kepangkatan
                        <div class="text-[11px] font-normal normal-case">Pangkat Golongan | TMT</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Informasi
                        <div class="text-[11px] font-normal normal-case">Usia Pangkat | Nomor SK</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Dokumen
                        <div class="text-[11px] font-normal normal-case">File SK</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($histories as $r)
                @php
                $staff = $r->staff;
                $nik = $staff?->vault?->nik ?? '-';

                // Format tanggal dan kalkulasi Usia Pangkat
                $tmtObj = $r->effective_date ? \Carbon\Carbon::parse($r->effective_date) : null;
                $tmt = $tmtObj ? $tmtObj->translatedFormat('d F Y') : '-';

                $usiaPangkat = '-';
                if ($tmtObj) {
                $diff = $tmtObj->diff(\Carbon\Carbon::now());
                $usiaPangkat = $diff->y . ' Tahun ' . $diff->m . ' Bulan';
                }
                @endphp

                <tr id="row-promotion-{{ $r->id }}" class="group transition-colors hover:bg-muted/40">
                    <td class="px-5 py-4 min-w-[240px]">
                        <a href="{{ route('admin.personnel.promotions.show', $staff->id) }}" class="flex items-center gap-3 cursor-pointer group">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$staff?->name ?? 'Unknown'" :gender="$staff?->gender" :index="$loop->index" />
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm uppercase group-hover:text-primary transition-colors whitespace-nowrap">
                                    {{ $staff?->name ?? 'Data Tidak Ditemukan' }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-1 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $nik }}
                                </div>
                            </div>
                        </a>
                    </td>

                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">
                            {{ $r->grade?->grade_name ?? '-' }} ({{ $r->grade?->grade_code ?? '-' }})
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            TMT. {{ $tmt }}
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            <i data-lucide="clock" class="size-3.5 text-secondary/50"></i>
                            {{ $usiaPangkat }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            SK. {{ $r->decree_number ?? '-' }}
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[100px]">
                        <div class="flex items-center">
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
                            <p class="font-medium text-sm">Tidak ada data riwayat kepangkatan ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($histories as $r)
        @php
        $staff = $r->staff;
        $nik = $staff?->vault?->nik ?? '-';

        $tmtObj = $r->effective_date ? \Carbon\Carbon::parse($r->effective_date) : null;
        $tmt = $tmtObj ? $tmtObj->translatedFormat('d F Y') : '-';

        $usiaPangkat = '-';
        if ($tmtObj) {
        $diff = $tmtObj->diff(\Carbon\Carbon::now());
        $usiaPangkat = $diff->y . ' Tahun ' . $diff->m . ' Bulan';
        }
        @endphp

        <div id="card-promotion-{{ $r->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors">

            {{-- Header Card --}}
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.personnel.promotions.show', $staff->id) }}" class="flex items-center gap-3 min-w-0 cursor-pointer group">
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
            </div>

            {{-- Detail Data --}}
            <div class="mt-3 border-y border-border divide-y divide-border text-xs">

                {{-- Baris Kepangkatan --}}
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="medal" class="size-3.5 text-secondary/50"></i>
                        Kepangkatan
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $r->grade?->grade_name ?? '-' }} ({{ $r->grade?->grade_code ?? '-' }})</p>
                        <p class="text-secondary truncate mt-0.5">TMT. {{ $tmt }}</p>
                    </div>
                </div>

                {{-- Baris Informasi --}}
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="info" class="size-3.5 text-secondary/50"></i>
                        Informasi
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $usiaPangkat }}</p>
                        <p class="text-secondary truncate mt-0.5">SK. {{ $r->decree_number ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Dokumen Mobile --}}
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
                <p class="font-medium text-sm">Tidak ada data riwayat kepangkatan ditemukan.</p>
            </div>
        </div>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$histories" hxTarget="#promotions-container" />

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>