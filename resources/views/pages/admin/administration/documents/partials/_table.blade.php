{{-- Dipakai di index (Semua Dokumen) dan halaman detail pegawai. Param: $documents --}}
@php
$hasQuery = ($activeFilterCount ?? 0) > 0 || filled($search ?? null);
$emptyText = $hasQuery ? 'Tidak ada dokumen yang sesuai pencarian atau filter.' : 'Belum ada data dokumen.';
$verificationBadges = [
'draft' => ['class' => 'bg-slate-100 text-slate-600', 'label' => 'Draft'],
'verified' => ['class' => 'bg-emerald-100 text-emerald-700', 'label' => 'Terverifikasi'],
'rejected' => ['class' => 'bg-red-100 text-red-700', 'label' => 'Ditolak'],
];
@endphp

<div id="documents-container" class="animate-fade-in"
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

    {{-- ============ DESKTOP ============ --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[36%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Dokumen
                        <div class="text-[11px] font-normal normal-case">Nama, Status & Nomor Dokumen</div>
                    </th>
                    <th class="w-[27%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pemilik
                        <div class="text-[11px] font-normal normal-case">Pegawai & Info Unggahan</div>
                    </th>
                    <th class="w-[27%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Informasi
                        <div class="text-[11px] font-normal normal-case">Kategori & Tautan</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Kelola</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $item)
                @php $badge = $verificationBadges[$item->verification_status] ?? $verificationBadges['draft']; @endphp
                <tr class="border-b border-border hover:bg-muted/50 transition-colors">
                    {{-- Dokumen: nama + badge status + nomor --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="shrink-0 h-10 w-10 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center">
                                <i data-lucide="file-text" class="size-5 text-red-500 pointer-events-none"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-foreground text-sm flex items-center gap-2 min-w-0">
                                    <span class="truncate">{{ $item->document_name }}</span>
                                    <span class="inline-flex shrink-0 px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </div>
                                @if ($item->document_number)
                                <div class="text-xs text-secondary mt-0.5 truncate">No. {{ $item->document_number }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Pemilik: pegawai + pengunggah + tanggal dokumen --}}
                    <td class="px-4 py-4">
                        <div class="font-semibold text-foreground text-sm truncate">{{ $item->staff->name ?? '-' }}</div>
                        <div class="flex items-center gap-1.5 text-xs text-secondary mt-1 truncate">
                            <i data-lucide="cloud-upload" class="size-3 shrink-0 pointer-events-none"></i>
                            <span class="truncate">{{ $item->creator_label }}</span>
                            <i data-lucide="calendar-days" class="size-3 shrink-0 pointer-events-none"></i>
                            <span class="truncate">{{ $item->document_date?->translatedFormat('d M Y') ?? '-' }}</span>
                        </div>
                    </td>

                    {{-- Informasi: kategori + tautan --}}
                    <td class="px-4 py-4">
                        <div class="text-sm font-semibold text-foreground truncate">{{ $item->category->name ?? '-' }}</div>
                        <div class="mt-1.5">
                            @include('pages.admin.administration.documents.partials._link-badge', ['item' => $item])
                        </div>
                    </td>

                    {{-- Aksi --}}
                    <td class="px-4 py-4">
                        @include('pages.admin.administration.documents.partials._actions', ['item' => $item])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium text-sm">{{ $emptyText }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ MOBILE ============ --}}
    <div class="md:hidden divide-y divide-border border-y border-border mt-2 mb-4">
        @forelse ($documents as $item)
        @php $badge = $verificationBadges[$item->verification_status] ?? $verificationBadges['draft']; @endphp
        <div class="py-3.5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 h-10 w-10 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center">
                    <i data-lucide="file-text" class="size-5 text-red-500 pointer-events-none"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-foreground flex items-center gap-2 min-w-0">
                            <span class="truncate">{{ $item->document_name }}</span>
                            <span class="inline-flex shrink-0 px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </div>
                        @include('pages.admin.administration.documents.partials._actions', ['item' => $item, 'compact' => true])
                    </div>

                    @if ($item->document_number)
                    <div class="text-xs text-secondary mt-0.5 truncate">No. {{ $item->document_number }}</div>
                    @endif

                    <div class="text-xs font-semibold text-foreground mt-1.5 truncate">{{ $item->staff->name ?? '-' }}</div>
                    <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5">
                        <i data-lucide="cloud-upload" class="size-3 shrink-0 pointer-events-none"></i>
                        <span class="truncate">{{ $item->creator_label }}</span>
                        <i data-lucide="calendar-days" class="size-3 shrink-0 pointer-events-none"></i>
                        <span class="truncate">{{ $item->document_date?->translatedFormat('d M Y') ?? '-' }}</span>
                    </div>

                    <div class="text-xs text-secondary mt-1.5 truncate">{{ $item->category->name ?? '-' }}</div>
                    <div class="mt-0.5">
                        @include('pages.admin.administration.documents.partials._link-badge', ['item' => $item])
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <p class="font-medium text-sm">{{ $emptyText }}</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        <x-ui.pagination :paginator="$documents" hxTarget="#documents-container" />
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>