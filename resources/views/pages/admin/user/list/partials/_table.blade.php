<div id="user-list-container"
    hx-trigger="refreshTable from:body"
    hx-get="{{ route('admin.users.index', request()->query()) }}"
    hx-target="#user-list-container"
    hx-swap="outerHTML">

    {{-- ============ 1. DESKTOP TABLE ============ --}}
    {{-- Tambahkan "hidden lg:block" untuk menyembunyikan tabel di HP --}}
    <div class="hidden lg:block overflow-x-auto w-full pb-2">
        <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
                <tr class="border-b border-border bg-gray-50/30">
                    <th class="w-[28%] px-4 py-3 text-sm font-bold text-secondary">
                        Pengguna
                        <div class="text-[11px] font-normal normal-case">Nama | Username</div>
                    </th>
                    <th class="w-[27%] px-4 py-3 text-sm font-bold text-secondary">
                        Kontak
                        <div class="text-[11px] font-normal normal-case">Telepon | Email</div>
                    </th>
                    <th class="w-[25%] px-4 py-3 text-sm font-bold text-secondary">
                        Status
                        <div class="text-[11px] font-normal normal-case">Status | Role</div>
                    </th>
                    <th class="w-[20%] px-4 py-3 text-sm font-bold text-secondary">
                        Aksi
                        <div class="text-[11px] font-normal normal-case">Edit | Role | Sandi | Cabut</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $user)
                @php
                $staff = $user->staff;
                $displayName = $staff->name ?? $user->username;
                $email = optional(optional($staff)->vault)->email;
                $phone = optional(optional($staff)->vault)->phone_number;
                $userRoles = $user->roles ?? collect();
                $isSelf = (string) $user->id === (string) $currentUserId;
                $canManage = $isSuperadmin || ! $userRoles->contains(fn ($r) => strtolower($r->name) === 'superadmin');
                @endphp
                <tr id="row-user-{{ $user->id }}" class="border-b border-border hover:bg-muted/50 transition-colors">

                    {{-- Kolom Pengguna --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3 group">
                            <x-ui.avatar :name="$displayName" :gender="optional($staff)->gender" :index="$loop->index" />

                            <div class="min-w-0">
                                <div class="font-semibold text-foreground text-sm group-hover:text-primary transition-colors truncate">
                                    {{ $displayName }}
                                </div>
                                <div class="text-xs text-secondary mt-0.5 truncate">
                                    {{ $user->username }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Kolom Kontak (Telepon & Email) --}}
                    <td class="px-4 py-4 text-sm">
                        <div class="text-foreground font-medium truncate mb-1">
                            {{ $phone ?? '-' }}
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-secondary truncate" title="{{ $email }}">
                            <i data-lucide="mail" class="size-3.5 shrink-0"></i>
                            <span class="truncate">{{ $email ?? '-' }}</span>
                        </div>
                    </td>

                    {{-- Kolom Status & Role --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">

                            {{-- Status (Kiri) --}}
                            @if ($user->status === 'active')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-100 text-emerald-700 shrink-0">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-gray-100 text-gray-500 shrink-0">
                                <span class="size-1.5 rounded-full bg-gray-400"></span>
                                Nonaktif
                            </span>
                            @endif

                            {{-- Role (Kanan) --}}
                            <div class="flex flex-wrap items-center gap-1">
                                @forelse ($userRoles as $role)
                                <span class="inline-block px-2 py-1 text-[11px] font-semibold rounded-full bg-primary/10 text-primary capitalize">
                                    {{ $role->name }}
                                </span>
                                @empty
                                <span class="text-[11px] text-secondary">Belum ada role</span>
                                @endforelse
                            </div>

                        </div>
                    </td>

                    {{-- Kolom Aksi --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            @if ($canManage)
                            <button type="button" title="Ubah Username"
                                hx-get="{{ route('admin.users.edit', $user->id) }}"
                                hx-target="#modal-form-container"
                                hx-swap="innerHTML"
                                class="flex items-center justify-center size-8 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                                <i data-lucide="user-pen" class="size-4 pointer-events-none"></i>
                            </button>
                            <button type="button" title="Ubah Hak Akses"
                                hx-get="{{ route('admin.users.edit-role', $user->id) }}"
                                hx-target="#modal-form-container"
                                hx-swap="innerHTML"
                                class="flex items-center justify-center size-8 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                                <i data-lucide="shield-check" class="size-4 pointer-events-none"></i>
                            </button>
                            <button type="button" title="Reset Kata Sandi"
                                hx-get="{{ route('admin.users.edit-password', $user->id) }}"
                                hx-target="#modal-form-container"
                                hx-swap="innerHTML"
                                class="flex items-center justify-center size-8 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                                <i data-lucide="key-round" class="size-4 pointer-events-none"></i>
                            </button>
                            @unless ($isSelf)
                            <button type="button" title="Cabut Akses SIMKA"
                                hx-delete="{{ route('admin.users.revoke-access', $user->id) }}"
                                hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                                hx-swap="none"
                                hx-confirm="Cabut akses SIMKA untuk {{ $user->username }}? Akunnya tidak dihapus dan tetap bisa dipakai di aplikasi lain. Untuk memberi akses lagi, gunakan Tambah Pengguna."
                                class="flex items-center justify-center size-8 rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-all cursor-pointer">
                                <i data-lucide="user-minus" class="size-4 pointer-events-none"></i>
                            </button>
                            @endunless
                            @else
                            <span title="Hanya superadmin yang dapat mengelola akun ini" class="inline-flex items-center gap-1.5 text-[11px] text-secondary">
                                <i data-lucide="lock" class="size-3.5"></i> Dikunci
                            </span>
                            @endif
                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-secondary">
                        <div class="flex flex-col items-center gap-3">
                            <i data-lucide="users-round" class="size-10 text-border"></i>
                            <p class="font-medium text-sm">Belum ada pengguna yang terdaftar</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ 2. MOBILE CARDS ============ --}}
    <div class="lg:hidden divide-y divide-border border-y border-border bg-white -mx-5 mb-5 mt-2">
        @forelse ($data as $user)
        @php
        $staff = $user->staff;
        $displayName = $staff->name ?? $user->username;
        $email = optional(optional($staff)->vault)->email;
        $phone = optional(optional($staff)->vault)->phone_number;
        $userRoles = $user->roles ?? collect();
        $isSelf = (string) $user->id === (string) $currentUserId;
        $canManage = $isSuperadmin || ! $userRoles->contains(fn ($r) => strtolower($r->name) === 'superadmin');
        @endphp

        <div id="card-user-{{ $user->id }}" class="px-5 py-4 border-border hover:bg-muted/40 active:bg-muted/60 transition-colors">
            <div class="flex items-start gap-3">
                <x-ui.avatar :name="$displayName" :gender="optional($staff)->gender" :index="$loop->index" />

                <div class="min-w-0 flex-1">
                    {{-- Bagian Atas: Nama, Username & Status --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground text-sm uppercase truncate">
                                {{ $displayName }}
                            </p>
                            <p class="text-xs text-secondary mt-0.5 truncate">
                                {{ $user->username }}
                            </p>
                        </div>

                        {{-- Badge Status --}}
                        @if ($user->status === 'active')
                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold rounded-md bg-emerald-100 text-emerald-700">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            AKTIF
                        </span>
                        @else
                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold rounded-md bg-gray-100 text-gray-500">
                            <span class="size-1.5 rounded-full bg-gray-400"></span>
                            NONAKTIF
                        </span>
                        @endif
                    </div>

                    {{-- Bagian Tengah: Detail Role & Kontak --}}
                    <div class="mt-3 border-t border-b border-border divide-y divide-border text-xs">

                        {{-- Role Section --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="shield" class="size-3 text-slate-400"></i>
                                Akses Role
                            </p>
                            <div class="flex flex-wrap items-center justify-end gap-1">
                                @forelse ($userRoles as $role)
                                <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-primary/10 text-primary capitalize">
                                    {{ $role->name }}
                                </span>
                                @empty
                                <span class="text-[10px] text-secondary">Belum ada role</span>
                                @endforelse
                            </div>
                        </div>

                        {{-- Telepon Section --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="phone" class="size-3 text-slate-400"></i>
                                Telepon
                            </p>
                            <p class="text-foreground text-right font-medium truncate">{{ $phone ?? '-' }}</p>
                        </div>

                        {{-- Email Section --}}
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <p class="text-secondary flex items-center gap-1.5 shrink-0">
                                <i data-lucide="mail" class="size-3 text-slate-400"></i>
                                Email
                            </p>
                            <p class="text-foreground text-right truncate">{{ $email ?? '-' }}</p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Bagian Bawah: Aksi --}}
            <div class="mt-3 flex flex-wrap justify-end gap-2">
                @if ($canManage)
                <button type="button" title="Ubah Username"
                    hx-get="{{ route('admin.users.edit', $user->id) }}"
                    hx-target="#modal-form-container"
                    hx-swap="innerHTML"
                    class="flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                    <i data-lucide="user-pen" class="size-3.5 pointer-events-none"></i>
                    <span class="text-xs font-medium">Edit</span>
                </button>
                <button type="button" title="Ubah Hak Akses"
                    hx-get="{{ route('admin.users.edit-role', $user->id) }}"
                    hx-target="#modal-form-container"
                    hx-swap="innerHTML"
                    class="flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                    <i data-lucide="shield-check" class="size-3.5 pointer-events-none"></i>
                    <span class="text-xs font-medium">Role</span>
                </button>
                <button type="button" title="Reset Kata Sandi"
                    hx-get="{{ route('admin.users.edit-password', $user->id) }}"
                    hx-target="#modal-form-container"
                    hx-swap="innerHTML"
                    class="flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-secondary hover:bg-muted hover:text-foreground transition-all cursor-pointer">
                    <i data-lucide="key-round" class="size-3.5 pointer-events-none"></i>
                    <span class="text-xs font-medium">Sandi</span>
                </button>
                @unless ($isSelf)
                <button type="button" title="Cabut Akses SIMKA"
                    hx-delete="{{ route('admin.users.revoke-access', $user->id) }}"
                    hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                    hx-swap="none"
                    hx-confirm="Cabut akses SIMKA untuk {{ $user->username }}? Akunnya tidak dihapus dan tetap bisa dipakai di aplikasi lain. Untuk memberi akses lagi, gunakan Tambah Pengguna."
                    class="flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-border bg-white text-secondary hover:bg-error/10 hover:text-error hover:border-error/30 transition-all cursor-pointer">
                    <i data-lucide="user-minus" class="size-3.5 pointer-events-none"></i>
                    <span class="text-xs font-medium">Cabut Akses</span>
                </button>
                @endunless
                @else
                <span class="inline-flex items-center gap-1.5 text-[11px] text-secondary">
                    <i data-lucide="lock" class="size-3.5"></i> Hanya superadmin yang dapat mengelola akun ini
                </span>
                @endif
            </div>

        </div>
        @empty
        <div class="px-4 py-16 text-center text-secondary">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="users-round" class="size-10 text-border"></i>
                <p class="font-medium text-sm">Belum ada pengguna yang terdaftar</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Memanggil komponen pagination --}}
    <div class="mt-4">
        <x-ui.pagination :paginator="$data" hxTarget="#user-list-container" />
    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>