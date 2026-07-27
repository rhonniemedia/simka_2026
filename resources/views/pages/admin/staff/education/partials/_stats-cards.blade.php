<div id="stats-container" {!! isset($isOob) && $isOob ? 'hx-swap-oob="true"' : '' !!}>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <x-ui.stat-card
            theme="blue"
            icon="users"
            title="Total Pegawai"
            :value="$totalStats ?? 0" />

        <x-ui.stat-card
            theme="success"
            icon="user-check"
            title="Pegawai Aktif"
            :value="$activeStats ?? 0" />

        <x-ui.stat-card
            theme="purple"
            icon="award"
            title="Pensiun"
            :value="$retiredStats ?? 0" />

        <x-ui.stat-card
            theme="orange"
            icon="user-minus"
            title="Non-Aktif"
            :value="$inactiveStats ?? 0" />

    </div>
</div>