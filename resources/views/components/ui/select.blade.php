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
        
        selectOption(option) {
            this.selectedId = option.value;
            this.selectedLabel = option.label;
            this.close();
            
            this.$nextTick(() => { 
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        },
        init() {
            if (this.selectedId) {
                const opt = this.options.find(o => String(o.value) === String(this.selectedId));
                if (opt) {
                    this.selectedLabel = opt.label;
                }
            }

            // PERBAIKAN: Menambahkan pengecekan elemen target scroll
            window.addEventListener('scroll', (e) => {
                if(!this.openDropdown) return;
                
                // Jika yang di-scroll adalah bagian dalam dropdown, abaikan perintah tutup
                if (this.$refs.dropdownList && this.$refs.dropdownList.contains(e.target)) {
                    return;
                }
                
                this.close();
            }, true);

            window.addEventListener('resize', () => {
                if(this.openDropdown) this.close();
            });
        },
        open() {
            const rect = this.$refs.btn.getBoundingClientRect();
            this.dropdownStyle = `top: ${rect.bottom + 4}px; left: ${rect.left}px; width: ${rect.width}px;`;
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
    @click.outside="openDropdown = false"
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

    <!-- Dropdown List (Tambahkan x-ref="dropdownList") -->
    <div x-show="openDropdown"
        x-ref="dropdownList"
        x-transition.opacity.duration.200ms
        style="display: none;"
        :style="dropdownStyle"
        class="fixed z-[9999] bg-white border shadow-lg border-border rounded-xl overflow-hidden">

        <div class="max-h-52 overflow-y-auto">
            <template x-for="option in options" :key="option.value">
                <div @click="selectOption(option)"
                    class="px-3 py-1.5 text-sm transition-colors cursor-pointer hover:bg-muted border-b border-border/50 last:border-0"
                    :class="{'bg-primary/5 text-primary font-semibold': String(selectedId) === String(option.value)}">
                    <span x-text="option.label"></span>
                </div>
            </template>
        </div>
    </div>
</div>