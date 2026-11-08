@props([
'name',
'options' => [],
'value' => '',
'placeholder' => '-- Pilih --',
])

@php
$inputClass = 'w-full bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-primary transition-colors flex items-center justify-between cursor-pointer';
$errorClass = 'border-error ring-1 ring-error/30';
$hasError = $errors->has($name);
@endphp

<div x-data="{
        openDropdown: false,
        selectedId: '{{ old($name, $value) }}',
        selectedLabel: '',
        options: @js($options), 
        dropdownStyle: '',
        listMaxHeight: '208px',
        
        selectOption(option) {
            this.selectedId = option.value;
            this.selectedLabel = option.label;
            this.close();
            
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
                }
            }

            window.addEventListener('scroll', (e) => {
                if(!this.openDropdown) return;
                
                // Abaikan jika yang discroll adalah isi dropdown itu sendiri
                if (this.$refs.dropdownList && this.$refs.dropdownList.contains(e.target)) {
                    return;
                }
                
                this.close();
            }, true);

            window.addEventListener('resize', () => {
                if(this.openDropdown) this.close();
            });
        },
        // Hitung posisi dropdown: ke bawah jika cukup, ke atas jika bawah sempit dan atas lebih lega
        updatePosition() {
            const rect = this.$refs.btn.getBoundingClientRect();
            const gap = 4;        // jarak antara input dan dropdown
            const margin = 8;     // jarak minimal ke tepi layar
            const maxH = 208;     // setara max-h-52

            // Estimasi tinggi konten (±34px per opsi), maksimal maxH
            const needed = Math.min(maxH, this.options.length * 34 + 2);

            const spaceBelow = window.innerHeight - rect.bottom - gap - margin;
            const spaceAbove = rect.top - gap - margin;

            const openUp = spaceBelow < needed && spaceAbove > spaceBelow;
            const space = openUp ? spaceAbove : spaceBelow;

            this.listMaxHeight = Math.max(80, Math.min(maxH, space)) + 'px';

            this.dropdownStyle = openUp
                ? `bottom: ${window.innerHeight - rect.top + gap}px; left: ${rect.left}px; width: ${rect.width}px;`
                : `top: ${rect.bottom + gap}px; left: ${rect.left}px; width: ${rect.width}px;`;
        },
        open() {
            this.updatePosition();
            this.openDropdown = true;
        },
        close() {
            this.openDropdown = false;
        },
        toggle() {
            this.openDropdown ? this.close() : this.open();
        }
    }"
    class="relative w-full"
    {{-- PERBAIKAN 1: Ganti @click.outside karena elemen dropdown sekarang dilempar ke body --}}
    @click.window="if (openDropdown && !$el.contains($event.target) && $refs.dropdownList && !$refs.dropdownList.contains($event.target)) close()"
    @reset-filters.window="selectedId = ''; selectedLabel = ''"
    @update-options.window="if ($event.detail.name === '{{ $name }}') { options = $event.detail.options; selectedId = ''; selectedLabel = ''; }">

    <!-- Input Tersembunyi -->
    <input type="hidden" name="{{ $name }}" x-model="selectedId" x-ref="hiddenInput">

    <!-- Tombol Pemicu -->
    <button type="button"
        x-ref="btn"
        @click="toggle()"
        class="{{ $inputClass }} {{ $hasError ? $errorClass : '' }}">

        <span x-text="selectedId ? selectedLabel : '{{ $placeholder }}'"
            :class="selectedId ? 'text-foreground' : 'text-secondary/60'"
            class="truncate text-left block w-full">
        </span>

        <i data-lucide="chevron-down" class="size-4 text-secondary shrink-0 transition-transform duration-200" :class="openDropdown ? 'rotate-180' : ''"></i>
    </button>

    {{-- PERBAIKAN 2: Bungkus dropdown list dengan x-teleport="body" --}}
    <template x-teleport="body">
        <div x-show="openDropdown"
            x-ref="dropdownList"
            x-transition.opacity.duration.200ms
            style="display: none;"
            :style="dropdownStyle"
            class="fixed z-[9999] bg-white border shadow-lg border-border rounded-xl overflow-hidden">

            <div class="overflow-y-auto" :style="{ maxHeight: listMaxHeight }">
                <template x-for="option in options" :key="option.value">
                    <div @click="selectOption(option)"
                        class="px-3 py-1.5 text-sm transition-colors cursor-pointer hover:bg-muted border-b border-border/50 last:border-0"
                        :class="{'bg-primary/5 text-primary font-semibold': String(selectedId) === String(option.value)}">
                        <span x-text="option.label"></span>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>