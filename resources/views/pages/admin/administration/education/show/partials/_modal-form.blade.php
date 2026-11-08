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
// Selalu hx-post: PHP tidak mem-parsing body multipart pada request PUT,
// jadi untuk edit method di-spoof lewat @method('PUT') di dalam form.
$method = 'hx-post';

// 4. Format Tanggal
$graduationDateValue = !empty($education->graduation_date)
? \Carbon\Carbon::parse($education->graduation_date)->format('Y-m-d')
: '';
$certificateDateValue = !empty($education->certificate_date)
? \Carbon\Carbon::parse($education->certificate_date)->format('Y-m-d')
: '';

// Berkas yang sudah terunggah (hanya saat edit)
$existingIjazah = $isEdit
? $education->documents()->whereHas('category', fn ($c) => $c->where('code', 'ijazah'))->latest()->first()
: null;
$existingTranscript = $isEdit
? $education->documents()->whereHas('category', fn ($c) => $c->where('code', 'transkrip_nilai'))->latest()->first()
: null;

// Nomor & tanggal transkrip tersimpan di baris dokumennya (bukan di riwayat pendidikan)
$transcriptNumberValue = $existingTranscript?->document_number ?? '';
$transcriptDateValue = !empty($existingTranscript?->document_date)
? \Carbon\Carbon::parse($existingTranscript->document_date)->format('Y-m-d')
: '';

// Opsi status dokumen (sama dengan form di menu Dokumen)
$verificationOptions = [
['value' => 'draft', 'label' => 'Draft'],
['value' => 'verified', 'label' => 'Terverifikasi'],
['value' => 'rejected', 'label' => 'Ditolak'],
];

