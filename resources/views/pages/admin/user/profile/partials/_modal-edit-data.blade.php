<div x-data="{ open: false }" x-init="setTimeout(() => open = true, 10)">
    <x-ui.modal show="open" maxWidth="md">

        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-11 sm:size-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="user-pen" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">Edit Data Profil</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                        Perbarui nomor telepon yang dapat dihubungi.
                    </p>
                </div>
            </div>

            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="edit-profile-form"
            hx-put="{{ route('admin.profile.update-data') }}"
            hx-target="#modal-form-container"
            hx-swap="innerHTML"
            x-data="{ saving: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @method('PUT')

            <div class="block p-4 sm:p-6 overflow-y-auto bg-white flex-1 space-y-4">

                {{-- NAMA LENGKAP (Readonly) --}}
                <div>
                    <label class="block text-[10px] font-bold text-secondary uppercase tracking-widest mb-1.5">Nama Lengkap</label>
                    <input type="text" value="{{ $user->staff->name ?? $user->name }}" readonly
                        class="w-full bg-slate-50 border border-border text-secondary rounded-xl px-4 py-3 text-sm focus:outline-none cursor-not-allowed select-none">
                </div>

                {{-- EMAIL (Readonly) --}}
                <div>
                    <label class="block text-[10px] font-bold text-secondary uppercase tracking-widest mb-1.5">Alamat Email</label>
                    <input type="email" value="{{ $user->staff->vault->email ?? $user->email }}" readonly
                        class="w-full bg-slate-50 border border-border text-secondary rounded-xl px-4 py-3 text-sm focus:outline-none cursor-not-allowed select-none">
                    <p class="text-[11px] text-secondary mt-1.5 flex items-center gap-1">
                        <i data-lucide="lock" class="size-3"></i> Identitas login utama tidak dapat diubah.
                    </p>
                </div>

                {{-- NIP / NIK (Readonly) --}}
                <div>
                    <label class="block text-[10px] font-bold text-secondary uppercase tracking-widest mb-1.5">NIP / NIK</label>
                    <input type="text" value="{{ $user->staff->vault->nip ?? '-' }}" readonly
                        class="w-full bg-slate-50 border border-border text-secondary rounded-xl px-4 py-3 text-sm focus:outline-none cursor-not-allowed select-none">
                </div>

                <hr class="border-border my-2">

                {{-- TELEPON (Editable) --}}
                <div>
                    <label class="block text-[10px] font-bold text-foreground uppercase tracking-widest mb-1.5">
                        Nomor Telepon <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <i data-lucide="phone" class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-secondary"></i>
                        {{-- Mengambil value lama jika error, atau value dari vault jika pertama kali dibuka --}}
                        <input type="text" name="telephone" required value="{{ old('telephone', $user->staff->vault->phone_number ?? '') }}" placeholder="Contoh: 081234567890"
                            class="w-full bg-white border @error('telephone') border-error @else border-border @enderror rounded-xl pl-11 pr-4 py-3 text-sm font-medium focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    @error('telephone') <span class="text-error text-[10px] mt-1.5 block font-medium">{{ $message }}</span> @enderror
                </div>

            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-border bg-slate-50/50 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                    class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-slate-50 hover:border-gray-300 transition-all cursor-pointer">
                    Batal
                </button>

                <button type="submit" :disabled="saving"
                    class="flex items-center justify-center min-w-[140px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
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