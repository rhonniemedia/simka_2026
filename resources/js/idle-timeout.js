const ACTIVITY_KEY = 'simka:idle:activity';
const SYNC_KEY = 'simka:idle:sync';
const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'wheel', 'touchstart'];

const root = document.getElementById('idle-timeout');
if (root) init(root);

function init(root) {
    const lifetime = Number(root.dataset.lifetime) * 60_000;
    if (!lifetime) return;

    // ── Konfigurasi ─────────────────────────────────────────────
    const syncInterval = Math.min(Number(root.dataset.sync) * 1000, lifetime / 3);
    const idleLimit = lifetime - syncInterval;
    const warning = Math.min(Number(root.dataset.warning) * 1000, idleLimit / 2);
    const keepAliveUrl = root.dataset.keepAliveUrl;
    const logoutUrl = root.dataset.logoutUrl;
    const loginUrl = root.dataset.loginUrl;

    const els = {
        countdown: root.querySelector('[data-idle-countdown]'),
        bar: root.querySelector('[data-idle-bar]'),
        extend: root.querySelector('[data-idle-extend]'),
        logout: root.querySelector('[data-idle-logout]'),
        error: root.querySelector('[data-idle-error]'),
    };

    // ── Penyimpanan bersama antar tab (fallback ke memori) ──────
    const mem = {};
    const store = {
        get(key) {
            try {
                const v = Number(localStorage.getItem(key));
                if (v) return v;
            } catch {}
            return mem[key] || 0;
        },
        set(key, value) {
            mem[key] = value;
            try { localStorage.setItem(key, String(value)); } catch {}
        },
        clear() {
            try {
                localStorage.removeItem(ACTIVITY_KEY);
                localStorage.removeItem(SYNC_KEY);
            } catch {}
        },
    };

    const channel = 'BroadcastChannel' in window ? new BroadcastChannel('simka:idle') : null;
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const pathOf = (u) => {
        try { return new URL(u, location.origin).pathname.replace(/\/+$/, ''); } catch { return ''; }
    };
    const loginPath = pathOf(loginUrl);

    let modalOpen = false;
    let loggingOut = false;
    let lastLocalWrite = 0;
    let lastAttempt = 0;
    let pending = null;

    // Memuat halaman = server baru saja memperbarui sesi
    store.set(ACTIVITY_KEY, Date.now());
    store.set(SYNC_KEY, Date.now());

    // ── Modal ───────────────────────────────────────────────────
    const fmt = (secs) =>
        `${String(Math.floor(secs / 60)).padStart(2, '0')}:${String(secs % 60).padStart(2, '0')}`;

    function showModal(remaining) {
        if (!modalOpen) {
            modalOpen = true;
            root.classList.remove('hidden');
            root.classList.add('flex');
            els.error.classList.add('hidden');
            els.extend.focus();
        }
        els.countdown.textContent = fmt(Math.ceil(remaining / 1000));
        els.bar.style.width = `${Math.max(0, Math.min(100, (remaining / warning) * 100))}%`;
    }

    function hideModal() {
        modalOpen = false;
        root.classList.add('hidden');
        root.classList.remove('flex');
    }

    // ── Komunikasi dengan server ────────────────────────────────
    function keepAlive() {
        if (!pending) pending = doKeepAlive().finally(() => { pending = null; });
        return pending;
    }

    async function doKeepAlive() {
        lastAttempt = Date.now();
        try {
            const res = await fetch(keepAliveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            if (res.status === 401 || res.status === 419) {
                sessionEnded('timeout');
                return false;
            }
            if (!res.ok) return false;
            store.set(SYNC_KEY, Date.now());
            return true;
        } catch {
            return false;
        }
    }

    function redirectToLogin(reason) {
        const url = new URL(loginUrl, location.origin);
        if (reason === 'timeout') url.searchParams.set('reason', 'timeout');
        location.replace(url.toString());
    }

    // Sesi di server sudah mati: cukup arahkan ke login
    function sessionEnded(reason = 'timeout') {
        if (loggingOut) return;
        loggingOut = true;
        channel?.postMessage({ type: 'logout', reason });
        store.clear();
        redirectToLogin(reason);
    }

    // Logout resmi lewat server (invalidate sesi + regenerate token)
    async function logout(reason = 'timeout') {
        if (loggingOut) return;
        loggingOut = true;
        channel?.postMessage({ type: 'logout', reason });
        try {
            await fetch(logoutUrl, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
        } catch {}
        store.clear();
        redirectToLogin(reason);
    }

    // ── Deteksi aktivitas (Opsi B) ──────────────────────────────
    function onActivity() {
        if (modalOpen || loggingOut) return;
        const now = Date.now();
        if (now - lastLocalWrite < 1000) return;
        lastLocalWrite = now;
        store.set(ACTIVITY_KEY, now);

        if (now - store.get(SYNC_KEY) >= syncInterval && now - lastAttempt > 15_000) {
            keepAlive();
        }
    }

    ACTIVITY_EVENTS.forEach((ev) =>
        window.addEventListener(ev, onActivity, { passive: true, capture: true })
    );

    // ── Timer utama (berbasis timestamp, aman untuk tab tidur) ──
    function tick() {
        if (loggingOut) return;
        const remaining = idleLimit - (Date.now() - store.get(ACTIVITY_KEY));
        if (remaining <= 0) return logout('timeout');
        if (remaining <= warning) showModal(remaining);
        else if (modalOpen) hideModal();
    }

    setInterval(tick, 1000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') tick();
    });
    tick();

    // ── Tombol modal ────────────────────────────────────────────
    els.extend.addEventListener('click', async () => {
        els.extend.disabled = true;
        const ok = await keepAlive();
        els.extend.disabled = false;
        if (ok) {
            store.set(ACTIVITY_KEY, Date.now());
            hideModal();
        } else if (!loggingOut) {
            els.error.textContent = 'Gagal memperpanjang sesi. Periksa koneksi Anda lalu coba lagi.';
            els.error.classList.remove('hidden');
        }
    });

    els.logout.addEventListener('click', () => logout('manual'));

    // ── Sinkron logout antar tab ────────────────────────────────
    if (channel) {
        channel.onmessage = (e) => {
            if (e.data?.type === 'logout' && !loggingOut) {
                loggingOut = true;
                redirectToLogin(e.data.reason);
            }
        };
    }

    // ── Integrasi HTMX ──────────────────────────────────────────
    // Request HTMX yang sukses berarti server sudah memperbarui sesi
    document.addEventListener('htmx:afterRequest', (e) => {
        const status = e.detail?.xhr?.status;
        if (status >= 200 && status < 400) store.set(SYNC_KEY, Date.now());
    });

    // Cegat respons "sesi habis" supaya halaman login tidak masuk ke area konten
    document.addEventListener('htmx:beforeSwap', (e) => {
        const xhr = e.detail?.xhr;
        if (!xhr) return;
        const requestPath = pathOf(e.detail.pathInfo?.requestPath || '');
        const redirectedToLogin =
            pathOf(xhr.responseURL) === loginPath && requestPath !== loginPath;

        if (xhr.status === 401 || xhr.status === 419 || redirectedToLogin) {
            e.detail.shouldSwap = false;
            sessionEnded('timeout');
        }
    });
}