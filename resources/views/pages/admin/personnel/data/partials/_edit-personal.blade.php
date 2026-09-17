@php
$isEdit = !empty($staff);
$vault = $staff->vault ?? null;

$employmentSelectOptions = $employmentOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$personnelSelectOptions = $personnelOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$positionSelectOptions = $positionOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$concentrationSelectOptions = $concentrationOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();

$genderSelectOptions = array_map(fn($g) => ['value' => $g->value, 'label' => $g->label()], $genderOptions);
$religionSelectOptions = array_map(fn($r) => ['value' => $r->value, 'label' => $r->label()], $religionOptions);

$modalTitle = $isEdit ? 'Edit Data Pegawai' : 'Tambah Data Pegawai';
$actionUrl = $isEdit ? route('admin.personnel.data.update', $staff->id) : route('admin.personnel.data.store');
$method = $isEdit ? 'hx-put' : 'hx-post';

$dobValue = $vault?->dob ? \Carbon\Carbon::parse($vault->dob)->format('Y-m-d') : '';
// Sesuaikan nama kolom di bawah ini (entry_date, last_rank_effective_date, dst) dengan nama kolom sebenarnya di tabel/model jika berbeda.
$entryDateValue = $staff?->entry_date ? \Carbon\Carbon::parse($staff->entry_date)->format('Y-m-d') : '';
$lastRankEffectiveDateValue = $staff?->last_rank_effective_date ? \Carbon\Carbon::parse($staff->last_rank_effective_date)->format('Y-m-d') : '';
$careerReviewValue = old('career_review', $staff->career_review ?? '0');
$careerReviewSelectOptions = [
['value' => '0', 'label' => 'Tidak'],
['value' => '1', 'label' => 'Ya'],
];
$maritalDependentsSelectOptions = [
['value' => '1', 'label' => 'Kawin dengan tanggungan'],
['value' => '0', 'label' => 'Belum/Kawin tanpa tanggungan'],
];

$existingPhotoUrl = ($isEdit && $staff->photo) ? asset('storage/' . $staff->photo) : null;
@endphp

