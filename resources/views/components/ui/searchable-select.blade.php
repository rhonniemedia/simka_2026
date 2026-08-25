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
            return this.options.filter(opt => opt.label.toLowerCase().includes(this.search.toLowerCase()));
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
            @focus="open()"
            @input="openDropdown = true"
            placeholder="{{ $placeholder }}"
            class="{{ $inputClass }} {{ $hasError ? $errorClass : '' }} pr-10"
            autocomplete="off"
            required>

        <div class="absolute inset-y-0 right-0 flex items-center px-3.5 pointer-events-none">
            <i data-lucide="chevron-down" class="size-4 text-secondary"></i>
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
                <template x-for="option in filteredOptions" :key="option.value">
                    <div @click="selectOption(option)"
                        class="px-3 py-1.5 text-sm transition-colors cursor-pointer hover:bg-muted border-b border-border/50 last:border-0"
                        :class="{'bg-primary/5 text-primary font-semibold': String(selectedId) === String(option.value)}">
                        <span x-text="option.label"></span>
                    </div>
                </template>

                <div x-show="filteredOptions.length === 0" class="px-3 py-3 text-sm text-center text-secondary">
                    Data tidak ditemukan.
                </div>
            </div>
        </div>
    </template>
</div>