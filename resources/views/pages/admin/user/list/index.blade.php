@php
// Persiapan data opsi role untuk x-ui.select
$roleOptionsList = [
['value' => '', 'label' => 'Semua Role']
];
if (isset($roles)) {
foreach ($roles as $roleItem) {
$roleOptionsList[] = [
'value' => $roleItem->name,
'label' => ucwords($roleItem->name)
];
}
}
@endphp

@extends('layouts.main.admin')

@section('title', 'Daftar Pengguna')
@section('page_title', 'Daftar Pengguna')
@section('page_subtitle', 'Kelola akun pengguna, hak akses, dan kata sandi')

@section('content')
<div class="px-5 py-8 md:p-8">

    {{-- PAGE HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Daftar Pengguna</h1>
            <p class="text-sm text-secondary">Kelola akun, hak akses (role), dan kata sandi pengguna aplikasi.</p>
        </div>

        <button type="button"
            hx-get="{{ route('admin.users.create') }}"
            hx-target="#modal-form-container"
            hx-swap="innerHTML"
            class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all cursor-pointer">
            <i data-lucide="user-plus" class="size-4 pointer-events-none"></i>
            Tambah Pengguna
        </button>
    </div>

    {{-- KARTU STATISTIK (ikut diperbarui otomatis lewat hx-swap-oob) --}}
    @include('pages.admin.user.list.partials._stats-cards')

    {{-- KOTAK KONTEN UTAMA --}}
    <div class="bg-white rounded-2xl border border-border p-5">

        {{-- Header Tabel & Search --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
            <div>
                <h2 class="text-lg font-bold text-foreground">Akun Pengguna</h2>
                <p class="text-sm text-secondary mt-1">Menampilkan seluruh akun pengguna yang terdaftar.</p>
            </div>

            {{-- Form Filter & Search (Terhubung dengan HTMX) --}}
            <form id="filter-form" class="flex flex-col sm:flex-row items-center gap-2" x-data="{ searchQuery: '{{ $search ?? '' }}' }" @submit.prevent>

                {{-- Filter Role (Menggunakan x-ui.select dengan HTMX Wrapper & Ikon) --}}
                <div class="relative w-full sm:w-44"
                    hx-get="{{ route('admin.users.index') }}"
                    hx-include="#filter-form"
                    hx-trigger="change"
                    hx-target="#user-list-container"
                    hx-swap="outerHTML"
                    hx-push-url="true">

                    {{-- Ikon di sebelah kiri --}}
                    <i data-lucide="shield" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-secondary z-10 pointer-events-none"></i>

                    <div class="[&>div>button]:pl-10">
                        <x-ui.select
                            name="role"
                            :options="$roleOptionsList"
                            value="{{ request('role') ?? '' }}"
                            placeholder="Semua Role" />
                    </div>
                </div>

                {{-- Search Input --}}
                <div class="relative w-full sm:w-auto flex items-center">
                    <i data-lucide="search" class="absolute left-3 size-4 transition-colors"
                        :class="searchQuery.length > 0 ? 'text-primary' : 'text-secondary'"></i>

                    <input
                        x-ref="searchInput"
                        type="text"
                        name="search"
                        x-model="searchQuery"
                        placeholder="Cari nama / username..."
                        autocomplete="off"
                        hx-get="{{ route('admin.users.index') }}"
                        hx-include="#filter-form"
                        hx-trigger="keyup changed delay:400ms, search"
                        hx-target="#user-list-container"
                        hx-swap="outerHTML"
                        hx-push-url="true"
                        class="h-11 w-full sm:w-56 md:w-64 bg-white border rounded-xl pl-10 pr-10 text-sm focus:outline-none focus:border-primary transition-all"
                        :class="searchQuery.length > 0 ? 'border-primary/50 text-foreground font-medium' : 'border-border text-foreground'">

                    <button
                        type="button"
                        x-show="searchQuery.length > 0"
                        x-cloak
                        @click="searchQuery = ''; $nextTick(() => $refs.searchInput.dispatchEvent(new Event('search')))"
                        class="absolute right-3 flex items-center justify-center size-5 rounded-full bg-slate-100 hover:bg-error/10 text-secondary hover:text-error transition-all cursor-pointer focus:outline-none">
                        <i data-lucide="x" class="size-3"></i>
                    </button>
                </div>
            </form>
        </div>

        @include('pages.admin.user.list.partials._table', ['data' => $data, 'search' => $search])

    </div>

    {{-- MODAL TAMBAH / EDIT / ROLE / PASSWORD --}}
    <div id="modal-form-container"></div>

</div>
@endsection