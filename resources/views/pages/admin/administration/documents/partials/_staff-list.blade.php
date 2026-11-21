{{-- File: resources/views/pages/admin/administration/documents/partials/_staff-list.blade.php --}}
<div id="documents-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshDocuments from:body"
    hx-target="this"
    hx-select="#documents-container"
    hx-swap="outerHTML"
    hx-push-url="true"
    data-no-loader
    data-view="{{ $viewMode ?? 'all' }}"
    data-filter-count="{{ $activeFilterCount ?? 0 }}"
    data-search="{{ $search ?? '' }}">

    {{-- Ringkasan filter aktif (server-side) --}}
    @include('pages.admin.administration.documents.partials._active-filters')

    @php
    // Filter ikut dibawa ke halaman detail; "back" menyimpan daftar asal
    // (pencarian, filter, halaman) untuk tombol Kembali.
    $filtering = ($activeFilterCount ?? 0) > 0;
    $missing = ($filterPresence ?? null) === 'missing';

    // Mode "belum punya dokumen": pegawai ini justru tidak punya dokumen yang cocok filter,
    // jadi filter TIDAK dibawa ke detail (kalau dibawa, daftarnya pasti kosong).
    $carryFilters = $missing ? [] : [
    'filter_category' => $filterCategory ?? null,
    'filter_verification' => $filterVerification ?? null,
    'filter_link' => $filterLink ?? null,
    'filter_date_from' => $filterDateFrom ?? null,
    'filter_date_to' => $filterDateTo ?? null,
    ];
    $carryQuery = array_filter($carryFilters + [
    'back' => route('admin.personnel.documents.index', array_filter(request()->query(), fn ($v) => filled($v))),
    ], fn ($v) => filled($v));
    $detailUrl = fn ($id) => route('admin.personnel.documents.staff', $id) . '?' . http_build_query($carryQuery);
    @endphp

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    {{-- 3 kolom: 30% + 30% + 40% (sisa 10% ditambahkan ke kolom terakhir) --}}
    <x-ui.table>
        <thead>
            <tr class="border-b border-border">
                <x-ui.table.th width="w-[30%]" sub="Nama Pegawai | NIK">Data Pegawai</x-ui.table.th>
                <x-ui.table.th width="w-[30%]" :sub="$missing ? 'Kelengkapan berkas' : ($filtering ? 'Dokumen yang sesuai filter' : 'Total Berkas Terunggah')">Ringkasan Dokumen</x-ui.table.th>
                <x-ui.table.th width="w-[40%]" sub="Kelola Dokumen Pegawai">Aksi / Detail</x-ui.table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($staffList as $r)
            @php
            $nik = $r->vault?->nik ?? '-';
            $docCount = $r->documents_count ?? 0;
            @endphp
            <tr id="row-staff-{{ $r->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                {{-- Data Pegawai --}}
                <x-ui.table.td>
                    <a href="{{ $detailUrl($r->id) }}" class="flex items-center gap-3 group min-w-0">
                        <div class="shrink-0">
                            <x-ui.avatar :name="$r->name" :gender="$r->gender ?? 'M'" :index="$loop->index" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <x-ui.table.text :text="$r->name" class="font-semibold text-foreground text-sm group-hover:text-primary transition-colors" />
                            <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5 min-w-0">
                                <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="truncate" title="{{ $nik }}">{{ $nik }}</span>
                            </div>
                        </div>
                    </a>
                </x-ui.table.td>

                {{-- Ringkasan Dokumen --}}
                <x-ui.table.td>
                    <div class="flex items-center gap-2 min-w-0">
                        @if ($missing)
                        <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Belum ada yang sesuai</span>
                        <span class="min-w-0 truncate text-xs text-secondary">Total berkas: {{ $r->total_documents_count ?? 0 }}</span>
                        @else
                        <span class="truncate px-2.5 py-1 rounded-full text-xs font-semibold {{ $docCount > 0 ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-secondary' }}">
                            {{ $docCount }} {{ $filtering ? 'Dokumen Cocok' : 'Dokumen Tersedia' }}
                        </span>
                        @endif
                    </div>
                </x-ui.table.td>

                {{-- Aksi / Detail --}}
                <x-ui.table.td>
                    <a href="{{ $detailUrl($r->id) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-white text-xs font-semibold text-foreground hover:bg-muted transition-colors whitespace-nowrap">
                        <i data-lucide="eye" class="size-3.5 text-secondary"></i>
                        <span>Buka Detail</span>
                    </a>
                </x-ui.table.td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="px-4 py-16 text-center text-secondary">
                    <div class="flex flex-col items-center gap-3">
                        <i data-lucide="users" class="size-10 text-border"></i>
                        <p class="font-medium text-sm">{{ $missing ? 'Semua pegawai sudah memiliki dokumen yang sesuai filter' : 'Pegawai tidak ditemukan' }}</p>
                        <p class="text-xs text-secondary">Coba ubah kata kunci atau filter pencarian.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    {{-- ============ 2. MOBILE CARDS ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border bg-white -mx-5 mb-5 mt-2">
        @forelse ($staffList as $r)
        @php
        $nik = $r->vault?->nik ?? '-';
        $docCount = $r->documents_count ?? 0;
        @endphp
        <div id="card-staff-{{ $r->id }}" class="px-5 py-4 border-border hover:bg-muted/40 transition-colors">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <x-ui.avatar :name="$r->name" :gender="$r->gender ?? 'M'" :index="$loop->index" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ $detailUrl($r->id) }}" class="font-semibold text-foreground text-sm uppercase truncate hover:text-primary transition-colors block">
                                {{ $r->name }}
                            </a>
                            <p class="text-xs text-secondary mt-0.5 truncate flex items-center gap-1.5">
                                <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                {{ $nik }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between pt-2 border-t border-border">
                        @if ($missing)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                            Belum ada yang sesuai &bull; total {{ $r->total_documents_count ?? 0 }}
                        </span>
                        @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $docCount > 0 ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-secondary' }}">
                            {{ $docCount }} {{ $filtering ? 'dokumen cocok' : 'dokumen' }}
                        </span>
                        @endif
                        <a href="{{ $detailUrl($r->id) }}"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-border text-xs font-medium text-foreground hover:bg-muted">
                            <i data-lucide="eye" class="size-3 text-secondary"></i>
                            <span>Detail</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="size-10 text-border"></i>
                <p class="font-medium text-sm">{{ $missing ? 'Semua pegawai sudah memiliki dokumen yang sesuai filter' : 'Pegawai tidak ditemukan' }}</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        <x-ui.pagination :paginator="$staffList" hxTarget="#documents-container" />
    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>