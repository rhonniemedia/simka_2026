<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="border-b border-border">
                <th class="px-4 py-3 text-sm font-bold text-secondary tracking-wider w-[25%]">Tingkat & Institusi</th>
                <th class="px-4 py-3 text-sm font-bold text-secondary tracking-wider w-[20%]">Jurusan</th>
                <th class="px-4 py-3 text-sm font-bold text-secondary tracking-wider w-[25%]">Ijazah & Kelulusan</th>
                <th class="px-4 py-3 text-sm font-bold text-secondary tracking-wider w-[15%]">Gelar</th>
                <th class="px-4 py-3 text-sm font-bold text-secondary tracking-wider w-[15%] text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            {{-- Target perulangan adalah relasi educations --}}
            @forelse ($staff->educations as $edu)
            <tr class="border-b border-border hover:bg-muted/50 transition-colors">

                {{-- Kolom 1: Tingkat & Institusi --}}
                <td class="px-5 py-4">
                    <div class="font-semibold text-foreground text-sm uppercase">
                        {{ $edu->level->name ?? 'Tidak Diketahui' }}
                    </div>
                    <div class="text-xs text-secondary mt-0.5">
                        {{ $edu->institution_name }}
                        @if($edu->province) <br><span class="opacity-75">{{ $edu->province }}</span> @endif
                    </div>
                </td>

                {{-- Kolom 2: Jurusan --}}
                <td class="px-5 py-4">
                    <div class="text-sm font-medium text-foreground">
                        {{ $edu->major ?? '-' }}
                    </div>
                    @if($edu->is_linear !== null)
                    <div class="text-[10px] mt-1 inline-flex px-2 py-0.5 rounded-full {{ $edu->is_linear ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $edu->is_linear ? 'Linear' : 'Non-Linear' }}
                    </div>
                    @endif
                </td>

                {{-- Kolom 3: Ijazah & Lulus --}}
                <td class="px-5 py-4">
                    <div class="text-sm text-foreground">
                        No: <span class="font-medium">{{ $edu->certificate_number ?? '-' }}</span>
                    </div>
                    <div class="text-xs text-secondary mt-0.5 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="size-3"></i>
                        {{ $edu->graduation_date ? \Carbon\Carbon::parse($edu->graduation_date)->translatedFormat('d M Y') : '-' }}
                    </div>
                </td>

                {{-- Kolom 4: Gelar --}}
                <td class="px-5 py-4">
                    <div class="text-sm font-medium text-foreground">
                        {{ $edu->degree_abbreviation ?? '-' }}
                    </div>
                    <div class="text-xs text-secondary mt-0.5 capitalize">
                        {{ $edu->degree_position ? 'Posisi: ' . $edu->degree_position : '' }}
                    </div>
                </td>

                {{-- Kolom 5: Status Verifikasi --}}
                <td class="px-5 py-4 text-center">
                    @php
                    $statusColors = [
                    'verified' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'draft' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                    ];
                    $color = $statusColors[$edu->verification_status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                    @endphp
                    <span class="px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider rounded-lg border {{ $color }}">
                        {{ $edu->verification_status }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-16 text-center text-secondary">
                    <div class="flex flex-col items-center gap-3">
                        <i data-lucide="graduation-cap" class="size-10 text-border"></i>
                        <p class="font-medium">Belum ada riwayat pendidikan untuk pegawai ini.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>