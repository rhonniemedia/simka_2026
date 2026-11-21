{{-- Pakai di dalam trigger dropdown Anda --}}
<button type="button" {{ $attributes->class('inline-flex items-center justify-center gap-1.5 rounded-lg border border-border bg-white text-sm font-medium text-foreground hover:bg-muted transition-colors size-8 2xl:size-auto 2xl:px-3 2xl:py-1.5') }}
    aria-label="Aksi">
    <i data-lucide="ellipsis-vertical" class="size-4 2xl:hidden pointer-events-none"></i>
    <span class="hidden 2xl:inline">Aksi</span>
    <i data-lucide="chevron-down" class="hidden 2xl:inline size-4 pointer-events-none"></i>
</button>