<div x-data="{ showLoading: false }"
    {{-- Trigger saat memuat data modal via HTMX --}}
    @htmx:before-request.window="if($event.detail.target.id === 'modal-container') showLoading = true"
    @htmx:after-request.window="if($event.detail.target.id === 'modal-container') showLoading = false"

    {{-- Trigger saat berpindah halaman (navigasi route normal) --}}
    @beforeunload.window="showLoading = true"

    {{-- Sembunyikan loader jika pengguna kembali menggunakan tombol 'Back' di browser (BFCache) --}}
    @pageshow.window="showLoading = false"

    x-show="showLoading"
    x-cloak
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 transition-opacity"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    {{-- Hanya Custom Loader --}}
    <span class="custom-loader"></span>

    {{-- CSS Khusus --}}
    <style>
        .custom-loader {
            --color-1: #fff;
            --color-2: #ff3d00;
            --size: 1px;

            width: calc(48 * var(--size));
            height: calc(48 * var(--size));
            border-radius: 50%;
            display: inline-block;
            border-top: calc(4 * var(--size)) solid var(--color-1);
            border-right: calc(4 * var(--size)) solid transparent;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
            position: relative;
        }

        .custom-loader::after {
            content: '';
            box-sizing: border-box;
            position: absolute;
            left: 0;
            top: 0;
            width: calc(48 * var(--size));
            height: calc(48 * var(--size));
            border-radius: 50%;
            border-bottom: calc(4 * var(--size)) solid var(--color-2);
            border-left: calc(4 * var(--size)) solid transparent;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</div>