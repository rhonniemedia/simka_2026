@php
// 1. Inisialisasi default agar tidak error saat mode Tambah
$education = $education ?? null;

// 2. Pemetaan data untuk komponen select kustom
$levelOptions = $levels->map(function($lvl) {
return [
'value' => $lvl->id,
'label' => $lvl->name . ' (' . $lvl->alias . ')'
];
})->toArray();

$positionOptions = [
['value' => 'depan', 'label' => 'Depan (Misal: dr.)'],
['value' => 'belakang', 'label' => 'Belakang (Misal: S.T.)']
];

// 3. Logika Mode Edit/Tambah
$isEdit = !empty($education);
$modalTitle = $isEdit ? 'Edit Pendidikan' : 'Tambah Pendidikan';
$actionUrl = $isEdit
? route('admin.personnel.education.update', [$staff->id, $education->id])
: route('admin.personnel.education.store', $staff->id);
$method = $isEdit ? 'hx-put' : 'hx-post';

// 4. Format Tanggal
$graduationDateValue = !empty($education->graduation_date)
? \Carbon\Carbon::parse($education->graduation_date)->format('Y-m-d')
: '';
$certificateDateValue = !empty($education->certificate_date)
? \Carbon\Carbon::parse($education->certificate_date)->format('Y-m-d')
: '';
@endphp

<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="2xl">
        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full {{ $isEdit ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'graduation-cap' }}" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $modalTitle }}</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                        {{ $staff->name }}
                    </p>
                </div>
            </div>

            {{-- Tombol Silang (X) --}}
            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="education-form"
            {!! $method !!}="{{ $actionUrl }}"
            hx-target="#education-container"
            hx-swap="outerHTML"
            x-data="{ saving: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf

            <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                    {{-- Jenjang & Jurusan --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenjang Pendidikan <span class="text-error">*</span></label>
                        <x-ui.searchable-select
                            name="education_level_id"
                            :options="$levelOptions"
                            :value="$education->education_level_id ?? ''"
                            placeholder="-- Pilih Jenjang --" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jurusan / Program Studi</label>
                        <input type="text" name="major" value="{{ $education->major ?? '' }}" placeholder="Cth: Teknik Informatika" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Institusi --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nama Institusi / Satuan Pendidikan <span class="text-error">*</span></label>
                        <input type="text" name="institution_name" value="{{ $education->institution_name ?? '' }}" required placeholder="Cth: Universitas Indonesia" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Tanggal & Nomor Ijazah --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Ijazah <span class="text-error">*</span></label>
                        <input type="text" name="certificate_number" value="{{ $education->certificate_number ?? '' }}" required placeholder="Nomor pada dokumen ijazah" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Ijazah <span class="text-error">*</span></label>
                        <input type="date" name="certificate_date" value="{{ $certificateDateValue }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Tanggal Lulus & Provinsi --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Lulus</label>
                        <input type="date" name="graduation_date" value="{{ $graduationDateValue }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Provinsi</label>
                        <input type="text" name="province" value="{{ $education->province ?? '' }}" placeholder="Provinsi institusi" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Gelar Akademik --}}
                    <div class="md:col-span-2 mt-1 sm:mt-2 p-3.5 sm:p-4 rounded-xl border border-border bg-slate-50/50 grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                        <div class="md:col-span-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Data Gelar Akademik</h4>
                        </div>

                        {{-- PERBAIKAN: class input diubah menjadi rounded-xl px-3.5 py-2.5 --}}
                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Nama Gelar</label>
                            <input type="text" name="degree_name" value="{{ $education->degree_name ?? '' }}" placeholder="Cth: Sarjana Komputer" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        {{-- PERBAIKAN: class input diubah menjadi rounded-xl px-3.5 py-2.5 --}}
                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Singkatan Gelar</label>
                            <input type="text" name="degree_abbreviation" value="{{ $education->degree_abbreviation ?? '' }}" placeholder="Cth: S.Kom" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Posisi Gelar</label>
                            <x-ui.select
                                name="degree_position"
                                :options="$positionOptions"
                                :value="$education->degree_position ?? ''"
                                placeholder="-- Pilih --" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 sm:gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    <i data-lucide="x-circle" class="size-4"></i>
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
                        <span>Menyimpan...</span>
                    </div>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>