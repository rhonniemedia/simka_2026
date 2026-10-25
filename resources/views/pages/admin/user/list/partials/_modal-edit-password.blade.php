<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)">
    {{-- Memanggil komponen modal bawaan agar animasinya sama dengan master --}}
    <x-ui.modal show="open" maxWidth="md">

        {{-- Modal Header --}}
        <div class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-border bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <div class="size-11 sm:size-12 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="key-round" class="size-5 sm:size-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-foreground text-base sm:text-lg leading-tight truncate">Reset Kata Sandi</h3>
                    <p class="text-xs sm:text-sm text-secondary mt-0.5 truncate">
                        {{ $user->staff->name ?? $user->username }}
                    </p>
                </div>
            </div>

            {{-- Tombol Silang (X) --}}
            <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                class="size-8 sm:size-9 flex items-center justify-center rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-colors cursor-pointer shrink-0">
                <i data-lucide="x" class="size-4 pointer-events-none"></i>
            </button>
        </div>

        {{-- Form HTMX --}}
        <form id="edit-password-form"
            hx-put="{{ route('admin.users.edit-password.update', $user->id) }}"
            hx-target="#modal-form-container"
            hx-swap="innerHTML"
            x-data="{ saving: false }"
            @htmx:before-request="saving = true"
            @htmx:after-request="saving = false"
            class="flex flex-col flex-1 min-h-0">
            @csrf
            @method('PUT')

            <div x-data="{ showUI: false }" x-init="setTimeout(() => showUI = true, 50)" class="block p-5 sm:p-7 overflow-y-auto flex-1">

                @if ($phone)
                <div class="space-y-6">

                    {{-- Intro: eyebrow + ringkasan + chip nomor terdaftar --}}
                    <div class="transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                        :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-primary/70">
                                Reset Kata Sandi
                            </p>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-white px-2.5 py-1 text-[10px] font-mono text-secondary shadow-sm">
                                <i data-lucide="smartphone" class="size-3 text-primary/60"></i>
                                {{ $phone }}
                            </span>
                        </div>
                        <p class="mt-2 text-[13px] leading-relaxed text-secondary">
                            Kata sandi pengguna akan diatur ulang menggunakan nomor telepon terdaftar di samping.
                        </p>
                    </div>

                    {{-- Kartu Sandi (hero) --}}
                    <div x-data="{ copied: false }"
                        class="transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out motion-safe:delay-100"
                        :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">

                        <label class="flex items-center justify-between text-[10px] font-bold uppercase tracking-[0.2em] text-secondary mb-2.5">
                            Kata Sandi Default Baru
                            {{-- Efek 'ms-pop' diganti dengan x-transition bawaan Alpine --}}
                            <span x-cloak x-show="copied"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 scale-75"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="inline-flex items-center gap-1 normal-case tracking-normal font-semibold text-green-600">
                                <i data-lucide="check" class="size-3"></i> Tersalin
                            </span>
                        </label>

                        <div class="relative rounded-xl border bg-white shadow-sm transition-all duration-300"
                            :class="copied ? 'border-green-300/80 ring-4 ring-green-500/10' : 'border-border'">

                            {{-- Ikon kunci --}}
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                <i data-lucide="key-round" class="size-4 text-secondary/60"></i>
                            </div>

                            {{-- Input baca-saja: font-code diganti font-mono bawaan tailwind --}}
                            <input x-ref="pw" type="text" readonly value="MySch{{ $phone }}*"
                                @click="$refs.pw.select()"
                                class="w-full bg-transparent border-0 py-3.5 pl-11 pr-[4.75rem] text-sm font-mono font-semibold tracking-[0.06em] text-foreground focus:outline-none selection:bg-primary/15">

                            {{-- Tombol salin --}}
                            <div class="absolute inset-y-0 right-1.5 my-1.5 flex items-center">
                                <button type="button" title="Salin kata sandi"
                                    @click="
                                        (async () => {
                                            try { await navigator.clipboard.writeText('MySch{{ $phone }}*'); }
                                            catch (e) { $refs.pw.select(); document.execCommand('copy'); }
                                            copied = true;
                                            setTimeout(() => copied = false, 2200);
                                        })()
                                    "
                                    :class="copied
                                        ? 'border-green-300 bg-green-50 text-green-600'
                                        : 'border-border bg-white text-secondary hover:text-primary hover:border-primary/40'"
                                    class="size-9 flex items-center justify-center rounded-lg border shadow-sm transition-all duration-300 active:scale-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <span class="relative block size-4">
                                        <i data-lucide="copy" x-show="!copied" x-transition.opacity.duration.200ms class="absolute inset-0 size-4"></i>
                                        <i data-lucide="check" x-show="copied" x-cloak x-transition.opacity.duration.200ms class="absolute inset-0 size-4"></i>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <p class="mt-2.5 flex items-center gap-1.5 text-[11px] text-secondary/80">
                            <i data-lucide="shield-check" class="size-3.5 text-primary/50"></i>
                            Dibuat otomatis dari nomor terdaftar — bagikan melalui kanal pribadi yang aman.
                        </p>
                    </div>

                    {{-- Peringatan wajib ganti sandi --}}
                    <div class="flex gap-3 rounded-r-xl border-l-2 border-primary/50 bg-primary/[0.04] px-4 py-3.5 transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out motion-safe:delay-200"
                        :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">
                        <i data-lucide="lock-keyhole" class="size-4 text-primary/70 shrink-0 mt-0.5"></i>
                        <p class="text-xs leading-relaxed text-secondary">
                            <span class="font-semibold text-foreground">Wajibkan pengguna segera mengganti sandi</span>
                            setelah berhasil masuk kembali demi keamanan akun.
                        </p>
                    </div>

                </div>
                @else

                {{-- State kosong --}}
                <div class="relative overflow-hidden rounded-2xl border border-dashed border-error/25 bg-error/[0.03] px-6 py-10 sm:py-12 text-center transform motion-safe:transition-all motion-safe:duration-500 motion-safe:ease-out"
                    :class="showUI ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-error/30 to-transparent"></div>

                    <div class="mx-auto size-11 rounded-full border border-error/20 bg-white text-error flex items-center justify-center shadow-sm">
                        <i data-lucide="phone-off" class="size-4.5"></i>
                    </div>

                    <h4 class="mt-4 text-sm font-semibold tracking-tight text-foreground">Nomor Telepon Kosong</h4>
                    <p class="mx-auto mt-1.5 max-w-[280px] text-xs leading-relaxed text-secondary">
                        Proses reset sandi tidak dapat dilanjutkan. Lengkapi profil staf ini dengan nomor telepon terlebih dahulu.
                    </p>

                    <div class="mt-5 inline-flex items-center gap-1.5 rounded-full border border-error/15 bg-white px-3 py-1.5 text-[10px] font-medium text-secondary shadow-sm">
                        <i data-lucide="user-round-pen" class="size-3 text-error/70"></i>
                        Profil Staf
                        <i data-lucide="chevron-right" class="size-3 text-secondary/40"></i>
                        Nomor Telepon
                    </div>
                </div>

                @endif
            </div>

            {{-- Footer Modal --}}
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-border bg-slate-50/50 flex items-center justify-end gap-2 shrink-0">

                {{-- Tombol Batal --}}
                <button type="button" @click="open = false; setTimeout(() => $el.closest('#modal-form-container').innerHTML = '', 150)"
                    class="px-5 py-2.5 rounded-xl border border-border bg-white text-secondary text-sm font-semibold hover:bg-muted hover:border-gray-300 transition-all cursor-pointer">
                    Batal
                </button>

                {{-- Tombol Simpan --}}
                <button type="submit" :disabled="saving" {{ $phone ? '' : 'disabled' }}
                    class="flex items-center justify-center min-w-[140px] px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">

                    <div x-show="!saving" class="flex items-center gap-1.5">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                        <span>Reset Sandi</span>
                    </div>

                    <div x-show="saving" x-cloak class="flex items-center gap-1.5">
                        <i data-lucide="loader-2" stroke-width="3" class="size-4 animate-spin"></i>
                        <span>Memproses...</span>
                    </div>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</div>