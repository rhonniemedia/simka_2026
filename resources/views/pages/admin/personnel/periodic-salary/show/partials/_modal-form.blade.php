@php
$history = $history ?? null;
$isEdit = !empty($history);
$modalTitle = $isEdit ? 'Edit Gaji Berkala' : 'Tambah Gaji Berkala';
$actionUrl = $isEdit
? route('admin.personnel.periodic-salary.update', [$staff->id, $history->id])
: route('admin.personnel.periodic-salary.store', $staff->id);
$method = $isEdit ? 'hx-put' : 'hx-post';

$effectiveDateValue = old('effective_date', !empty($history->effective_date)
? \Carbon\Carbon::parse($history->effective_date)->format('Y-m-d')
: '');
$decreeDateValue = old('decree_date', !empty($history->decree_date)
? \Carbon\Carbon::parse($history->decree_date)->format('Y-m-d')
: '');
$decreeNumberValue = old('decree_number', $history->decree_number ?? '');
$baseSalaryValue = old('base_salary', $history->base_salary ?? '');
@endphp

<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="2xl">
        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full {{ $isEdit ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'wallet' }}" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $modalTitle }}</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                        {{ $staff->name }}
                    </p>
                </div>
            </div>

            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="periodic-salary-form"
            {!! $method !!}="{{ $actionUrl }}"
            hx-target="#periodic-salary-container"
            hx-swap="outerHTML"
            x-data="{ saving: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf

            <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">

                @if ($errors->any())
                <div class="md:col-span-2 mb-4 rounded-xl border border-error/30 bg-error/5 px-4 py-3 flex items-start gap-2.5">
                    <i data-lucide="alert-triangle" class="size-4 text-error shrink-0 mt-0.5"></i>
                    <div class="text-sm text-error/90">
                        <p class="font-semibold mb-1">Periksa kembali data yang dimasukkan:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                    {{-- Periode TMT --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">TMT Berkala (Terhitung Mulai Tanggal) <span class="text-error">*</span></label>
                        <input type="date" name="effective_date" value="{{ $effectiveDateValue }}" required
                            class="w-full rounded-xl border {{ $errors->has('effective_date') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('effective_date')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Informasi SK --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Surat Keputusan (SK)</label>
                        <input type="text" name="decree_number" value="{{ $decreeNumberValue }}" placeholder="Cth: 822.3/..."
                            class="w-full rounded-xl border {{ $errors->has('decree_number') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('decree_number')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal SK</label>
                        <input type="date" name="decree_date" value="{{ $decreeDateValue }}"
                            class="w-full rounded-xl border {{ $errors->has('decree_date') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('decree_date')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nominal Gaji Pokok Baru --}}
                    <div class="md:col-span-2 mt-1 sm:mt-2 p-3.5 sm:p-4 rounded-xl border border-border bg-slate-50/50">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Gaji Pokok (Otomatis Dienkripsi)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <span class="text-secondary text-sm">Rp</span>
                            </div>
                            <input type="number" name="base_salary" value="{{ $baseSalaryValue }}" placeholder="0"
                                class="w-full rounded-xl border {{ $errors->has('base_salary') ? 'border-error' : 'border-border' }} pl-10 pr-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        @error('base_salary')
                        <p class="text-xs text-error mt-1.5">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-secondary mt-1.5 flex items-center gap-1">
                            <i data-lucide="shield-check" class="size-3 text-emerald-500"></i> Disimpan secara aman di dalam *database*.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 sm:gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    <i data-lucide="undo-2" class="size-4"></i>
                    <span>Batal</span>
                </button>
                <button type="submit" :disabled="saving"
                    class="w-full sm:w-auto flex items-center justify-center min-w-[140px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                    <div x-show="!saving" class="flex items-center gap-1.5">
                        <i data-lucide="save" class="size-4"></i>
                        <span>Simpan Data</span>
                    </div>
                    <div x-show="saving" x-cloak class="flex items-center gap-1.5">
                        <i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i>
                        <span>Memproses...</span>
                    </div>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>