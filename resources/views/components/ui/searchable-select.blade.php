@props([
'name',
'options' => [],
'value' => '',
'placeholder' => '-- Pilih --',
])

@php
$inputClass = 'w-full bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:border-primary transition-colors';
$errorClass = 'border-error ring-1 ring-error/30';
$hasError = $errors->has($name);
@endphp

<div x-data="{
        openDropdown: false,
        search: '',
        selectedId: '{{ old($name, $value) }}',
        selectedLabel: '',
        options: @js($options), 
        dropdownStyle: '',
        
        get filteredOptions() {
            if (this.search === '') return this.options;
            const q = this.search.toLowerCase();
            // Cari di label maupun nama grup (opsional)
            return this.options.filter(opt =>
                opt.label.toLowerCase().includes(q) ||
                String(opt.group || '').toLowerCase().includes(q)
            );
        },
        // Kelompokkan hasil filter berdasarkan key 'group' (urutan kemunculan dipertahankan).
        // Opsi tanpa 'group' masuk ke grup kosong dan ditampilkan tanpa judul.
        get groupedOptions() {
            const map = new Map();
            this.filteredOptions.forEach(opt => {
                const g = opt.group || '';
                if (!map.has(g)) map.set(g, []);
                map.get(g).push(opt);
            });
            return Array.from(map, ([name, items]) => ({ name, items }));
        },
        selectOption(option) {
            this.selectedId = option.value;
            this.selectedLabel = option.label;
            this.search = option.label;
            this.close();
            
            // Memicu event change
            this.$nextTick(() => { 
                if (this.$refs.hiddenInput) {
                    this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        },
        init() {
            if (this.selectedId) {
                const opt = this.options.find(o => String(o.value) === String(this.selectedId));
                if (opt) {
                    this.selectedLabel = opt.label;
                    this.search = opt.label;
                }
            }
            
            // Menutup dropdown jika halaman/modal di-scroll, KECUALI scroll di dalam list dropdown
            window.addEventListener('scroll', (e) => {
                if(!this.openDropdown) return;
                if (this.$refs.dropdownList && this.$refs.dropdownList.contains(e.target)) return;
                this.close();
            }, true);

            window.addEventListener('resize', () => {
                if(this.openDropdown) this.close();
            });
        },
        open() {
            const rect = this.$refs.btn.getBoundingClientRect();
            // Kalkulasi posisi fixed terhadap body layar
            this.dropdownStyle = `top: ${rect.bottom + 4}px; left: ${rect.left}px; width: ${rect.width}px;`;
            this.openDropdown = true;
            this.search = ''; 
        },
        close() {
            this.openDropdown = false;
            this.search = this.selectedLabel;
        }
    }"
    class="relative w-full"
    {{-- Mengganti @click.outside karena elemennya sekarang dilempar ke body --}}
    @click.window="if (openDropdown && !$el.contains($event.target) && $refs.dropdownList && !$refs.dropdownList.contains($event.target)) close()"
    @reset-filters.window="selectedId = ''; selectedLabel = ''; search = ''"
    @update-options.window="if ($event.detail.name === '{{ $name }}') { options = $event.detail.options; selectedId = ''; selectedLabel = ''; search = ''; }">

    <!-- Input Tersembunyi -->
    <input type="hidden" name="{{ $name }}" x-model="selectedId" x-ref="hiddenInput">

    <!-- Input Teks Pencarian -->
    <div class="relative" x-ref="btn">
        <input type="text"
            x-model="search"
            x-ref="input"
            @click="openDropdown ? close() : open()"
            @input="openDropdown = true"
            @keydown.arrow-down.prevent="if (!openDropdown) open()"
            @keydown.escape="if (openDropdown) { close(); $event.stopPropagation(); }"
            @keydown.tab="close()"
            placeholder="{{ $placeholder }}"
            class="{{ $inputClass }} {{ $hasError ? $errorClass : '' }} pr-10"
            autocomplete="off"
            required>

        <div class="absolute inset-y-0 right-0 flex items-center px-3.5 pointer-events-none">
            <span class="flex transition-transform duration-200" :class="openDropdown ? 'rotate-180' : ''">
                <i data-lucide="chevron-down" class="size-4 text-secondary"></i>
            </span>
        </div>
    </div>

    <!-- Dropdown List dengan fitur TELEPORT -->
    <template x-teleport="body">
        <div x-show="openDropdown"
            x-ref="dropdownList"
            x-transition.opacity.duration.200ms
            style="display: none;"
            :style="dropdownStyle"
            class="fixed z-[9999] bg-white border shadow-lg border-border rounded-xl overflow-hidden">

            <div class="max-h-52 overflow-y-auto">
                <template x-for="grp in groupedOptions" :key="grp.name">
                    <div :class="grp.name ? 'pb-1.5' : ''">
                        {{-- Judul grup (hanya tampil jika opsi punya key 'group') --}}
                        <div x-show="grp.name"
                            class="sticky top-0 z-10 flex items-center gap-2 px-3.5 pt-2.5 pb-1.5 bg-white/95 backdrop-blur-sm">
                            <span class="size-1.5 shrink-0 rounded-full bg-primary/60"></span>
                            <span class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-secondary" x-text="grp.name"></span>
                            <span class="h-px flex-1 bg-border/70"></span>
                            <span class="text-[10px] font-semibold tabular-nums text-secondary/70" x-text="grp.items.length"></span>
                        </div>

                        <template x-for="option in grp.items" :key="option.value">
                            <div @click="selectOption(option)"
                                class="flex items-center justify-between gap-2 text-sm transition-colors cursor-pointer"
                                :class="[
                                    grp.name
                                        ? 'mx-1.5 rounded-lg px-3 py-1.5 hover:bg-primary/5'
                                        : 'px-3 py-1.5 hover:bg-muted border-b border-border/50 last:border-0',
                                    String(selectedId) === String(option.value)
                                        ? (grp.name ? 'bg-primary/10 text-primary font-semibold' : 'bg-primary/5 text-primary font-semibold')
                                        : ''
                                ]">
                                <span x-text="option.label"></span>
                                <svg x-show="grp.name && String(selectedId) === String(option.value)"
                                    class="size-3.5 shrink-0 text-primary" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="filteredOptions.length === 0" class="px-3 py-3 text-sm text-center text-secondary">
                    Data tidak ditemukan.
                </div>
            </div>
        </div>
    </template>
</div>