{{--
    File: resources/views/pages/admin/personnel/data/partials/_detail-layout.blade.php

    Layout bersama modal detail pegawai. Dipakai oleh _detail-personal dan _detail-employment.

    Variabel yang dibutuhkan:
      $staff    : model Data (dengan relasi position & employmentStatus bila ada)
      $fullName : nama beserta gelar
      $purpose  : ['label' => 'Data pribadi', 'icon' => 'user']  (badge jenis data)
      $sections : [['title' => ..., 'icon' => ..., 'items' => [[label, nilai, (opsional) keterangan], ...]], ...]

    Opsional:
      $photoUrl : URL pas foto pegawai. Bila diisi, foto tampil menggantikan inisial.
--}}
@php
$initials = collect(preg_split('/\s+/', trim((string) $staff->name)))
->filter()
->take(2)
->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
->implode('');

$positionName = $staff->position?->name;
$statusChip = $staff->employmentStatus?->alias ?? $staff->employmentStatus?->name;
$photoUrl = $photoUrl ?? null;
@endphp

<div x-data="{
        open: false,
        close() {
            const container = this.$root.closest('#modal-container');
            this.open = false;
            setTimeout(() => { if (container) container.innerHTML = ''; }, 150);
        }
    }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="close()"
    @keydown.escape.window="if (open) close()">

    <x-ui.modal show="open" maxWidth="2xl">

        {{-- ============================ HEADER ============================ --}}
        <div class="flex items-start justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-4 min-w-0">
                {{-- Avatar berproporsi 3,3:4 (aspect-[33/40]), siap untuk pas foto. Tinggi mengikuti lebar: 96px (mobile) / 112px (desktop). --}}
                <div class="relative w-[3.7125rem] sm:w-[4.33125rem] aspect-[33/40] shrink-0 overflow-hidden rounded-xl bg-blue-100 text-blue-700 shadow-sm flex items-center justify-center text-base sm:text-lg font-bold tracking-tight select-none">
                    @if (filled($photoUrl))
                    <img src="{{ $photoUrl }}" alt="Foto {{ $staff->name }}" class="absolute inset-0 size-full object-cover">
                    @else
                    {{ $initials !== '' ? $initials : '?' }}
                    @endif
                </div>

                {{-- Nama, jabatan, dan badge disusun di samping avatar --}}
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-foreground leading-snug break-words">{{ $fullName }}</h3>
                    @if (filled($positionName))
                    <p class="mt-0.5 text-xs sm:text-sm text-secondary truncate">{{ $positionName }}</p>
                    @endif

                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                            <i data-lucide="{{ $purpose['icon'] }}" class="size-3.5"></i>
                            {{ $purpose['label'] }}
                        </span>
                        @if (filled($statusChip))
                        <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-foreground ring-1 ring-border">
                            {{ $statusChip }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <button type="button" @click="close()" title="Tutup" aria-label="Tutup"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- ============================ ISI ============================ --}}
        <div class="flex-1 min-h-0 overflow-y-auto px-5 sm:px-8 py-6 space-y-8">
            @foreach ($sections as $section)
            <section>
                <div class="flex items-center gap-2.5">
                    <span class="size-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $section['icon'] }}" class="size-4"></i>
                    </span>
                    <h4 class="text-sm font-bold text-foreground">{{ $section['title'] }}</h4>
                </div>

                <dl class="mt-2 divide-y divide-border/60">
                    @foreach ($section['items'] as $item)
                    @php $isEmpty = blank($item[1]) || $item[1] === '-'; @endphp
                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-6 py-3">
                        <dt class="sm:w-[40%] shrink-0 text-[13px] text-secondary pr-2">{{ $item[0] }}</dt>
                        <dd class="sm:w-[60%] min-w-0 text-sm tabular-nums break-words {{ $isEmpty ? 'text-secondary/50' : 'font-medium text-foreground' }}">
                            {{ $isEmpty ? '–' : $item[1] }}
                            @if (!$isEmpty && filled($item[2] ?? null))
                            <span class="ml-1.5 text-xs font-normal text-secondary">{{ $item[2] }}</span>
                            @endif
                        </dd>
                    </div>
                    @endforeach
                </dl>
            </section>
            @endforeach
        </div>

        {{-- ============================ FOOTER ============================ --}}
        <div class="shrink-0 border-t border-border bg-white px-5 sm:px-8 py-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
            <button type="button" @click="close()"
                class="w-full sm:w-auto flex items-center justify-center px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:text-foreground transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                Tutup
            </button>
            <button type="button"
                hx-get="{{ route('admin.personnel.data.edit-personal', $staff->id) }}"
                hx-target="#modal-container"
                hx-swap="innerHTML"
                class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2">
                <i data-lucide="file-pen-line" class="size-4"></i>
                <span>Edit data</span>
            </button>
        </div>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>