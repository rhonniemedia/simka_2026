{{-- File: resources/views/pages/admin/personnel/positions/show/partials/_table.blade.php --}}
<div id="position-history-container">

    {{-- ============ 1. DESKTOP TABLE (lg ke atas) ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-5 py-3 text-sm font-bold text-secondary tracking-wider">
                        Jabatan ASN
                        <div class="text-[11px] font-normal normal-case">Nama | Jenis</div>
                    </th>
                    <th class="w-[30%] px-5 py-3 text-sm font-bold text-secondary tracking-wider">
                        SK & TMT
                        <div class="text-[11px] font-normal normal-case">Nomor SK | Tanggal SK</div>
                    </th>
                    <th class="w-[30%] px-5 py-3 text-sm font-bold text-secondary tracking-wider">
                        Status
                        <div class="text-[11px] font-normal normal-case">Status | TMT</div>
                    </th>
                    <th class="w-[10%] px-5 py-3 text-sm font-bold text-secondary tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($histories as $h)
                @php
                $positionTypeLabel = [
                'fungsional_keahlian' => 'Fungsional Keahlian',
                'fungsional_keterampilan' => 'Fungsional Keterampilan',
                'pelaksana' => 'Pelaksana',
                ][$h->asnPosition?->position_type] ?? '-';
                @endphp

                <tr id="row-position-history-{{ $h->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                    {{-- Kolom 1: Jabatan ASN (dengan avatar ikon) --}}
                    <td class="px-5 py-4 min-w-[220px]">
                        <div class="flex items-center gap-3">
                            <div class="size-10 shrink-0 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center">
                                <i data-lucide="briefcase-business" class="size-4.5"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-foreground truncate">{{ $h->asnPosition?->name ?? '-' }}</p>
                                <p class="text-xs text-secondary mt-0.5">{{ $positionTypeLabel }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: SK & TMT (Nomor SK & Tanggal SK) --}}
                    <td class="px-5 py-4 min-w-[180px]">
                        <p class="text-sm font-medium text-foreground">{{ $h->decree_number }}</p>
                        <p class="text-xs text-secondary mt-0.5">
                            {{ optional($h->decree_date)->translatedFormat('d M Y') }}
                        </p>
                    </td>

                    {{-- Kolom 3: Status (badge & TMT) --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        @if ($h->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span> Aktif
                        </span>
                        @else
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-secondary">
                            Nonaktif
                        </span>
                        @endif
                        <p class="text-xs text-secondary mt-1.5">
                            TMT: {{ optional($h->effective_date)->translatedFormat('d M Y') }}
                        </p>
                    </td>

                    {{-- Kolom 4: Aksi --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <button type="button"
                                hx-get="{{ route('admin.personnel.positions.edit', [$staff->id, $h->id]) }}"
                                hx-target="#modal-container" hx-swap="innerHTML"
                                class="size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-muted transition-colors cursor-pointer">
                                <i data-lucide="pencil" class="size-4 pointer-events-none"></i>
                            </button>
                            <button type="button"
                                hx-delete="{{ route('admin.personnel.positions.destroy', [$staff->id, $h->id]) }}"
                                hx-target="#position-history-container" hx-select="#position-history-container" hx-swap="outerHTML"
                                hx-confirm="Yakin ingin menghapus riwayat jabatan ini?"
                                class="size-9 flex items-center justify-center rounded-lg border border-error/30 bg-white text-error hover:bg-error/10 transition-colors cursor-pointer">
                                <i data-lucide="trash-2" class="size-4 pointer-events-none"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium text-sm">Belum ada riwayat jabatan ASN untuk pegawai ini</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARD (di bawah lg) ============ --}}
    <div class="lg:hidden flex flex-col gap-3">
        @forelse ($histories as $h)
        @php
        $positionTypeLabel = [
        'fungsional_keahlian' => 'Fungsional Keahlian',
        'fungsional_keterampilan' => 'Fungsional Keterampilan',
        'pelaksana' => 'Pelaksana',
        ][$h->asnPosition?->position_type] ?? '-';
        @endphp

        <div id="card-position-history-{{ $h->id }}" class="rounded-2xl border border-border p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="size-10 shrink-0 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center">
                        <i data-lucide="briefcase-business" class="size-4.5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-foreground text-sm truncate">{{ $h->asnPosition?->name ?? '-' }}</p>
                        <p class="text-xs text-secondary mt-0.5">{{ $positionTypeLabel }}</p>
                    </div>
                </div>
                @if ($h->is_active)
                <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span> Aktif
                </span>
                @else
                <span class="shrink-0 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-secondary">Nonaktif</span>
                @endif
            </div>

            <div class="mt-3 pt-3 border-t border-border/60 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <p class="text-[10px] font-bold text-secondary uppercase tracking-widest mb-0.5">No. SK</p>
                    <p class="font-medium text-foreground">{{ $h->decree_number }}</p>
                    <p class="text-secondary mt-0.5">{{ optional($h->decree_date)->translatedFormat('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-secondary uppercase tracking-widest mb-0.5">TMT</p>
                    <p class="font-medium text-foreground">{{ optional($h->effective_date)->translatedFormat('d M Y') }}</p>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button type="button"
                    hx-get="{{ route('admin.personnel.positions.edit', [$staff->id, $h->id]) }}"
                    hx-target="#modal-container" hx-swap="innerHTML"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-border text-xs font-semibold text-foreground hover:bg-muted transition-colors cursor-pointer">
                    <i data-lucide="pencil" class="size-3.5"></i> Edit
                </button>
                <button type="button"
                    hx-delete="{{ route('admin.personnel.positions.destroy', [$staff->id, $h->id]) }}"
                    hx-target="#position-history-container" hx-select="#position-history-container" hx-swap="outerHTML"
                    hx-confirm="Yakin ingin menghapus riwayat jabatan ini?"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-error/30 text-xs font-semibold text-error hover:bg-error/10 transition-colors cursor-pointer">
                    <i data-lucide="trash-2" class="size-3.5"></i> Hapus
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Belum ada riwayat jabatan ASN untuk pegawai ini</p>
            </div>
        </div>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$histories" hxTarget="#position-history-container" />

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>