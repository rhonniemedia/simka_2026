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
$statusEffectiveDateValue = $staff?->status_effective_date ? \Carbon\Carbon::parse($staff->status_effective_date)->format('Y-m-d') : '';
$priorDateValue = $staff?->prior_service_period_effective_date ? \Carbon\Carbon::parse($staff->prior_service_period_effective_date)->format('Y-m-d') : '';
$priorServiceValue = old('prior_service_period', $staff->prior_service_period ?? '0');

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
            hx-select="#staff-container"
            hx-swap="outerHTML"
            hx-encoding="multipart/form-data"
            hx-validate="true"
            x-data="{
                saving: false,
                photoPreview: null,
                photoError: '',
                existingPhoto: {{ $existingPhotoUrl ? \Illuminate\Support\Js::from($existingPhotoUrl) : 'null' }},
                fileName: '',
                
                checkPhoto(event) {
                    const file = event.target.files[0];
                    this.photoError = '';
                    this.fileName = '';
                    this.photoPreview = null;
                    if (file) {
                        if (!file.type.startsWith('image/')) {
                            this.photoError = 'File harus berupa gambar (JPG/PNG).';
                            event.target.value = '';
                            return;
                        }
                        if (file.size > 2 * 1024 * 1024) {
                            this.photoError = 'Ukuran foto maksimal 2MB.';
                            event.target.value = '';
                            return;
                        }
                        this.photoPreview = URL.createObjectURL(file);
                        this.fileName = file.name;
                    }
                },
                clearPhoto() {
                    this.photoPreview = null;
                    this.fileName = '';
                    this.photoError = '';
                    this.$refs.photoInput.value = '';
                },
                stripNumbers(event) {
                    event.target.value = event.target.value.replace(/[^0-9]/g, '');
                },
                calculatePriorDate() {
                    const lastDate = this.$refs.lastRankDate?.value;
                    const years = parseInt(this.$refs.serviceYears?.value) || 0;
                    const months = parseInt(this.$refs.serviceMonths?.value) || 0;
                    const hiddenInput = this.$refs.priorDateHidden;
                    
                    if (lastDate && hiddenInput) {
                        let d = new Date(lastDate);
                        d.setFullYear(d.getFullYear() - years);
                        d.setMonth(d.getMonth() - months);
                        const yyyy = d.getFullYear();
                        const mm = String(d.getMonth() + 1).padStart(2, '0');
                        const dd = String(d.getDate()).padStart(2, '0');
                        hiddenInput.value = `${yyyy}-${mm}-${dd}`;
                    } else if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                }
            }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">

            @csrf

            {{-- TAMBAHAN PENTING: Default status ke 'active' agar lolos validasi Laravel --}}
            <input type="hidden" name="status" value="active">

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
                        <div class="size-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                            <i data-lucide="camera" class="size-3.5"></i>
                        </div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-900 whitespace-nowrap">1. Foto Pegawai</h4>
                        <div class="flex-1 h-px bg-blue-200"></div>
                    </div>
                    <div class="md:col-span-2 flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl border-2 border-dashed border-blue-300 bg-blue-50/40 shadow-sm">
                        {{-- Preview foto --}}
                        <div class="relative w-20 aspect-[3.3/4] rounded-lg bg-white border border-blue-300 overflow-hidden shrink-0 shadow-sm">
                            <img x-show="photoPreview || existingPhoto" x-cloak :src="photoPreview || existingPhoto" alt="Preview" class="absolute inset-0 w-full h-full object-cover">
                            <div x-show="!photoPreview && !existingPhoto" class="absolute inset-0 flex flex-col items-center justify-center bg-blue-100/30 text-blue-400">
                                <i data-lucide="image" class="size-6"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-blue-300 bg-white text-blue-800 hover:bg-blue-100 hover:border-blue-400 text-xs font-semibold transition-all shadow-sm cursor-pointer">
                                    <i data-lucide="upload" class="size-3.5 text-blue-600"></i>
                                    <span x-text="photoPreview || existingPhoto ? 'Ganti Foto' : 'Pilih Foto'"></span>
                                    <input type="file" name="photo" accept="image/*" class="hidden" x-ref="photoInput" @change="checkPhoto($event)">
                                </label>
                                <button type="button" x-show="photoPreview" x-cloak @click="clearPhoto()"
                                    class="p-2 rounded-xl border border-red-200 bg-white text-red-600 hover:bg-red-50 hover:border-red-300 transition-colors cursor-pointer shadow-sm" title="Hapus pilihan">
                                    <i data-lucide="trash-2" class="size-3.5"></i>
                                </button>
                            </div>
                            <p x-show="fileName" x-cloak class="text-xs font-medium text-blue-950 truncate mt-2 flex items-center gap-1">
                                <i data-lucide="paperclip" class="size-3 text-blue-600"></i>
                                <span x-text="fileName"></span>
                            </p>
                            <p x-show="photoError" x-cloak class="text-xs font-semibold text-red-600 mt-1.5" x-text="photoError"></p>
                            <p x-show="!photoError" class="text-[11px] font-medium text-blue-800/80 mt-1.5">Maksimal 2MB (Format JPG atau PNG).</p>
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
                        <input type="text" name="nik" value="{{ old('nik', $vault->nik ?? '') }}" required placeholder="16 Digit NIK"
                            pattern="[0-9]{16}" minlength="16" maxlength="16" title="NIK harus terdiri dari exactly 16 digit angka"
                            @input="stripNumbers($event)"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
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
                        <x-ui.select name="marital_dependents" :options="[['value' => 'K/0', 'label' => 'Kawin tanpa tanggungan'], ['value' => 'K/1', 'label' => 'Kawin 1 tanggungan'], ['value' => 'K/2', 'label' => 'Kawin 2 tanggungan'], ['value' => 'K/3', 'label' => 'Kawin 3 tanggungan'], ['value' => 'BK', 'label' => 'Belum Kawin']]" :value="old('marital_dependents', $staff->marital_dependents ?? '')" placeholder="-- Pilih Status --" />
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
                        <input type="text" name="nip" value="{{ old('nip', $vault->nip ?? '') }}" placeholder="15-20 Digit NIP"
                            pattern="[0-9]{15,20}" minlength="15" maxlength="20" title="NIP harus terdiri dari 15 hingga 20 digit angka"
                            @input="stripNumbers($event)"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Pegawai <span class="text-error">*</span></label>
                        <x-ui.select name="personnel_id" :options="$personnelSelectOptions" :value="old('personnel_id', $staff->personnel_id ?? '')" required placeholder="-- Pilih Jenis Pegawai --" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NUPTK</label>
                        <input type="text" name="nuptk" value="{{ old('nuptk', $vault->nuptk ?? '') }}" placeholder="Nomor Unik Pendidik & Tenaga Kependidikan"
                            @input="stripNumbers($event)"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jabatan <span class="text-error">*</span></label>
                        <x-ui.searchable-select name="position_id" :options="$positionSelectOptions" :value="old('position_id', $staff->position_id ?? '')" required placeholder="-- Pilih Jabatan --" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Masuk / TMT Status <span class="text-error">*</span></label>
                        <input type="date" name="status_effective_date" value="{{ old('status_effective_date', $statusEffectiveDateValue) }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Konsentrasi / Jurusan</label>
                        <x-ui.searchable-select name="concentration_id" :options="$concentrationSelectOptions" :value="old('concentration_id', $staff->concentration_id ?? '')" placeholder="-- Pilih Konsentrasi (jika ada) --" />
                    </div>

                    {{-- CARD KHUSUS: PENINJAUAN MASA KERJA --}}
                    <div class="md:col-span-2 mt-1 p-4 rounded-xl border border-indigo-200 bg-indigo-50/50 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-4"
                        x-data="{ showPMK: '{{ $priorServiceValue }}' === '1' }">
                        <div class="md:col-span-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <div class="size-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="history" class="size-3.5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-950">Peninjauan Masa Kerja <span class="text-error">*</span></h4>
                                    <p class="text-[11px] font-medium text-indigo-800/80 mt-0.5 leading-snug">
                                        Gunakan jika ada masa kerja sebelum PNS yang diakui.
                                    </p>
                                </div>
                            </div>
                            <div class="w-full sm:w-56 shrink-0" @change="showPMK = $event.target.value === '1'">
                                <x-ui.select name="prior_service_period" :options="[['value' => '0', 'label' => 'Tidak'], ['value' => '1', 'label' => 'Ya']]" :value="$priorServiceValue" required placeholder="-- Pilih --" />
                            </div>
                        </div>
                        <div x-show="showPMK" x-cloak class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-indigo-200/60">
                            <div>
                                <label class="block text-xs font-semibold text-indigo-950 mb-1.5">TMT Pangkat Terakhir</label>
                                <input type="date" name="last_rank_effective_date" x-ref="lastRankDate" @change="calculatePriorDate()" class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm text-foreground">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-indigo-950 mb-1.5">Masa Kerja (Dikurangkan)</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="number" min="0" name="service_period_years" x-ref="serviceYears" @input="calculatePriorDate()" placeholder="0" class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm text-foreground">
                                        <span class="block text-[11px] font-medium text-indigo-900/80 mt-1">Tahun</span>
                                    </div>
                                    <div>
                                        <input type="number" min="0" max="11" name="service_period_months" x-ref="serviceMonths" @input="calculatePriorDate()" placeholder="0" class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm text-foreground">
                                        <span class="block text-[11px] font-medium text-indigo-900/80 mt-1">Bulan</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Hidden input untuk hasil kalkulasi tanggal PMK --}}
                    <input type="hidden" name="prior_service_period_effective_date" x-ref="priorDateHidden" value="{{ old('prior_service_period_effective_date', $priorDateValue) }}">

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
                        <input type="text" name="phone_number" value="{{ old('phone_number', $vault->phone_number ?? '') }}" required placeholder="10-15 Digit Nomor"
                            pattern="[0-9]{10,15}" minlength="10" maxlength="15" title="Nomor telepon harus terdiri dari 10 hingga 15 digit angka"
                            @input="stripNumbers($event)"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
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
                    {{-- BAGIAN 5 — DATA FINANSIAL (Opsional)                          --}}
                    {{-- ============================================================ --}}
                    <div class="md:col-span-2 mt-2 p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-4"
                        x-data="{ showFinance: {{ ($vault?->npwp || $vault?->bank_account) ? 'true' : 'false' }} }">
                        <div class="md:col-span-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="wallet" class="size-3.5"></i>
                                </div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-950 whitespace-nowrap">5. Data Finansial (Opsional)</h4>
                            </div>
                            <button type="button" @click="showFinance = !showFinance" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:bg-emerald-100/80 px-2.5 py-1 rounded-lg flex items-center gap-1 cursor-pointer shrink-0 transition-colors">
                                <span x-text="showFinance ? 'Sembunyikan' : 'Tampilkan'"></span>
                                <i data-lucide="chevron-down" class="size-3.5 transition-transform" :class="showFinance ? 'rotate-180' : ''"></i>
                            </button>
                        </div>
                        <div x-show="showFinance" x-cloak>
                            <label class="block text-xs font-semibold text-emerald-950 mb-1.5">NPWP</label>
                            <input type="text" name="npwp" value="{{ old('npwp', $vault->npwp ?? '') }}" placeholder="15-16 Digit NPWP"
                                pattern="[0-9]{15,16}" minlength="15" maxlength="16" title="NPWP harus terdiri dari 15 atau 16 digit angka"
                                @input="stripNumbers($event)"
                                class="w-full rounded-xl border border-emerald-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm text-foreground">
                        </div>
                        <div x-show="showFinance" x-cloak>
                            <label class="block text-xs font-semibold text-emerald-950 mb-1.5">Nomor Rekening</label>
                            <input type="text" name="bank_account" value="{{ old('bank_account', $vault->bank_account ?? '') }}" placeholder="8-20 Digit Rekening"
                                pattern="[0-9]{8,20}" minlength="8" maxlength="20" title="Nomor rekening harus terdiri dari 8 hingga 20 digit angka"
                                @input="stripNumbers($event)"
                                class="w-full rounded-xl border border-emerald-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm text-foreground">
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

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // DEBUGGING HTMX: Agar error validasi Laravel (422) tidak "diam" lagi
        document.body.addEventListener('htmx:responseError', function(evt) {
            if (evt.detail.xhr.status === 422) {
                console.warn("⚠️ Validasi Gagal! Periksa Console untuk detail:");
                try {
                    const errors = JSON.parse(evt.detail.xhr.responseText);
                    console.error(errors);
                    alert("Terjadi kesalahan validasi. Silakan periksa kembali data yang Anda masukkan.\n(Cek Console browser untuk detail field yang error)");
                } catch (e) {
                    console.error(evt.detail.xhr.responseText);
                }
            }
        });
    </script>
</div>