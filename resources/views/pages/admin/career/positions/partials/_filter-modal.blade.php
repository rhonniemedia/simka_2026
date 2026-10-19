{{-- File: resources/views/pages/admin/personnel/positions/partials/_filter-modal.blade.php --}}
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
                <h3 class="font-bold text-foreground text-base">Filter Jabatan ASN</h3>
            </div>
            <button type="button" @click="filterModalOpen = false"
                class="size-8 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        <form id="staff-filter-form"
            hx-get="{{ route('admin.personnel.positions.index') }}"
            hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML"
            hx-include="[name=search]"
            hx-push-url="true"
            @htmx:after-request="filterModalOpen = false">

            <div class="p-5 space-y-4">
                <div>
                    <label class="text-xs font-bold text-secondary uppercase tracking-wider">Status Kepegawaian</label>
                    <select name="filter_employment_status" class="mt-1.5 w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <option value="">Semua status</option>
                        @foreach ($employmentOptions as $status)
                        <option value="{{ $status->id }}" @selected($filterEmploymentStatus==$status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-start gap-3 rounded-2xl border border-border p-4 cursor-pointer hover:bg-muted/50 transition-colors">
                    <input type="checkbox" name="filter_active_only" value="1" @checked($filterActiveOnly)
                        class="mt-0.5 size-4 rounded border-border text-primary focus:ring-primary/30">
                    <span>
                        <span class="block text-sm font-semibold text-foreground">Hanya yang sudah punya jabatan aktif</span>
                        <span class="block text-xs text-secondary mt-0.5">Sembunyikan pegawai yang belum memiliki riwayat jabatan ASN aktif.</span>
                    </span>
                </label>
            </div>

            <div class="px-5 py-4 border-t border-border bg-slate-50/50 flex items-center justify-between gap-3">
                <button type="button"
                    @click="
                        $el.closest('form').querySelectorAll('select, input[type=checkbox]').forEach(el => {
                            if (el.type === 'checkbox') { el.checked = false } else { el.selectedIndex = 0 }
                        });
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