<div id="documents-container">
    @if ($staffList->isEmpty())
    <div class="py-14 text-center">
        <i data-lucide="users" class="size-10 mx-auto text-secondary/50 mb-3"></i>
        <p class="text-sm font-semibold text-foreground">Pegawai tidak ditemukan</p>
        <p class="text-xs text-secondary mt-1">Coba ubah kata kunci atau filter.</p>
    </div>
    @else
    <div class="border border-border rounded-xl divide-y divide-border overflow-hidden">
        @foreach ($staffList as $staff)
        <div x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-muted/60 transition-colors cursor-pointer"
                :class="open ? 'bg-muted/60' : ''">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="size-9 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0">
                        {{ Str::upper(Str::substr($staff->name, 0, 1)) }}
                    </div>
                    <span class="font-semibold text-sm text-foreground truncate">{{ $staff->name }}</span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $staff->documents_count ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-secondary' }}">
                        {{ $staff->documents_count }} dokumen
                    </span>
                    <i data-lucide="chevron-down" class="size-4 text-secondary transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </div>
            </button>

            <div x-show="open" x-cloak class="border-t border-border bg-slate-50/50">
                <template x-if="open">
                    <div x-init="htmx.process($el); if (typeof lucide !== 'undefined') lucide.createIcons();"
                        hx-get="{{ route('admin.personnel.documents.staff', $staff->id) }}"
                        hx-include="#documents-filter-form"
                        hx-trigger="load, refreshDocuments from:body"
                        hx-swap="innerHTML"
                        class="p-3 sm:p-4">
                        <p class="text-xs text-secondary">Memuat dokumen...</p>
                    </div>
                </template>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-5">{{ $staffList->links() }}</div>
    @endif
</div>