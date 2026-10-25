@extends('layouts.main.admin')

@section('title', 'Profil Saya')
@section('page_title', 'Profil Saya')
@section('page_subtitle', 'Kelola informasi akun dan keamanan Anda')

@section('content')
<div class="px-5 py-8 md:p-8"
    x-data="profileApp(@js($userData))"
    @profile-updated.window="
        user.telephone = $event.detail.telephone; 
        window.ShowAlert({type: 'success', title: 'Berhasil!', message: $event.detail.message});
     "
    @password-updated.window="
        window.ShowAlert({type: 'success', title: 'Aman!', message: $event.detail.message});
     ">

    {{-- PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Profil Saya</h1>
            <p class="text-sm text-secondary">Kelola informasi akun dan keamanan Anda.</p>
        </div>
    </div>

    {{-- ── CARD 1: Profile Header ── --}}
    <div class="bg-white rounded-2xl border border-border px-6 py-5 mb-4">
        <div class="flex items-center gap-5">
            {{-- Avatar --}}
            <div class="relative shrink-0">
                <template x-if="user.photo">
                    <img :src="user.photo" alt="Avatar" class="w-16 h-16 rounded-full object-cover shadow-sm border border-border">
                </template>
                <template x-if="!user.photo">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center shadow-sm border border-primary/20">
                        <span class="text-xl font-bold text-primary tracking-wide" x-text="initials"></span>
                    </div>
                </template>
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-foreground leading-snug" x-text="user.name"></h2>
                <span class="mt-1.5 inline-flex items-center gap-1.5 text-[10px] font-bold text-primary bg-primary/10 border border-primary/20 px-2.5 py-1 rounded-full tracking-wider uppercase">
                    <i data-lucide="shield-check" class="size-3"></i>
                    <span x-text="user.role"></span>
                </span>
            </div>

            {{-- Status --}}
            <div class="shrink-0 flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-50 border border-green-100">
                <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_6px_rgba(34,197,94,0.6)] animate-pulse"></span>
                <span class="text-xs font-semibold text-green-700">Aktif</span>
            </div>
        </div>
    </div>

    {{-- ── CARD 2: Upload Foto ── --}}
    <div class="bg-white rounded-2xl border border-border px-6 py-5 mb-4">
        <h5 class="text-[10px] font-bold text-secondary uppercase tracking-widest mb-1">Upload Foto</h5>
        <p class="text-xs text-secondary mb-4">Maksimal 1MB · JPG atau PNG</p>

        {{-- Input hidden --}}
        <input type="file" x-ref="photoInput" @change="uploadPhoto" class="hidden" accept="image/png, image/jpeg, image/jpg">

        <div @click="$refs.photoInput.click()" class="border-2 border-dashed border-border rounded-xl py-8 flex flex-col items-center gap-3 hover:border-primary/50 hover:bg-primary/5 transition-colors duration-200 cursor-pointer relative overflow-hidden group">

            <div x-show="photoLoading" x-cloak class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
                <i data-lucide="loader-2" class="size-6 text-primary animate-spin mb-2"></i>
                <span class="text-xs font-semibold text-primary">Mengunggah...</span>
            </div>

            <div class="size-11 rounded-full bg-slate-50 border border-border flex items-center justify-center group-hover:bg-white group-hover:shadow-sm transition-all">
                <i data-lucide="cloud-upload" class="size-5 text-secondary group-hover:text-primary transition-colors"></i>
            </div>
            <div class="text-center">
                <p class="text-sm font-semibold text-foreground">Klik untuk upload atau drag & drop</p>
                <p class="text-[11px] text-secondary mt-1">Pilih foto langsung dari perangkat</p>
            </div>
        </div>
    </div>

    {{-- ── CARD 3: Informasi Pribadi ── --}}
    <div class="bg-white rounded-2xl border border-border mb-4">

        {{-- Card Header & Dropdown --}}
        <div class="flex items-start justify-between px-6 py-5 border-b border-border">
            <div>
                <h5 class="text-[10px] font-bold text-secondary uppercase tracking-widest">Informasi Pribadi</h5>
                <p class="text-xs text-secondary mt-0.5">Data profil pengguna</p>
            </div>

            {{-- Dropdown Menu (Terintegrasi HTMX) --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false"
                    class="size-8 rounded-lg border border-border hover:bg-slate-50 flex items-center justify-center text-secondary hover:text-foreground transition cursor-pointer">
                    <i data-lucide="more-vertical" class="size-4"></i>
                </button>

                <div x-show="open" x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-52 bg-white rounded-xl border border-border shadow-lg shadow-slate-200/50 overflow-hidden z-30 p-1.5">

                    {{-- Tombol Edit Data Profil --}}
                    <button type="button" @click="open = false"
                        hx-get="{{ route('admin.profile.edit-data') }}"
                        hx-target="#modal-form-container"
                        hx-swap="innerHTML"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-foreground hover:text-primary hover:bg-primary/5 transition cursor-pointer">
                        <i data-lucide="user-pen" class="size-4"></i>
                        Edit Data Profil
                    </button>

                    {{-- Tombol Ubah Kata Sandi --}}
                    <button type="button" @click="open = false"
                        hx-get="{{ route('admin.profile.edit-password') }}"
                        hx-target="#modal-form-container"
                        hx-swap="innerHTML"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-foreground hover:text-amber-600 hover:bg-amber-50 transition cursor-pointer mt-1">
                        <i data-lucide="key-round" class="size-4"></i>
                        Ubah Kata Sandi
                    </button>

                </div>
            </div>
        </div>

        {{-- Fields Grid (Dikembalikan ke 3 Kolom) --}}
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <template x-for="field in fields" :key="field.key">
                <div>
                    <label class="block text-[10px] font-bold text-secondary uppercase tracking-widest mb-1.5" x-text="field.label"></label>
                    <div class="w-full rounded-xl bg-slate-50 border border-border text-foreground text-sm px-4 py-3 cursor-default select-none flex items-center min-h-[46px]">
                        <span x-text="field.value" class="font-medium"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- TEMPAT RENDER MODAL HTMX --}}
    <div id="modal-form-container"></div>

</div>
@endsection

@push('scripts')
<script>
    function profileApp(initialData) {
        return {
            user: initialData,
            photoLoading: false,

            get initials() {
                if (!this.user.name) return 'U';
                return this.user.name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
            },

            get fields() {
                return [{
                        key: 'name',
                        label: 'Nama Lengkap',
                        value: this.user.name || '-'
                    },
                    {
                        key: 'email',
                        label: 'Email',
                        value: this.user.email || '-'
                    },
                    {
                        key: 'telephone',
                        label: 'Telepon',
                        value: this.user.telephone || '-'
                    },
                    {
                        key: 'nip',
                        label: 'NIP',
                        value: this.user.nip || '-'
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        value: 'Aktif'
                    }, // Sesuai dengan desain asli
                    {
                        key: 'role',
                        label: 'Role',
                        value: this.user.role || '-'
                    },
                ];
            },

            async uploadPhoto(event) {
                const file = event.target.files[0];
                if (!file) return;

                if (file.size > 1 * 1024 * 1024) {
                    window.ShowAlert({
                        type: 'error',
                        title: 'File Terlalu Besar!',
                        message: 'Maksimal 1MB.'
                    });
                    event.target.value = '';
                    return;
                }

                this.photoLoading = true;
                const formData = new FormData();
                formData.append('photo', file);

                try {
                    const res = await fetch(@js(route('admin.profile.upload-photo')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await res.json();
                    if (res.ok && data.status === 'success') {
                        this.user.photo = data.photo_url;
                        window.ShowAlert({
                            type: 'success',
                            title: 'Foto Diperbarui!',
                            message: data.message
                        });
                    } else {
                        window.ShowAlert({
                            type: 'error',
                            title: 'Gagal',
                            message: data.message
                        });
                    }
                } catch (e) {
                    window.ShowAlert({
                        type: 'error',
                        title: 'Gagal',
                        message: 'Periksa jaringan internet.'
                    });
                } finally {
                    this.photoLoading = false;
                    event.target.value = '';
                }
            },

            init() {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        }
    }
</script>
@endpush