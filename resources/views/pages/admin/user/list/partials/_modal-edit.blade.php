<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)">
    <x-ui.modal show="open" maxWidth="md">

        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-11 sm:size-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="user-pen" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">Ubah Username</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                        {{ $user->staff->name ?? $user->username }}
                    </p>
                </div>
            </div>

            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="edit-user-form"
            hx-put="{{ route('admin.users.update', $user->id) }}"
            hx-target="#modal-form-container"
            hx-swap="innerHTML"
            x-data="{ saving: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @method('PUT')

            <div class="block p-4 sm:p-6 overflow-y-auto bg-slate-50/30 flex-1 space-y-5">

                {{-- Username --}}
                <div>
                    <label for="edit-username" class="block text-[10px] font-semibold text-secondary uppercase tracking-wider mb-2">
                        Username
                    </label>
                    <input id="edit-username" type="text" name="username" value="{{ old('username', $user->username) }}"
                        maxlength="50" required autocomplete="off"
                        class="w-full bg-white border rounded-xl px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:border-primary transition-colors {{ $errors->has('username') ? 'border-error ring-1 ring-error/30' : 'border-border' }}">
                    @error('username') <span class="text-error text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <p class="text-[11px] text-secondary">
                    Username berlaku di semua aplikasi yang memakai akun ini. Untuk mengubah hak akses atau mereset kata sandi, gunakan tombol Role dan Sandi pada daftar.
                </p>
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-border bg-slate-50/50 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                    class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    Batal
                </button>

                <button type="submit" :disabled="saving"
                    class="flex items-center justify-center min-w-[140px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">

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