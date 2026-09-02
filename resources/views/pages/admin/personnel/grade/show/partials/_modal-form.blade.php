@php
$history = $history ?? null;
$isEdit = !empty($history);
$modalTitle = $isEdit ? 'Edit Kepangkatan' : 'Tambah Kepangkatan';
$actionUrl = $isEdit
? route('admin.personnel.promotions.update', [$staff->id, $history->id])
: route('admin.personnel.promotions.store', $staff->id);
$method = $isEdit ? 'hx-put' : 'hx-post';

$gradeIdValue = old('grade_id', $history->grade_id ?? '');
$effectiveDateValue = old('effective_date', !empty($history->effective_date)
? \Carbon\Carbon::parse($history->effective_date)->format('Y-m-d')
: '');
$decreeDateValue = old('decree_date', !empty($history->decree_date)
? \Carbon\Carbon::parse($history->decree_date)->format('Y-m-d')
: '');
$decreeNumberValue = old('decree_number', $history->decree_number ?? '');
$servicePeriodYearsValue = old('service_period_years', $history->service_period_years ?? '');
$approvalReferenceValue = old('approval_reference', $history->approval_reference ?? '');
$approvalDateValue = old('approval_date', !empty($history->approval_date)
? \Carbon\Carbon::parse($history->approval_date)->format('Y-m-d')
: '');
$positionAtTimeValue = old('position_at_time', $history->position_at_time ?? '');
@endphp

<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="2xl">
        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full {{ $isEdit ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'medal' }}" class="size-5 sm:size-6"></i>
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
        <form id="grade-history-form"
            {!! $method !!}="{{ $actionUrl }}"
            hx-target="#promotions-container"
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

                    {{-- Golongan --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Pangkat / Golongan <span class="text-error">*</span></label>
                        <select name="grade_id" required
                            class="w-full rounded-xl border {{ $errors->has('grade_id') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                            <option value="" disabled {{ $gradeIdValue ? '' : 'selected' }}>Pilih Golongan</option>
                            @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" {{ (string) $gradeIdValue === (string) $grade->id ? 'selected' : '' }}>
                                {{ $grade->grade_name }} ({{ $grade->grade_code }})
                            </option>
                            @endforeach
                        </select>
                        @error('grade_id')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- TMT Pangkat --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">TMT Pangkat (Terhitung Mulai Tanggal) <span class="text-error">*</span></label>
                        <input type="date" name="effective_date" value="{{ $effectiveDateValue }}" required
                            class="w-full rounded-xl border {{ $errors->has('effective_date') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('effective_date')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Masa Kerja --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Masa Kerja (Tahun)</label>
                        <input type="number" min="0" name="service_period_years" value="{{ $servicePeriodYearsValue }}" placeholder="0"
                            class="w-full rounded-xl border {{ $errors->has('service_period_years') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('service_period_years')
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

                    {{-- Informasi Pertek --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Pertek</label>
                        <input type="text" name="approval_reference" value="{{ $approvalReferenceValue }}" placeholder="Cth: B/123/..."
                            class="w-full rounded-xl border {{ $errors->has('approval_reference') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('approval_reference')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Pertek</label>
                        <input type="date" name="approval_date" value="{{ $approvalDateValue }}"
                            class="w-full rounded-xl border {{ $errors->has('approval_date') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('approval_date')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Jabatan Saat SK --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jabatan Saat SK Diterbitkan</label>
                        <input type="text" name="position_at_time" value="{{ $positionAtTimeValue }}" placeholder="Cth: Staf Pelaksana"
                            class="w-full rounded-xl border {{ $errors->has('position_at_time') ? 'border-error' : 'border-border' }} px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('position_at_time')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
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