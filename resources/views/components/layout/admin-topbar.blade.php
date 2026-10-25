@php
$authUser = auth()->user();
$authStaff = $authUser?->staff;
$authName = $authStaff->name ?? $authUser?->username ?? 'Pengguna';
$authInitials = collect(preg_split('/\s+/', trim($authName)))->filter()->take(2)
->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
$authPhoto = $authStaff?->photo ? asset('storage/' . $authStaff->photo) : null;
$authRole = $authUser?->roles()->wherePivot('app_id', config('app.core_id'))->value('user_roles.name');
@endphp
<div class="flex items-center justify-between w-full h-[90px] shrink-0 border-b border-border bg-white px-5 md:px-8 sticky top-0 z-50">

    <button
        @click="sidebarOpen = !sidebarOpen"
        class="lg:hidden size-11 flex items-center justify-center rounded-xl ring-1 ring-border hover:ring-primary transition-all duration-300 cursor-pointer"
        :aria-label="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'">

        <i data-lucide="menu" x-show="!sidebarOpen" class="size-6 text-foreground"></i>
        <i data-lucide="x" x-show="sidebarOpen" x-cloak class="size-6 text-foreground"></i>
    </button>

    {{-- Brand --}}
    <div class="min-w-0 flex-1 ml-3 lg:ml-0">
        <h2 class="text-lg md:text-2xl font-bold text-foreground leading-tight">
            Dashboard
        </h2>

        <p class="hidden md:block text-xs text-secondary leading-tight truncate">
            SIMKA | Sistem Informasi Kepegawaian dan Arsip
        </p>
    </div>
    <div class="flex items-center gap-3">
        <button class="size-11 flex items-center justify-center rounded-xl ring-1 ring-border hover:ring-primary transition-all duration-300 cursor-pointer">
            <i data-lucide="search" class="size-5 text-secondary"></i>
        </button>

        <div
            class="hidden md:flex items-center gap-3 pl-3 border-l border-border relative"
            x-data="{ openProfile: false }"
            @click.outside="openProfile = false">

            {{-- Avatar inisial (Dummy) --}}
            <div
                class="size-11 rounded-full bg-primary flex items-center justify-center ring-2 ring-border cursor-pointer shrink-0 overflow-hidden"
                @click="openProfile = !openProfile">
                @if ($authPhoto)
                <img src="{{ $authPhoto }}" alt="{{ $authName }}" class="size-full object-cover">
                @else
                <span class="text-white font-black text-sm">{{ $authInitials }}</span>
                @endif
            </div>

            <div
                x-show="openProfile"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-border z-[100]"
                style="display: none">
                <div class="p-2">

                    {{-- Info user di atas (Dummy) --}}
                    <div class="flex items-center gap-3 px-2 py-2 mb-1">
                        <div class="size-9 rounded-full bg-primary flex items-center justify-center shrink-0 overflow-hidden">
                            @if ($authPhoto)
                            <img src="{{ $authPhoto }}" alt="{{ $authName }}" class="size-full object-cover">
                            @else
                            <span class="text-white font-black text-xs">{{ $authInitials }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-foreground truncate">{{ $authName }}</p>
                            <p class="text-xs text-secondary capitalize">{{ $authRole ?? '-' }}</p>
                        </div>
                    </div>

                    <hr class="my-1 border-border" />

                    <a href="{{ route('admin.profile.index') }}"
                        class="flex items-center gap-2 px-2 py-2 rounded-md text-sm text-secondary hover:bg-muted hover:text-primary transition-colors">
                        <i data-lucide="user" class="size-4"></i> Profil Saya
                    </a>

                    <a href="#"
                        class="flex items-center gap-2 px-2 py-2 rounded-md text-sm text-secondary hover:bg-muted hover:text-primary transition-colors">
                        <i data-lucide="settings" class="size-4"></i> Account Settings
                    </a>

                    <hr class="my-1 border-border" />

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-2 px-2 py-2 rounded-md text-sm text-error hover:bg-error/10 transition-colors cursor-pointer">
                            <i data-lucide="log-out" class="size-4"></i>
                            <span>Keluar</span>
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>