<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="3xl">
        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-10 sm:size-12 rounded-full {{ $isEdit ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'user-plus' }}" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">{{ $modalTitle }}</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                        @if ($isEdit)
                        {{ $staff->name }}
                        @else
                        Lengkapi data personal, kepegawaian, dan identitas
                        @endif
                    </p>
                </div>
            </div>
            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="staff-form"
            {!! $method !!}="{{ $actionUrl }}"
            hx-target="#staff-container"
            hx-swap="outerHTML"
            hx-encoding="multipart/form-data"
            x-data="{ saving: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @if ($isEdit)
            @method('PUT')
            @endif

            <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                    {{-- ============================================================ --}}
                    {{-- BAGIAN 1 — FOTO PEGAWAI                                       --}}
                    {{-- ============================================================ --}}
                    <div class="md:col-span-2 flex items-center gap-2">
                        <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="camera" class="size-3.5"></i>
                        </div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">1. Foto Pegawai</h4>
                        <div class="flex-1 h-px bg-border"></div>
                    </div>

                    <div class="md:col-span-2 flex items-center gap-4 p-4 rounded-xl border border-dashed border-border bg-slate-50/50"
                        x-data="{
                            preview: null,
                            existing: {{ $existingPhotoUrl ? \Illuminate\Support\Js::from($existingPhotoUrl) : 'null' }},
                            onPick(e) {
                                const file = e.target.files[0];
                                this.preview = file ? URL.createObjectURL(file) : null;
                            },
                            clear() {
                                this.preview = null;
                                this.$refs.photoInput.value = '';
                            }
                        }">
                        {{-- Preview foto (rasio 3.3:4, selalu full-cover tanpa celah) --}}
                        <div class="relative w-20 aspect-[3.3/4] rounded-md bg-white border border-border overflow-hidden shrink-0 shadow-sm">
                            <img x-show="preview || existing" x-cloak :src="preview || existing" alt="Preview foto pegawai" class="absolute inset-0 w-full h-full object-cover">
                            <div x-show="!preview && !existing" class="absolute inset-0 flex items-center justify-center">
                                <i data-lucide="image" class="size-6 text-secondary/40"></i>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <label class="block text-sm font-medium text-foreground mb-1.5">Unggah Foto</label>
                            <div class="flex items-center gap-2">
                                <input type="file" name="photo" accept="image/*" x-ref="photoInput" @change="onPick($event)"
                                    class="w-full text-sm text-secondary file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:text-sm file:font-semibold hover:file:bg-primary/20 cursor-pointer">
                                <button type="button" x-show="preview" x-cloak @click="clear()"
                                    class="shrink-0 text-secondary hover:text-error transition-colors cursor-pointer" title="Batalkan pilihan">
                                    <i data-lucide="x-circle" class="size-4"></i>
                                </button>
                            </div>
                            <p class="text-[11px] text-secondary mt-1">Opsional. Maks 2MB, format JPG/PNG.</p>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- BAGIAN 2 — DATA PRIBADI                                       --}}
                    {{-- ============================================================ --}}
                    <div class="md:col-span-2 flex items-center gap-2 pt-2">
                        <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="user" class="size-3.5"></i>
                        </div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">2. Data Pribadi</h4>
                        <div class="flex-1 h-px bg-border"></div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nama Lengkap <span class="text-error">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $staff->name ?? '') }}" required placeholder="Nama lengkap tanpa gelar" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Kelamin <span class="text-error">*</span></label>
                        <x-ui.select name="gender" :options="$genderSelectOptions" :value="old('gender', $staff->gender ?? '')" required placeholder="-- Pilih Kelamin --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NIK <span class="text-error">*</span></label>
                        <input type="text" name="nik" value="{{ old('nik', $vault->nik ?? '') }}" required placeholder="Nomor Induk Kependudukan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tempat Lahir</label>
                        <input type="text" name="pob" value="{{ old('pob', $vault->pob ?? '') }}" placeholder="Kota kelahiran" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Lahir</label>
                        <input type="date" name="dob" value="{{ old('dob', $dobValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Agama</label>
                        <x-ui.select name="religion" :options="$religionSelectOptions" :value="old('religion', $vault->religion ?? '')" placeholder="-- Pilih Agama --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Status Kawin & Tanggungan</label>
                        <x-ui.select name="marital_dependents" :options="$maritalDependentsSelectOptions" :value="old('marital_dependents', $staff->marital_dependents ?? '')" placeholder="-- Pilih Status --" />
                    </div>

                    {{-- ============================================================ --}}
                    {{-- BAGIAN 3 — DATA KEPEGAWAIAN                                   --}}
                    {{-- ============================================================ --}}
                    <div class="md:col-span-2 flex items-center gap-2 pt-2">
                        <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="briefcase" class="size-3.5"></i>
                        </div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">3. Data Kepegawaian</h4>
                        <div class="flex-1 h-px bg-border"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Status Kepegawaian <span class="text-error">*</span></label>
                        <x-ui.select name="employment_id" :options="$employmentSelectOptions" :value="old('employment_id', $staff->employment_id ?? '')" required placeholder="-- Pilih Status --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NIP</label>
                        <input type="text" name="nip" value="{{ old('nip', $vault->nip ?? '') }}" placeholder="Nomor Induk Pegawai (jika ada)" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Pegawai <span class="text-error">*</span></label>
                        <x-ui.select name="personnel_id" :options="$personnelSelectOptions" :value="old('personnel_id', $staff->personnel_id ?? '')" required placeholder="-- Pilih Jenis Pegawai --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NUPTK</label>
                        <input type="text" name="nuptk" value="{{ old('nuptk', $vault->nuptk ?? '') }}" placeholder="Nomor Unik Pendidik & Tenaga Kependidikan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jabatan <span class="text-error">*</span></label>
                        <x-ui.searchable-select name="position_id" :options="$positionSelectOptions" :value="old('position_id', $staff->position_id ?? '')" placeholder="-- Pilih Jabatan --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Masuk ke Sekolah Ini <span class="text-error">*</span></label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', $entryDateValue) }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Konsentrasi / Jurusan</label>
                        <x-ui.searchable-select name="concentration_id" :options="$concentrationSelectOptions" :value="old('concentration_id', $staff->concentration_id ?? '')" placeholder="-- Pilih Konsentrasi (jika ada) --" />
                    </div>

                    {{-- Peninjauan Masa Kerja: baris berikutnya (TMT / Tahun / Bulan) hanya muncul jika "Ya" --}}
                    <div x-data="{ careerReview: '{{ $careerReviewValue }}' }" class="contents">
                        <div @change="careerReview = $event.target.value">
                            <label class="block text-sm font-medium text-foreground mb-1.5">Peninjauan Masa Kerja <span class="text-error">*</span></label>
                            <x-ui.select name="career_review" :options="$careerReviewSelectOptions" :value="$careerReviewValue" required placeholder="-- Pilih Peninjauan --" />
                        </div>

                        <div x-show="careerReview === '1'" x-cloak class="md:col-span-2 flex flex-col md:flex-row gap-4 sm:gap-5">
                            <div class="md:w-1/2">
                                <label class="block text-sm font-medium text-foreground mb-1.5">TMT Pangkat Terakhir</label>
                                <input type="date" name="last_rank_effective_date" value="{{ old('last_rank_effective_date', $lastRankEffectiveDateValue) }}" :required="careerReview === '1'" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>

                            <div class="md:w-1/2">
                                <label class="block text-sm font-medium text-foreground mb-1.5">Masa Kerja</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="number" min="0" name="service_period_years" placeholder="0" value="{{ old('service_period_years', $staff->service_period_years ?? '') }}" :required="careerReview === '1'" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <span class="block text-[11px] text-secondary mt-1">Tahun</span>
                                    </div>

                                    <div>
                                        <input type="number" min="0" max="11" name="service_period_months" placeholder="0" value="{{ old('service_period_months', $staff->service_period_months ?? '') }}" :required="careerReview === '1'" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <span class="block text-[11px] text-secondary mt-1">Bulan</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- BAGIAN 4 — KONTAK & ALAMAT                                    --}}
                    {{-- ============================================================ --}}
                    <div class="md:col-span-2 flex items-center gap-2 pt-2">
                        <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="map-pin" class="size-3.5"></i>
                        </div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">4. Kontak & Alamat</h4>
                        <div class="flex-1 h-px bg-border"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Telepon <span class="text-error">*</span></label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $vault->phone_number ?? '') }}" required placeholder="Contoh: 0812..." class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email', $vault->email ?? '') }}" placeholder="nama@contoh.com" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Alamat <span class="text-error">*</span></label>
                        <input type="text" name="address" value="{{ old('address', $vault->address ?? '') }}" required placeholder="Nama jalan, nomor rumah" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">RT</label>
                        <input type="text" name="rt" value="{{ old('rt', $vault->rt ?? '') }}" placeholder="001" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">RW</label>
                        <input type="text" name="rw" value="{{ old('rw', $vault->rw ?? '') }}" placeholder="002" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Desa / Kelurahan <span class="text-error">*</span></label>
                        <input type="text" name="village" value="{{ old('village', $vault->village ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Kecamatan <span class="text-error">*</span></label>
                        <input type="text" name="district" value="{{ old('district', $vault->district ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Kabupaten / Kota <span class="text-error">*</span></label>
                        <input type="text" name="regency" value="{{ old('regency', $vault->regency ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Provinsi <span class="text-error">*</span></label>
                        <input type="text" name="province" value="{{ old('province', $vault->province ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- ============================================================ --}}
                    {{-- BAGIAN 5 — DATA FINANSIAL (OPSIONAL)                          --}}
                    {{-- ============================================================ --}}
                    <div class="md:col-span-2 mt-2 p-4 rounded-xl border border-border bg-slate-50/50 grid grid-cols-1 md:grid-cols-2 gap-4"
                        x-data="{ showFinance: {{ ($vault?->npwp || $vault?->bank_account) ? 'true' : 'false' }} }">
                        <div class="md:col-span-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                    <i data-lucide="wallet" class="size-3.5"></i>
                                </div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">5. Data Finansial (Opsional)</h4>
                            </div>
                            <button type="button" @click="showFinance = !showFinance" class="text-xs font-medium text-primary flex items-center gap-1 cursor-pointer shrink-0">
                                <span x-text="showFinance ? 'Sembunyikan' : 'Tampilkan'"></span>
                                <i data-lucide="chevron-down" class="size-3.5 transition-transform" :class="showFinance ? 'rotate-180' : ''"></i>
                            </button>
                        </div>

                        <div x-show="showFinance" x-cloak>
                            <label class="block text-xs font-medium text-foreground mb-1.5">NPWP</label>
                            <input type="text" name="npwp" value="{{ old('npwp', $vault->npwp ?? '') }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div x-show="showFinance" x-cloak>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Nomor Rekening</label>
                            <input type="text" name="bank_account" value="{{ old('bank_account', $vault->bank_account ?? '') }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
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