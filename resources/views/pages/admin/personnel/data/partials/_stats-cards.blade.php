<div id="stats-container" {!! isset($isOob) && $isOob ? 'hx-swap-oob="true"' : '' !!}>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <x-ui.stat-card
            theme="blue"
            icon="users"
            title="Total Pegawai"
            :value="$totalStats ?? 0" />

        <x-ui.stat-card
            theme="success"
            icon="award"
            title="Total PNS"
            :value="$pnsStats ?? 0" />

        <x-ui.stat-card
            theme="purple"
            icon="briefcase"
            title="Total PPPK"
            :value="$pppkTotal ?? 0"
            description="{{ $pppkPenuh ?? 0 }} Penuh Waktu | {{ $pppkParuh ?? 0 }} Paruh Waktu" />

        <x-ui.stat-card
            theme="orange"
            icon="user"
            title="Honorer"
            :value="$honorerStats ?? 0" />

    </div>
</div>