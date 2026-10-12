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
            transform: translateY(-3rem);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .idle-backdrop-in {
        animation: idle-fade-in 300ms ease-out;
    }

    .idle-panel-in {
        animation: idle-slide-in 300ms ease-out;
    }

    @media (prefers-reduced-motion: reduce) {

        .idle-backdrop-in,
        .idle-panel-in {
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
    class="idle-backdrop-in hidden fixed inset-0 z-[500] items-center justify-center bg-black/50 p-4 sm:p-6"
    role="alertdialog" aria-modal="true"
    aria-labelledby="idle-title" aria-describedby="idle-desc">

    <div class="idle-panel-in relative z-10 w-full max-w-md bg-white rounded-2xl flex flex-col overflow-hidden shadow-2xl">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-gray-50/50">
            <div class="size-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="size-4 text-amber-600"></i>
            </div>
            <div>
                <h3 id="idle-title" class="font-bold text-foreground text-base sm:text-lg">Sesi Anda akan berakhir</h3>
                <p class="text-[11px] sm:text-xs text-secondary mt-0.5">Tidak ada aktivitas terdeteksi</p>
            </div>
        </div>

        {{-- Isi --}}
        <div class="p-4 sm:p-6">
            <p id="idle-desc" class="text-sm text-secondary">
                Anda akan logout otomatis dalam
                <span data-idle-countdown class="font-bold tabular-nums text-foreground">00:00</span>.
                Pilih <span class="font-semibold text-foreground">Perpanjang sesi</span> untuk tetap masuk.
            </p>

            <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div data-idle-bar class="h-full w-full rounded-full bg-amber-500 transition-[width] duration-1000 ease-linear"></div>
            </div>

            <p data-idle-error class="mt-3 hidden text-sm text-red-600"></p>
        </div>

        {{-- Footer --}}
        <div class="px-4 sm:px-6 py-4 border-t border-border bg-gray-50/50 flex flex-col-reverse sm:flex-row items-center sm:justify-end shrink-0 gap-3">
            <button type="button" data-idle-logout
                class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-xl border border-border bg-white text-secondary hover:bg-muted transition-colors cursor-pointer text-sm">
                <i data-lucide="log-out" class="size-3.5"></i>
                Logout sekarang
            </button>

            <button type="button" data-idle-extend
                class="flex items-center justify-center gap-1.5 w-full sm:w-auto px-5 py-2.5 bg-primary text-white hover:bg-primary/85 hover:shadow-lg active:scale-[0.98] shadow-md text-sm font-bold rounded-xl transition-all cursor-pointer outline-none focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed">
                <i data-lucide="check" class="size-4"></i>
                Perpanjang sesi
            </button>
        </div>
    </div>
</div>
@endauth