(function () {
    'use strict';

    const config = window.SESSION_TIMEOUT_CONFIG;
    if (!config || !Number(config.timeoutSeconds)) return;

    const timeoutMs = Math.max(1000, Number(config.timeoutSeconds) * 1000);
    const heartbeatIntervalMs = Math.min(30000, Math.max(5000, timeoutMs / 4));
    const storageKey = 'capstone:lastUserActivity';
    let lastActivity = Date.now();
    let lastHeartbeat = 0;
    let logoutTimer = null;
    let expired = false;

    function logoutIfInactive() {
        const idleFor = Date.now() - lastActivity;
        if (idleFor >= timeoutMs) {
            expired = true;
            window.location.replace(config.logoutUrl);
            return;
        }
        scheduleLogout();
    }

    function scheduleLogout() {
        clearTimeout(logoutTimer);
        logoutTimer = setTimeout(logoutIfInactive, Math.max(250, timeoutMs - (Date.now() - lastActivity)));
    }

    function heartbeat(now) {
        if (now - lastHeartbeat < heartbeatIntervalMs) return;
        lastHeartbeat = now;
        fetch(config.activityUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            keepalive: true
        }).then(function (response) {
            if (response.status === 401 && !expired) {
                expired = true;
                window.location.replace(config.logoutUrl);
            }
        }).catch(function () {});
    }

    function recordActivity() {
        if (expired) return;
        const now = Date.now();
        lastActivity = now;
        try { localStorage.setItem(storageKey, String(now)); } catch (error) {}
        scheduleLogout();
        heartbeat(now);
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'pointerdown'].forEach(function (eventName) {
        window.addEventListener(eventName, recordActivity, { passive: true });
    });

    window.addEventListener('storage', function (event) {
        if (event.key !== storageKey) return;
        const sharedActivity = Number(event.newValue || 0);
        if (sharedActivity > lastActivity) {
            lastActivity = sharedActivity;
            scheduleLogout();
            heartbeat(sharedActivity);
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') logoutIfInactive();
    });

    // Browsers may restore a dashboard snapshot after logout without requesting
    // it from PHP. Reload BFCache-restored pages so require_auth checks the
    // current server session and redirects logged-out users to the login page.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    recordActivity();
}());
