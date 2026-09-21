<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - PINTAR</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 h-screen flex items-center justify-center p-5">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl p-8 text-center border border-gray-100">

        <!-- Ikon Peringatan -->
        <div class="size-20 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-5">
            <div class="size-14 rounded-full bg-red-100 flex items-center justify-center text-red-500">
                <i data-lucide="shield-alert" class="size-7"></i>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-800 mb-2">Akses Ditolak</h2>
        <p class="text-sm text-gray-500 mb-8">
            Anda tidak memiliki akses ke aplikasi ini. Halaman ini hanya diperuntukkan bagi Administrator.
        </p>

        <!-- Tombol Kembali -->
        <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 rounded-xl bg-gray-900 text-white text-sm font-semibold py-3 shadow-md hover:bg-gray-800 transition-colors">
            <i data-lucide="arrow-left" class="size-4"></i>
            Kembali ke Halaman Login
        </a>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>

</html>