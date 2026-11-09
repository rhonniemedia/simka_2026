@auth
<style>
    /* Animasi masuk disamakan dengan x-ui.modal (fade backdrop + panel turun dari atas, 300ms).
       Animasi berjalan ulang setiap elemen berubah dari hidden -> flex. */
    @keyframes idle-fade-in {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes idle-slide-in {
        from {
            opacity: 0;
            transform: translateY(-2rem) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Denyut halus pada cincin ikon, satu-satunya gerakan yang berjalan terus. */
    @keyframes idle-halo {
        0% {
            transform: scale(1);
            opacity: 0.55;
        }

        70% {
            transform: scale(1.5);
            opacity: 0;
        }

        100% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    .idle-backdrop-in {
        animation: idle-fade-in 300ms ease-out;
    }

    .idle-panel-in {
        animation: idle-slide-in 350ms cubic-bezier(0.16, 1, 0.3, 1);
    }

    .idle-halo {
        animation: idle-halo 2.4s ease-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {

        .idle-backdrop-in,
        .idle-panel-in,
        .idle-halo {
            animation: none;
        }
    }
</style>

<div id="idle-timeout"
    data-lifetime="{{ config('session.lifetime') }}"
    data-warning="{{ config('idle_timeout.warning') }}"
    data-sync="{{ config('idle_timeout.sync_interval') }}"
    data-keep-alive-url="{{ route('session.keep-alive') }}"
    data-logout-url="{{ route('logout') }}"
    data-login-url="{{ route('login') }}"
    class="idle-backdrop-in hidden fixed inset-0 z-[500] items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 sm:p-6"
    role="alertdialog" aria-modal="true"
    aria-labelledby="idle-title" aria-describedby="idle-desc">

    <div class="idle-panel-in relative z-10 w-full max-w-sm bg-white rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/20 ring-1 ring-black/5">

        {{-- Garis aksen tipis di tepi atas --}}
        <div class="h-1 w-full bg-gradient-to-r from-amber-300 via-amber-500 to-amber-300"></div>

        <div class="px-6 sm:px-8 pt-8 pb-6 flex flex-col items-center text-center">

            {{-- Ikon --}}
            <div class="relative size-14 flex items-center justify-center">
                <span class="idle-halo absolute inset-0 rounded-full bg-amber-200" aria-hidden="true"></span>
                <span class="relative size-14 rounded-full bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center">
                    <i data-lucide="clock" class="size-6 text-amber-600"></i>
                </span>
            </div>

            {{-- Judul --}}
            <h3 id="idle-title" class="mt-5 text-lg sm:text-xl font-bold tracking-tight text-foreground">
                Sesi Anda akan berakhir
            </h3>
            <p id="idle-desc" class="mt-1.5 max-w-[17rem] text-sm leading-relaxed text-secondary">
                Tidak ada aktivitas terdeteksi. Perpanjang sesi untuk tetap masuk.
            </p>

            {{-- Hitung mundur --}}
            <div class="mt-6 w-full rounded-2xl bg-gray-50 ring-1 ring-gray-100 px-5 py-4">
                <p class="text-xs text-secondary">Logout otomatis dalam</p>
                <span data-idle-countdown
                    class="mt-1 block text-4xl sm:text-5xl font-bold tabular-nums tracking-tight text-foreground">00:00</span>

                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-gray-200/70">
                    <div data-idle-bar class="h-full w-full rounded-full bg-gradient-to-r from-amber-400 to-amber-500 transition-[width] duration-1000 ease-linear"></div>
                </div>
            </div>

            <p data-idle-error class="mt-3 hidden text-sm text-red-600" role="alert"></p>
        </div>

        {{-- Aksi: tombol utama di atas, aksi sekunder sebagai tautan teks --}}
        <div class="px-6 sm:px-8 pb-6 sm:pb-7 flex flex-col gap-2">
            <button type="button" data-idle-extend
                class="flex items-center justify-center gap-2 w-full px-5 py-3 bg-primary text-white hover:bg-primary/90 hover:shadow-lg active:scale-[0.98] shadow-md text-sm font-bold rounded-xl transition-all cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                <i data-lucide="check" class="size-4"></i>
                Perpanjang sesi
            </button>

            <button type="button" data-idle-logout
                class="flex items-center justify-center gap-1.5 w-full px-4 py-2.5 rounded-xl text-secondary hover:text-foreground hover:bg-gray-100 transition-colors cursor-pointer text-sm outline-none focus-visible:ring-2 focus-visible:ring-gray-300">
                <i data-lucide="log-out" class="size-3.5"></i>
                Logout sekarang
            </button>
        </div>
    </div>
</div>
@endauth