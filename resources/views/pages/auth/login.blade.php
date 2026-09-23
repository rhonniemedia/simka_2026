<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIMKA - Masuk</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Memastikan input tidak zoom otomatis di iOS */
        input[type="text"],
        input[type="password"] {
            font-size: 16px !important;
        }

        /* Sembunyikan scrollbar (jaring pengaman di layar sangat pendek),
           konten tetap bisa discroll tanpa scrollbar terlihat */
        .no-scrollbar {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Background grid pattern (desktop) — kotak lebih lebar + fade halus di tepi */
        .pintar-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 72px 72px;
            -webkit-mask-image: radial-gradient(ellipse 90% 80% at 50% 40%, #000 55%, transparent 100%);
            mask-image: radial-gradient(ellipse 90% 80% at 50% 40%, #000 55%, transparent 100%);
        }

        /* Partikel mengambang (desktop) */
        .pintar-particle {
            position: absolute;
            bottom: -12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.55);
            animation: pintarFloat linear infinite;
            pointer-events: none;
        }

        @keyframes pintarFloat {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 0.9;
            }

            90% {
                opacity: 0.9;
            }

            100% {
                transform: translateY(-110vh) translateX(18px);
                opacity: 0;
            }
        }
    </style>
</head>

<body class="font-sans min-h-screen bg-[#f8f9fb] sm:bg-gray-900" x-data="{ ready: false }" x-init="setTimeout(() => ready = true, 60)">

    {{-- ══════════════════════════════════════════════
         VERSI MOBILE — Sesuai Referensi Gambar (1 Halaman, Tanpa Scroll)
         ══════════════════════════════════════════════ --}}
    <div class="sm:hidden h-[100dvh] w-full relative overflow-hidden flex flex-col bg-[#eef1f7]" x-data="{ 
        loading: false, 
        showPassword: false,
        loginId: '{{ old('login_id') }}',
        password: '',
        loginIdError: false,
        passwordError: false,
        validate(e) {
            this.loginIdError = this.loginId.trim() === '';
            this.passwordError = this.password === '';
            
            if (this.loginIdError || this.passwordError) {
                e.preventDefault();
            } else {
                this.loading = true;
            }
        }
    }">

        <!-- ═══ AKSEN BACKGROUND ═══ -->
        <div class="absolute inset-0 pointer-events-none z-0 flex flex-col justify-end" aria-hidden="true">
            <!-- Kurva biru dongker di bawah -->
            <svg class="w-full h-[40vh]" viewBox="0 0 100 50" preserveAspectRatio="none">
                <path d="M0,25 Q50,45 100,5 L100,50 L0,50 Z" fill="#152c4f" />
            </svg>
        </div>

        <!-- ═══ CONTENT WRAPPER ═══ -->
        <div class="relative z-10 flex flex-col h-full px-5 pt-[3dvh] pb-[2dvh] overflow-y-auto no-scrollbar"
            x-show="ready" x-cloak
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0">

            <!-- ═══ HEADER ═══ -->
            <div class="shrink-0 flex flex-col items-center text-center">
                <!-- Ikon Topi Wisuda -->
                <div class="flex justify-center mb-[clamp(12px,1.5dvh,24px)]">
                    <div class="size-[clamp(56px,9dvh,88px)] rounded-full bg-white border-[3px] border-[#152c4f]/10 shadow-md shadow-[#152c4f]/15 flex items-center justify-center relative">
                        <div class="absolute inset-1 rounded-full bg-[#a3195b] shadow-inner flex items-center justify-center">
                            <i data-lucide="shield-user" class="size-[clamp(22px,3.2dvh,34px)] text-white"></i>
                        </div>
                    </div>
                </div>

                <h1 class="text-[#152c4f] text-[clamp(22px,3.4dvh,30px)] font-black tracking-[0.08em] uppercase">
                    SIMKA
                </h1>
                <div class="w-10 h-[3px] rounded-full bg-[#a3195b] mt-[clamp(4px,0.6dvh,10px)] mb-[clamp(6px,0.8dvh,12px)]"></div>
                <p class="text-slate-600 text-[clamp(12.5px,1.7dvh,15px)] font-medium mt-0 max-w-[230px] leading-snug">
                    Platform Informasi Kesiswaan Terintegrasi
                </p>
            </div>

            <!-- Spacer: mendorong kartu turun mendekati footer, menyusut duluan di layar pendek -->
            <div class="flex-1 min-h-[3dvh]"></div>

            <!-- ═══ KARTU LOGIN ═══ -->
            <div class="w-full bg-white rounded-[24px] border border-[#152c4f]/10 shadow-[0_18px_45px_-12px_rgba(21,44,79,0.35)] px-6 py-[clamp(18px,3dvh,32px)] shrink-0">
                <h2 class="text-[clamp(17px,2.2dvh,20px)] font-bold text-slate-900 leading-tight">Selamat Datang Kembali!</h2>
                <p class="text-[clamp(12.5px,1.6dvh,14px)] text-slate-400 mt-1 mb-[clamp(14px,2dvh,24px)]">Masuk ke akun anda untuk melanjutkan</p>

                <!-- Alert error umum -->
                @if ($errors->any())
                <div class="mb-[clamp(12px,1.6dvh,20px)] flex items-start gap-2.5 rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-red-700 text-xs">
                    <i data-lucide="alert-circle" class="size-4 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" @submit="validate($event)" class="space-y-[clamp(14px,1.8dvh,22px)]">
                    @csrf

                    <!-- Input Username -->
                    <div>
                        <label for="login_id-m" class="block text-[clamp(12.5px,1.6dvh,14px)] font-bold text-slate-800 mb-1.5">
                            Username / NIP / Email
                        </label>
                        <div class="relative">
                            <i data-lucide="mail" class="size-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input
                                x-model="loginId"
                                @input="loginIdError = false"
                                id="login_id-m"
                                type="text"
                                name="login_id"
                                autofocus
                                autocomplete="username"
                                placeholder="Masukkan identitas..."
                                class="w-full pl-10 pr-4 py-[clamp(10px,1.6dvh,14px)] rounded-xl border text-[13px] text-slate-900 placeholder:text-slate-400 placeholder:text-sm focus:outline-none focus:ring-1 focus:ring-[#152c4f] focus:border-[#152c4f] transition-all bg-white"
                                :class="loginIdError ? 'border-red-400' : 'border-slate-200'" />
                        </div>
                    </div>

                    <!-- Input Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password-m" class="text-[clamp(12.5px,1.6dvh,14px)] font-bold text-slate-800">
                                Password
                            </label>
                            <a href="{{ Route::has('password.request') ? route('password.request') : '#' }}" class="text-[clamp(12.5px,1.6dvh,14px)] font-bold text-[#a3195b] hover:underline">
                                Lupa password?
                            </a>
                        </div>
                        <div class="relative">
                            <i data-lucide="lock" class="size-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input
                                x-model="password"
                                @input="passwordError = false"
                                :type="showPassword ? 'text' : 'password'"
                                id="password-m"
                                name="password"
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                                class="w-full pl-10 pr-10 py-[clamp(10px,1.6dvh,14px)] rounded-xl border text-[13px] text-slate-900 placeholder:text-slate-400 placeholder:text-sm focus:outline-none focus:ring-1 focus:ring-[#152c4f] focus:border-[#152c4f] transition-all bg-white"
                                :class="passwordError ? 'border-red-400' : 'border-slate-200'" />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                tabindex="-1">
                                <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="size-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Checkbox Ingat Saya -->
                    <div class="pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer select-none text-[clamp(12.5px,1.6dvh,14px)] text-slate-600">
                            <input type="checkbox" name="remember" class="size-[14px] rounded border-slate-300 text-[#a3195b] focus:ring-[#a3195b]/30" />
                            Ingat saya di perangkat ini
                        </label>
                    </div>

                    <!-- Tombol Masuk -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#a3195b] text-white text-[15px] font-bold py-[clamp(13px,2dvh,18px)] mt-1 shadow-lg shadow-[#a3195b]/30 transition-all hover:bg-[#8a1149] disabled:opacity-70 disabled:cursor-not-allowed">
                        <i data-lucide="loader-2" class="size-4 animate-spin" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'Memproses...' : 'Masuk Sekarang'"></span>
                    </button>
                </form>

                <!-- Divider Atau Masuk Dengan -->
                <div class="flex items-center gap-3 my-[clamp(14px,1.8dvh,22px)]">
                    <div class="flex-1 h-px bg-slate-100"></div>
                    <span class="text-[11px] text-slate-400 font-medium">Atau masuk dengan</span>
                    <div class="flex-1 h-px bg-slate-100"></div>
                </div>

                <!-- Tombol Google -->
                <button type="button" class="w-full flex items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 py-[clamp(13px,2dvh,18px)] text-[14px] font-bold text-slate-700 hover:bg-slate-50 transition-all active:scale-[0.98]">
                    <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.56c2.08-1.92 3.28-4.74 3.28-8.1z" />
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.56-2.77c-.99.66-2.25 1.05-3.72 1.05-2.86 0-5.28-1.93-6.15-4.52H2.18v2.84A11 11 0 0 0 12 23z" />
                        <path fill="#FBBC05" d="M5.85 14.1A6.6 6.6 0 0 1 5.5 12c0-.73.13-1.44.35-2.1V7.07H2.18A11 11 0 0 0 1 12c0 1.77.43 3.45 1.18 4.93l3.67-2.83z" />
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A11 11 0 0 0 12 1 11 11 0 0 0 2.18 7.07l3.67 2.83C6.72 7.31 9.14 5.38 12 5.38z" />
                    </svg>
                    Masuk dengan Google
                </button>
            </div>

            <!-- Jarak aman antara kartu dan footer -->
            <div class="shrink-0 h-[clamp(14px,2dvh,20px)]"></div>

            <!-- ═══ FOOTER ═══ -->
            <div class="text-center shrink-0">
                <p class="text-[11px] text-slate-500">&copy; {{ date('Y') }} SIMKA. Seluruh hak cipta dilindungi.</p>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         VERSI DESKTOP — Tidak Ada Perubahan Sesuai Instruksi
         ══════════════════════════════════════════════ --}}
    <div class="hidden sm:block relative min-h-screen">

        <!-- Background: gradasi navy pekat + grid pattern + partikel mengambang -->
        <div class="fixed inset-0 overflow-hidden bg-gradient-to-br from-[#0b1830] via-[#16293f] to-[#1e3a5f]">
            <!-- Grid pattern -->
            <div class="absolute inset-0 pintar-grid"></div>

            <!-- Glow orbs (warna brand, besar & menyebar, tidak hanya di pojok) -->
            <div class="absolute rounded-full" style="width:900px;height:900px;top:-260px;left:-200px;background:radial-gradient(circle,rgba(190,18,60,0.15),transparent 68%);"></div>
            <div class="absolute rounded-full" style="width:800px;height:800px;bottom:-260px;right:-180px;background:radial-gradient(circle,rgba(126,34,206,0.13),transparent 68%);"></div>
            <div class="absolute rounded-full" style="width:700px;height:700px;top:50%;left:50%;transform:translate(-50%,-50%);background:radial-gradient(circle,rgba(30,58,95,0.18),transparent 70%);"></div>

            <!-- Partikel -->
            <div id="pintarParticles" class="absolute inset-0"></div>
        </div>

        <div class="relative z-10 min-h-screen flex flex-col items-center justify-center p-5">
            <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8" x-data="{ 
                loading: false, 
                showPassword: false,
                loginId: '{{ old('login_id') }}',
                password: '',
                loginIdError: false,
                passwordError: false,
                validate(e) {
                    this.loginIdError = this.loginId.trim() === '';
                    this.passwordError = this.password === '';
                    
                    if (this.loginIdError || this.passwordError) {
                        e.preventDefault();
                    } else {
                        this.loading = true;
                    }
                }
            }">

                <!-- Logo -->
                <div class="flex justify-center mb-4">
                    <div class="size-20 rounded-full bg-white border-4 border-gray-100 shadow-sm flex items-center justify-center">
                        <div class="size-14 rounded-full bg-gradient-to-br from-[#a3195b] to-[#be123c] flex items-center justify-center">
                            <i data-lucide="shield-user" class="size-7 text-white"></i>
                        </div>
                    </div>
                </div>

                <h2 class="text-center text-xl font-bold tracking-tight bg-gradient-to-br from-[#a3195b] to-[#be123c] bg-clip-text text-transparent">SIMKA</h2>
                <p class="text-center text-xs text-gray-400 mb-6">Platform Informasi Kesiswaan Terintegrasi</p>

                <!-- Alert error umum -->
                @if ($errors->any())
                <div class="mb-5 flex items-start gap-2.5 rounded-lg bg-red-50 border border-red-100 px-3.5 py-2.5 text-red-700 text-xs">
                    <i data-lucide="alert-circle" class="size-4 shrink-0 mt-0.5"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                @if (session('status'))
                <div class="mb-5 flex items-start gap-2.5 rounded-lg bg-green-50 border border-green-100 px-3.5 py-2.5 text-green-700 text-xs">
                    <i data-lucide="check-circle-2" class="size-4 shrink-0 mt-0.5"></i>
                    <span>{{ session('status') }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" @submit="validate($event)" class="space-y-1">
                    @csrf

                    <!-- Login ID -->
                    <div>
                        <label for="login_id" class="block text-xs font-medium text-gray-600 mb-1.5">Username / NIP / Email</label>
                        <div class="relative">
                            <i data-lucide="user" class="size-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input
                                x-model="loginId"
                                @input="loginIdError = false"
                                id="login_id"
                                type="text"
                                name="login_id"
                                autofocus
                                autocomplete="username"
                                placeholder="Masukkan identitas..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border text-sm text-gray-900 placeholder:text-gray-400 placeholder:text-sm focus:outline-none focus:ring-2 focus:ring-[#1e3a5f]/30 focus:border-[#1e3a5f] transition"
                                :class="loginIdError ? 'border-red-300' : 'border-gray-200'" />
                        </div>
                        <p x-show="loginIdError" x-cloak class="text-xs text-red-600 pt-1">Username/email/nip harus diisi!</p>
                    </div>

                    <!-- Password -->
                    <div class="mt-5">
                        <label for="password" class="block text-xs font-medium text-gray-600 mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="size-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input
                                x-model="password"
                                @input="passwordError = false"
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full pl-10 pr-11 py-2.5 rounded-xl border text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1e3a5f]/30 focus:border-[#1e3a5f] transition"
                                :class="passwordError ? 'border-red-300' : 'border-gray-200'" />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" tabindex="-1">
                                <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="size-4"></i>
                            </button>
                        </div>
                        <p x-show="passwordError" x-cloak class="text-xs text-red-600 pt-1">Password harus diisi!</p>
                    </div>

                    <!-- Ingat saya + lupa password -->
                    <div class="flex items-center justify-between pt-4 pb-5 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer select-none text-gray-500">
                            <input type="checkbox" name="remember" class="size-3.5 rounded border-gray-300 text-[#1e3a5f] focus:ring-[#1e3a5f]/30" />
                            Biarkan tetap masuk
                        </label>
                        <a href="{{ Route::has('password.request') ? route('password.request') : '#' }}" class="font-medium text-[#000000] hover:underline">
                            Lupa Password?
                        </a>
                    </div>

                    <!-- Tombol login -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 rounded-lg bg-gradient-to-br from-[#a3195b] to-[#be123c] text-white text-sm font-semibold py-2.5 shadow-md shadow-[#be123c]/40 transition-all duration-300 ease-out hover:shadow-lg hover:shadow-[#be123c]/50 hover:brightness-110 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.99] disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:brightness-100">
                        <i data-lucide="loader-2" class="size-4 animate-spin" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'Memproses...' : 'Login'"></span>
                    </button>
                </form>

                <!-- Footer kartu -->
                <p class="text-center text-xs text-gray-400 mt-6">
                    &copy; {{ date('Y') }} SIMKA. Seluruh hak cipta dilindungi.
                </p>
            </div>

            <!-- Footer halaman -->
            <p class="text-center text-xs text-white/60 mt-6">
                Butuh bantuan masuk?
                <a href="mailto:admin@simka.sch.id" class="text-white/90 font-medium hover:underline">Hubungi administrator</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
            document.addEventListener('alpine:updated', () => {
                if (window.lucide) lucide.createIcons();
            });

            // Partikel mengambang untuk background desktop
            const container = document.getElementById('pintarParticles');
            if (container) {
                for (let i = 0; i < 20; i++) {
                    const p = document.createElement('div');
                    p.className = 'pintar-particle';
                    const size = 2 + Math.random() * 3;
                    p.style.width = size + 'px';
                    p.style.height = size + 'px';
                    p.style.left = Math.random() * 100 + '%';
                    p.style.animationDuration = (8 + Math.random() * 15) + 's';
                    p.style.animationDelay = Math.random() * 10 + 's';
                    container.appendChild(p);
                }
            }
        });
    </script>

</body>

</html>