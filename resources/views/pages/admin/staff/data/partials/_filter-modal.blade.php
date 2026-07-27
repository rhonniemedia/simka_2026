<x-ui.modal show="filterModalOpen" maxWidth="lg">

    {{-- Header --}}
    <div class="flex items-center justify-between px-6 py-5 border-b border-border shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <i data-lucide="sliders-horizontal" class="size-4 text-primary"></i>
            </div>
            <div>
                <h3 class="font-bold text-foreground text-lg">Filter Data Pegawai</h3>
                <p class="text-xs text-secondary mt-0.5">Persempit daftar berdasarkan kriteria berikut</p>
            </div>
        </div>
        <button @click="filterModalOpen = false" class="size-8 rounded-lg border border-border flex items-center justify-center hover:bg-muted transition-colors">
            <i data-lucide="x" class="size-4 text-secondary"></i>
        </button>
    </div>

    {{-- Form filter --}}
    <div id="staff-filter-form" class="flex-1 overflow-y-auto p-6 space-y-6">

        {{-- Grup: Kepegawaian --}}
        <div class="space-y-3">
            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary">
                <i data-lucide="briefcase" class="size-3.5"></i>
                Kepegawaian
            </p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-foreground mb-2">Jenis Pegawai</label>
                    <select name="filter_personnel" class="w-full bg-white border border-border rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary transition-all">
                        <option value="">Semua Jenis</option>
                        @foreach ($personnelOptions ?? [] as $id => $name)
                        <option value="{{ $id }}" @selected(isset($filterPersonnel) && $filterPersonnel==$id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-foreground mb-2">Jabatan</label>
                    <select name="filter_position" class="w-full bg-white border border-border rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary transition-all">
                        <option value="">Semua Jabatan</option>
                        @foreach ($positionOptions ?? [] as $id => $name)
                        <option value="{{ $id }}" @selected(isset($filterPosition) && $filterPosition==$id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Grup: Demografi --}}
        <div class="space-y-3 pt-1 border-t border-border/70">
            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-secondary pt-3">
                <i data-lucide="users" class="size-3.5"></i>
                Demografi
            </p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-foreground mb-2">Jenis Kelamin</label>
                    <select name="filter_gender" class="w-full bg-white border border-border rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary transition-all">
                        <option value="">Semua Gender</option>
                        <option value="L" @selected(isset($filterGender) && $filterGender==='L' )>Laki-laki</option>
                        <option value="P" @selected(isset($filterGender) && $filterGender==='P' )>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-foreground mb-2">Status Aktif</label>
                    <select name="filter_status" class="w-full bg-white border border-border rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary transition-all">
                        <option value="">Semua Status</option>
                        <option value="active" @selected(isset($filterStatus) && $filterStatus==='active' )>Aktif</option>
                        <option value="inactive" @selected(isset($filterStatus) && $filterStatus==='inactive' )>Non-Aktif</option>
                        <option value="retired" @selected(isset($filterStatus) && $filterStatus==='retired' )>Pensiun</option>
                        <option value="resigned" @selected(isset($filterStatus) && $filterStatus==='resigned' )>Resign</option>
                    </select>
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="px-6 py-4 border-t border-border bg-gray-50/50 flex items-center justify-between shrink-0">
        <button type="button"
            @click="
                document.querySelectorAll('#staff-filter-form select').forEach(el => el.value = '');
                document.getElementById('btn-apply-filter').click();
            "
            class="flex items-center gap-1.5 px-4 py-2 rounded-xl border border-border bg-white text-secondary hover:bg-muted transition-colors cursor-pointer">
            <i data-lucide="rotate-ccw" class="size-3.5"></i>
            Reset Filter
        </button>

        <button type="button"
            id="btn-apply-filter"
            hx-get="{{ route('admin.staff.data.index') }}"
            hx-include="#staff-filter-form, [name='search']"
            hx-target="#staff-container" hx-select="#staff-container" hx-swap="outerHTML" hx-push-url="true"
            @click="filterModalOpen = false"
            class="flex items-center gap-1.5 px-5 py-2.5 bg-primary text-white hover:bg-primary-dark shadow-md text-sm font-bold rounded-xl transition-all cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            Terapkan Filter
        </button>
    </div>

</x-ui.modal>