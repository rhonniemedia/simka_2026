<?php

return [
    // Detik peringatan sebelum auto logout
    'warning' => (int) env('IDLE_WARNING_SECONDS', 60),

    // Jarak minimum (detik) antar keep-alive otomatis ke server
    'sync_interval' => (int) env('IDLE_SYNC_SECONDS', 300),
];
