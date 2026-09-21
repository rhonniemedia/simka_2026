{{-- File: resources/views/pages/admin/personnel/transfers/partials/_filter-modal.blade.php --}}
<div x-show="filterModalOpen" x-cloak
    class="fixed inset-0 z-[200] flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div x-show="filterModalOpen" x-transition.opacity @click="filterModalOpen = false"
        class="absolute inset-0 bg-black/50"></div>

    {{-- Panel --}}
    <div x-show="filterModalOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="relative bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden">

        {{-- HEADER --}}
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-border bg-slate-50/50">
            <div class="flex items-center gap-3 min-w-0">
                <div class="size-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="filter" class="size-4.5"></i>
                </div>
                <h3 class="font-bold text-foreground text-base">Filter Mutasi</h3>
            </div>
            <button type="button" @click="filterModalOpen = false"
                class="size-8 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        <form id="mutation-filter-form"
            hx-get="{{ route('admin.personnel.mutation.index') }}"
            hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML"
            hx-push-url="true"
            @htmx:after-request="filterModalOpen = false">

            <div class="p-5 space-y-4">
                <div>
                    <label class="text-xs font-bold text-secondary uppercase tracking-wider">Tahun</label>
                    <select name="year" class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <option value="">1 tahun terakhir (default)</option>
                        @foreach ($yearOptions as $y)
                        <option value="{{ $y }}" @selected((string) $year===(string) $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-secondary uppercase tracking-wider">Status</label>
                    <select name="filter_status" class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <option value="">Semua status</option>
                        <option value="transferred" @selected($filterStatus==='transferred' )>Pindah</option>
                        <option value="resigned" @selected($filterStatus==='resigned' )>Mengundurkan Diri</option>
                        <option value="retired" @selected($filterStatus==='retired' )>Pensiun</option>
                        <option value="deceased" @selected($filterStatus==='deceased' )>Meninggal Dunia</option>
                        <option value="dismissed" @selected($filterStatus==='dismissed' )>Diberhentikan</option>
                    </select>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-border bg-slate-50/50 flex items-center justify-between gap-3">
                <button type="button"
                    @click="
                        $el.closest('form').querySelectorAll('select').forEach(el => el.selectedIndex = 0);
                        htmx.trigger($el.closest('form'), 'submit');
                    "
                    class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                    Reset
                </button>

                <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all shadow-sm shadow-primary/30 cursor-pointer">
                    <i data-lucide="check" class="size-4"></i>
                    Terapkan
                </button>
            </div>
        </form>
    </div>
</div>