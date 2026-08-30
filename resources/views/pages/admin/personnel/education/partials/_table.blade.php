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
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | NIK</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pendidikan
                        <div class="text-[11px] font-normal normal-case">Pendidikan | Jurusan</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Informasi Kelulusan
                        <div class="text-[11px] font-normal normal-case">Tahun | Satuan Pendidikan</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Dokumen
                        <div class="text-[11px] font-normal normal-case">File Ijazah</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $r)
                @php
                $nik = $r->vault?->nik ?? '-';

                $pendidikan = $r->highestEducation;
                $namaPendidikan = $pendidikan ? $pendidikan->level->alias : 'Belum ada data';
                $jurusan = $pendidikan ? $pendidikan->major : '-';

                // Mengambil tahun lulus dan institusi dari relasi highestEducation
                $tahunLulus = ($pendidikan && $pendidikan->graduation_date) ? \Carbon\Carbon::parse($pendidikan->graduation_date)->format('Y') : '-';
                $institusi = $pendidikan->institution_name ?? '-';

                // Cek ketersediaan file dokumen (sesuaikan nama propertinya dengan database Anda)
                $hasDocument = $pendidikan && $pendidikan->certificate_file_id;
                @endphp

                <tr id="row-staff-{{ $r->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                    <td class="px-5 py-4 min-w-[240px]">
                        <a href="{{ route('admin.personnel.education.show', $r->id) }}" class="flex items-center gap-3 group transition-all">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$r->name" :gender="$r->gender" :index="$loop->index" />
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm uppercase group-hover:text-primary transition-colors whitespace-nowrap">
                                    {{ $r->name }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $nik }}
                                </div>
                            </div>
                        </a>
                    </td>

                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-medium text-foreground whitespace-nowrap">{{ $namaPendidikan }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1">
                            {{ $jurusan }}
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            <i data-lucide="calendar-check" class="size-3.5 text-secondary/50"></i>
                            {{ $tahunLulus }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            {{ $institusi }}
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-[100px]">
                        <div class="flex items-center">
                            @if ($hasDocument)
                            <button type="button" class="shrink-0 hover:opacity-80 transition-opacity" title="Lihat Dokumen Ijazah">
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

        $pendidikan = $r->highestEducation;
        $namaPendidikan = $pendidikan ? $pendidikan->level->alias : 'Belum ada data';
        $jurusan = $pendidikan ? $pendidikan->major : '-';

        $tahunLulus = ($pendidikan && $pendidikan->graduation_date) ? \Carbon\Carbon::parse($pendidikan->graduation_date)->format('Y') : '-';
        $institusi = $pendidikan->institution_name ?? '-';

        $hasDocument = $pendidikan && $pendidikan->certificate_file_id;
        @endphp

        <div id="card-staff-{{ $r->id }}" class="px-5 py-4 border-border hover:bg-muted/40 active:bg-muted/60 transition-colors">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <x-ui.avatar :name="$r->name" :gender="$r->gender" :index="$loop->index" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('admin.personnel.education.show', $r->id) }}" class="font-semibold text-foreground text-sm uppercase truncate hover:text-primary transition-colors block">
                                {{ $r->name }}
                            </a>
                            <p class="text-xs text-secondary mt-0.5 truncate flex items-center gap-1.5" title="NIK">
                                <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                {{ $nik }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 border-t border-b border-border divide-y divide-border text-xs">
                        {{-- Baris Pendidikan --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="graduation-cap" class="size-3 text-slate-400"></i>
                                Pendidikan
                            </p>
                            <div class="text-right min-w-0 flex-1">
                                <p class="font-semibold text-foreground truncate">{{ $namaPendidikan }}</p>
                                <p class="text-secondary truncate mt-0.5">{{ $jurusan }}</p>
                            </div>
                        </div>

                        {{-- Baris Kelulusan --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="building-2" class="size-3 text-slate-400"></i>
                                Kelulusan
                            </p>
                            <div class="text-right min-w-0 flex-1">
                                <p class="font-semibold text-foreground truncate">Tahun {{ $tahunLulus }}</p>
                                <p class="text-secondary truncate mt-0.5">{{ $institusi }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 flex justify-end">
                @if ($hasDocument)
                <button type="button" title="Lihat Dokumen Ijazah" class="inline-flex items-center justify-center size-8 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors cursor-pointer">
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