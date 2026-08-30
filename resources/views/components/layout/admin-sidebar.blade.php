<!-- ══ SIDEBAR ══ -->
<aside
    class="flex flex-col w-[280px] shrink-0 h-screen fixed inset-y-0 left-0 z-50 bg-white border-r border-border overflow-hidden transition-transform duration-300 ease-in-out"
    :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0 lg:shadow-none'">

    <!-- Logo -->
    <div class="flex items-center justify-between border-b border-border h-[90px] px-5 gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center bg-gradient-to-br from-red-500 to-purple-700 shadow-sm">
                <i data-lucide="graduation-cap" class="size-5 text-white"></i>
            </div>
            <div>
                <h1 class="font-bold text-base text-foreground leading-tight">SIMKA</h1>
                <p class="text-xs text-secondary">SMK Negeri 1 Rejang Lebong</p>
            </div>
        </div>

        <!-- Tombol Close (X) -->
        <button @click="sidebarOpen = false" class="lg:hidden size-11 flex shrink-0 bg-white rounded-xl p-[10px] items-center justify-center ring-1 ring-border hover:ring-primary transition-all duration-300 cursor-pointer">
            <i data-lucide="x" class="size-6 text-secondary"></i>
        </button>
    </div>

    <!-- Navigasi Utama -->
    <div class="flex flex-col p-5 pb-28 gap-2 overflow-y-auto flex-1 scrollbar-hide">

        <!-- Menu Utama (Tanpa Kategori) -->
        <div class="flex flex-col gap-1">
            <a href="#" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="layout-dashboard" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Dashboard
                        </span>
                    </div>
                </div>
            </a>

            <a href="#" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="copy" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Data Master
                        </span>
                    </div>
                    <i data-lucide="plus" class="size-4 text-secondary/50 group-hover:text-foreground transition-all duration-300"></i>
                </div>
            </a>

            <a href="{{ route('admin.personnel.data.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="file-text" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Data Pegawai
                        </span>
                    </div>
                </div>
            </a>

            <a href="#" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="book-user" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Mutasi Pegawai
                        </span>
                    </div>
                    <i data-lucide="plus" class="size-4 text-secondary/50 group-hover:text-foreground transition-all duration-300"></i>
                </div>
            </a>
        </div>

        <!-- Berkas Pegawai -->
        <div class="flex flex-col gap-1 mt-4">
            <h3 class="font-bold text-sm text-foreground px-3 mb-2">Berkas Pegawai</h3>

            <a href="#" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="phone-call" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Pendidik
                        </span>
                    </div>
                    <i data-lucide="plus" class="size-4 text-secondary/50 group-hover:text-foreground transition-all duration-300"></i>
                </div>
            </a>

            <a href="#" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="users" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Kependidikan
                        </span>
                    </div>
                    <i data-lucide="plus" class="size-4 text-secondary/50 group-hover:text-foreground transition-all duration-300"></i>
                </div>
            </a>

            <a href="#" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="file-check" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Berkas Terpadu
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Kepegawaian -->
        <div class="flex flex-col gap-1 mt-4">
            <h3 class="font-bold text-sm text-foreground px-3 mb-2">Kepegawaian</h3>

            <a href="{{ route('admin.personnel.periodic-salary.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="layers" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Berkala
                        </span>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.personnel.promotions.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="user-cog" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Kepangkatan
                        </span>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.personnel.education.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="graduation-cap" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Pendidikan
                        </span>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.personnel.family.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="users-round" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Keluarga
                        </span>
                    </div>
                </div>
            </a>

            <a href="#" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 hover:bg-muted">
                    <div class="flex items-center gap-3">
                        <i data-lucide="user-minus" class="size-5 text-secondary group-hover:text-foreground transition-all duration-300"></i>
                        <span class="font-medium text-sm text-secondary group-hover:text-foreground transition-all duration-300">
                            Pensiun
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="absolute bottom-0 left-0 w-[280px]">
        <div class="flex items-center justify-between border-t bg-white border-border p-5 gap-3">
            <div class="min-w-0">
                <p class="font-semibold text-foreground text-sm">Admin</p>
                <p class="text-xs text-secondary mt-0.5">Pintar 2026</p>
            </div>

            <div class="size-11 bg-primary/10 rounded-xl flex items-center justify-center flex-shrink-0 cursor-pointer hover:bg-primary/20 transition-all">
                <i data-lucide="circle-power" class="size-6 text-primary"></i>
            </div>
        </div>
    </div>

</aside>