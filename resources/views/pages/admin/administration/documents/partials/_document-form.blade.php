@php
$item = $item ?? null;
$isEdit = !empty($item);
$modalTitle = $isEdit ? 'Edit Dokumen' : 'Tambah Dokumen';
$actionUrl = $isEdit
? route('admin.personnel.documents.update', $item->id)
: route('admin.personnel.documents.store');
$method = 'hx-post';

$categoryLinks = $categoryLinks ?? [];
$linkedOptions = $linkedOptions ?? [];
// Dokumen lama yang kategorinya bertaut tapi belum menaut ke data apa pun.
$legacyUnlinked = $isEdit && blank($item->linked_record_id) && filled($item->category?->linked_table);

$verificationOptions = [
['value' => 'draft', 'label' => 'Draft'],
['value' => 'verified', 'label' => 'Terverifikasi'],
['value' => 'rejected', 'label' => 'Ditolak'],
];
@endphp

<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="lg">
        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="size-10 rounded-full {{ $isEdit ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="{{ $isEdit ? 'file-pen-line' : 'plus' }}" class="size-5"></i>
                </div>
                <h3 class="font-bold text-foreground text-base leading-tight truncate">{{ $modalTitle }}</h3>
            </div>
            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                class="size-8 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="document-form"
            {!! $method !!}="{{ $actionUrl }}"
            hx-swap="none"
            hx-encoding="multipart/form-data"
            data-no-loader
            x-data="{
                saving: false,
                fileError: '',
                categoryLinks: @js($categoryLinks),
                staffId: @js($item?->staff_id ?? ''),
                categoryId: @js($item?->staff_document_category_id ?? ''),
                loadingLinks: false,
                linkMessage: '',
                get needsLink() { return !!(this.categoryLinks[this.categoryId] ?? null); },
                async onChange(e) {
                    const name = e.target?.name;
                    if (name === 'staff_id') { this.staffId = e.target.value; }
                    else if (name === 'staff_document_category_id') { this.categoryId = e.target.value; }
                    else { return; }
                    await this.refreshLinks();
                },
                async refreshLinks() {
                    this.linkMessage = '';
                    const setOptions = (options) => window.dispatchEvent(new CustomEvent('update-options', { detail: { name: 'linked_record_id', options } }));
                    if (!this.needsLink || !this.staffId) { setOptions([]); return; }
                    this.loadingLinks = true;
                    try {
                        const url = '{{ route('admin.personnel.documents.linkable') }}' + '?staff_id=' + encodeURIComponent(this.staffId) + '&staff_document_category_id=' + encodeURIComponent(this.categoryId);
                        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        const data = await res.json();
                        const options = data.options ?? [];
                        setOptions(options);
                        if (options.length === 0) { this.linkMessage = 'Pegawai ini belum punya data riwayat untuk jenis dokumen ini. Tambahkan datanya di menu terkait terlebih dahulu.'; }
                    } catch (e) {
                        this.linkMessage = 'Gagal memuat data terkait. Coba pilih ulang pegawai atau kategori.';
                    } finally {
                        this.loadingLinks = false;
                    }
                }
            }"
            @change="onChange($event)"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @if($isEdit)
            @method('PUT')
            @endif

            <div class="p-4 sm:p-6 space-y-4 overflow-y-auto">

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1.5">Pegawai <span class="text-error">*</span></label>
                    <x-ui.searchable-select
                        name="staff_id"
                        :options="$staffOptions"
                        :value="old('staff_id', $item?->staff_id ?? '')"
                        placeholder="Cari nama pegawai..." />
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1.5">Kategori Dokumen <span class="text-error">*</span></label>
                    <x-ui.select
                        name="staff_document_category_id"
                        :options="$categoryOptions"
                        value="{{ old('staff_document_category_id', $item?->staff_document_category_id ?? '') }}"
                        placeholder="-- Pilih Kategori --" />
                </div>

                {{-- Data terkait: hanya untuk kategori yang bertaut ke data riwayat --}}
                <div x-show="needsLink" x-cloak>
                    <label class="block text-sm font-medium text-foreground mb-1.5">
                        Data Terkait
                        @if ($legacyUnlinked)
                        <span class="text-secondary font-normal">(dokumen ini belum bertaut)</span>
                        @else
                        <span class="text-error">*</span>
                        @endif
                    </label>
                    <x-ui.select
                        name="linked_record_id"
                        :options="$linkedOptions"
                        value="{{ old('linked_record_id', $item?->linked_record_id ?? '') }}"
                        placeholder="-- Pilih data terkait --" />
                    <p x-show="loadingLinks" x-cloak class="text-[11px] text-secondary mt-1">Memuat data terkait...</p>
                    <p x-show="linkMessage" x-cloak x-text="linkMessage" class="text-[11px] text-amber-700 mt-1"></p>
                    <p class="text-[11px] text-secondary mt-1">
                        @if ($legacyUnlinked)
                        Pilih data riwayat untuk menautkan berkas ini, atau biarkan kosong untuk tetap "Belum bertaut".
                        @else
                        Berkas jenis ini harus ditautkan ke data riwayat milik pegawai yang dipilih.
                        @endif
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1.5">Nama Dokumen <span class="text-error">*</span></label>
                    <input type="text" name="document_name" value="{{ old('document_name', $item?->document_name ?? '') }}" required placeholder="Contoh: SK Pengangkatan CPNS" class="w-full h-11 rounded-xl border border-border px-3.5 py-0 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Nomor Dokumen</label>
                        <input type="text" name="document_number" value="{{ old('document_number', $item?->document_number ?? '') }}" placeholder="Opsional" class="w-full h-11 rounded-xl border border-border px-3.5 py-0 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Tanggal Dokumen</label>
                        <input type="date" name="document_date" value="{{ old('document_date', $item?->document_date?->format('Y-m-d') ?? '') }}" class="w-full h-11 rounded-xl border border-border px-3.5 py-0 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1.5">Status Verifikasi <span class="text-error">*</span></label>
                    <x-ui.select
                        name="verification_status"
                        :options="$verificationOptions"
                        value="{{ old('verification_status', $item?->verification_status ?? 'draft') }}"
                        placeholder="-- Pilih Status --" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1.5">
                        File Dokumen
                        @if(!$isEdit) <span class="text-error">*</span> @endif
                    </label>
                    <input type="file" name="file" accept=".pdf,application/pdf" x-ref="sk" {{ $isEdit ? '' : 'required' }}
                        @change="fileError = ''; const f = $event.target.files[0]; if (f && f.type !== 'application/pdf') { fileError = 'Format berkas harus PDF.'; } else if (f && f.size > 1048576) { fileError = 'Ukuran berkas maksimal 1 MB.'; }"
                        class="w-full h-11 rounded-xl border border-border pl-1.5 pr-3.5 py-0 text-sm leading-[2.5rem] text-secondary file:mr-3 file:h-8 file:px-3 file:my-0 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:text-xs file:font-semibold file:cursor-pointer focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <p class="text-[11px] text-secondary mt-1">Format PDF, maksimal 1 MB.</p>
                    <p x-show="fileError" x-cloak class="text-xs text-error mt-1">
                        <span x-text="fileError"></span>
                        Pilih berkas lain{{ $isEdit ? ', atau' : '.' }}
                        @if ($isEdit)
                        <button type="button" class="underline font-medium cursor-pointer" @click="$refs.sk.value = ''; fileError = ''">hapus pilihan</button>
                        untuk tidak mengganti berkas.
                        @endif
                    </p>

                    @if($isEdit && $item->original_filename)
                    <p class="text-xs text-foreground mt-2 flex items-center gap-1.5">
                        <i data-lucide="paperclip" class="size-3.5 text-secondary"></i>
                        File saat ini: <a href="{{ route('admin.personnel.documents.preview', $item->id) }}" target="_blank" rel="noopener" class="font-medium text-blue-600 hover:underline break-all">{{ $item->original_filename }}</a>
                    </p>
                    <p class="text-[11px] text-secondary mt-0.5">Kosongkan kolom di atas jika tidak ingin mengganti file. Berkas pengganti akan menggantikan yang lama (yang lama tetap tersimpan sebagai arsip).</p>
                    @endif
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border bg-slate-50/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 sm:gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)"
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    <i data-lucide="x-circle" class="size-4"></i>
                    <span>Batal</span>
                </button>
                <button type="submit" :disabled="saving || fileError !== ''"
                    class="w-full sm:w-auto flex items-center justify-center min-w-[120px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                    <div x-show="!saving" class="flex items-center gap-1.5">
                        <i data-lucide="save" class="size-4"></i>
                        <span>Simpan</span>
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