$errorClass = 'text-[11px] text-error mt-1.5 font-medium';
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
            hx-encoding="multipart/form-data"
            hx-target="#education-container"
            hx-swap="outerHTML"
            x-data="{ 
                saving: false, 
                fileErrors: { ijazah: '', transcript: '' }, 
                errors: {},
                hasFile: { ijazah: false, transcript: false }, 
                hasDoc: { ijazah: {{ $existingIjazah ? 'true' : 'false' }}, transcript: {{ $existingTranscript ? 'true' : 'false' }} },
                
                {{-- State untuk field wajib utama --}}
                education_level_id: '{{ $education->education_level_id ?? '' }}',
                institution_name: '{{ $education->institution_name ?? '' }}',
                
                {{-- State untuk nomor & tanggal ijazah --}}
                certificate_number: '{{ $education->certificate_number ?? '' }}',
                certificate_date: '{{ $certificateDateValue }}',

                {{-- State untuk nomor & tanggal transkrip --}}
                transcript_number: '{{ $transcriptNumberValue }}',
                transcript_date: '{{ $transcriptDateValue }}',

                {{-- Computed validation untuk tombol simpan --}}
                get isIjazahValid() {
                    // Jika ada file ijazah baru ATAU sudah ada dokumen ijazah lama, nomor & tanggal wajib terisi
                    if (this.hasFile.ijazah || this.hasDoc.ijazah) {
                        return this.certificate_number.trim() !== '' && this.certificate_date !== '';
                    }
                    return true;
                },

                get isTranscriptValid() {
                    // Jika ada file transkrip baru ATAU sudah ada dokumen transkrip lama, nomor & tanggal wajib terisi
                    if (this.hasFile.transcript || this.hasDoc.transcript) {
                        return this.transcript_number.trim() !== '' && this.transcript_date !== '';
                    }
                    return true;
                },

                setErrors(xhr) {
                    this.errors = {};
                    if (xhr && xhr.status === 422) {
                        try {
                            this.errors = JSON.parse(xhr.response).errors ?? {};
                        } catch (e) {
                            this.errors = {};
                        }
                    }
                },
                err(field) {
                    return this.errors[field]?.[0] ?? null;
                }
            }"
            @htmx:before-request="if ($event.target === $el) saving = true"
            @htmx:after-request="
                if ($event.target === $el) {
                    saving = false;
                    setErrors($event.detail.xhr);
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            "
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @if ($isEdit)
            @method('PUT')
            @endif

            <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-4 sm:p-7 overflow-y-auto max-h-[calc(100vh-10rem)] sm:max-h-[70vh]">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                    {{-- Jenjang & Jurusan --}}
                    <div @change="education_level_id = $event.target.value">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jenjang Pendidikan <span class="text-error">*</span></label>
                        <x-ui.searchable-select
                            name="education_level_id"
                            :options="$levelOptions"
                            :value="$education->education_level_id ?? ''"
                            placeholder="-- Pilih Jenjang --" />
                        <p class="{{ $errorClass }}" x-show="err('education_level_id')" x-cloak x-text="err('education_level_id')"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Jurusan / Program Studi</label>
                        <input type="text" name="major" value="{{ $education->major ?? '' }}" placeholder="Cth: Teknik Informatika" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="{{ $errorClass }}" x-show="err('major')" x-cloak x-text="err('major')"></p>
                    </div>

                    {{-- Institusi --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nama Institusi / Satuan Pendidikan <span class="text-error">*</span></label>
                        <input type="text" name="institution_name" x-model="institution_name" value="{{ $education->institution_name ?? '' }}" placeholder="Cth: Universitas Indonesia" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="{{ $errorClass }}" x-show="err('institution_name')" x-cloak x-text="err('institution_name')"></p>
                    </div>

                    {{-- Tanggal Lulus & Provinsi --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Lulus</label>
                        <input type="date" name="graduation_date" value="{{ $graduationDateValue }}" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="{{ $errorClass }}" x-show="err('graduation_date')" x-cloak x-text="err('graduation_date')"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Provinsi</label>
                        <input type="text" name="province" value="{{ $education->province ?? '' }}" placeholder="Provinsi institusi" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="{{ $errorClass }}" x-show="err('province')" x-cloak x-text="err('province')"></p>
                    </div>

                    {{-- Gelar Akademik --}}
                    <div class="md:col-span-2 mt-1 sm:mt-2 p-3.5 sm:p-4 rounded-xl border border-border bg-slate-50/50 grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                        <div class="md:col-span-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Data Gelar Akademik</h4>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Nama Gelar</label>
                            <input type="text" name="degree_name" value="{{ $education->degree_name ?? '' }}" placeholder="Cth: Sarjana Komputer" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <p class="{{ $errorClass }}" x-show="err('degree_name')" x-cloak x-text="err('degree_name')"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Singkatan Gelar</label>
                            <input type="text" name="degree_abbreviation" value="{{ $education->degree_abbreviation ?? '' }}" placeholder="Cth: S.Kom" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <p class="{{ $errorClass }}" x-show="err('degree_abbreviation')" x-cloak x-text="err('degree_abbreviation')"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Posisi Gelar</label>
                            <x-ui.select
                                name="degree_position"
                                :options="$positionOptions"
                                :value="$education->degree_position ?? ''"
                                placeholder="-- Pilih --" />
                            <p class="{{ $errorClass }}" x-show="err('degree_position')" x-cloak x-text="err('degree_position')"></p>
                        </div>
                    </div>

                    {{-- ============================================================
                         DOKUMEN IJAZAH
                         ============================================================ --}}
                    <div class="md:col-span-2 p-3.5 sm:p-4 rounded-xl border border-border bg-slate-50/50 space-y-4">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Dokumen Ijazah</h4>
                            <p class="text-[11px] text-secondary mt-1">Isi nomor dan tanggal sesuai yang tertera pada ijazah. Berkas PDF bersifat opsional (maksimal 1 MB).</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                            <div>
                                <label class="block text-xs font-medium text-foreground mb-1.5">
                                    Nomor Ijazah
                                    <span class="text-error" x-show="hasFile.ijazah || hasDoc.ijazah" x-cloak>*</span>
                                </label>
                                <input type="text" name="certificate_number" x-model="certificate_number" placeholder="Nomor pada dokumen ijazah" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <p class="{{ $errorClass }}" x-show="err('certificate_number')" x-cloak x-text="err('certificate_number')"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-foreground mb-1.5">
                                    Tanggal Ijazah
                                    <span class="text-error" x-show="hasFile.ijazah || hasDoc.ijazah" x-cloak>*</span>
                                </label>
                                <input type="date" name="certificate_date" x-model="certificate_date" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <p class="{{ $errorClass }}" x-show="err('certificate_date')" x-cloak x-text="err('certificate_date')"></p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Berkas Ijazah</label>
                            @if ($existingIjazah)
                            <p class="text-xs text-secondary mb-2 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                                <i data-lucide="paperclip" class="size-3.5 shrink-0"></i>
                                <span>Berkas saat ini:</span>
                                <a href="{{ route('admin.personnel.documents.preview', $existingIjazah->id) }}" target="_blank" rel="noopener" title="{{ $existingIjazah->original_filename }}" class="text-blue-600 hover:underline break-all">{{ $existingIjazah->document_name ?? $existingIjazah->original_filename ?? 'Lihat berkas' }}</a>
                                <span>- unggah berkas baru untuk menggantinya.</span>
                            </p>
                            @endif
                            <input type="file" name="file_ijazah" accept="application/pdf,.pdf" x-ref="ijazah"
                                @change="fileErrors.ijazah = ''; errors.file_ijazah = null; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileErrors.ijazah = 'Format berkas harus PDF.'; } else if (f && f.size > 1048576) { fileErrors.ijazah = 'Ukuran berkas maksimal 1 MB.'; } hasFile.ijazah = !!f;"
                                class="w-full rounded-xl border border-border px-3.5 py-2 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/15 cursor-pointer">
                            <p x-show="fileErrors.ijazah" x-cloak class="{{ $errorClass }}">
                                <span x-text="fileErrors.ijazah"></span>
                                Pilih berkas lain, atau
                                <button type="button" class="underline font-medium cursor-pointer" @click="$refs.ijazah.value = ''; fileErrors.ijazah = ''; hasFile.ijazah = false">hapus pilihan</button>
                                untuk menyimpan tanpa berkas.
                            </p>
                            <p class="{{ $errorClass }}" x-show="err('file_ijazah')" x-cloak x-text="err('file_ijazah')"></p>

                            <div class="mt-3">
                                <label class="block text-[11px] font-medium text-secondary mb-1">Status Ijazah</label>
                                <fieldset :disabled="!hasFile.ijazah && !hasDoc.ijazah" class="min-w-0 border-0 p-0 m-0 disabled:opacity-60">
                                    <x-ui.select name="status_ijazah" :options="$verificationOptions" :value="$existingIjazah?->verification_status ?? 'draft'" placeholder="-- Pilih Status --" />
                                </fieldset>
                                <p class="text-[11px] text-secondary mt-1" x-show="!hasFile.ijazah && !hasDoc.ijazah" x-cloak>Pilih berkas Ijazah terlebih dahulu; status disimpan bersama berkas.</p>
                                <p class="{{ $errorClass }}" x-show="err('status_ijazah')" x-cloak x-text="err('status_ijazah')"></p>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================================
                         DOKUMEN TRANSKRIP NILAI
                         ============================================================ --}}
                    <div class="md:col-span-2 p-3.5 sm:p-4 rounded-xl border border-border bg-slate-50/50 space-y-4">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">Dokumen Transkrip Nilai</h4>
                            <p class="text-[11px] text-secondary mt-1">Nomor dan tanggal sesuai yang tertera pada transkrip nilai, disimpan bersama berkas dan wajib diisi saat mengunggah. Berkas PDF bersifat opsional (maksimal 1 MB).</p>
                        </div>

                        <div>
                            <fieldset :disabled="!hasFile.transcript && !hasDoc.transcript" class="min-w-0 border-0 p-0 m-0 disabled:opacity-60">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-foreground mb-1.5">
                                            Nomor Transkrip
                                            <span class="text-error" x-show="hasFile.transcript || hasDoc.transcript" x-cloak>*</span>
                                        </label>
                                        <input type="text" name="transcript_number" x-model="transcript_number" placeholder="Nomor pada transkrip nilai" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <p class="{{ $errorClass }}" x-show="err('transcript_number')" x-cloak x-text="err('transcript_number')"></p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-foreground mb-1.5">
                                            Tanggal Transkrip
                                            <span class="text-error" x-show="hasFile.transcript || hasDoc.transcript" x-cloak>*</span>
                                        </label>
                                        <input type="date" name="transcript_date" x-model="transcript_date" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <p class="{{ $errorClass }}" x-show="err('transcript_date')" x-cloak x-text="err('transcript_date')"></p>
                                    </div>
                                </div>
                            </fieldset>
                            <p class="text-[11px] text-secondary mt-1" x-show="!hasFile.transcript && !hasDoc.transcript" x-cloak>Pilih berkas Transkrip Nilai terlebih dahulu; nomor dan tanggal disimpan bersama berkas.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-foreground mb-1.5">Berkas Transkrip Nilai</label>
                            @if ($existingTranscript)
                            <p class="text-xs text-secondary mb-2 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                                <i data-lucide="paperclip" class="size-3.5 shrink-0"></i>
                                <span>Berkas saat ini:</span>
                                <a href="{{ route('admin.personnel.documents.preview', $existingTranscript->id) }}" target="_blank" rel="noopener" title="{{ $existingTranscript->original_filename }}" class="text-blue-600 hover:underline break-all">{{ $existingTranscript->document_name ?? $existingTranscript->original_filename ?? 'Lihat berkas' }}</a>
                                <span>- unggah berkas baru untuk menggantinya.</span>
                            </p>
                            @endif
                            <input type="file" name="file_transcript" accept="application/pdf,.pdf" x-ref="transcript"
                                @change="fileErrors.transcript = ''; errors.file_transcript = null; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileErrors.transcript = 'Format berkas harus PDF.'; } else if (f && f.size > 1048576) { fileErrors.transcript = 'Ukuran berkas maksimal 1 MB.'; } hasFile.transcript = !!f;"
                                class="w-full rounded-xl border border-border px-3.5 py-2 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/15 cursor-pointer">
                            <p x-show="fileErrors.transcript" x-cloak class="{{ $errorClass }}">
                                <span x-text="fileErrors.transcript"></span>
                                Pilih berkas lain, atau
                                <button type="button" class="underline font-medium cursor-pointer" @click="$refs.transcript.value = ''; fileErrors.transcript = ''; hasFile.transcript = false">hapus pilihan</button>
                                untuk menyimpan tanpa berkas.
                            </p>
                            <p class="{{ $errorClass }}" x-show="err('file_transcript')" x-cloak x-text="err('file_transcript')"></p>

                            <div class="mt-3">
                                <label class="block text-[11px] font-medium text-secondary mb-1">Status Transkrip Nilai</label>
                                <fieldset :disabled="!hasFile.transcript && !hasDoc.transcript" class="min-w-0 border-0 p-0 m-0 disabled:opacity-60">
                                    <x-ui.select name="status_transcript" :options="$verificationOptions" :value="$existingTranscript?->verification_status ?? 'draft'" placeholder="-- Pilih Status --" />
                                </fieldset>
                                <p class="text-[11px] text-secondary mt-1" x-show="!hasFile.transcript && !hasDoc.transcript" x-cloak>Pilih berkas Transkrip Nilai terlebih dahulu; status disimpan bersama berkas.</p>
                                <p class="{{ $errorClass }}" x-show="err('status_transcript')" x-cloak x-text="err('status_transcript')"></p>
                            </div>
                        </div>
                    </div>

                    <p class="md:col-span-2 text-[11px] text-secondary">Nama berkas dibuat otomatis dari jenis dokumen dan jenjang pendidikan (contoh: <span class="font-medium">Ijazah S1</span>, <span class="font-medium">Transkrip Nilai S1</span>).</p>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 sm:gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    <i data-lucide="x-circle" class="size-4"></i>
                    <span>Batal</span>
                </button>
                <button type="submit"
                    :disabled="saving || fileErrors.ijazah !== '' || fileErrors.transcript !== '' || !education_level_id || !institution_name.trim() || !isIjazahValid || !isTranscriptValid"
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