(function () {
    'use strict';

    const config = window.CSRF_CONFIG;
    if (!config || !config.token || typeof window.fetch !== 'function') return;

    const originalFetch = window.fetch.bind(window);
    const unsafeMethods = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

    window.fetch = function (input, init) {
        const options = Object.assign({}, init || {});
        const method = String(options.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
        const requestUrl = input instanceof Request ? input.url : String(input);
        const url = new URL(requestUrl, window.location.href);

        if (url.origin === window.location.origin && unsafeMethods.has(method)) {
            const headers = new Headers(input instanceof Request ? input.headers : undefined);
            new Headers(options.headers || {}).forEach(function (value, name) {
                headers.set(name, value);
            });
            headers.set('X-CSRF-Token', config.token);
            options.headers = headers;
            options.credentials = options.credentials || 'same-origin';
        }

        return originalFetch(input, options);
    };
}());
