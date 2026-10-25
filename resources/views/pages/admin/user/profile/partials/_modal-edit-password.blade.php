<div x-data="{ open: false }" x-init="setTimeout(() => open = true, 10)">
    <x-ui.modal show="open" maxWidth="md">

        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-11 sm:size-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="key-round" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">Ubah Kata Sandi</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                        Pastikan akun Anda menggunakan sandi yang kuat.
                    </p>
                </div>
            </div>

            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Alert Google User (Opsional: Ditangani Backend jika dicegah dari server, tapi ditampilkan untuk visual fallback) --}}
        @if(isset($user->is_google_user) && $user->is_google_user)
        <div class="p-6">
            <div class="bg-error/10 border border-error/30 text-error text-sm rounded-xl p-4 flex gap-3">
                <i data-lucide="alert-triangle" class="size-5 shrink-0 mt-0.5"></i>
                <p>Akun Anda terhubung dengan otentikasi pihak ketiga (Google). Kata sandi tidak dapat diubah secara manual melalui form ini.</p>
            </div>
        </div>
        @else

        {{-- Form HTMX --}}
        <form id="edit-password-form"
            hx-put="{{ route('admin.profile.update-password') }}"
            hx-target="#modal-form-container"
            hx-swap="innerHTML"
            x-data="{ saving: false, showOld: false, showNew: false, showConfirm: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @method('PUT')

            <div class="block p-4 sm:p-6 overflow-y-auto bg-white flex-1 space-y-4">

                {{-- Alert Tips --}}
                <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-3.5 mb-2">
                    <p class="text-[11px] font-bold text-blue-800 mb-1 flex items-center gap-1.5 uppercase tracking-wider">
                        <i data-lucide="shield-check" class="size-3.5"></i> Tips Keamanan
                    </p>
                    <p class="text-xs text-blue-900/80 leading-relaxed">
                        Gunakan minimal 8 karakter dengan kombinasi huruf dan angka. Jangan gunakan informasi pribadi yang mudah ditebak.
                    </p>
                </div>

                {{-- PASSWORD LAMA --}}
                <div>
                    <label class="block text-[10px] font-bold text-foreground uppercase tracking-widest mb-1.5">
                        Kata Sandi Saat Ini <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showOld ? 'text' : 'password'" name="current_password" required placeholder="Masukkan sandi saat ini"
                            class="w-full bg-white border @error('current_password') border-error @else border-border @enderror rounded-xl px-4 py-3 pr-10 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <button type="button" @click="showOld = !showOld" class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary hover:text-foreground transition-colors">
                            <i data-lucide="eye" x-show="!showOld" class="size-4"></i>
                            <i data-lucide="eye-off" x-show="showOld" x-cloak class="size-4"></i>
                        </button>
                    </div>
                    @error('current_password') <span class="text-error text-[10px] mt-1.5 block font-medium">{{ $message }}</span> @enderror
                </div>

                {{-- PASSWORD BARU --}}
                <div>
                    <label class="block text-[10px] font-bold text-foreground uppercase tracking-widest mb-1.5">
                        Kata Sandi Baru <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'" name="new_password" required placeholder="Minimal 8 karakter"
                            class="w-full bg-white border @error('new_password') border-error @else border-border @enderror rounded-xl px-4 py-3 pr-10 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary hover:text-foreground transition-colors">
                            <i data-lucide="eye" x-show="!showNew" class="size-4"></i>
                            <i data-lucide="eye-off" x-show="showNew" x-cloak class="size-4"></i>
                        </button>
                    </div>
                    @error('new_password') <span class="text-error text-[10px] mt-1.5 block font-medium">{{ $message }}</span> @enderror
                </div>

                {{-- KONFIRMASI PASSWORD BARU --}}
                <div>
                    <label class="block text-[10px] font-bold text-foreground uppercase tracking-widest mb-1.5">
                        Konfirmasi Sandi Baru <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="new_password_confirmation" required placeholder="Ketik ulang sandi baru"
                            class="w-full bg-white border @error('new_password_confirmation') border-error @else border-border @enderror rounded-xl px-4 py-3 pr-10 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary hover:text-foreground transition-colors">
                            <i data-lucide="eye" x-show="!showConfirm" class="size-4"></i>
                            <i data-lucide="eye-off" x-show="showConfirm" x-cloak class="size-4"></i>
                        </button>
                    </div>
                </div>

            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-border bg-slate-50/50 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                    class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-slate-50 hover:border-gray-300 transition-all cursor-pointer">
                    Batal
                </button>

                <button type="submit" :disabled="saving"
                    class="flex items-center justify-center min-w-[150px] px-5 py-2.5 rounded-xl bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 shadow-sm shadow-amber-500/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                    <div x-show="!saving" class="flex items-center gap-1.5">
                        <i data-lucide="check-circle" class="size-4"></i>
                        <span>Perbarui Sandi</span>
                    </div>
                    <div x-show="saving" x-cloak class="flex items-center gap-1.5">
                        <i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i>
                        <span>Memproses...</span>
                    </div>
                </button>
            </div>
        </form>
        @endif
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>