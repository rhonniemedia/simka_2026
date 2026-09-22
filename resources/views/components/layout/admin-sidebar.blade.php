<!-- ══ SIDEBAR ══ -->
<aside
    class="flex flex-col w-[280px] shrink-0 h-screen fixed inset-y-0 left-0 z-50 bg-white border-r border-border overflow-hidden transition-transform duration-300 ease-in-out"
    :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0 lg:shadow-none'">

    <!-- Logo -->
    <div class="flex items-center justify-between border-b border-border h-[90px] px-5 gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center bg-gradient-to-br from-red-500 to-purple-700 shadow-sm">
                <i data-lucide="file-archive" class="size-5 text-white"></i>
            </div>

            <div>
                <h1 class="font-bold text-base text-foreground leading-tight">SIMKA</h1>
                <p class="text-xs text-secondary">SMK Negeri 1 Rejang Lebong</p>
            </div>
        </div>

        <!-- Tombol Close (X) -->
        <button
            @click="sidebarOpen = false"
            class="lg:hidden size-11 flex shrink-0 bg-white rounded-xl p-[10px] items-center justify-center ring-1 ring-border hover:ring-primary transition-all duration-300 cursor-pointer">
            <i data-lucide="x" class="size-6 text-secondary"></i>
        </button>
    </div>

    <!-- Navigasi Utama -->
    <div class="flex flex-col p-5 pb-28 gap-2 overflow-y-auto flex-1 scrollbar-hide">

        <!-- Dashboard -->
        <div class="flex flex-col gap-1">
            <h3 class="font-bold text-sm text-foreground px-3 mb-2">
                Dashboard
            </h3>

            <a href="{{ route('dashboard') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('dashboard') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="layout-dashboard"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('dashboard') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Beranda
                        </span>
                    </div>
                </div>
            </a>
        </div>


        <!-- Master Data -->
        <div class="flex flex-col gap-1 mt-4">
            <h3 class="font-bold text-sm text-foreground px-3 mb-2">
                Master Data
            </h3>

            <!-- Data Utama -->
            <a href="{{ route('admin.master.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.master.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="database"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.master.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.master.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Data Utama
                        </span>
                    </div>
                </div>
            </a>

            <!-- Data Pegawai -->
            <a href="{{ route('admin.personnel.data.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.data.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="users"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.data.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.data.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Data Pegawai
                        </span>
                    </div>
                </div>
            </a>
        </div>


        <!-- Kepegawaian -->
        <div class="flex flex-col gap-1 mt-4">
            <h3 class="font-bold text-sm text-foreground px-3 mb-2">
                Kepegawaian
            </h3>

            <!-- Dokumen -->
            <a href="{{ route('admin.personnel.documents.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.documents.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="folder-open"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.documents.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.documents.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Dokumen
                        </span>
                    </div>
                </div>
            </a>

            <!-- Jabatan -->
            <a href="{{ route('admin.personnel.positions.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.positions.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="briefcase-business"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.positions.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.positions.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Jabatan
                        </span>
                    </div>
                </div>
            </a>

            <!-- Mutasi -->
            <a href="{{ route('admin.personnel.mutation.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.mutation.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="arrow-right-left"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.mutation.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.mutation.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Mutasi
                        </span>
                    </div>
                </div>
            </a>

            <!-- Berkala -->
            <a href="{{ route('admin.personnel.periodic-salary.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.periodic-salary.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="layers"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.periodic-salary.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.periodic-salary.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Berkala
                        </span>
                    </div>
                </div>
            </a>

            <!-- Kepangkatan -->
            <a href="{{ route('admin.personnel.promotions.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.promotions.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="user-cog"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.promotions.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.promotions.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Kepangkatan
                        </span>
                    </div>
                </div>
            </a>

            <!-- Pendidikan -->
            <a href="{{ route('admin.personnel.education.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.education.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="graduation-cap"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.education.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.education.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Pendidikan
                        </span>
                    </div>
                </div>
            </a>

            <!-- Keluarga -->
            <a href="{{ route('admin.personnel.family.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.family.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="users-round"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.family.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.family.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Keluarga
                        </span>
                    </div>
                </div>
            </a>

            <!-- Pensiun -->
            <a href="{{ route('admin.personnel.retirement.index') }}" class="group cursor-pointer">
                <div class="flex items-center justify-between rounded-xl p-3 transition-all duration-300 {{ request()->routeIs('admin.personnel.retirement.*') ? 'bg-primary/10 hover:bg-primary/20' : 'hover:bg-muted' }}">
                    <div class="flex items-center gap-3">
                        <i
                            data-lucide="user-minus"
                            class="size-5 transition-all duration-300 {{ request()->routeIs('admin.personnel.retirement.*') ? 'text-primary' : 'text-secondary group-hover:text-foreground' }}">
                        </i>

                        <span class="font-medium text-sm transition-all duration-300 {{ request()->routeIs('admin.personnel.retirement.*') ? 'text-primary font-semibold' : 'text-secondary group-hover:text-foreground' }}">
                            Pensiun
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pengguna -->
        <div class="flex flex-col gap-1 mt-4">
            <h3 class="font-bold text-sm text-foreground px-3 mb-2">
                Pengguna
            </h3>

            <div class="flex flex-col gap-1">

                <a href="#" class="group cursor-pointer">
                    <div class="flex items-center rounded-xl p-3 gap-3 transition-all duration-300 hover:bg-muted">
                        <i data-lucide="users-round" class="size-5 text-secondary transition-all duration-300 group-hover:text-foreground"></i>
                        <span class="text-sm font-medium text-secondary transition-all duration-300 group-hover:text-foreground">
                            Daftar Pengguna
                        </span>
                    </div>
                </a>

                <a href="#" class="group cursor-pointer">
                    <div class="flex items-center rounded-xl p-3 gap-3 transition-all duration-300 hover:bg-muted">
                        <i data-lucide="user-cog" class="size-5 text-secondary transition-all duration-300 group-hover:text-foreground"></i>
                        <span class="text-sm font-medium text-secondary transition-all duration-300 group-hover:text-foreground">
                            Profil Saya
                        </span>
                    </div>
                </a>

            </div>
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