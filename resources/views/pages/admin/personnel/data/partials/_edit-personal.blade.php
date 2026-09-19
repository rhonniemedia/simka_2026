@php
$isDraft = (bool) ($staff?->is_draft);
$isEdit = !empty($staff) && !$isDraft;
$vault = $staff->vault ?? null;

$employmentSelectOptions = $employmentOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$personnelSelectOptions = $personnelOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$positionSelectOptions = $positionOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$concentrationSelectOptions = $concentrationOptions->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->toArray();
$genderSelectOptions = array_map(fn($g) => ['value' => $g->value, 'label' => $g->label()], $genderOptions);
$religionSelectOptions = array_map(fn($r) => ['value' => $r->value, 'label' => $r->label()], $religionOptions);

$modalTitle = $isEdit ? 'Edit Data Pegawai' : 'Tambah Data Pegawai';

$dobValue = $vault?->dob ? \Carbon\Carbon::parse($vault->dob)->format('Y-m-d') : '';
$statusEffectiveDateValue = $staff?->status_effective_date ? \Carbon\Carbon::parse($staff->status_effective_date)->format('Y-m-d') : '';
$priorDateValue = $staff?->prior_service_period_effective_date ? \Carbon\Carbon::parse($staff->prior_service_period_effective_date)->format('Y-m-d') : '';
$priorServiceValue = (string) old('prior_service_period', $staff->prior_service_period ?? '0');

// Definisi Step untuk Stepper
$steps = [
1 => ['label' => 'Pribadi', 'icon' => 'user'],
2 => ['label' => 'Kepegawaian', 'icon' => 'briefcase'],
3 => ['label' => 'Kontak & Alamat', 'icon' => 'map-pin'],
4 => ['label' => 'Finansial', 'icon' => 'wallet'],
];
$totalSteps = count($steps);

// Nilai awal yang dibaca Alpine. startStep/maxStep dihitung controller:
// data baru = 1/1, draft = step pertama yang belum tuntas, edit = 1/4.
$formConfig = [
'staffId' => $staff?->id,
'isEdit' => $isEdit,
'step' => (int) ($startStep ?? 1),
'maxStep' => (int) ($maxStep ?? 1),
'totalSteps' => $totalSteps,
'storeUrl' => route('admin.personnel.data.store-step'),
'updateUrl' => route('admin.personnel.data.update-step', ['id' => '__ID__']),
'discardUrl' => $isDraft ? route('admin.personnel.data.discard-draft', ['id' => $staff->id]) : null,
'createUrl' => route('admin.personnel.data.create'),
'prior' => [
'enabled' => $priorServiceValue === '1',
'lastRank' => '',
'years' => '',
'months' => '',
'initial' => (string) old('prior_service_period_effective_date', $priorDateValue),
],
];

