<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pintar - @yield('title')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Mencegah elemen Alpine berkedip sebelum dimuat */
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="font-sans bg-white min-h-screen overflow-x-hidden"
    x-data="{ sidebarOpen: false }"
    :class="sidebarOpen ? 'overflow-hidden' : ''">

    <!-- Overlay mobile -->
    <div class="fixed inset-0 bg-black/80 z-40 lg:hidden"
        x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" style="display:none"></div>

    <div class="flex h-screen max-h-screen flex-1 bg-muted overflow-hidden">

        <!-- ══ SIDEBAR ══ -->
        @include('components.layout.admin-sidebar')

        <!-- ══ MAIN ══ -->
        <main class="flex-1 lg:ml-[280px] flex flex-col bg-white min-h-screen overflow-x-hidden">

            <!-- Topbar -->
            @include('components.layout.admin-topbar')

            <!-- Content -->
            @yield('content')
            <!-- end content -->

        </main>
    </div>

    <!-- Toast -->
    <div id="toast" class="fixed bottom-6 right-6 z-[400] flex items-center gap-3 px-4 py-3 rounded-xl text-white text-sm font-semibold shadow-xl transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
        <i data-lucide="circle-check" class="size-4 shrink-0"></i>
        <span id="toastMsg">Berhasil</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            // Re-render lucide setiap kali HTMX selesai load konten
            document.body.addEventListener('htmx:afterSwap', () => {
                if (window.lucide) lucide.createIcons();
            });

            // Jembatan global: event 'showAlert' yang dikirim controller lewat header
            // HX-Trigger (mis. dari respons store/update/destroy) diteruskan ke
            // komponen SweetAlert global (window.ShowAlert) di components.ui.sweet-alert.
            // Berlaku untuk semua halaman/partial, tidak perlu didaftarkan ulang.
            document.body.addEventListener('showAlert', (e) => {
                const {
                    icon,
                    title,
                    text
                } = e.detail || {};
                window.ShowAlert({
                    type: icon || 'info',
                    title: title || '',
                    message: text || '',
                });
            });
        });
    </script>

    <!-- include('components.layout.modal') -->
    @include('components.ui.sweet-alert')

    @stack('scripts')
</body>

</html>