<div class="flex flex-col md:flex-row items-center justify-between gap-4 pt-4 px-1">
    <span class="text-sm text-secondary text-center">
        Menampilkan <span class="font-semibold text-foreground">{{ $paginator->firstItem() ?? 0 }}</span>
        sampai <span class="font-semibold text-foreground">{{ $paginator->lastItem() ?? 0 }}</span>
        dari <span class="font-semibold text-foreground">{{ number_format($paginator->total() ?? 0, 0, ',', '.') }}</span> data
    </span>
    <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0">
        @if ($paginator->onFirstPage())
        <button class="p-2 rounded-lg border border-border bg-white hover:bg-muted cursor-not-allowed opacity-50 transition-colors" disabled>
            <i data-lucide="chevron-left" class="size-4"></i>
        </button>
        @else
        <button type="button" hx-get="{{ $paginator->previousPageUrl() }}" hx-target="{{ $hxTarget }}"
            hx-select="{{ $hxTarget }}" hx-swap="outerHTML" hx-push-url="true"
            class="p-2 rounded-lg border border-border bg-white hover:bg-muted cursor-pointer transition-colors">
            <i data-lucide="chevron-left" class="size-4"></i>
        </button>
        @endif

        @php
        $curr = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $curr - 1, $curr, $curr + 1, $last])
        ->filter(fn($p) => $p >= 1 && $p <= $last)
            ->unique()->sort()->values();
            @endphp

            @foreach ($pages as $i => $page)
            @if ($i > 0 && $page - $pages[$i - 1] > 1)
            <span class="px-1 text-secondary text-sm">…</span>
            @endif
            @if ($page === $curr)
            <button class="w-9 h-9 rounded-lg bg-primary text-white text-sm font-bold" disabled>{{ $page }}</button>
            @else
            <button type="button"
                hx-get="{{ $paginator->url($page) }}"
                hx-target="{{ $hxTarget }}" hx-select="{{ $hxTarget }}"
                hx-swap="outerHTML" hx-push-url="true"
                class="w-9 h-9 rounded-lg border border-border bg-white hover:bg-muted text-sm cursor-pointer transition-colors">
                {{ $page }}
            </button>
            @endif
            @endforeach

            @if ($paginator->hasMorePages())
            <button type="button" hx-get="{{ $paginator->nextPageUrl() }}" hx-target="{{ $hxTarget }}"
                hx-select="{{ $hxTarget }}" hx-swap="outerHTML" hx-push-url="true"
                class="p-2 rounded-lg border border-border bg-white hover:bg-muted cursor-pointer transition-colors">
                <i data-lucide="chevron-right" class="size-4"></i>
            </button>
            @else
            <button class="p-2 rounded-lg border border-border bg-white hover:bg-muted cursor-not-allowed opacity-50 transition-colors" disabled>
                <i data-lucide="chevron-right" class="size-4"></i>
            </button>
            @endif
    </div>
</div>