@extends('layouts.main.admin')

@section('title', 'Master Data Kepegawaian')
@section('page_title', 'Master Data')
@section('page_subtitle', 'Kelola basis data referensi dan parameter kepegawaian')

@section('content')
@php
// Terima variabel dari Controller. Jika kosong, gunakan tab pertama sebagai default.
$activeTab = $activeTab ?? 'personnel_types';
$fetchUrl = $fetchUrl ?? route('admin.master.personnel-types');

// Sumber tunggal untuk navigasi desktop (sidebar) dan mobile (select).
// Menambah tab baru cukup dengan menambah satu baris di sini.
$masterTabs = [
['value' => 'personnel_types', 'label' => 'Jenis Pegawai', 'icon' => 'users', 'group' => 'Kepegawaian', 'route' => route('admin.master.personnel-types')],
['value' => 'employment_statuses', 'label' => 'Status Kepegawaian', 'icon' => 'briefcase', 'group' => 'Kepegawaian', 'route' => route('admin.master.employment-statuses')],
['value' => 'positions', 'label' => 'Jabatan Organisasi', 'icon' => 'award', 'group' => 'Kepegawaian', 'route' => route('admin.master.positions')],
['value' => 'asn_positions', 'label' => 'Jabatan Kepegawaian', 'icon' => 'badge-check', 'group' => 'Kepegawaian', 'route' => route('admin.master.asn-positions')],
['value' => 'grades', 'label' => 'Golongan Pangkat', 'icon' => 'trending-up', 'group' => 'Kepegawaian', 'route' => route('admin.master.grades')],
['value' => 'education_levels', 'label' => 'Tingkat Pendidikan', 'icon' => 'graduation-cap', 'group' => 'Referensi Lainnya', 'route' => route('admin.master.education-levels')],
['value' => 'document_categories', 'label' => 'Kategori Dokumen', 'icon' => 'folder-open', 'group' => 'Referensi Lainnya', 'route' => route('admin.master.document-categories')],
];
$masterGroups = collect($masterTabs)->groupBy('group');
@endphp

<div class="px-5 py-8 md:p-8" x-data="{ activeTab: @js($activeTab) }">

    {{-- 1. PAGE HEADER --}}
    <div class="flex items-start gap-4 mb-6">
        <div class="size-11 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
            <i data-lucide="database" class="size-5 text-primary"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl md:text-3xl font-bold text-foreground mb-1">Master Data Kepegawaian</h1>
            <p class="text-sm text-secondary">Kelola basis data referensi dan parameter kepegawaian secara terpusat.</p>
        </div>
        <a href="{{ route('admin.personnel.data.index') }}"
            title="Kembali ke Data Pegawai"
            class="flex items-center justify-center size-10 rounded-full border border-border bg-white text-secondary hover:text-green-500 hover:border-green-500 transition-colors cursor-pointer focus:outline-none shrink-0">
            <i data-lucide="arrow-left" class="size-5"></i>
        </a>
    </div>

    {{-- 2. NAVIGASI MOBILE --}}
    {{-- hx-push-url di wrapper dipakai htmx.ajax (lewat `source`) agar riwayat/URL
         ditangani htmx persis seperti klik di sidebar desktop. --}}
    <div class="lg:hidden mb-5"
        hx-push-url="true"
        x-data="{
            routes: @js(collect($masterTabs)->pluck('route', 'value')),
            goTo(tab) {
                const route = this.routes[tab];
                if (!route || tab === this.activeTab) return;
                this.activeTab = tab;
                htmx.ajax('GET', route, {
                    source: this.$el,
                    target: '#master-tab-content',
                    swap: 'innerHTML'
                });
            }
        }"
        @change="goTo($event.target.value)">

        <x-ui.select
            name="master_tab"
            :options="$masterTabs"
            :value="$activeTab"
            placeholder="Pilih Menu Master" />
    </div>

    {{-- 3. LAYOUT --}}
    <div class="flex flex-col lg:flex-row gap-5 lg:gap-6">

        {{-- Sidebar navigasi (desktop) --}}
        <div class="hidden lg:block w-64 shrink-0">
            <div class="bg-white rounded-2xl border border-border p-3 lg:sticky lg:top-6">

                @foreach ($masterGroups as $groupName => $tabs)
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-secondary {{ $loop->first ? 'pt-1' : 'pt-2 border-t border-border/70' }}">
                    {{ $groupName }}
                </p>
                <ul class="space-y-0.5 {{ $loop->last ? '' : 'mb-3' }}">
                    @foreach ($tabs as $tab)
                    <li>
                        <button type="button" @click="activeTab = @js($tab['value'])"
                            :class="activeTab === @js($tab['value']) ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ $tab['route'] }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === @js($tab['value']) ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="{{ $tab['icon'] }}" class="size-3.5"></i>
                            </span>
                            <span class="truncate"
                                :class="activeTab === @js($tab['value']) ? 'text-primary font-bold' : 'text-foreground font-medium'">{{ $tab['label'] }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
                @endforeach

            </div>
        </div>

        {{-- Bagian Kanan: Area Konten (Target HTMX) --}}
        <div class="flex-1 min-w-0 w-full flex flex-col">
            <div class="bg-white rounded-2xl border border-border p-5 lg:p-6 min-h-[400px] flex-1"
                id="master-tab-content"
                x-init="htmx.ajax('GET', @js($fetchUrl), { target: '#master-tab-content' })">
                {{-- Skeleton loading --}}
                <div class="animate-pulse space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="h-5 w-40 bg-slate-100 rounded"></div>
                        <div class="h-9 w-28 bg-slate-100 rounded-full"></div>
                    </div>
                    <div class="space-y-2.5 pt-2">
                        <div class="h-11 bg-slate-50 rounded-xl"></div>
                        <div class="h-11 bg-slate-50 rounded-xl"></div>
                        <div class="h-11 bg-slate-50 rounded-xl"></div>
                        <div class="h-11 bg-slate-50 rounded-xl w-2/3"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Kontainer Modal (untuk form Tambah/Edit Master) --}}
    <div id="modal-container"></div>
</div>
@endsection