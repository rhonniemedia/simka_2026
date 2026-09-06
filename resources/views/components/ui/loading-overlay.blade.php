<div x-data="{
        showLoading: false,
        pendingRequests: 0,
        // Elemen yang MEMICU request boleh menandai DIRINYA SENDIRI dengan
        // atribut data-no-loader jika tidak ingin request-nya menampilkan
        // overlay (mis. form yang sudah punya loading state di tombolnya
        // sendiri, atau container yang auto-refresh via hx-trigger).
        //
        // PENTING: sengaja dicek pada elemen itu sendiri saja (bukan
        // elt.closest(...)). Kalau pakai closest(), menaruh data-no-loader
        // di sebuah container akan ikut membungkam SEMUA elemen di dalam
        // container itu yang men-trigger request-nya sendiri (mis. tombol
        // Edit/Tambah atau link pagination di dalam tabel yang container-nya
        // ditandai data-no-loader) - padahal aksi-aksi itu tetap harus
        // menampilkan overlay.
        isOptedOut(elt) {
            return !!(elt && elt.hasAttribute && elt.hasAttribute('data-no-loader'));
        },
        startLoading() {
            this.pendingRequests++;
            this.showLoading = true;
        },
        endLoading() {
            this.pendingRequests = Math.max(0, this.pendingRequests - 1);
            if (this.pendingRequests === 0) this.showLoading = false;
        }
    }"
    {{--
        Trigger untuk SEMUA request HTMX, apa pun target/container-nya
        (tab master, pagination, form modal, refresh tabel, dsb).
        Sengaja tidak lagi dicek berdasarkan id target tertentu, karena
        setiap container baru (mis. tabel pagination) butuh id baru
        didaftarkan manual - gampang lolos/tidak ke-cover.
        pendingRequests dipakai sebagai counter supaya kalau ada beberapa
        request HTMX yang tumpang tindih, overlay baru hilang setelah
        semuanya selesai.
    --}}
    @htmx:before-request.window="if (!isOptedOut($event.detail.elt)) startLoading()"
    @htmx:after-request.window="if (!isOptedOut($event.detail.elt)) endLoading()"

    {{--
        Trigger saat user klik link navigasi biasa (bukan HTMX/hash/blank/download).
        Ditaruh di 'click' (bukan hanya 'beforeunload') karena 'beforeunload' baru
        jalan tepat saat browser mulai unload halaman - browser sering tidak sempat
        repaint overlay-nya sebelum navigasi berikutnya jalan. Dengan trigger di klik,
        overlay sempat tampil dulu sebelum request navigasi benar-benar dikirim.
    --}}
    @click.window="
        const link = $event.target.closest('a[href]');
        if (
            link &&
            !link.hasAttribute('hx-get') &&
            !link.hasAttribute('hx-post') &&
            !link.hasAttribute('hx-boost') &&
            !link.hasAttribute('download') &&
            link.target !== '_blank' &&
            !link.getAttribute('href').startsWith('#') &&
            !link.href.startsWith('javascript:') &&
            !link.href.startsWith('mailto:') &&
            !link.href.startsWith('tel:')
        ) {
            showLoading = true;
        }
    "

    {{-- Trigger saat submit form biasa (bukan form yang di-handle HTMX) --}}
    @submit.window="
        const form = $event.target;
        if (!form.hasAttribute('hx-post') && !form.hasAttribute('hx-get') && !form.hasAttribute('hx-put')) {
            showLoading = true;
        }
    "

    {{-- Fallback: tetap pasang untuk kasus lain (mis. history.pushState manual, dsb) --}}
    @beforeunload.window="showLoading = true"

    {{-- Sembunyikan loader jika pengguna kembali menggunakan tombol 'Back' di browser (BFCache) --}}
    @pageshow.window="showLoading = false; pendingRequests = 0"

    {{-- Jaring pengaman: jika karena suatu sebab navigasi/request batal atau gagal tanpa event penutup, jangan sampai overlay nyangkut selamanya --}}
    x-init="$watch('showLoading', value => { if (value) setTimeout(() => { showLoading = false; pendingRequests = 0 }, 15000) })"

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