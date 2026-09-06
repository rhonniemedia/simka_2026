@php
// Terima variabel dari Controller. Jika kosong, baru gunakan tab pertama sebagai default.
$activeTab = $activeTab ?? 'personnel_types';
$fetchUrl = $fetchUrl ?? route('admin.master.personnel-types');
@endphp

@extends('layouts.main.admin')

@section('title', 'Master Data Kepegawaian')
@section('page_title', 'Master Data')
@section('page_subtitle', 'Kelola basis data referensi dan parameter kepegawaian')

@section('content')
<div class="px-5 py-8 md:p-8" x-data="{ activeTab: '{{ $activeTab }}' }">

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

    {{-- 2. NAVIGASI MOBILE: pill horizontal, scroll ke samping --}}
    @php
    $masterTabs = [
    ['key' => 'personnel_types', 'label' => 'Jenis Personel', 'icon' => 'users', 'route' => route('admin.master.personnel-types')],
    ['key' => 'employment_statuses', 'label' => 'Status Kepegawaian', 'icon' => 'briefcase', 'route' => route('admin.master.employment-statuses')],
    ['key' => 'positions', 'label' => 'Jabatan Organisasi', 'icon' => 'award', 'route' => route('admin.master.positions')],
    ['key' => 'asn_positions', 'label' => 'Jabatan Kepegawaian', 'icon' => 'badge-check', 'route' => route('admin.master.asn-positions')],
    ['key' => 'grades', 'label' => 'Golongan Pangkat', 'icon' => 'trending-up', 'route' => route('admin.master.grades')],
    ['key' => 'education_levels', 'label' => 'Tingkat Pendidikan', 'icon' => 'graduation-cap', 'route' => route('admin.master.education-levels')],
    ['key' => 'document_categories', 'label' => 'Kategori Dokumen', 'icon' => 'folder-open', 'route' => route('admin.master.document-categories')],
    ];
    @endphp
    <div class="lg:hidden -mx-5 px-5 mb-5 overflow-x-auto">
        <div class="flex items-center gap-2 w-max pb-1">
            @foreach ($masterTabs as $tab)
            <button type="button"
                @click="activeTab = '{{ $tab['key'] }}'"
                :class="activeTab === '{{ $tab['key'] }}' ? 'bg-primary text-white border-primary' : 'bg-white text-secondary border-border'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors cursor-pointer focus:outline-none"
                hx-get="{{ $tab['route'] }}"
                hx-target="#master-tab-content"
                hx-swap="innerHTML"
                hx-push-url="true">
                <i data-lucide="{{ $tab['icon'] }}" class="size-3.5 shrink-0"></i>
                <span>{{ $tab['label'] }}</span>
            </button>
            @endforeach
        </div>
    </div>

    {{-- 3. LAYOUT --}}
    <div class="flex flex-col lg:flex-row gap-5 lg:gap-6">

        {{-- Sidebar navigasi (desktop) --}}
        <div class="hidden lg:block w-64 shrink-0">
            <div class="bg-white rounded-2xl border border-border p-3 lg:sticky lg:top-6">

                {{-- Grup: Kepegawaian --}}
                <p class="px-3 pt-1 pb-2 text-[11px] font-bold uppercase tracking-wider text-secondary">Kepegawaian</p>
                <ul class="space-y-0.5 mb-3">
                    <li>
                        <button type="button" @click="activeTab = 'personnel_types'"
                            :class="activeTab === 'personnel_types' ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ route('admin.master.personnel-types') }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === 'personnel_types' ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="users" class="size-3.5"></i>
                            </span>
                            <span class="truncate" :class="activeTab === 'personnel_types' ? 'text-primary font-bold' : 'text-foreground font-medium'">Jenis Personel</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" @click="activeTab = 'employment_statuses'"
                            :class="activeTab === 'employment_statuses' ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ route('admin.master.employment-statuses') }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === 'employment_statuses' ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="briefcase" class="size-3.5"></i>
                            </span>
                            <span class="truncate" :class="activeTab === 'employment_statuses' ? 'text-primary font-bold' : 'text-foreground font-medium'">Status Kepegawaian</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" @click="activeTab = 'positions'"
                            :class="activeTab === 'positions' ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ route('admin.master.positions') }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === 'positions' ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="award" class="size-3.5"></i>
                            </span>
                            <span class="truncate" :class="activeTab === 'positions' ? 'text-primary font-bold' : 'text-foreground font-medium'">Jabatan Organisasi</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" @click="activeTab = 'asn_positions'"
                            :class="activeTab === 'asn_positions' ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ route('admin.master.asn-positions') }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === 'asn_positions' ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="badge-check" class="size-3.5"></i>
                            </span>
                            <span class="truncate" :class="activeTab === 'asn_positions' ? 'text-primary font-bold' : 'text-foreground font-medium'">Jabatan Kepegawaian</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" @click="activeTab = 'grades'"
                            :class="activeTab === 'grades' ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ route('admin.master.grades') }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === 'grades' ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="trending-up" class="size-3.5"></i>
                            </span>
                            <span class="truncate" :class="activeTab === 'grades' ? 'text-primary font-bold' : 'text-foreground font-medium'">Golongan Pangkat</span>
                        </button>
                    </li>
                </ul>

                {{-- Grup: Referensi Lainnya --}}
                <p class="px-3 pt-2 pb-2 text-[11px] font-bold uppercase tracking-wider text-secondary border-t border-border/70">Referensi Lainnya</p>
                <ul class="space-y-0.5">
                    <li>
                        <button type="button" @click="activeTab = 'education_levels'"
                            :class="activeTab === 'education_levels' ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ route('admin.master.education-levels') }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === 'education_levels' ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="graduation-cap" class="size-3.5"></i>
                            </span>
                            <span class="truncate" :class="activeTab === 'education_levels' ? 'text-primary font-bold' : 'text-foreground font-medium'">Tingkat Pendidikan</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" @click="activeTab = 'document_categories'"
                            :class="activeTab === 'document_categories' ? 'bg-primary/5' : 'hover:bg-muted'"
                            class="w-full flex items-center gap-3 pl-3 pr-3 py-2.5 text-sm rounded-xl transition-all cursor-pointer focus:outline-none text-left"
                            hx-get="{{ route('admin.master.document-categories') }}"
                            hx-target="#master-tab-content"
                            hx-swap="innerHTML"
                            hx-push-url="true">
                            <span class="size-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="activeTab === 'document_categories' ? 'bg-primary text-white' : 'bg-slate-100 text-secondary'">
                                <i data-lucide="folder-open" class="size-3.5"></i>
                            </span>
                            <span class="truncate" :class="activeTab === 'document_categories' ? 'text-primary font-bold' : 'text-foreground font-medium'">Kategori Dokumen</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bagian Kanan: Area Konten (Target HTMX) --}}
        <div class="flex-1 min-w-0 w-full flex flex-col">
            <div class="bg-white rounded-2xl border border-border p-5 lg:p-6 min-h-[400px] flex-1"
                id="master-tab-content"
                x-init="htmx.ajax('GET', '{!! $fetchUrl !!}', {target: '#master-tab-content'})">
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