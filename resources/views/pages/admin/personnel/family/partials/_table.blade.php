{{-- File: resources/views/pages/admin/personnel/family/partials/_table.blade.php --}}
<div id="family-container"
    hx-get="{{ request()->fullUrl() }}"
    hx-trigger="refreshFamily from:body"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE ============ --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Data Pegawai
                        <div class="text-[11px] font-normal normal-case">Nama | Nomor Induk Kependudukan</div>
                    </th>
                    <th class="w-[30%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pasangan
                        <div class="text-[11px] font-normal normal-case">Nama Lengkap | Hubungan</div>
                    </th>
                    <th class="w-[40%] px-4 py-3 text-sm font-bold text-secondary tracking-wider">
                        Pekerjaan dan Kontak
                        <div class="text-[11px] font-normal normal-case">Pekerjaan | Telepon</div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border border-b border-border">
                @forelse ($staffs as $staff)
                @php
                $nik = $staff->vault?->nik ?? '-';
                $f = $staff->familyRelations->first();

                $hubungan = $f->relationship ?? '-';

                $namaPasangan = $f->familyMember->name ?? '-';
                $gender = $f->familyMember->gender ?? '-';
                $telepon = $f->familyMember->telephone ?? '-';

                // Format Pekerjaan menggunakan Enum
                $occ = $f->familyMember->occupation ?? null;
                $pekerjaan = '-';
                if ($occ instanceof \App\Enums\Staff\Profession) {
                $pekerjaan = $occ->label();
                } elseif (is_string($occ)) {
                $pekerjaan = \App\Enums\Staff\Profession::tryFrom($occ)?->label() ?? $occ;
                }

                $hubLabel = match($hubungan) {
                'husband', 'suami' => 'Suami',
                'wife', 'istri' => 'Istri',
                'child', 'anak' => 'Anak',
                'other' => 'Lainnya',
                default => '-',
                };

                $hubColor = match($hubungan) {
                'husband', 'suami' => 'bg-blue-100 text-blue-700 border-blue-200',
                'wife', 'istri' => 'bg-pink-100 text-pink-700 border-pink-200',
                'child', 'anak' => 'bg-purple-100 text-purple-700 border-purple-200',
                default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                @endphp

                <tr id="row-staff-{{ $staff->id }}" class="group transition-colors hover:bg-muted/40">
                    <td class="px-5 py-4 min-w-[240px]">
                        <a href="{{ route('admin.personnel.family.show', $staff->id) }}" class="flex items-center gap-3 cursor-pointer group">
                            <div class="shrink-0">
                                <x-ui.avatar :name="$staff->name ?? 'Unknown'" :gender="$staff->gender" :index="$loop->index" />
                            </div>
                            <div>
                                <div class="font-semibold text-foreground text-sm group-hover:text-primary transition-colors whitespace-nowrap">
                                    {{ $staff->name ?? 'Data Tidak Ditemukan' }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-secondary mt-1 whitespace-nowrap">
                                    <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    {{ $nik }}
                                </div>
                            </div>
                        </a>
                    </td>

                    <td class="px-5 py-4 min-w-[200px]">
                        @if ($f)
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-semibold text-foreground whitespace-nowrap">{{ $namaPasangan }}</div>
                            <i data-lucide="{{ strtolower($gender) === 'p' || strtolower($gender) === 'perempuan' ? 'user-round-female' : 'user-round' }}" class="size-3.5 text-secondary/50" title="Gender: {{ $gender }}"></i>
                        </div>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $hubColor }} uppercase tracking-wider">
                                {{ $hubLabel }}
                            </span>
                        </div>
                        @else
                        <span class="text-xs text-secondary italic">Belum ada data pasangan</span>
                        @endif
                    </td>

                    <td class="px-5 py-4 min-w-[240px]">
                        @if ($f)
                        <div class="flex items-center gap-1.5 text-sm font-medium text-foreground whitespace-nowrap">
                            <i data-lucide="briefcase" class="size-3.5 text-secondary/50"></i>
                            {{ $pekerjaan }}
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-secondary whitespace-nowrap mt-1 pl-5">
                            <i data-lucide="phone" class="size-3 text-secondary/50"></i>
                            {{ $telepon }}
                        </div>
                        @else
                        <span class="text-xs text-secondary">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="inbox" class="size-10 text-border"></i>
                            <p class="font-medium text-sm">Tidak ada data staf ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border -mx-5 mt-2 mb-4">
        @forelse ($staffs as $staff)
        @php
        $nik = $staff->vault?->nik ?? '-';
        $f = $staff->familyRelations->first();

        $hubungan = $f->relationship ?? '-';

        $namaPasangan = $f->familyMember->name ?? '-';
        $gender = $f->familyMember->gender ?? '-';
        $telepon = $f->familyMember->telephone ?? '-';

        // Format Pekerjaan menggunakan Enum
        $occ = $f->familyMember->occupation ?? null;
        $pekerjaan = '-';
        if ($occ instanceof \App\Enums\Staff\Profession) {
        $pekerjaan = $occ->label();
        } elseif (is_string($occ)) {
        $pekerjaan = \App\Enums\Staff\Profession::tryFrom($occ)?->label() ?? $occ;
        }

        $hubLabel = match($hubungan) {
        'husband', 'suami' => 'Suami',
        'wife', 'istri' => 'Istri',
        'child', 'anak' => 'Anak',
        'other' => 'Lainnya',
        default => '-',
        };

        $hubColor = match($hubungan) {
        'husband', 'suami' => 'bg-blue-100 text-blue-700 border-blue-200',
        'wife', 'istri' => 'bg-pink-100 text-pink-700 border-pink-200',
        'child', 'anak' => 'bg-purple-100 text-purple-700 border-purple-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
        @endphp

        <div id="card-staff-{{ $staff->id }}" class="px-5 py-4 active:bg-muted/40 transition-colors">

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.personnel.family.show', $staff->id) }}" class="flex items-center gap-3 min-w-0 cursor-pointer group">
                    <div class="shrink-0">
                        <x-ui.avatar :name="$staff->name ?? 'Unknown'" :gender="$staff->gender" :index="$loop->index" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-foreground text-sm uppercase truncate group-hover:text-primary transition-colors">
                            {{ $staff->name ?? 'Data Tidak Ditemukan' }}
                        </div>
                        <p class="text-xs text-secondary mt-1 truncate flex items-center gap-1.5">
                            <span class="inline-block size-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                            {{ $nik }}
                        </p>
                    </div>
                </a>

                <div class="shrink-0">
                    @if ($f)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $hubColor }} uppercase tracking-wider">
                        {{ $hubLabel }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="mt-3 border-y border-border divide-y divide-border text-xs">
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="contact" class="size-3.5 text-secondary/50"></i>
                        Pasangan
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        @if ($f)
                        <p class="font-medium text-foreground uppercase truncate">{{ $namaPasangan }}</p>
                        <p class="text-secondary truncate mt-0.5 capitalize">{{ $gender === 'P' ? 'Perempuan' : ($gender === 'L' ? 'Laki-Laki' : $gender) }}</p>
                        @else
                        <p class="text-secondary italic">Belum ada data pasangan</p>
                        @endif
                    </div>
                </div>

                @if ($f)
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <p class="text-secondary flex items-center gap-1.5 shrink-0 pt-0.5">
                        <i data-lucide="briefcase" class="size-3.5 text-secondary/50"></i>
                        Pekerjaan / Kontak
                    </p>
                    <div class="text-right min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate">{{ $pekerjaan }}</p>
                        <p class="text-secondary truncate mt-0.5">{{ $telepon }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Tidak ada data staf ditemukan.</p>
            </div>
        </div>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$staffs" hxTarget="#family-container" />

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>