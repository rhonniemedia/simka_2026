<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)">
    <x-ui.modal show="open" maxWidth="md">

        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-11 sm:size-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="user-plus" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">Tambah Pengguna</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">Buat akun atau beri akses SIMKA dari data staf</p>
                </div>
            </div>

            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="create-user-form"
            hx-post="{{ route('admin.users.store') }}"
            hx-target="#modal-form-container"
            hx-swap="innerHTML"
            x-data="{ saving: false, staffId: '{{ old('staff_id') }}', existing: @js($existingAccounts), get isExisting() { return !!this.existing[this.staffId]; } }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            @change="if ($event.target.name === 'staff_id') staffId = $event.target.value"
            class="flex flex-col flex-1 min-h-0">
            @csrf

            <div class="block p-4 sm:p-6 overflow-y-auto bg-slate-50/30 flex-1 space-y-5">

                @if (count($staffOptions) === 0)
                <div class="rounded-xl border border-dashed border-border bg-white px-4 py-8 text-center">
                    <i data-lucide="users-round" class="size-8 text-border mx-auto"></i>
                    <p class="mt-2 text-sm font-medium text-foreground">Tidak ada staf yang bisa dibuatkan akun</p>
                    <p class="mt-1 text-xs text-secondary">
                        Semua staf aktif sudah memiliki akses SIMKA.
                    </p>
                </div>
                @else

                {{-- Pilih Staf --}}
                <div>
                    <label class="block text-[10px] font-semibold text-secondary uppercase tracking-wider mb-2">
                        Staf
                    </label>
                    <x-ui.searchable-select
                        name="staff_id"
                        :options="$staffOptions"
                        placeholder="Cari nama staf..." />
                    <p class="mt-1.5 text-[11px] text-secondary">
                        Hanya staf aktif yang belum punya akses SIMKA. Staf yang sudah punya akun dari aplikasi lain cukup diberi akses.
                    </p>
                    @error('staff_id') <span class="text-error text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Username (hanya untuk staf yang belum punya akun) --}}
                <div x-show="!isExisting">
                    <label for="create-username" class="block text-[10px] font-semibold text-secondary uppercase tracking-wider mb-2">
                        Username
                    </label>
                    <input id="create-username" type="text" name="username" value="{{ old('username') }}"
                        maxlength="50" autocomplete="off" placeholder="contoh: budi.santoso"
                        :required="!isExisting" :disabled="isExisting"
                        class="w-full bg-white border rounded-xl px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:border-primary transition-colors {{ $errors->has('username') ? 'border-error ring-1 ring-error/30' : 'border-border' }}">
                    <p class="mt-1.5 text-[11px] text-secondary">
                        Huruf, angka, titik, garis bawah, atau strip. Tidak boleh seluruhnya angka 10-20 digit.
                    </p>
                    @error('username') <span class="text-error text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Info akun yang sudah ada --}}
                <div x-show="isExisting" x-cloak class="rounded-xl border border-border bg-white px-3.5 py-3">
                    <p class="text-[10px] font-semibold text-secondary uppercase tracking-wider mb-1">Akun Sudah Ada</p>
                    <p class="text-sm text-foreground">
                        Username: <span class="font-semibold" x-text="existing[staffId]"></span>
                    </p>
                    <p class="mt-1.5 text-[11px] text-secondary">
                        Akun ini hanya diberi akses SIMKA. Username dan kata sandinya tidak diubah.
                    </p>
                </div>

                {{-- Pilih Role --}}
                <div class="space-y-2">
                    <label class="block text-[10px] font-semibold text-secondary uppercase tracking-wider mb-2">
                        Role
                    </label>

                    @forelse ($roles as $role)
                    <label class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-border bg-white cursor-pointer hover:border-primary/40 transition-colors">
                        <input type="radio" name="role_id" value="{{ $role->id }}"
                            @checked(old('role_id', $defaultRoleId)===$role->id)
                        class="size-4 border-border text-primary focus:ring-primary/30">
                        <span class="text-sm font-medium text-foreground capitalize">{{ $role->name }}</span>
                    </label>
                    @empty
                    <p class="text-sm text-secondary">Belum ada role yang tersedia.</p>
                    @endforelse

                    @error('role_id') <span class="text-error text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Info kata sandi awal (tidak ditampilkan di form; muncul sekali setelah akun dibuat) --}}
                <div x-show="staffId && !isExisting" x-cloak class="rounded-xl border border-border bg-white px-3.5 py-3">
                    <p class="text-[10px] font-semibold text-secondary uppercase tracking-wider mb-1">Kata Sandi Awal</p>
                    <p class="text-[11px] text-secondary">
                        Dibuat otomatis dari nomor telepon staf dan ditampilkan satu kali setelah akun dibuat.
                        Minta pengguna segera menggantinya setelah masuk.
                    </p>
                </div>

                @endif
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-border bg-slate-50/50 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                    class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    Batal
                </button>

                <button type="submit" :disabled="saving" {{ count($staffOptions) === 0 ? 'disabled' : '' }}
                    class="flex items-center justify-center min-w-[140px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">

                    <div x-show="!saving" class="flex items-center gap-1.5">
                        <i data-lucide="user-plus" class="size-4"></i>
                        <span>Buat Akun</span>
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