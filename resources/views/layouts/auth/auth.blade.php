<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title') - SPMB SMK Negeri 1</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}" />

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.0"></script>
    <!-- Alpine.js (harus SEBELUM scripts lain yang pakai Alpine) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- x-cloak: sembunyikan elemen Alpine sebelum init -->
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="auth-bg">

    <!-- Loader -->
    <div id="loader">
        <div class="logo-badge"><i class="fa-solid fa-graduation-cap text-white text-xl"></i></div>
        <p style="margin-top:14px; font-weight:700; font-size:0.9rem; color:var(--foreground);">SPMB - SMK Negeri 1 Rejang Lebong</p>
        <div class="loader-bar">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Grid pattern -->
    <div class="grid-pattern fixed inset-0 opacity-40 pointer-events-none"></div>

    <!-- Glow Orbs -->
    <div class="glow-orb" style="width:500px;height:500px;top:-150px;left:-100px;background:rgba(255,20,67,0.05);"></div>
    <div class="glow-orb" style="width:400px;height:400px;bottom:-100px;right:-80px;background:rgba(245,158,11,0.05);"></div>

    <!-- Particles -->
    <div id="particles" class="fixed inset-0 pointer-events-none overflow-hidden" style="z-index:0;"></div>

    <!-- Layout -->
    <div class="relative z-10 flex min-h-screen w-full items-stretch">

        <!-- Left Panel — dekoratif, tersembunyi di mobile -->
        <div class="left-panel hidden lg:flex lg:w-[45%] xl:w-[42%] flex-col justify-between p-12 relative overflow-hidden">
            <!-- Orbs dekoratif -->
            <div style="position:absolute;width:300px;height:300px;top:-60px;left:-60px;background:radial-gradient(circle,rgba(255,20,67,0.15),transparent);border-radius:50%;"></div>
            <div style="position:absolute;width:250px;height:250px;bottom:80px;right:-80px;background:radial-gradient(circle,rgba(245,158,11,0.12),transparent);border-radius:50%;"></div>
            <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);background-size:50px 50px;"></div>

            <!-- Content -->
            @if (request()->routeIs('login'))
            @include('components.layout.left-login')
            @elseif (request()->routeIs('register'))
            @include('components.layout.left-register')
            @endif

            <!-- Bottom -->
            <div class="relative z-10">
                <p style="font-size:0.75rem;color:rgba(255,255,255,0.25);">© 2026 SMK Negeri 1 Rejang Lebong. Hak cipta dilindungi.</p>
            </div>
        </div>

        <!-- Right Panel — Form -->

        <!-- Content -->
        <div class="w-full lg:w-[55%] xl:w-[58%] lg:ml-[45%] xl:ml-[42%] flex flex-col overflow-x-hidden">
            @yield('content')
        </div>

    </div>

    <script>
        // Loader
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 600);
            }, 1200);
        });

        // Particles
        (function() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 18; i++) {
                const p = document.createElement('div');
                p.classList.add('particle');
                p.style.left = Math.random() * 100 + 'vw';
                p.style.animationDuration = (8 + Math.random() * 15) + 's';
                p.style.animationDelay = Math.random() * 10 + 's';
                p.style.width = p.style.height = (2 + Math.random() * 3) + 'px';
                container.appendChild(p);
            }
        })();

        // Toggle password
        function togglePwd(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }

        // Handle login
        function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            const text = document.getElementById('btnLoginText');
            const spinner = document.getElementById('btnLoginSpinner');
            const alert = document.getElementById('alertError');

            // Show loading
            text.style.display = 'none';
            spinner.style.display = 'inline';
            btn.disabled = true;

            // Simulasi request
            setTimeout(() => {
                text.style.display = 'inline';
                spinner.style.display = 'none';
                btn.disabled = false;
                // Simulasi error (ganti dengan logika nyata)
                // alert.style.display = 'flex';
                // Simulasi sukses → redirect
                window.location.href = '#dashboard';
            }, 1800);
        }

        // Login Google
        function loginGoogle() {
            window.location.href = "{{ route('google.login') }}";
        }

        // Pulse animation
        const style = document.createElement('style');
        style.textContent = `@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.3)} }`;
        document.head.appendChild(style);
    </script>
    @stack('scripts')
</body>

</html>