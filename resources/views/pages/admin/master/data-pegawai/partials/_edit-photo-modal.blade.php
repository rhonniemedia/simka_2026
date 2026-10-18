<div id="modal-container"
    x-data="{ 
        open: false,
        closeModal() {
            this.open = false;
            setTimeout(() => document.getElementById('modal-container').outerHTML = '<div id=\'modal-container\'></div>', 300);
        }
    }"
    x-init="setTimeout(() => open = true, 50)"
    @close-modal.window="closeModal()">

    <x-ui.modal show="open" maxWidth="xl">
        {{-- Pembungkus Utama --}}
        <div id="photo-modal-content" class="flex flex-col flex-1 h-full w-full min-h-0 bg-white overflow-hidden"
            x-data="{
                fotoFile: null,
                fotoPreview: null,
                existingPhoto: {{ $staff->photo ? 'true' : 'false' }},
                errorsPhoto: {},
                hasErrorPhoto: false,

                handleFoto(e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    this.fotoFile = file.name;
                    this.existingPhoto = false;

                    /* Reset error foto saat user memilih file baru */
                    this.errorsPhoto = {};
                    this.hasErrorPhoto = false;

                    const reader = new FileReader();
                    reader.onload = (ev) => { this.fotoPreview = ev.target.result; };
                    reader.readAsDataURL(file);
                },

                setErrorsPhoto(xhr) {
                    if (xhr.status === 422) {
                        try {
                            const body = JSON.parse(xhr.response);
                            this.errorsPhoto   = body.errors ?? {};
                            this.hasErrorPhoto = Object.keys(this.errorsPhoto).length > 0;
                        } catch(e) {
                            this.errorsPhoto   = {};
                            this.hasErrorPhoto = false;
                        }
                    } else {
                        this.errorsPhoto   = {};
                        this.hasErrorPhoto = false;
                    }
                },

                errPhoto(field) {
                    return this.errorsPhoto[field]?.[0] ?? null;
                }
            }"
            x-init="
                @if($staff->photo)
                    fotoPreview = '{{ \Storage::url($staff->photo) }}';
                    fotoFile = '{{ basename($staff->photo) }}';
                @endif
            ">

            {{-- HEADER --}}
            <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-border bg-slate-50/50 shrink-0">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <div class="size-11 sm:size-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 shadow-sm">
                        <i data-lucide="camera" class="size-5 sm:size-6"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">Upload Foto Pegawai</h3>
                        <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate uppercase">{{ $staff->name }}</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()"
                    class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                    <i data-lucide="x" class="size-4 pointer-events-none"></i>
                </button>
            </div>

            <form id="edit-photo-form" class="flex flex-col flex-1 min-h-0"
                hx-post="{{ route('admin.personnel.data.update-photo', $staff->id) }}"
                hx-encoding="multipart/form-data"
                hx-target="#photo-modal-content" hx-select="#photo-modal-content" hx-swap="outerHTML"
                @htmx:after-request="
                    const xhr = $event.detail.xhr;
                    if (xhr.status === 422) {
                        setErrorsPhoto(xhr);
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    } else if (xhr.status === 200) {
                        window.dispatchEvent(new CustomEvent('close-modal'));
                    }
                ">
                @csrf @method('PUT')

                {{-- AREA KONTEN --}}
                <div class="flex-1 min-h-0 p-4 sm:p-6 space-y-5 overflow-y-auto [scrollbar-gutter:stable]">

                    {{-- Info box --}}
                    <div class="flex gap-3 items-start bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3.5">
                        <i data-lucide="info" class="text-blue-500 size-5 mt-0.5 flex-shrink-0"></i>
                        <p class="text-xs font-medium text-blue-800 leading-relaxed">
                            Foto harus berupa foto formal terbaru (maks. 6 bulan terakhir), dengan latar belakang <strong>merah atau biru</strong>. Wajah terlihat jelas, tidak memakai kacamata atau topi.
                        </p>
                    </div>

                    {{-- Muncul saat server tolak file --}}
                    <div x-show="hasErrorPhoto" x-transition x-cloak
                        class="flex gap-3 items-start bg-red-50 border border-red-200 rounded-2xl px-4 py-3.5">
                        <i data-lucide="alert-triangle" class="text-error size-5 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-black text-red-700 mb-1">Upload foto gagal:</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                <template x-for="(msgs, field) in errorsPhoto" :key="field">
                                    <template x-for="msg in msgs" :key="msg">
                                        <li class="text-sm text-red-600" x-text="msg"></li>
                                    </template>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Ketentuan pas foto --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach([
                        ['icon' => 'image', 'label' => 'Format', 'desc' => 'JPG atau PNG'],
                        ['icon' => 'hard-drive', 'label' => 'Ukuran File', 'desc' => 'Maks. 1 MB'],
                        ['icon' => 'maximize', 'label' => 'Dimensi', 'desc' => '3×4 atau 4×6 cm'],
                        ['icon' => 'palette', 'label' => 'Latar', 'desc' => 'Merah / Biru'],
                        ] as $item)
                        <div class="flex flex-col items-center gap-2 p-4 bg-slate-50 rounded-2xl border border-border text-center">
                            <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                                <i data-lucide="{{ $item['icon'] }}" class="text-primary size-5"></i>
                            </div>
                            <span class="text-[11px] font-bold text-foreground leading-tight">{{ $item['label'] }}</span>
                            <span class="text-[11px] text-secondary leading-tight">{{ $item['desc'] }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Upload area --}}
                    <div class="space-y-2.5">
                        <label class="text-xs font-black text-secondary uppercase tracking-wider">
                            File Foto <span class="text-error">*</span>
                        </label>

                        {{-- Empty state: belum ada foto --}}
                        <div x-show="!fotoPreview"
                            @click="$refs.inputFoto.click()"
                            :class="errPhoto('photo') ? 'border-red-400 bg-red-50/40 hover:border-red-500' : 'border-border hover:border-primary/50 hover:bg-primary/5'"
                            class="border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all group">

                            <div class="size-14 rounded-full flex items-center justify-center mx-auto mb-3 transition-colors"
                                :class="errPhoto('photo') ? 'bg-red-100' : 'bg-slate-100 group-hover:bg-primary/10'">
                                <i data-lucide="camera" class="size-6 transition-colors"
                                    :class="errPhoto('photo') ? 'text-red-400' : 'text-slate-400 group-hover:text-primary'"></i>
                            </div>
                            <p class="text-sm font-bold text-foreground mb-1">Klik untuk memilih foto</p>
                            <p class="text-xs text-secondary">atau seret & lepas file ke sini</p>

                            <p class="text-xs text-error font-bold mt-3 flex items-center justify-center gap-1.5" x-show="errPhoto('photo')" x-cloak>
                                <i data-lucide="alert-circle" class="size-4"></i>
                                <span x-text="errPhoto('photo')"></span>
                            </p>
                        </div>

                        {{-- Preview state --}}
                        <div x-show="fotoPreview" class="relative" x-cloak>
                            <div class="flex flex-col sm:flex-row items-center gap-5 p-5 rounded-2xl border-2 transition-colors"
                                :class="errPhoto('photo') ? 'bg-red-50 border-red-300' : 'bg-primary/5 border-primary/20'">

                                <div class="flex-shrink-0">
                                    <img :src="fotoPreview" alt="Preview Pas Foto"
                                        class="w-[90px] h-[120px] object-cover rounded-xl shadow-sm border-2"
                                        :class="errPhoto('photo') ? 'border-red-300' : 'border-primary/30'">
                                </div>

                                <div class="flex-1 text-center sm:text-left">

                                    {{-- Status --}}
                                    <div x-show="existingPhoto && !errPhoto('photo')" x-cloak class="flex items-center justify-center sm:justify-start gap-1.5 mb-2">
                                        <i data-lucide="circle-check-big" class="text-blue-500 size-4"></i>
                                        <span class="text-xs font-black text-blue-700">Foto tersimpan saat ini</span>
                                    </div>
                                    <div x-show="!existingPhoto && !errPhoto('photo')" x-cloak class="flex items-center justify-center sm:justify-start gap-1.5 mb-2">
                                        <i data-lucide="circle-check-big" class="text-emerald-500 size-4"></i>
                                        <span class="text-xs font-black text-emerald-700">Foto berhasil dipilih</span>
                                    </div>
                                    <div x-show="errPhoto('photo')" x-cloak class="flex items-center justify-center sm:justify-start gap-1.5 mb-2">
                                        <i data-lucide="x-circle" class="text-red-500 size-4"></i>
                                        <span class="text-xs font-black text-red-700" x-text="errPhoto('photo')"></span>
                                    </div>

                                    <p class="text-sm font-bold text-foreground truncate max-w-[200px]" x-text="fotoFile"></p>
                                    <p class="text-[11px] text-secondary mt-0.5">Pastikan wajah terlihat jelas</p>

                                    <button type="button"
                                        @click="fotoFile = null; fotoPreview = null; existingPhoto = false; errorsPhoto = {}; hasErrorPhoto = false; $refs.inputFoto.value = ''"
                                        class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-error border border-error/30 rounded-xl hover:bg-error/10 transition-all cursor-pointer">
                                        <i data-lucide="trash-2" class="size-3.5"></i> Ganti Foto
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Input file tersembunyi --}}
                        <input type="file" x-ref="inputFoto" name="photo" accept=".jpg,.jpeg,.png" class="hidden" @change="handleFoto($event)">
                    </div>
                </div>

                {{-- AREA FOOTER --}}
                <div class="mt-auto px-4 sm:px-6 py-3.5 sm:py-4 border-t border-border bg-slate-50/50 flex items-center justify-end gap-3 shrink-0 sm:rounded-b-2xl">
                    <button type="button" @click="closeModal()"
                        class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                        Batal
                    </button>

                    <button type="submit"
                        x-data="{ saving: false }"
                        @htmx:before-request="saving = true"
                        @htmx:after-request="saving = false"
                        :disabled="saving"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all shadow-sm shadow-primary/30 disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer">
                        <i data-lucide="save" class="size-4" x-show="!saving"></i>
                        <i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin text-white" x-show="saving" x-cloak></i>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Foto'"></span>
                    </button>
                </div>
            </form>

            <script>
                if (typeof lucide !== 'undefined') lucide.createIcons();
            </script>
        </div>
    </x-ui.modal>
</div>