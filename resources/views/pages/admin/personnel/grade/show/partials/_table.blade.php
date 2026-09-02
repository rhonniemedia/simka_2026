{{-- File: resources/views/pages/admin/personnel/promotions/show/partials/_table.blade.php --}}
<div id="promotions-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshPromotionsData from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pangkat & Golongan
                        <div class="text-[11px] font-normal normal-case">Nama Pangkat | Gol. Ruang</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Surat Keputusan (SK)
                        <div class="text-[11px] font-normal normal-case">Nomor SK | Tanggal SK</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Penetapan
                        <div class="text-[11px] font-normal normal-case">TMT Pangkat | Masa Kerja</div>
                    </th>
                    <th class="w-[10%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Lihat | Edit | Delete</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($histories as $history)
                @php
                $tmt = $history->effective_date ? \Carbon\Carbon::parse($history->effective_date)->translatedFormat('d F Y') : '-';
                $tglSK = $history->decree_date ? \Carbon\Carbon::parse($history->decree_date)->translatedFormat('d F Y') : '-';
                $isLatest = $loop->first;
                @endphp

                <tr id="row-promotion-{{ $history->id }}" class="group transition-colors hover:bg-muted/40 {{ $isLatest ? 'bg-primary/5' : '' }}">

                    {{-- Kolom 1: Pangkat & Golongan --}}
                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                @if($isLatest)
                                <div class="size-11 rounded-2xl bg-gradient-to-br from-emerald-300 to-emerald-500 text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10 transition-transform duration-200 group-hover:scale-[1.04]" title="Pangkat Saat Ini">
                                    <i data-lucide="medal" class="size-5"></i>
                                </div>
                                @else
                                <div class="size-11 rounded-2xl bg-gradient-to-br from-slate-200 to-slate-400 text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10 transition-transform duration-200 group-hover:scale-[1.04]">
                                    <i data-lucide="award" class="size-5"></i>
                                </div>
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm whitespace-nowrap flex items-center gap-2">
                                    {{ $history->grade?->grade_name ?? 'Tidak Diketahui' }}
                                    @if($isLatest)
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700">Terbaru</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-1 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full {{ $isLatest ? 'bg-emerald-500' : 'bg-slate-400' }} shrink-0"></span>
                                    Gol. {{ $history->grade?->grade_code ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: Surat Keputusan --}}
                    <td class="px-5 py-4 min-w-[200px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            <i data-lucide="file-text" class="size-3.5 text-secondary/50"></i>
                            {{ $history->decree_number ?? '-' }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            Tgl. {{ $tglSK }}
                        </div>
                    </td>

                    {{-- Kolom 3: Penetapan (TMT & MK) --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                            TMT. {{ $tmt }}
                        </div>
                        <div class="text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            MK. {{ $history->service_period_years ?? 0 }} Tahun
                        </div>
                    </td>

                    {{-- Kolom 4: Aksi (Dropdown Tergabung) --}}
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

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Aksi Kepangkatan</p>

                                @if ($history->decree_file_id)
                                <button type="button" @click="open = false"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-text" class="size-4 pointer-events-none"></i> Lihat SK
                                </button>
                                @endif

                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.personnel.promotions.edit', ['staff_id' => $staff->id, 'history_id' => $history->id]) }}"
                                    hx-target="#modal-container" hx-swap="innerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                <button type="button"
                                    @click="
                                        open = false;
                                        ShowConfirm({
                                            title: 'Hapus Riwayat Kepangkatan?',
                                            message: 'Yakin ingin menghapus riwayat kepangkatan ini? Tindakan ini tidak dapat dibatalkan.',
                                            confirmText: 'Ya, Hapus',
                                            cancelText: 'Batal',
                                        }, () => {
                                            htmx.ajax('DELETE', '{{ route('admin.personnel.promotions.destroy', ['staff_id' => $staff->id, 'history_id' => $history->id]) }}', {
                                                target: '#promotions-container',
                                                swap: 'outerHTML',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                                                }
                                            });
                                        })
                                    "
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
                                <i data-lucide="medal" class="size-7 text-secondary/50"></i>
                            </div>
                            <p class="font-medium text-sm">Belum ada riwayat kepangkatan untuk pegawai ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($histories as $history)
        @php
        $tmt = $history->effective_date ? \Carbon\Carbon::parse($history->effective_date)->translatedFormat('d F Y') : '-';
        $tglSK = $history->decree_date ? \Carbon\Carbon::parse($history->decree_date)->translatedFormat('d F Y') : '-';
        $isLatest = $loop->first;
        @endphp

        <div id="card-promotion-{{ $history->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors {{ $isLatest ? 'bg-primary/5' : '' }}">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    @if($isLatest)
                    <div class="size-11 rounded-2xl bg-gradient-to-br from-emerald-300 to-emerald-500 text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10">
                        <i data-lucide="medal" class="size-5"></i>
                    </div>
                    @else
                    <div class="size-11 rounded-2xl bg-gradient-to-br from-slate-200 to-slate-400 text-white flex items-center justify-center ring-2 ring-white shadow-sm shadow-black/10">
                        <i data-lucide="award" class="size-5"></i>
                    </div>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-foreground text-sm truncate flex items-center gap-2">
                        {{ $history->grade?->grade_name ?? 'Tidak Diketahui' }}
                        @if($isLatest)
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700">Terbaru</span>
                        @endif
                    </div>
                    <p class="text-xs text-secondary mt-1 truncate flex items-center gap-1.5">
                        <span class="inline-block size-1.5 rounded-full {{ $isLatest ? 'bg-emerald-500' : 'bg-slate-400' }} shrink-0"></span>
                        Gol. {{ $history->grade?->grade_code ?? '-' }}
                    </p>
                </div>
            </div>

            <div class="mt-3 border-t border-border divide-y divide-border text-xs">
                {{-- Baris Surat Keputusan --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="file-text" class="size-3.5 text-secondary/50"></i>
                        Nomor SK
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $history->decree_number ?? '-' }}</p>
                        <p class="text-secondary truncate mt-0.5">Tgl. {{ $tglSK }}</p>
                    </div>
                </div>

                {{-- Baris TMT & MK --}}
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0">
                        <i data-lucide="calendar-check-2" class="size-3.5 text-secondary/50"></i>
                        Penetapan
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">TMT. {{ $tmt }}</p>
                        <p class="text-secondary truncate mt-0.5">MK. {{ $history->service_period_years ?? 0 }} Tahun</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Mobile --}}
            <div class="mt-3 flex items-center justify-end gap-2 border-t border-border pt-3">
                @if ($history->decree_file_id)
                <button type="button"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-blue-200 bg-blue-50 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer" title="Lihat SK">
                    <i data-lucide="file-text" class="size-3.5"></i>
                    Lihat
                </button>
                @endif

                <button type="button"
                    hx-get="{{ route('admin.personnel.promotions.edit', ['staff_id' => $staff->id, 'history_id' => $history->id]) }}"
                    hx-target="#modal-container"
                    hx-swap="innerHTML"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-xs font-medium text-secondary hover:bg-muted transition-colors cursor-pointer">
                    <i data-lucide="file-pen-line" class="size-3.5"></i>
                    Edit
                </button>
                <button type="button"
                    @click="
                        ShowConfirm({
                            title: 'Hapus Riwayat Kepangkatan?',
                            message: 'Yakin ingin menghapus riwayat kepangkatan ini? Tindakan ini tidak dapat dibatalkan.',
                            confirmText: 'Ya, Hapus',
                            cancelText: 'Batal',
                        }, () => {
                            htmx.ajax('DELETE', '{{ route('admin.personnel.promotions.destroy', ['staff_id' => $staff->id, 'history_id' => $history->id]) }}', {
                                target: '#promotions-container',
                                swap: 'outerHTML',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                                }
                            });
                        })
                    "
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
                    <i data-lucide="medal" class="size-7 text-secondary/50"></i>
                </div>
                <p class="font-medium text-sm">Belum ada riwayat kepangkatan untuk pegawai ini.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- ============ 3. PAGINATION ============ --}}
    <x-ui.pagination :paginator="$histories" hxTarget="#promotions-container" />

    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (!highlightId) return;
            const row = document.getElementById('row-promotion-' + highlightId);
            const card = document.getElementById('card-promotion-' + highlightId);
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

        // Guard supaya listener tidak dobel setiap kali partial ini di-swap ulang oleh htmx
        if (!window.__gradeHistoryErrorHandlerAttached) {
            window.__gradeHistoryErrorHandlerAttached = true;

            document.body.addEventListener('htmx:responseError', function(evt) {
                const path = evt.detail?.requestConfig?.path || '';
                if (!path.includes('promotions')) return;

                const status = evt.detail?.xhr?.status;
                let text = 'Terjadi kesalahan saat memproses permintaan.';
                if (status === 419) {
                    text = 'Sesi Anda kedaluwarsa (token CSRF tidak valid). Silakan muat ulang halaman lalu coba lagi.';
                } else if (status === 404) {
                    text = 'Data yang ingin dihapus tidak ditemukan. Coba muat ulang halaman.';
                } else if (status === 500) {
                    text = 'Terjadi kesalahan pada server saat memproses data.';
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire('Gagal!', text, 'error');
                } else {
                    alert(text);
                }
            });

            document.body.addEventListener('htmx:sendError', function() {
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