// Teks error per field (diisi dari validasi client maupun server).
$err = fn(string $field) => new \Illuminate\Support\HtmlString(
'<p x-show="errors.' . $field . '" x-cloak x-text="errors.' . $field . '" data-field-error class="text-[11px] text-error mt-1.5 font-medium"></p>'
);
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
                        @elseif ($isDraft)
                        Melanjutkan draft: {{ $staff->name }}
                        @else
                        Data tersimpan setiap kali Anda menekan Selanjutnya
                        @endif
                    </p>
                </div>
            </div>
            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{--
            Form tanpa atribut hx-*: setiap step dikirim lewat method di bawah.
            Step 1-3 memakai fetch (respons JSON), step terakhir memakai htmx.ajax
            supaya tabel + statistik ikut diperbarui seperti sebelumnya.
            "novalidate" mencegah field required di step lain memblokir pengiriman.
        --}}
        <form id="staff-form"
            novalidate
            x-data="{
                ...@js($formConfig),
                saving: false,
                errors: {},
                formError: '',

                rules: {
                    nik: { regex: /^\d{16}$/, msg: 'NIK harus berisi tepat 16 digit angka.' },
                    nip: { regex: /^\d{15,20}$/, msg: 'NIP harus berisi 15 hingga 20 digit angka.' },
                    phone_number: { regex: /^[0-9]{10,15}$/, msg: 'Nomor telepon tidak valid (10-15 angka).' },
                    email: { regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Format email tidak valid.' },
                    npwp: { regex: /^\d{15,16}$/, msg: 'NPWP harus 15 atau 16 digit angka.' },
                    bank_account: { regex: /^\d{8,20}$/, msg: 'Nomor rekening harus 8 hingga 20 digit angka.' }
                },
                stepFields: { 1: ['nik'], 2: ['nip'], 3: ['phone_number', 'email'], 4: ['npwp', 'bank_account'] },

                /* TMT masa kerja = TMT pangkat terakhir dikurangi masa kerja. */
                get priorDate() {
                    if (!this.prior.enabled) return '';
                    if (!this.prior.lastRank) return this.prior.initial;
                    const years = parseInt(this.prior.years) || 0;
                    const months = parseInt(this.prior.months) || 0;
                    const parts = this.prior.lastRank.split('-').map(Number);
                    const total = parts[0] * 12 + (parts[1] - 1) - years * 12 - months;
                    const y = Math.floor(total / 12);
                    const m = ((total % 12) + 12) % 12;
                    const lastDay = new Date(y, m + 1, 0).getDate();
                    const d = Math.min(parts[2], lastDay);
                    return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                },

                validateField(field, value) {
                    this.errors[field] = '';
                    if (!value || !this.rules[field]) return;
                    if (!this.rules[field].regex.test(value)) {
                        this.errors[field] = this.rules[field].msg;
                    }
                },
                onInput(e) {
                    const name = e.target && e.target.name;
                    if (!name) return;
                    if (this.rules[name]) {
                        this.validateField(name, String(e.target.value || '').trim());
                    } else if (this.errors[name]) {
                        this.errors[name] = '';
                    }
                },
                validateStepClient(step) {
                    const fd = new FormData(this.$root);
                    let ok = true;
                    (this.stepFields[step] || []).forEach((f) => {
                        this.validateField(f, String(fd.get(f) || '').trim());
                        if (this.errors[f]) ok = false;
                    });
                    if (!ok) this.$nextTick(() => this.scrollToFirstError());
                    return ok;
                },
                scrollToFirstError() {
                    const el = Array.from(this.$root.querySelectorAll('[data-field-error]'))
                        .find((n) => n.offsetParent !== null && n.textContent.trim() !== '');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                },

                stepUrl() {
                    return this.staffId ? this.updateUrl.replace('__ID__', this.staffId) : this.storeUrl;
                },
                buildBody(step) {
                    const fd = new FormData(this.$root);
                    fd.set('step', step);
                    if (this.staffId) fd.set('_method', 'PUT');
                    return fd;
                },
                handleError(status, data) {
                    data = data || {};
                    if (status === 422 && data.errors) {
                        const flat = {};
                        Object.keys(data.errors).forEach((k) => {
                            const messages = Array.isArray(data.errors[k]) ? data.errors[k] : [data.errors[k]];
                            flat[k] = messages.join(' ');
                        });
                        this.formError = flat._form || '';
                        delete flat._form;
                        this.errors = flat;
                        this.$nextTick(() => this.scrollToFirstError());
                    } else if (status === 419) {
                        this.formError = 'Sesi Anda telah berakhir. Muat ulang halaman lalu coba lagi.';
                    } else {
                        this.formError = data.message || 'Terjadi kesalahan pada server. Silakan coba lagi.';
                    }
                },

                /* Simpan satu step (step 1-3). Mengembalikan true bila berhasil. */
                async save(step) {
                    this.errors = {};
                    this.formError = '';
                    if (!this.validateStepClient(step)) return false;

                    this.saving = true;
                    try {
                        const res = await fetch(this.stepUrl(), {
                            method: 'POST',
                            body: this.buildBody(step),
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.ok) {
                            this.staffId = data.staff_id || this.staffId;
                            this.maxStep = Math.max(this.maxStep, step + 1);
                            return true;
                        }
                        this.handleError(res.status, data);
                        return false;
                    } catch (e) {
                        this.formError = 'Tidak dapat terhubung ke server. Periksa koneksi Anda lalu coba lagi.';
                        return false;
                    } finally {
                        this.saving = false;
                    }
                },
                async next() {
                    if (this.saving) return;
                    if (await this.save(this.step)) {
                        this.step = Math.min(this.step + 1, this.totalSteps);
                    }
                },
                prev() {
                    if (!this.saving && this.step > 1) this.step--;
                },
                /* Klik stepper: mundur bebas, maju harus menyimpan step yang dilewati. */
                async goToStep(target) {
                    if (this.saving || target === this.step) return;
                    if (target < this.step) { this.step = target; return; }
                    if (target > this.maxStep) return;
                    const from = this.step;
                    for (let s = from; s < target; s++) {
                        this.step = s;
                        if (!(await this.save(s))) return;
                    }
                    this.step = target;
                },
                submitStep() {
                    return this.step < this.totalSteps ? this.next() : this.finish();
                },

                /* Step terakhir: simpan lalu segarkan tabel + statistik (respons HTMX). */
                finish() {
                    if (this.saving) return;
                    this.errors = {};
                    this.formError = '';
                    if (!this.validateStepClient(this.step)) return;
                    if (!this.staffId) {
                        this.formError = 'Simpan langkah sebelumnya terlebih dahulu.';
                        return;
                    }
                    this.saving = true;
                    htmx.ajax('POST', this.stepUrl(), {
                        source: this.$root,
                        target: '#staff-container',
                        select: '#staff-container',
                        swap: 'outerHTML',
                        values: { _method: 'PUT', step: this.totalSteps },
                        headers: { 'Accept': 'application/json' }
                    });
                },
                onFinishResponse(e) {
                    if (e.target !== this.$root || !this.saving) return;
                    this.saving = false;
                    if (e.detail.successful) return;
                    let data = {};
                    try { data = JSON.parse(e.detail.xhr.responseText); } catch (err) {}
                    this.handleError(e.detail.xhr.status, data);
                },

                async discardDraft() {
                    if (!this.discardUrl || this.saving) return;
                    if (!confirm('Buang draft ini dan mulai dari awal? Data yang sudah tersimpan di draft akan dihapus.')) return;
                    this.saving = true;
                    try {
                        const fd = new FormData();
                        fd.set('_token', this.$root.querySelector('[name=_token]').value);
                        fd.set('_method', 'DELETE');
                        const res = await fetch(this.discardUrl, {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) throw new Error('discard failed');
                        htmx.ajax('GET', this.createUrl, { target: '#modal-container', swap: 'innerHTML' });
                    } catch (e) {
                        this.formError = 'Draft gagal dibuang. Silakan coba lagi.';
                    } finally {
                        this.saving = false;
                    }
                }
            }"
            x-init="$watch('step', () => {
                setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 10);
                const body = document.getElementById('staff-form-body');
                if (body) body.scrollTop = 0;
            })"
            @submit.prevent.stop="submitStep()"
            @input="onInput($event)"
            @change="onInput($event)"
            @htmx:after-request="onFinishResponse($event)"
            class="flex flex-col flex-1 min-h-0">

            @csrf

            {{-- ============================ STEPPER ============================ --}}
            {{-- Navigasi murni client-side: klik step TIDAK mengirim request apa pun, --}}
            {{-- sehingga loading overlay global tidak muncul. Mundur bebas; maju hanya --}}
            {{-- sampai step yang sudah pernah tersimpan (maxStep). Simpan tetap lewat tombol Selanjutnya. --}}
            <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-border/60 shrink-0">
                <div class="flex items-start px-4 sm:px-10 pt-5 pb-4">
                    @foreach($steps as $number => $stepItem)
                    {{-- Step (lingkaran ikon + label) --}}
                    <div class="flex flex-col items-center gap-1.5 shrink-0">
                        <button type="button"
                            @click="if ({{ $number }} <= maxStep) step = {{ $number }}"
                            :disabled="saving"
                            class="flex items-center justify-center size-10 sm:size-11 rounded-full transition-all duration-300"
                            :class="{
                    'bg-gradient-to-br from-sky-400 to-blue-500 text-white shadow-md shadow-blue-500/25': step > {{ $number }},
                    'bg-primary text-white shadow-md shadow-primary/30 ring-4 ring-primary/10 scale-110': step === {{ $number }},
                    'bg-white text-slate-400 border-2 border-slate-200': step < {{ $number }},
                    'cursor-pointer hover:scale-105': {{ $number }} <= maxStep,
                    'cursor-not-allowed opacity-50': {{ $number }} > maxStep
                }">
                            <i data-lucide="check" class="size-4 sm:size-[18px]" x-show="step > {{ $number }}" x-cloak></i>
                            <i data-lucide="{{ $stepItem['icon'] }}" class="size-4 sm:size-[18px]" x-show="step <= {{ $number }}"></i>
                        </button>
                        <span class="text-[10px] sm:text-[11px] font-semibold leading-tight text-center whitespace-nowrap transition-colors duration-300"
                            :class="{
                    'text-blue-600': step > {{ $number }},
                    'text-primary': step === {{ $number }},
                    'text-slate-400': step < {{ $number }}
                }">
                            {{ $stepItem['label'] }}
                        </span>
                    </div>
                    {{-- Garis penghubung --}}
                    @if(!$loop->last)
                    <div class="flex-1 h-[3px] mx-2 sm:mx-3 mt-5 sm:mt-[22px] rounded-full bg-slate-200/80 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-sky-400 to-blue-500 transition-all duration-500 ease-out"
                            :class="step > {{ $number }} ? 'w-full' : 'w-0'"></div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            <div id="staff-form-body" x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">
                <div class="transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                    {{-- Error umum (gagal koneksi, sesi habis, langkah sebelumnya belum lengkap, dsb.) --}}
                    <div x-show="formError" x-cloak class="mb-4 flex items-start gap-2.5 rounded-xl border border-error/30 bg-error/10 px-3.5 py-3 text-sm font-medium text-error">
                        <i data-lucide="alert-circle" class="size-4 mt-0.5 shrink-0"></i>
                        <span x-text="formError"></span>
                    </div>

                    @if ($isDraft)
                    <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50/70 px-3.5 py-3">
                        <p class="text-xs sm:text-sm text-amber-900 leading-snug">
                            Draft ini belum selesai. Data langkah sebelumnya sudah tersimpan dan Anda bisa melanjutkannya.
                        </p>
                        <button type="button" @click="discardDraft()" :disabled="saving"
                            class="shrink-0 self-start sm:self-auto px-3 py-1.5 rounded-lg border border-amber-300 bg-white text-xs font-semibold text-amber-800 hover:bg-amber-100 transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            Mulai dari awal
                        </button>
                    </div>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- LANGKAH 1 — DATA PRIBADI                                      --}}
                    {{-- ============================================================ --}}
                    <div x-show="step === 1" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                        <div class="md:col-span-2 flex items-center gap-2 pt-2">
                            <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <i data-lucide="user" class="size-3.5"></i>
                            </div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">1. Data Pribadi</h4>
                            <div class="flex-1 h-px bg-border"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-foreground mb-1.5">Nama Lengkap <span class="text-error">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $staff->name ?? '') }}" required placeholder="Nama lengkap tanpa gelar" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('name') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Kelamin <span class="text-error">*</span></label>
                            <x-ui.select name="gender" :options="$genderSelectOptions" :value="old('gender', $staff->gender ?? '')" required placeholder="-- Pilih Kelamin --" />
                            {{ $err('gender') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">NIK <span class="text-error">*</span></label>
                            <input type="text" name="nik" value="{{ old('nik', $vault->nik ?? '') }}" required placeholder="16 Digit NIK"
                                :class="errors.nik ? 'border-error focus:ring-error/20 focus:border-error' : 'border-border focus:ring-primary/20 focus:border-primary'"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm focus:outline-none transition-colors">
                            {{ $err('nik') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Tempat Lahir</label>
                            <input type="text" name="pob" value="{{ old('pob', $vault->pob ?? '') }}" placeholder="Kota kelahiran" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('pob') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Lahir</label>
                            <input type="date" name="dob" value="{{ old('dob', $dobValue) }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('dob') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Agama</label>
                            <x-ui.select name="religion" :options="$religionSelectOptions" :value="old('religion', $vault->religion ?? '')" placeholder="-- Pilih Agama --" />
                            {{ $err('religion') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Status Kawin & Tanggungan</label>
                            <x-ui.select name="marital_dependents" :options="[['value' => 'K/0', 'label' => 'Kawin tanpa tanggungan'], ['value' => 'K/1', 'label' => 'Kawin 1 tanggungan'], ['value' => 'K/2', 'label' => 'Kawin 2 tanggungan'], ['value' => 'K/3', 'label' => 'Kawin 3 tanggungan'], ['value' => 'BK', 'label' => 'Belum Kawin']]" :value="old('marital_dependents', $staff->marital_dependents ?? '')" placeholder="-- Pilih Status --" />
                            {{ $err('marital_dependents') }}
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- LANGKAH 2 — DATA KEPEGAWAIAN                                  --}}
                    {{-- ============================================================ --}}
                    <div x-show="step === 2" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                        <div class="md:col-span-2 flex items-center gap-2 pt-2">
                            <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <i data-lucide="briefcase" class="size-3.5"></i>
                            </div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">2. Data Kepegawaian</h4>
                            <div class="flex-1 h-px bg-border"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Status Kepegawaian <span class="text-error">*</span></label>
                            <x-ui.select name="employment_id" :options="$employmentSelectOptions" :value="old('employment_id', $staff->employment_id ?? '')" required placeholder="-- Pilih Status --" />
                            {{ $err('employment_id') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">NIP</label>
                            <input type="text" name="nip" value="{{ old('nip', $vault->nip ?? '') }}" placeholder="15-20 Digit NIP"
                                :class="errors.nip ? 'border-error focus:ring-error/20 focus:border-error' : 'border-border focus:ring-primary/20 focus:border-primary'"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm focus:outline-none transition-colors">
                            {{ $err('nip') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Jenis Pegawai <span class="text-error">*</span></label>
                            <x-ui.select name="personnel_id" :options="$personnelSelectOptions" :value="old('personnel_id', $staff->personnel_id ?? '')" required placeholder="-- Pilih Jenis Pegawai --" />
                            {{ $err('personnel_id') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">NUPTK</label>
                            <input type="text" name="nuptk" value="{{ old('nuptk', $vault->nuptk ?? '') }}" placeholder="Nomor Unik Pendidik & Tenaga Kependidikan" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('nuptk') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Jabatan <span class="text-error">*</span></label>
                            <x-ui.searchable-select name="position_id" :options="$positionSelectOptions" :value="old('position_id', $staff->position_id ?? '')" required placeholder="-- Pilih Jabatan --" />
                            {{ $err('position_id') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Masuk / TMT Status <span class="text-error">*</span></label>
                            <input type="date" name="status_effective_date" value="{{ old('status_effective_date', $statusEffectiveDateValue) }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('status_effective_date') }}
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-foreground mb-1.5">Konsentrasi / Jurusan</label>
                            <x-ui.searchable-select name="concentration_id" :options="$concentrationSelectOptions" :value="old('concentration_id', $staff->concentration_id ?? '')" placeholder="-- Pilih Konsentrasi (jika ada) --" />
                            {{ $err('concentration_id') }}
                        </div>

                        {{-- CARD KHUSUS: PENINJAUAN MASA KERJA (state-nya ada di x-data form: prior.*) --}}
                        <div class="md:col-span-2 mt-1 p-4 rounded-xl border border-indigo-200 bg-indigo-50/50 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                <div class="w-full sm:w-56 shrink-0" @change="prior.enabled = $event.target.value === '1'">
                                    <x-ui.select name="prior_service_period" :options="[['value' => '0', 'label' => 'Tidak'], ['value' => '1', 'label' => 'Ya']]" :value="$priorServiceValue" required placeholder="-- Pilih --" />
                                    {{ $err('prior_service_period') }}
                                </div>
                            </div>
                            <div x-show="prior.enabled" x-cloak class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-indigo-200/60">
                                <div>
                                    <label class="block text-xs font-semibold text-indigo-950 mb-1.5">TMT Pangkat Terakhir</label>
                                    <input type="date" name="last_rank_effective_date" x-model="prior.lastRank" class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm text-foreground">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-indigo-950 mb-1.5">Masa Kerja (Dikurangkan)</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <input type="number" min="0" name="service_period_years" x-model="prior.years" placeholder="0" class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm text-foreground">
                                            <span class="block text-[11px] font-medium text-indigo-900/80 mt-1">Tahun</span>
                                        </div>
                                        <div>
                                            <input type="number" min="0" max="11" name="service_period_months" x-model="prior.months" placeholder="0" class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm text-foreground">
                                            <span class="block text-[11px] font-medium text-indigo-900/80 mt-1">Bulan</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="md:col-span-2" x-show="priorDate" x-cloak>
                                    <p class="text-[11px] font-medium text-indigo-900/80">
                                        TMT masa kerja yang akan disimpan: <span class="font-bold" x-text="priorDate"></span>
                                    </p>
                                    {{ $err('prior_service_period_effective_date') }}
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="prior_service_period_effective_date" :value="priorDate">
                    </div>

                    {{-- ============================================================ --}}
                    {{-- LANGKAH 3 — KONTAK & ALAMAT                                   --}}
                    {{-- ============================================================ --}}
                    <div x-show="step === 3" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                        <div class="md:col-span-2 flex items-center gap-2 pt-2">
                            <div class="size-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <i data-lucide="map-pin" class="size-3.5"></i>
                            </div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary whitespace-nowrap">3. Kontak & Alamat</h4>
                            <div class="flex-1 h-px bg-border"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Telepon <span class="text-error">*</span></label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $vault->phone_number ?? '') }}" required placeholder="10-15 Digit Nomor"
                                :class="errors.phone_number ? 'border-error focus:ring-error/20 focus:border-error' : 'border-border focus:ring-primary/20 focus:border-primary'"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm focus:outline-none transition-colors">
                            {{ $err('phone_number') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email', $vault->email ?? '') }}" placeholder="nama@contoh.com"
                                :class="errors.email ? 'border-error focus:ring-error/20 focus:border-error' : 'border-border focus:ring-primary/20 focus:border-primary'"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm focus:outline-none transition-colors">
                            {{ $err('email') }}
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-foreground mb-1.5">Alamat <span class="text-error">*</span></label>
                            <input type="text" name="address" value="{{ old('address', $vault->address ?? '') }}" required placeholder="Nama jalan, nomor rumah" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('address') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">RT</label>
                            <input type="text" name="rt" value="{{ old('rt', $vault->rt ?? '') }}" placeholder="001" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('rt') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">RW</label>
                            <input type="text" name="rw" value="{{ old('rw', $vault->rw ?? '') }}" placeholder="002" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('rw') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Desa / Kelurahan <span class="text-error">*</span></label>
                            <input type="text" name="village" value="{{ old('village', $vault->village ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('village') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Kecamatan <span class="text-error">*</span></label>
                            <input type="text" name="district" value="{{ old('district', $vault->district ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('district') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Kabupaten / Kota <span class="text-error">*</span></label>
                            <input type="text" name="regency" value="{{ old('regency', $vault->regency ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('regency') }}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Provinsi <span class="text-error">*</span></label>
                            <input type="text" name="province" value="{{ old('province', $vault->province ?? '') }}" required class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            {{ $err('province') }}
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- LANGKAH 4 — DATA FINANSIAL (Opsional)                         --}}
                    {{-- ============================================================ --}}
                    <div x-show="step === 4" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                        <div class="md:col-span-2 mt-2 p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-4"
                            x-data="{ showFinance: {{ ($vault?->npwp || $vault?->bank_account) ? 'true' : 'false' }} }">
                            <div class="md:col-span-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="size-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <i data-lucide="wallet" class="size-3.5"></i>
                                    </div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-950 whitespace-nowrap">4. Data Finansial (Opsional)</h4>
                                </div>
                                <button type="button" @click="showFinance = !showFinance" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:bg-emerald-100/80 px-2.5 py-1 rounded-lg flex items-center gap-1 cursor-pointer shrink-0 transition-colors">
                                    <span x-text="showFinance ? 'Sembunyikan' : 'Tampilkan'"></span>
                                    <i data-lucide="chevron-down" class="size-3.5 transition-transform" :class="showFinance ? 'rotate-180' : ''"></i>
                                </button>
                            </div>
                            <div x-show="showFinance" x-cloak>
                                <label class="block text-xs font-semibold text-emerald-950 mb-1.5">NPWP</label>
                                <input type="text" name="npwp" value="{{ old('npwp', $vault->npwp ?? '') }}" placeholder="15-16 Digit NPWP"
                                    :class="errors.npwp ? 'border-error focus:ring-error/20 focus:border-error' : 'border-emerald-300 focus:ring-emerald-500/20 focus:border-emerald-500'"
                                    class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm focus:outline-none shadow-sm text-foreground transition-colors">
                                {{ $err('npwp') }}
                            </div>
                            <div x-show="showFinance" x-cloak>
                                <label class="block text-xs font-semibold text-emerald-950 mb-1.5">Nomor Rekening</label>
                                <input type="text" name="bank_account" value="{{ old('bank_account', $vault->bank_account ?? '') }}" placeholder="8-20 Digit Rekening"
                                    :class="errors.bank_account ? 'border-error focus:ring-error/20 focus:border-error' : 'border-emerald-300 focus:ring-emerald-500/20 focus:border-emerald-500'"
                                    class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm focus:outline-none shadow-sm text-foreground transition-colors">
                                {{ $err('bank_account') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ FOOTER ============================ --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center justify-between gap-2.5 sm:gap-2 shrink-0">

                {{-- KIRI: Batal / Sebelumnya --}}
                <div class="w-full sm:w-auto flex items-center">
                    <button type="button" x-show="step === 1" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                        class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                        <i data-lucide="x-circle" class="size-4"></i>
                        <span>Batal</span>
                    </button>
                    <button type="button" x-show="step > 1" x-cloak @click="prev()" :disabled="saving"
                        class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                        <i data-lucide="arrow-left" class="size-4"></i>
                        <span>Sebelumnya</span>
                    </button>
                </div>

                {{-- KANAN: Info Step + Selanjutnya / Simpan (keduanya type=submit -> submitStep()) --}}
                <div class="w-full sm:w-auto flex items-center justify-end gap-3">
                    <span class="hidden sm:inline text-xs text-secondary font-medium whitespace-nowrap">Langkah <span x-text="step"></span> dari {{ $totalSteps }}</span>

                    <button type="submit" x-show="step < totalSteps" :disabled="saving"
                        class="w-full sm:w-auto flex items-center justify-center min-w-[140px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                        <div x-show="!saving" class="flex items-center gap-1.5">
                            <span>Selanjutnya</span>
                            <i data-lucide="arrow-right" class="size-4"></i>
                        </div>
                        <div x-show="saving" x-cloak class="flex items-center gap-1.5">
                            <i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i>
                            <span>Menyimpan...</span>
                        </div>
                    </button>

                    <button type="submit" x-show="step === totalSteps" x-cloak :disabled="saving"
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
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>