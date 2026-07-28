{{-- File: resources/views/pages/admin/staff/data/partials/_table.blade.php --}}
<div id="staff-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshStaffData from:body"
    hx-swap="outerHTML">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[32%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | NIK</div>
                    </th>

                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Kepegawaian
                        <div class="text-[11px] font-normal normal-case">
                            Status |
                            <span class="inline sm:hidden">NIP</span>
                            <span class="hidden sm:inline">Nomor Induk Pegawai</span>
                        </div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Kontak
                        <div class="text-[11px] font-normal normal-case">Telepon | Email</div>
                    </th>

                    <th class="w-[8%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Detail | Edit</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $r)
                @php
                // Mengambil nilai dekripsi secara otomatis berdasarkan Attribute accessor di model DataVault
                $nik = $r->vault?->nik ?? '-';
                $nip = $r->vault?->nip;
                $nuptk = $r->vault?->nuptk ?? '-';
                $status = $r->employmentStatus->alias ?? '-';
                $telepon = $r->vault?->phone_number ?? '-';
                $email = $r->vault?->email ?? '-';
                @endphp

                <tr id="row-staff-{{ $r->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">
                    {{-- Kolom 1: Profil & NIK --}}
                    <td class="px-5 py-4 min-w-[240px]">
                        <div class="flex items-center gap-3">
                            {{-- Komponen avatar bawaan dari UI Anda --}}
                            <x-ui.avatar :name="$r->name" :gender="$r->gender" :index="$loop->index" />
                            <div>
                                {{-- HAPUS KELAS uppercase DAN UBAH VARIABELNYA --}}
                                <div class="font-semibold text-foreground text-sm whitespace-nowrap">{{ $r->name_with_title }}</div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-0.5">
                                    {{ $nik }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom 2: NIP & NUPTK --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-semibold text-foreground whitespace-nowrap">{{ $status }}</div>
                        <div class="text-xs text-secondary whitespace-nowrap">
                            @if(empty($nip) || strcasecmp($nip, 'tidak ada') === 0)
                            -
                            @else
                            <span class="font-semibold">NIP </span>{{ $nip }}
                            @endif
                        </div>
                    </td>

                    {{-- Kolom 3: Kontak --}}
                    <td class="px-5 py-4 min-w-[160px]">
                        <div class="text-sm font-semibold text-foreground whitespace-nowrap">{{ $telepon }}</div>
                        <div class="flex items-center gap-1 text-xs text-secondary whitespace-nowrap mt-1">
                            <i data-lucide="mail" class="size-3 shrink-0"></i>
                            <span class="leading-none mt-[1px]">{{ $email }}</span>
                        </div>
                    </td>

                    {{-- Kolom 4: Aksi --}}
                    <td class="px-5 py-4 min-w-[120px]">
                        <div
                            x-data="{
                                open: false,
                                menuX: 0,
                                menuY: 0,

                                toggle() {
                                    if (this.open) {
                                        this.open = false;
                                        return;
                                    }

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
                            @click.outside="open = false"
                            @scroll.window="open = false"
                            @resize.window="open = false"
                            class="relative inline-block text-left">

                            <button
                                x-ref="button"
                                type="button"
                                @click="toggle()"
                                title="Aksi"
                                class="inline-flex items-center gap-2 h-9 px-3.5 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all focus:outline-none cursor-pointer whitespace-nowrap">

                                <span class="text-sm font-medium">Aksi</span>
                                <i data-lucide="chevron-down" class="size-4 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </button>

                            <div
                                x-ref="menu"
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                :style="`top: ${menuY}px; left: ${menuX}px;`"
                                class="fixed z-[9999] w-56 rounded-xl border border-border bg-white shadow-lg py-3 flex flex-col text-left origin-top-right">

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Detail</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.staff.data.detail-personal', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="user" class="size-4 text-secondary pointer-events-none"></i> Data Pegawai
                                </button>
                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.staff.data.detail-employment', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="briefcase" class="size-4 text-secondary pointer-events-none"></i> Detail Kepegawaian
                                </button>

                                <div class="my-2 border-t border-border"></div>

                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-secondary">Edit & Delete</p>
                                <button type="button" @click="open = false"
                                    hx-get="{{ route('admin.staff.data.edit-personal', $r->id) }}" hx-target="#modal-container" hx-swap="outerHTML"
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-foreground hover:bg-muted transition-colors cursor-pointer text-left">
                                    <i data-lucide="file-pen-line" class="size-4 text-secondary pointer-events-none"></i> Edit Data
                                </button>

                                <button type="button"
                                    hx-delete="{{ route('admin.staff.data.destroy', $r->id) }}"
                                    hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML"
                                    hx-confirm="Yakin ingin menghapus data {{ $r->name }}? Tindakan ini tidak dapat dibatalkan."
                                    class="flex items-center gap-2 mx-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-error/10 transition-colors cursor-pointer">
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
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium">Tidak ada data pegawai ditemukan</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Memanggil komponen pagination --}}
    <x-ui.pagination :paginator="$staff" hxTarget="#staff-container" />
</div>