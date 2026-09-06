@php
$isEdit = !empty($staff);
$vault = $staff->vault ?? null;

$employmentSelectOptions = $employmentOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$personnelSelectOptions = $personnelOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$positionSelectOptions = $positionOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$concentrationSelectOptions = $concentrationOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();

$genderSelectOptions = array_map(fn($g) => ['value' => $g->value, 'label' => $g->label()], $genderOptions);
$religionSelectOptions = array_map(fn($r) => ['value' => $r->value, 'label' => $r->label()], $religionOptions);
$statusSelectOptions = array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], $statusOptions);

$modalTitle = $isEdit ? 'Edit Data Pegawai' : 'Tambah Data Pegawai';
$actionUrl = $isEdit ? route('admin.personnel.data.update', $staff->id) : route('admin.personnel.data.store');
$method = $isEdit ? 'hx-put' : 'hx-post';

$dobValue = $vault?->dob ? \Carbon\Carbon::parse($vault->dob)->format('Y-m-d') : '';
$statusEffectiveDateValue = $staff?->status_effective_date ? \Carbon\Carbon::parse($staff->status_effective_date)->format('Y-m-d') : '';
$priorServiceDateValue = $staff?->prior_service_period_effective_date ? \Carbon\Carbon::parse($staff->prior_service_period_effective_date)->format('Y-m-d') : '';
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

                    {{-- ===================== FOTO ===================== --}}
                    <div class="md:col-span-2 flex items-center gap-4">
                        <div class="size-16 rounded-2xl bg-slate-100 border border-border flex items-center justify-center overflow-hidden shrink-0">
                            @if ($isEdit && $staff->photo)
                            <img src="{{ asset('storage/' . $staff->photo) }}" alt="Foto {{ $staff->name }}" class="size-full object-cover">
                            @else
                            <i data-lucide="image" class="size-6 text-secondary/40"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-foreground mb-1.5">Foto Pegawai</label>
                            <input type="file" name="photo" accept="image/*" class="w-full text-sm text-secondary file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:text-sm file:font-semibold hover:file:bg-primary/20 cursor-pointer">
                            <p class="text-[11px] text-secondary mt-1">Opsional. Maks 2MB, format gambar.</p>
                        </div>
                    </div>

                    {{-- ===================== DATA UTAMA ===================== --}}
                    <div class="md:col-span-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-1">Data Utama</h4>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nama Lengkap <span class="text-error">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $staff->name ?? '') }}" required placeholder="Nama lengkap tanpa gelar" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Gelar Depan</label>
                        <input type="text" name="front_title" value="{{ old('front_title', $staff->front_title ?? '') }}" placeholder="Contoh: Dr., Ir." class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Gelar Belakang</label>
                        <input type="text" name="back_title" value="{{ old('back_title', $staff->back_title ?? '') }}" placeholder="Contoh: S.Kom., M.T." class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Kelamin <span class="text-error">*</span></label>
                        <x-ui.select name="gender" :options="$genderSelectOptions" :value="old('gender', $staff->gender ?? '')" required placeholder="-- Pilih Kelamin --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Status Kawin & Tanggungan</label>
                        <input type="text" name="marital_dependents" value="{{ old('marital_dependents', $staff->marital_dependents ?? '') }}" placeholder="Contoh: K/2" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- ===================== DATA KEPEGAWAIAN ===================== --}}
                    <div class="md:col-span-2 mt-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-1">Data Kepegawaian</h4>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Pegawai <span class="text-error">*</span></label>
                        <x-ui.select name="personnel_id" :options="$personnelSelectOptions" :value="old('personnel_id', $staff->personnel_id ?? '')" required placeholder="-- Pilih Jenis Pegawai --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Status Kepegawaian <span class="text-error">*</span></label>
                        <x-ui.select name="employment_id" :options="$employmentSelectOptions" :value="old('employment_id', $staff->employment_id ?? '')" required placeholder="-- Pilih Status --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jabatan <span class="text-error">*</span></label>
                        <x-ui.searchable-select name="position_id" :options="$positionSelectOptions" :value="old('position_id', $staff->position_id ?? '')" placeholder="-- Pilih Jabatan --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Konsentrasi / Jurusan</label>
                        <x-ui.searchable-select name="concentration_id" :options="$concentrationSelectOptions" :value="old('concentration_id', $staff->concentration_id ?? '')" placeholder="-- Pilih Konsentrasi (jika ada) --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Status Aktif <span class="text-error">*</span></label>
                        <x-ui.select name="status" :options="$statusSelectOptions" :value="old('status', $staff->status ?? 'active')" required placeholder="-- Pilih Status --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Berlaku Status</label>
                        <input type="date" name="status_effective_date" value="{{ old('status_effective_date', $statusEffectiveDateValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Masa Kerja Tambahan</label>
                        <input type="text" name="prior_service_period" value="{{ old('prior_service_period', $staff->prior_service_period ?? '') }}" placeholder="Contoh: 2 Tahun 3 Bulan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">TMT Masa Kerja Tambahan</label>
                        <input type="date" name="prior_service_period_effective_date" value="{{ old('prior_service_period_effective_date', $priorServiceDateValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- ===================== IDENTITAS ===================== --}}
                    <div class="md:col-span-2 mt-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-1">Identitas</h4>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NIK <span class="text-error">*</span></label>
                        <input type="text" name="nik" value="{{ old('nik', $vault->nik ?? '') }}" required placeholder="Nomor Induk Kependudukan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NIP</label>
                        <input type="text" name="nip" value="{{ old('nip', $vault->nip ?? '') }}" placeholder="Nomor Induk Pegawai (jika ada)" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">NUPTK</label>
                        <input type="text" name="nuptk" value="{{ old('nuptk', $vault->nuptk ?? '') }}" placeholder="Nomor Unik Pendidik & Tenaga Kependidikan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Agama</label>
                        <x-ui.select name="religion" :options="$religionSelectOptions" :value="old('religion', $vault->religion ?? '')" placeholder="-- Pilih Agama --" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tempat Lahir</label>
                        <input type="text" name="pob" value="{{ old('pob', $vault->pob ?? '') }}" placeholder="Kota kelahiran" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Lahir</label>
                        <input type="date" name="dob" value="{{ old('dob', $dobValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- ===================== KONTAK & ALAMAT ===================== --}}
                    <div class="md:col-span-2 mt-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-1">Kontak & Alamat</h4>
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

                    {{-- ===================== FINANSIAL (opsional) ===================== --}}
                    <div class="md:col-span-2 mt-2 p-4 rounded-xl border border-border bg-slate-50/50 grid grid-cols-1 md:grid-cols-2 gap-4"
                        x-data="{ showFinance: {{ ($vault?->npwp || $vault?->bank_account || $vault?->base_salary) ? 'true' : 'false' }} }">
                        <div class="md:col-span-2 flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Data Finansial (Opsional)</h4>
                            <button type="button" @click="showFinance = !showFinance" class="text-xs font-medium text-primary flex items-center gap-1 cursor-pointer">
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

                        <div x-show="showFinance" x-cloak class="md:col-span-2">
                            <label class="block text-xs font-medium text-foreground mb-1.5">Gaji Pokok</label>
                            <input type="number" step="0.01" min="0" name="base_salary" value="{{ old('base_salary', $vault->base_salary ?? '') }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
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