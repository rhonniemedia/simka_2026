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
                        <div class="absolute inset-1 rounded-full bg-[#e31837] shadow-inner flex items-center justify-center">
                            <i data-lucide="file-archive" class="size-[clamp(22px,3.2dvh,34px)] text-white"></i>
                        </div>
                    </div>
                </div>

                <h1 class="text-[#152c4f] text-[clamp(22px,3.4dvh,30px)] font-black tracking-[0.08em] uppercase">
                    SIMKA
                </h1>
                <div class="w-10 h-[3px] rounded-full bg-[#e31837] mt-[clamp(4px,0.6dvh,10px)] mb-[clamp(6px,0.8dvh,12px)]"></div>
                <p class="text-slate-600 text-[clamp(12.5px,1.7dvh,15px)] font-medium mt-0 max-w-[230px] leading-snug">
                    Sistem Informasi Kepegawaian dan Arsip
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
                                class="w-full pl-10 pr-4 py-[clamp(10px,1.6dvh,14px)] rounded-xl border text-[13px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-[#152c4f] focus:border-[#152c4f] transition-all bg-white"
                                :class="loginIdError ? 'border-red-400' : 'border-slate-200'" />
                        </div>
                    </div>

                    <!-- Input Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password-m" class="text-[clamp(12.5px,1.6dvh,14px)] font-bold text-slate-800">
                                Password
                            </label>
                            <a href="{{ Route::has('password.request') ? route('password.request') : '#' }}" class="text-[clamp(12.5px,1.6dvh,14px)] font-bold text-[#e31837] hover:underline">
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
                                class="w-full pl-10 pr-10 py-[clamp(10px,1.6dvh,14px)] rounded-xl border text-[13px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-[#152c4f] focus:border-[#152c4f] transition-all bg-white"
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
                            <input type="checkbox" name="remember" class="size-[14px] rounded border-slate-300 text-[#e31837] focus:ring-[#e31837]/30" />
                            Ingat saya di perangkat ini
                        </label>
                    </div>

                    <!-- Tombol Masuk -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#e31837] text-white text-[15px] font-bold py-[clamp(13px,2dvh,18px)] mt-1 shadow-lg shadow-[#e31837]/30 transition-all hover:bg-[#c71530] disabled:opacity-70 disabled:cursor-not-allowed">
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

        <!-- Background: ilustrasi perpustakaan bergaya karikatur, sedikit blur -->
        <div class="fixed inset-0 overflow-hidden bg-[#f4efe4]">
            <svg class="absolute inset-0 w-full h-full scale-110 blur-sm" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#f4efe4" />
                        <stop offset="100%" stop-color="#e7ddc7" />
                    </linearGradient>
                </defs>
                <rect width="1600" height="900" fill="url(#sky)" />

                <!-- Lantai -->
                <rect x="0" y="740" width="1600" height="160" fill="#d8c9a3" />
                <rect x="0" y="740" width="1600" height="10" fill="#c9b98d" />

                <!-- Rak buku kiri -->
                <g>
                    <rect x="40" y="140" width="420" height="620" rx="14" fill="#5c3d2e" />
                    <rect x="60" y="160" width="380" height="150" rx="6" fill="#7a5340" />
                    <rect x="60" y="330" width="380" height="150" rx="6" fill="#7a5340" />
                    <rect x="60" y="500" width="380" height="150" rx="6" fill="#7a5340" />
                    <!-- buku-buku warna-warni -->
                    <g>
                        <rect x="72" y="175" width="26" height="120" fill="#8f5a3c" />
                        <rect x="100" y="180" width="22" height="115" fill="#6b8f71" />
                        <rect x="124" y="172" width="24" height="123" fill="#c9a227" />
                        <rect x="150" y="182" width="22" height="113" fill="#7d8fae" />
                        <rect x="174" y="178" width="26" height="117" fill="#b5651d" />
                        <rect x="202" y="184" width="20" height="111" fill="#5c7d8a" />
                        <rect x="224" y="176" width="24" height="119" fill="#a8577e" />
                        <rect x="250" y="180" width="22" height="115" fill="#6b8f71" />
                        <rect x="274" y="174" width="26" height="121" fill="#1e3a5f" />
                        <rect x="302" y="182" width="20" height="113" fill="#c9a227" />
                        <rect x="324" y="177" width="24" height="118" fill="#7a5340" />
                        <rect x="350" y="181" width="22" height="114" fill="#8f5a3c" />
                        <rect x="374" y="175" width="26" height="120" fill="#5c7d8a" />
                        <rect x="402" y="183" width="20" height="112" fill="#b5651d" />
                    </g>
                    <g>
                        <rect x="72" y="345" width="24" height="120" fill="#1e3a5f" />
                        <rect x="98" y="350" width="22" height="115" fill="#c9a227" />
                        <rect x="122" y="342" width="26" height="123" fill="#7d8fae" />
                        <rect x="150" y="352" width="20" height="113" fill="#a8577e" />
                        <rect x="172" y="348" width="24" height="117" fill="#6b8f71" />
                        <rect x="198" y="354" width="22" height="111" fill="#8f5a3c" />
                        <rect x="222" y="346" width="26" height="119" fill="#5c7d8a" />
                        <rect x="250" y="350" width="20" height="115" fill="#b5651d" />
                        <rect x="272" y="344" width="24" height="121" fill="#c9a227" />
                        <rect x="298" y="352" width="22" height="113" fill="#1e3a5f" />
                        <rect x="322" y="347" width="26" height="118" fill="#6b8f71" />
                        <rect x="350" y="351" width="20" height="114" fill="#7a5340" />
                        <rect x="372" y="345" width="26" height="120" fill="#a8577e" />
                        <rect x="400" y="353" width="20" height="112" fill="#5c7d8a" />
                    </g>
                    <g>
                        <rect x="72" y="515" width="26" height="120" fill="#7d8fae" />
                        <rect x="100" y="520" width="22" height="115" fill="#8f5a3c" />
                        <rect x="124" y="512" width="24" height="123" fill="#c9a227" />
                        <rect x="150" y="522" width="22" height="113" fill="#6b8f71" />
                        <rect x="174" y="518" width="26" height="117" fill="#1e3a5f" />
                        <rect x="202" y="524" width="20" height="111" fill="#a8577e" />
                        <rect x="224" y="516" width="24" height="119" fill="#5c7d8a" />
                        <rect x="250" y="520" width="22" height="115" fill="#b5651d" />
                        <rect x="274" y="514" width="26" height="121" fill="#c9a227" />
                        <rect x="302" y="522" width="20" height="113" fill="#7a5340" />
                        <rect x="324" y="517" width="24" height="118" fill="#6b8f71" />
                        <rect x="350" y="521" width="22" height="114" fill="#1e3a5f" />
                        <rect x="374" y="515" width="26" height="120" fill="#8f5a3c" />
                        <rect x="402" y="523" width="20" height="112" fill="#7d8fae" />
                    </g>
                </g>

                <!-- Rak buku kanan -->
                <g>
                    <rect x="1140" y="180" width="420" height="580" rx="14" fill="#5c3d2e" />
                    <rect x="1160" y="200" width="380" height="150" rx="6" fill="#7a5340" />
                    <rect x="1160" y="370" width="380" height="150" rx="6" fill="#7a5340" />
                    <rect x="1160" y="540" width="380" height="100" rx="6" fill="#7a5340" />
                    <g>
                        <rect x="1172" y="215" width="24" height="120" fill="#6b8f71" />
                        <rect x="1198" y="220" width="22" height="115" fill="#c9a227" />
                        <rect x="1222" y="212" width="26" height="123" fill="#a8577e" />
                        <rect x="1250" y="222" width="20" height="113" fill="#5c7d8a" />
                        <rect x="1272" y="218" width="24" height="117" fill="#1e3a5f" />
                        <rect x="1298" y="224" width="22" height="111" fill="#8f5a3c" />
                        <rect x="1322" y="216" width="26" height="119" fill="#7d8fae" />
                        <rect x="1350" y="220" width="20" height="115" fill="#c9a227" />
                        <rect x="1372" y="214" width="24" height="121" fill="#b5651d" />
                        <rect x="1398" y="222" width="22" height="113" fill="#6b8f71" />
                        <rect x="1422" y="217" width="26" height="118" fill="#1e3a5f" />
                        <rect x="1450" y="221" width="20" height="114" fill="#7a5340" />
                        <rect x="1474" y="215" width="26" height="120" fill="#5c7d8a" />
                        <rect x="1502" y="223" width="20" height="112" fill="#a8577e" />
                    </g>
                    <g>
                        <rect x="1172" y="385" width="24" height="120" fill="#c9a227" />
                        <rect x="1198" y="390" width="22" height="115" fill="#1e3a5f" />
                        <rect x="1222" y="382" width="26" height="123" fill="#6b8f71" />
                        <rect x="1250" y="392" width="20" height="113" fill="#8f5a3c" />
                        <rect x="1272" y="388" width="24" height="117" fill="#a8577e" />
                        <rect x="1298" y="394" width="22" height="111" fill="#5c7d8a" />
                        <rect x="1322" y="386" width="26" height="119" fill="#b5651d" />
                        <rect x="1350" y="390" width="20" height="115" fill="#7d8fae" />
                        <rect x="1372" y="384" width="24" height="121" fill="#1e3a5f" />
                        <rect x="1398" y="392" width="22" height="113" fill="#c9a227" />
                        <rect x="1422" y="387" width="26" height="118" fill="#7a5340" />
                        <rect x="1450" y="391" width="20" height="114" fill="#6b8f71" />
                        <rect x="1474" y="385" width="26" height="120" fill="#8f5a3c" />
                        <rect x="1502" y="393" width="20" height="112" fill="#5c7d8a" />
                    </g>
                </g>

                <!-- Jendela lengkung di tengah belakang -->
                <g opacity="0.9">
                    <path d="M700 620 L700 320 Q700 210 800 210 Q900 210 900 320 L900 620 Z" fill="#fbf6ea" stroke="#c9a227" stroke-width="6" />
                    <line x1="800" y1="210" x2="800" y2="620" stroke="#c9a227" stroke-width="4" />
                    <line x1="700" y1="420" x2="900" y2="420" stroke="#c9a227" stroke-width="4" />
                </g>

                <!-- Meja baca -->
                <rect x="620" y="640" width="360" height="26" rx="8" fill="#7a5340" />
                <rect x="640" y="666" width="18" height="90" fill="#5c3d2e" />
                <rect x="942" y="666" width="18" height="90" fill="#5c3d2e" />

                <!-- Tumpukan buku dengan topi wisuda di atas meja -->
                <g>
                    <rect x="735" y="600" width="150" height="24" rx="4" fill="#1e3a5f" transform="rotate(-2 735 600)" />
                    <rect x="742" y="578" width="140" height="24" rx="4" fill="#b5651d" transform="rotate(1.5 742 578)" />
                    <rect x="738" y="556" width="145" height="24" rx="4" fill="#6b8f71" transform="rotate(-1 738 556)" />
                    <!-- Buku terbuka -->
                    <path d="M745 552 Q800 536 855 552 L855 540 Q800 524 745 540 Z" fill="#fbf6ea" stroke="#c9a227" stroke-width="2" />
                    <!-- Topi wisuda -->
                    <g transform="translate(800 520)">
                        <ellipse cx="0" cy="6" rx="30" ry="10" fill="#1e3a5f" />
                        <polygon points="-46,0 46,0 0,-24" fill="#16293f" />
                        <circle cx="0" cy="-24" r="5" fill="#c9a227" />
                        <line x1="0" y1="-19" x2="24" y2="8" stroke="#c9a227" stroke-width="3" />
                        <circle cx="24" cy="10" r="5" fill="#c9a227" />
                    </g>
                </g>

                <!-- Burung hantu kecil bertengger, maskot edukasi -->
                <g transform="translate(1020 560)">
                    <ellipse cx="0" cy="20" rx="38" ry="46" fill="#7d8fae" />
                    <circle cx="-14" cy="-6" r="16" fill="#fbf6ea" />
                    <circle cx="14" cy="-6" r="16" fill="#fbf6ea" />
                    <circle cx="-14" cy="-6" r="7" fill="#1e3a5f" />
                    <circle cx="14" cy="-6" r="7" fill="#1e3a5f" />
                    <polygon points="-6,6 6,6 0,16" fill="#c9a227" />
                    <polygon points="-10,-26 -2,-26 -6,-40" fill="#7d8fae" />
                    <polygon points="10,-26 2,-26 6,-40" fill="#7d8fae" />
                </g>

                <!-- Tanaman pot -->
                <g transform="translate(560 660)">
                    <path d="M-16 40 L16 40 L10 0 L-10 0 Z" fill="#b5651d" />
                    <ellipse cx="0" cy="-20" rx="30" ry="34" fill="#6b8f71" />
                    <ellipse cx="-18" cy="-4" rx="16" ry="22" fill="#5a7a60" />
                    <ellipse cx="18" cy="-4" rx="16" ry="22" fill="#5a7a60" />
                </g>

                <!-- Bola lampu gantung -->
                <circle cx="500" cy="230" r="20" fill="#c9a227" opacity="0.85" />
                <line x1="500" y1="90" x2="500" y2="212" stroke="#8f5a3c" stroke-width="4" />
            </svg>
        </div>
        <div class="fixed inset-0 bg-gradient-to-b from-[#1e3a5f]/75 via-[#1e3a5f]/68 to-[#16293f]/80"></div>

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
                        <div class="size-14 rounded-full bg-gradient-to-br from-[#ff1443] to-[#c70d33] flex items-center justify-center">
                            <i data-lucide="file-archive" class="size-7 text-white"></i>
                        </div>
                    </div>
                </div>

                <h2 class="text-center text-xl font-bold tracking-tight bg-gradient-to-br from-[#ff1443] to-[#c70d33] bg-clip-text text-transparent">SIMKA</h2>
                <p class="text-center text-xs text-gray-400 mb-6">Sistem Informasi Kepegawaian dan Arsip</p>

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
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1e3a5f]/30 focus:border-[#1e3a5f] transition"
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
                        <a href="{{ Route::has('password.request') ? route('password.request') : '#' }}" class="font-medium text-[#ff1443] hover:underline">
                            Lupa Password?
                        </a>
                    </div>

                    <!-- Tombol login -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 rounded-lg bg-gradient-to-br from-[#ff1443] via-[#f0103d] to-[#c70d33] text-white text-sm font-semibold py-2.5 shadow-md shadow-[#c70d33]/40 transition-all duration-300 ease-out hover:shadow-lg hover:shadow-[#c70d33]/50 hover:brightness-110 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.99] disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:brightness-100">
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
        });
    </script>

</body>

</html>