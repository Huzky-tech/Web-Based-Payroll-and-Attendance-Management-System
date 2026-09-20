document.addEventListener('DOMContentLoaded', () => {
    const philippinesMapBounds = {
        north: 21.5,
        south: 4.2,
        west: 116.0,
        east: 127.3
    };

    const dashboardSiteState = {
        siteMap: new Map(),
        activeSiteId: null,
        saveInProgress: false,
        leafletReady: null,
        detailsMap: {
            instance: null,
            marker: null
        },
        editMap: {
            instance: null,
            marker: null,
            geocodeTimer: null,
            searchAbortController: null,
            defaultCenter: [12.8797, 121.7740],
            defaultZoom: 6
        },
        assignmentModal: {
            siteId: null,
            workers: [],
            selectedWorkerIds: new Set(),
            initialWorkerIds: new Set(),
            roleByWorkerId: new Map(),
            initialRoleByWorkerId: new Map(),
            searchTerm: '',
            statusFilter: '',
            roleFilter: '',
            locationFilter: '',
            timekeepers: [],
            timekeeperUserId: null,
            initialTimekeeperUserId: null,
            timekeeperLoadState: 'ok',
            saving: false
        }
    };

    const dashboardRole = document.body.dataset.dashboardRole || '';

    function updateShellClock() {
        const dateEl = document.getElementById('currentDate');
        const timeEl = document.getElementById('currentTime');

        if (!dateEl || !timeEl) {
            return;
        }

        const now = new Date();
        dateEl.textContent = now.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
        timeEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }

    function updateShellTitle() {
        const pageTitleEl = document.querySelector('.main-content > .top-header .page-title');
        const activeNavLabel = document.querySelector('.sidebar .nav-item.active span');

        if (pageTitleEl && activeNavLabel) {
            pageTitleEl.textContent = activeNavLabel.textContent.trim();
        }
    }

    function setText(selector, value) {
        const el = document.querySelector(selector);
        if (el) {
            el.textContent = value;
        }
    }

    function setHtml(selector, value) {
        const el = document.querySelector(selector);
        if (el) {
            el.innerHTML = value;
        }
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2
        }).format(Number(value || 0));
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric'
        });
    }

    function formatDateLabel(value) {
        if (!value) {
            return 'Not set';
        }

        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toISOString().split('T')[0];
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function parseCoordinateValue(value) {
        const match = String(value || '').match(/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/);
        if (!match) {
            return null;
        }

        const latitude = Number(match[1]);
        const longitude = Number(match[2]);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return null;
        }

        return { latitude, longitude };
    }

    function loadLeafletAssets() {
        if (window.L?.map) {
            return Promise.resolve(window.L);
        }

        if (dashboardSiteState.leafletReady) {
            return dashboardSiteState.leafletReady;
        }

        dashboardSiteState.leafletReady = new Promise((resolve, reject) => {
            if (!document.querySelector('link[data-leaflet-css="true"]')) {
                const stylesheet = document.createElement('link');
                stylesheet.rel = 'stylesheet';
                stylesheet.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                stylesheet.dataset.leafletCss = 'true';
                document.head.appendChild(stylesheet);
            }

            const existingScript = document.querySelector('script[data-leaflet-js="true"]');
            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(window.L), { once: true });
                existingScript.addEventListener('error', () => reject(new Error('Failed to load Leaflet.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.defer = true;
            script.dataset.leafletJs = 'true';
            script.onload = () => {
                if (window.L?.map) {
                    resolve(window.L);
                    return;
                }

                reject(new Error('Leaflet loaded without the map library.'));
            };
            script.onerror = () => reject(new Error('Failed to load Leaflet.'));
            document.head.appendChild(script);
        });

        return dashboardSiteState.leafletReady;
    }

    function getPhilippinesLeafletBounds() {
        return window.L.latLngBounds(
            [philippinesMapBounds.south, philippinesMapBounds.west],
            [philippinesMapBounds.north, philippinesMapBounds.east]
        );
    }

    function createLeafletMap(container, center, zoom) {
        const map = window.L.map(container, {
            minZoom: 5,
            maxBounds: getPhilippinesLeafletBounds(),
            maxBoundsViscosity: 0.25
        }).setView(center, zoom);

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        return map;
    }

    function normalizeLeafletCenter(center) {
        if (!center) {
            return null;
        }

        return Array.isArray(center) ? center : [center.lat, center.lng];
    }

    function refreshLeafletMap(map, center = null) {
        if (!window.L?.map || !map) {
            return;
        }

        setTimeout(() => {
            map.invalidateSize();
            const normalizedCenter = normalizeLeafletCenter(center);
            if (normalizedCenter) {
                map.setView(normalizedCenter, map.getZoom(), { animate: false });
            }
        }, 0);
    }

    async function geocodePhilippineLocation(locationText, signal) {
        const params = new URLSearchParams({
            format: 'json',
            limit: '1',
            countrycodes: 'ph',
            viewbox: `${philippinesMapBounds.west},${philippinesMapBounds.north},${philippinesMapBounds.east},${philippinesMapBounds.south}`,
            bounded: '1',
            q: locationText
        });

        const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`, { signal });
        if (!response.ok) {
            throw new Error(`Location search failed: ${response.status}`);
        }

        const results = await response.json();
        const firstResult = Array.isArray(results) ? results[0] : null;
        const latitude = Number(firstResult?.lat);
        const longitude = Number(firstResult?.lon);

        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return null;
        }

        return { latitude, longitude };
    }

    function ensureDashboardSiteModalStyles() {
        if (document.getElementById('dashboardSiteModalStyles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'dashboardSiteModalStyles';
        style.textContent = `
            .dashboard-site-modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.52);
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
                z-index: 3000;
            }

            .dashboard-site-modal-overlay.active {
                display: flex;
            }

            .details-modal-overlay {
                position: fixed;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: rgba(15, 23, 42, 0.58);
                z-index: 3000;
            }

            .details-modal-overlay.active {
                display: flex;
            }

            .details-modal-dialog {
                width: min(940px, 100%);
                max-height: 88vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                background: #ffffff;
                border-radius: 18px;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            }

            .details-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                padding: 22px 24px 16px;
                border-bottom: 1px solid #e5e7eb;
            }

            .details-modal-title {
                font-size: 22px;
                font-weight: 800;
                color: #111827;
            }

            .details-modal-subtitle {
                margin-top: 4px;
                font-size: 14px;
                color: #6b7280;
            }

            .details-modal-close {
                width: 36px;
                height: 36px;
                border: none;
                border-radius: 10px;
                background: #f3f4f6;
                color: #4b5563;
                cursor: pointer;
            }

            .details-modal-body {
                padding: 24px;
                overflow-y: auto;
            }

            .details-site-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.6fr);
                gap: 18px;
            }

            .details-site-panel,
            .details-site-side,
            .details-site-card,
            .details-site-summary {
                min-width: 0;
            }

            .details-site-card,
            .details-site-summary {
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #ffffff;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            }

            .details-site-card {
                padding: 18px;
            }

            .details-site-top {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                align-items: flex-start;
                margin-bottom: 16px;
            }

            .details-site-name {
                font-size: 20px;
                font-weight: 800;
                color: #111827;
            }

            .details-site-address {
                display: flex;
                gap: 8px;
                align-items: center;
                margin-top: 7px;
                color: #6b7280;
                font-size: 13px;
            }

            .details-site-address i {
                color: #d97706;
            }

            .details-site-map {
                overflow: hidden;
                border: 1px solid #e5e7eb;
                border-radius: 14px;
                background: #f8fafc;
            }

            .details-site-map-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 12px 14px;
                border-bottom: 1px solid #e5e7eb;
            }

            .details-site-map-title {
                font-size: 13px;
                font-weight: 800;
                color: #111827;
            }

            .details-site-map-link {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #d97706;
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
            }

            .details-site-map-canvas {
                width: 100%;
                height: 260px;
            }

            .details-site-map.empty {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 190px;
                padding: 24px;
                text-align: center;
                color: #6b7280;
            }

            .details-site-map.empty > div {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .details-site-map.empty i {
                color: #d97706;
                font-size: 24px;
            }

            .details-site-map.empty strong {
                color: #111827;
            }

            .details-site-stats {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
                margin-top: 14px;
            }

            .details-site-stat {
                padding: 14px;
                border-radius: 14px;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
            }

            .details-site-stat label,
            .details-site-item label {
                display: block;
                margin-bottom: 6px;
                color: #6b7280;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .details-site-stat strong {
                color: #111827;
                font-size: 18px;
            }

            .details-site-summary {
                padding: 18px;
                height: 100%;
            }

            .details-site-list {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .details-site-item div {
                color: #111827;
                font-size: 14px;
                overflow-wrap: anywhere;
            }

            .dashboard-site-modal {
                width: min(760px, 100%);
                max-height: 88vh;
                overflow-y: auto;
                background: #ffffff;
                border-radius: 18px;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            }

            .dashboard-site-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                padding: 22px 24px 16px;
                border-bottom: 1px solid #e5e7eb;
            }

            .dashboard-site-modal-title {
                font-size: 22px;
                font-weight: 700;
                color: #111827;
            }

            .dashboard-site-modal-subtitle {
                margin-top: 4px;
                font-size: 14px;
                color: #6b7280;
            }

            .dashboard-site-modal-close {
                border: none;
                background: transparent;
                color: #6b7280;
                font-size: 24px;
                cursor: pointer;
                line-height: 1;
            }

            .dashboard-site-modal-body {
                padding: 22px 24px 24px;
            }

            .dashboard-site-details-grid,
            .dashboard-site-edit-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .dashboard-site-detail-card {
                border: 1px solid #e5e7eb;
                border-radius: 14px;
                padding: 14px 16px;
                background: #fafafa;
            }

            .dashboard-site-detail-card.full {
                grid-column: 1 / -1;
            }

            .dashboard-site-detail-label {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #6b7280;
                margin-bottom: 6px;
            }

            .dashboard-site-detail-value {
                font-size: 14px;
                color: #111827;
            }

            .dashboard-site-form-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .dashboard-site-form-group.full {
                grid-column: 1 / -1;
            }

            .dashboard-site-form-group label {
                font-size: 13px;
                font-weight: 600;
                color: #374151;
            }

            .dashboard-site-form-group input,
            .dashboard-site-form-group select {
                width: 100%;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                padding: 11px 12px;
                font-size: 14px;
                color: #111827;
                background: #ffffff;
            }

            .dashboard-site-form-group input:focus,
            .dashboard-site-form-group select:focus {
                outline: none;
                border-color: #d97706;
                box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
            }

            .dashboard-site-feedback {
                display: none;
                margin-top: 16px;
                padding: 12px 14px;
                border-radius: 10px;
                font-size: 13px;
                font-weight: 600;
            }

            .dashboard-site-feedback.show {
                display: block;
            }

            .dashboard-site-feedback.info {
                background: #eff6ff;
                color: #1d4ed8;
            }

            .dashboard-site-feedback.success {
                background: #ecfdf3;
                color: #027a48;
            }

            .dashboard-site-feedback.error {
                background: #fef2f2;
                color: #b91c1c;
            }

            .dashboard-site-modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 18px;
            }

            .dashboard-site-btn {
                border: none;
                border-radius: 10px;
                padding: 11px 16px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
            }

            .dashboard-site-btn.secondary {
                background: #f3f4f6;
                color: #1f2937;
            }

            .dashboard-site-btn.primary {
                background: #d97706;
                color: #ffffff;
            }

            .dashboard-assign-workers-top-cards {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
                margin-bottom: 18px;
            }

            .dashboard-assign-workers-top-card {
                border-radius: 14px;
                padding: 14px 16px;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
            }

            .dashboard-assign-workers-top-title {
                font-size: 12px;
                font-weight: 700;
                color: #6b7280;
                margin-bottom: 6px;
            }

            .dashboard-assign-workers-top-value {
                font-size: 22px;
                font-weight: 700;
                color: #111827;
            }

            .dashboard-assign-workers-top-value span {
                font-size: 14px;
                color: #6b7280;
            }

            .dashboard-assign-workers-search-row,
            .dashboard-assign-workers-filters {
                display: grid;
                gap: 12px;
                margin-bottom: 14px;
            }

            .dashboard-assign-workers-search-row {
                grid-template-columns: 1fr auto;
            }

            .dashboard-assign-workers-filters {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-assign-workers-search-input,
            .dashboard-assign-workers-filter-select {
                width: 100%;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                padding: 11px 12px;
                font-size: 14px;
                background: #fff;
                color: #111827;
            }

            .dashboard-assign-workers-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-height: 44vh;
                overflow-y: auto;
                padding-right: 2px;
            }

            .dashboard-assign-worker-card {
                border: 1px solid #e5e7eb;
                border-radius: 14px;
                padding: 14px;
                display: flex;
                justify-content: space-between;
                gap: 12px;
                background: #fff;
            }

            .dashboard-assign-worker-card.selected {
                border-color: #d97706;
                background: #fffaf0;
            }

            .dashboard-assign-worker-card.locked {
                opacity: 0.75;
            }

            .dashboard-assign-worker-left {
                display: flex;
                gap: 12px;
                flex: 1;
            }

            .dashboard-assign-worker-toggle {
                width: 34px;
                height: 34px;
                border-radius: 999px;
                border: none;
                background: #f3f4f6;
                color: #111827;
                cursor: pointer;
                flex-shrink: 0;
            }

            .dashboard-assign-worker-card.selected .dashboard-assign-worker-toggle {
                background: #d97706;
                color: #fff;
            }

            .dashboard-assign-worker-avatar {
                width: 46px;
                height: 46px;
                border-radius: 999px;
                background: #fde68a;
                color: #92400e;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                flex-shrink: 0;
                overflow: hidden;
            }

            .dashboard-assign-worker-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .dashboard-assign-worker-meta {
                display: flex;
                flex-direction: column;
                gap: 4px;
                min-width: 0;
                flex: 1;
            }

            .dashboard-assign-worker-name-row {
                display: flex;
                gap: 8px;
                align-items: center;
                flex-wrap: wrap;
            }

            .dashboard-assign-worker-name {
                font-size: 15px;
                font-weight: 700;
                color: #111827;
            }

            .dashboard-assign-worker-id,
            .dashboard-assign-worker-location,
            .dashboard-assign-worker-role,
            .dashboard-assign-worker-lock-note {
                font-size: 12px;
                color: #6b7280;
            }

            .dashboard-assign-worker-status {
                align-self: center;
                border-radius: 999px;
                padding: 6px 10px;
                font-size: 12px;
                font-weight: 700;
                background: #eff6ff;
                color: #1d4ed8;
            }

            .dashboard-assign-worker-status.assigned {
                background: #ecfdf3;
                color: #027a48;
            }

            .dashboard-assign-worker-status.locked {
                background: #fef3c7;
                color: #b45309;
            }

            .dashboard-assign-workers-role-editor {
                display: flex;
                flex-direction: column;
                gap: 6px;
                margin-top: 6px;
            }

            .dashboard-assign-workers-role-editor label {
                font-size: 11px;
                font-weight: 700;
                color: #6b7280;
                text-transform: uppercase;
            }

            .dashboard-assign-workers-role-select {
                border: 1px solid #d1d5db;
                border-radius: 9px;
                padding: 9px 10px;
                font-size: 13px;
            }

            .dashboard-assign-workers-empty {
                padding: 28px 18px;
                border: 1px dashed #d1d5db;
                border-radius: 14px;
                text-align: center;
                color: #6b7280;
            }

            @media (max-width: 720px) {
                .dashboard-site-modal-overlay {
                    padding: 12px;
                }

                .dashboard-site-details-grid,
                .dashboard-site-edit-grid {
                    grid-template-columns: 1fr;
                }

                .dashboard-site-detail-card.full,
                .dashboard-site-form-group.full {
                    grid-column: auto;
                }

                .details-modal-overlay {
                    padding: 12px;
                }

                .details-site-grid,
                .details-site-stats {
                    grid-template-columns: 1fr;
                }

                .dashboard-assign-workers-top-cards,
                .dashboard-assign-workers-filters,
                .dashboard-assign-workers-search-row {
                    grid-template-columns: 1fr;
                }
            }
        `;

        document.head.appendChild(style);
    }

    function ensureDashboardSiteModals() {
        if (document.getElementById('dashboardSiteDetailsModal')) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="details-modal-overlay active" id="dashboardSiteDetailsModal" aria-hidden="true" style="display:none;">
                <div class="details-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dashboardSiteDetailsTitle">
                    <div class="details-modal-header">
                        <div>
                            <div class="details-modal-title" id="dashboardSiteDetailsTitle">Site Details</div>
                            <div class="details-modal-subtitle" id="dashboardSiteDetailsSubtitle">Review site information.</div>
                        </div>
                        <button type="button" class="details-modal-close" data-dashboard-close="details" aria-label="Close site details">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="details-modal-body" id="dashboardSiteDetailsBody"></div>
                </div>
            </div>
            <div class="edit-site-overlay active" id="dashboardSiteEditModal" aria-hidden="true" style="display:none;">
                <div class="edit-site-dialog" role="dialog" aria-modal="true" aria-labelledby="dashboardSiteEditTitle">
                    <div class="edit-site-header">
                        <div>
                            <div class="edit-site-title" id="dashboardSiteEditTitle">Edit Site</div>
                            <div class="edit-site-subtitle">Update the site information and schedule.</div>
                        </div>
                        <button type="button" class="edit-site-close" data-dashboard-close="edit" aria-label="Close edit site modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form class="edit-site-form" id="dashboardSiteEditForm">
                            <input type="hidden" id="dashboardEditSiteId" name="site_id">
                            <div class="edit-site-grid">
                                <div class="form-group form-group-full">
                                    <label for="dashboardEditSiteName">Site Name</label>
                                    <input type="text" id="dashboardEditSiteName" name="site_name" required>
                                </div>
                            <div class="form-group form-group-full">
                                <label for="dashboardEditSiteLocation">Location</label>
                                <input type="text" id="dashboardEditSiteLocation" name="location" required>
                            </div>
                            <div class="form-group form-group-full">
                                <div class="site-map-ui-card edit-site-map-card">
                                    <div class="site-map-ui-header">
                                        <div>
                                            <div class="site-map-ui-title">Edit Site Pin</div>
                                            <div class="site-map-ui-subtitle">Search a Philippine location, then click or drag the pin to the exact site spot.</div>
                                        </div>
                                        <span class="site-map-ui-chip">Pin Editor</span>
                                    </div>

                                    <div class="site-map-ui-actions">
                                        <button type="button" class="site-map-search-btn" id="dashboardFindEditLocationOnMapBtn">
                                            <i class="fas fa-location-crosshairs"></i>
                                            <span>Find on Map</span>
                                        </button>
                                    </div>

                                    <div class="site-map-feedback" id="dashboardEditSiteMapFeedback" aria-live="polite"></div>

                                    <div class="site-map-stage active">
                                        <div class="site-map-frame">
                                            <div class="site-location-map" id="dashboardEditSiteLocationMap" aria-label="Editable site location map"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="dashboardEditSiteStatus">Status</label>
                                <select id="dashboardEditSiteStatus" name="status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="dashboardEditRequiredWorkers">Required Workers</label>
                                    <input type="number" id="dashboardEditRequiredWorkers" name="required_workers" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label for="dashboardEditSiteManager">Site Manager</label>
                                    <input type="text" id="dashboardEditSiteManager" name="site_manager">
                                </div>
                                <div class="form-group">
                                    <label for="dashboardEditCoordinates">Coordinates</label>
                                    <input type="text" id="dashboardEditCoordinates" name="coordinates">
                                </div>
                                <div class="form-group">
                                    <label for="dashboardEditStartDate">Start Date</label>
                                    <input type="date" id="dashboardEditStartDate" name="start_date">
                                </div>
                                <div class="form-group">
                                    <label for="dashboardEditEndDate">End Date</label>
                                    <input type="date" id="dashboardEditEndDate" name="end_date">
                                </div>
                                <div class="form-group">
                                    <label for="dashboardEditStartTime">Start Time</label>
                                    <input type="time" id="dashboardEditStartTime" name="start_time">
                                </div>
                                <div class="form-group">
                                    <label for="dashboardEditEndTime">End Time</label>
                                    <input type="time" id="dashboardEditEndTime" name="end_time">
                                </div>
                            </div>
                            <div class="edit-site-feedback" id="dashboardSiteEditFeedback"></div>
                            <div class="edit-site-footer">
                                <button type="button" class="edit-site-btn secondary" id="dashboardCancelSiteEdit">Cancel</button>
                                <button type="submit" class="edit-site-btn primary" id="dashboardSaveSiteEdit">Save Changes</button>
                            </div>
                    </form>
                </div>
            </div>
            <div class="assign-workers-overlay active" id="dashboardAssignWorkersModal" aria-hidden="true" style="display:none;">
                <div class="assign-workers-dialog" role="dialog" aria-modal="true" aria-labelledby="dashboardAssignWorkersTitle">
                    <div class="assign-workers-header">
                        <div>
                            <div class="assign-workers-title" id="dashboardAssignWorkersTitle">Assign Workers</div>
                            <div class="assign-workers-subtitle">Manage worker assignments and designate site roles</div>
                        </div>
                        <button type="button" class="assign-workers-close" data-dashboard-close="assign" aria-label="Close assign workers modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="assign-workers-top-cards">
                            <div class="assign-workers-top-card">
                                <div class="assign-workers-top-title">Assigned Workers</div>
                                <div class="assign-workers-top-value" id="dashboardAssignWorkersCountCard">0 <span>/0</span></div>
                            </div>
                            <div class="assign-workers-top-card assign-timekeeper-card">
                                <div class="assign-workers-top-title">Timekeeper</div>
                                <select id="dashboardAssignSiteTimekeeperSelect" class="assign-timekeeper-select" aria-label="Assign timekeeper">
                                    <option value="">Not assigned</option>
                                </select>
                            </div>
                            <div class="assign-workers-top-card accent-blue">
                                <div class="assign-workers-top-title">
                                    <i class="fas fa-location-dot"></i>
                                    <span>Site Location</span>
                                </div>
                                <strong id="dashboardAssignWorkersLocationCard">No location provided</strong>
                            </div>
                        </div>
                        <div class="assign-workers-search-row">
                            <input type="text" id="dashboardAssignWorkersSearch" class="assign-workers-search-input" placeholder="Search by name, ID, position, or assigned site...">
                            <button type="button" class="assign-workers-temp-btn" id="dashboardAssignWorkersTempBtn">
                                <i class="fas fa-user-plus"></i>
                                <span>Add Worker</span>
                            </button>
                        </div>
                        <div class="assign-workers-filters">
                            <select id="dashboardAssignWorkersRoleFilter" class="assign-workers-filter-select">
                                <option value="">All Positions</option>
                            </select>
                            <select id="dashboardAssignWorkersStatusFilter" class="assign-workers-filter-select">
                                <option value="">All Status</option>
                            </select>
                            <select id="dashboardAssignWorkersLocationFilter" class="assign-workers-filter-select">
                                <option value="">All Locations</option>
                            </select>
                        </div>
                        <div class="assign-workers-feedback" id="dashboardAssignWorkersFeedback"></div>
                        <div class="assign-workers-list" id="dashboardAssignWorkersList">
                            <div class="assign-workers-empty">Select a site to manage worker assignments.</div>
                        </div>
                        <div class="assign-workers-footer">
                            <button type="button" class="assign-workers-footer-btn cancel" id="dashboardCancelAssignWorkers">Cancel</button>
                            <button type="button" class="assign-workers-footer-btn save" id="dashboardSaveAssignWorkers">Save Assignments</button>
                        </div>
                    
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);
    }

    function cacheSites(sites) {
        dashboardSiteState.siteMap = new Map((Array.isArray(sites) ? sites : []).map((site) => [Number(site.SiteID), site]));
    }

    function getSiteById(siteId) {
        return dashboardSiteState.siteMap.get(Number(siteId)) || null;
    }

    function setDashboardModalOpen(modalId, isOpen) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        modal.classList.toggle('active', isOpen);
        modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        modal.style.display = isOpen ? '' : 'none';
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function setDashboardSiteEditFeedback(message, type = '') {
        const feedback = document.getElementById('dashboardSiteEditFeedback');
        if (!feedback) {
            return;
        }

        if (!message) {
            feedback.className = 'edit-site-feedback';
            feedback.textContent = '';
            return;
        }

        feedback.className = `edit-site-feedback ${type}`;
        feedback.textContent = message;
    }

    function setDashboardEditSiteMapFeedback(message, type = '') {
        const feedback = document.getElementById('dashboardEditSiteMapFeedback');
        if (!feedback) {
            return;
        }

        feedback.className = `site-map-feedback${type ? ` ${type}` : ''}`;
        feedback.textContent = message || '';
    }

    function destroyDashboardDetailsMap() {
        if (dashboardSiteState.detailsMap.marker) {
            dashboardSiteState.detailsMap.marker.off();
            dashboardSiteState.detailsMap.marker.remove();
            dashboardSiteState.detailsMap.marker = null;
        }
        if (dashboardSiteState.detailsMap.instance) {
            dashboardSiteState.detailsMap.instance.off();
            dashboardSiteState.detailsMap.instance.remove();
        }
        dashboardSiteState.detailsMap.instance = null;
    }

    async function renderDashboardDetailsMap() {
        const mapCanvas = document.getElementById('dashboardSiteDetailsMapCanvas');
        if (!mapCanvas) {
            destroyDashboardDetailsMap();
            return;
        }

        const latitude = Number(mapCanvas.dataset.latitude || NaN);
        const longitude = Number(mapCanvas.dataset.longitude || NaN);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            destroyDashboardDetailsMap();
            return;
        }

        await loadLeafletAssets();
        destroyDashboardDetailsMap();

        const center = [latitude, longitude];
        const map = createLeafletMap(mapCanvas, center, 16);

        dashboardSiteState.detailsMap.marker = window.L.marker(center).addTo(map);
        dashboardSiteState.detailsMap.instance = map;
        refreshLeafletMap(map, center);
    }

    function resetDashboardEditMapState() {
        clearTimeout(dashboardSiteState.editMap.geocodeTimer);
        if (dashboardSiteState.editMap.searchAbortController) {
            dashboardSiteState.editMap.searchAbortController.abort();
            dashboardSiteState.editMap.searchAbortController = null;
        }

        if (dashboardSiteState.editMap.marker) {
            dashboardSiteState.editMap.marker.off();
            dashboardSiteState.editMap.marker.remove();
            dashboardSiteState.editMap.marker = null;
        }

        if (dashboardSiteState.editMap.instance) {
            dashboardSiteState.editMap.instance.off();
            dashboardSiteState.editMap.instance.remove();
            dashboardSiteState.editMap.instance = null;
            const mapNode = document.getElementById('dashboardEditSiteLocationMap');
            if (mapNode) {
                mapNode.innerHTML = '';
            }
        }

        setDashboardEditSiteMapFeedback('');
    }

    function updateDashboardEditCoordinateInput(latitude, longitude) {
        const input = document.getElementById('dashboardEditCoordinates');
        if (!input) {
            return;
        }

        input.value = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    }

    async function ensureDashboardEditSiteLocationMap() {
        const mapNode = document.getElementById('dashboardEditSiteLocationMap');
        if (!mapNode) {
            return null;
        }

        if (dashboardSiteState.editMap.instance) {
            refreshLeafletMap(dashboardSiteState.editMap.instance);
            return dashboardSiteState.editMap.instance;
        }

        const center = dashboardSiteState.editMap.defaultCenter;

        await loadLeafletAssets();

        const map = createLeafletMap(mapNode, center, dashboardSiteState.editMap.defaultZoom);

        map.on('click', (event) => {
            placeDashboardEditSiteMarker(event.latlng.lat, event.latlng.lng, true);
            setDashboardEditSiteMapFeedback('Pin moved. You can drag it again if needed.', 'success');
        });

        dashboardSiteState.editMap.instance = map;
        refreshLeafletMap(map, center);
        return map;
    }

    async function placeDashboardEditSiteMarker(latitude, longitude, centerMap = false) {
        const map = await ensureDashboardEditSiteLocationMap();
        if (!map) {
            return;
        }

        const position = [latitude, longitude];

        if (!dashboardSiteState.editMap.marker) {
            dashboardSiteState.editMap.marker = window.L.marker(position, {
                draggable: true
            }).addTo(map);
            dashboardSiteState.editMap.marker.on('dragend', (event) => {
                const markerPosition = event.target.getLatLng();
                updateDashboardEditCoordinateInput(markerPosition.lat, markerPosition.lng);
                setDashboardEditSiteMapFeedback('Pin updated from the map.', 'success');
            });
        } else {
            dashboardSiteState.editMap.marker.setLatLng(position);
        }

        if (centerMap) {
            map.setView(position, 16);
        }

        updateDashboardEditCoordinateInput(latitude, longitude);
        refreshLeafletMap(map, position);
    }

    async function syncDashboardEditMapToCoordinates() {
        const parsedCoordinates = parseCoordinateValue(document.getElementById('dashboardEditCoordinates')?.value || '');
        if (!parsedCoordinates) {
            return;
        }

        await placeDashboardEditSiteMarker(parsedCoordinates.latitude, parsedCoordinates.longitude, true);
    }

    async function geocodeDashboardEditSiteLocation() {
        const locationText = document.getElementById('dashboardEditSiteLocation')?.value.trim() || '';
        const findButton = document.getElementById('dashboardFindEditLocationOnMapBtn');
        if (!locationText) {
            setDashboardEditSiteMapFeedback('Enter a location first so we can search it on the map.', 'error');
            return;
        }

        if (dashboardSiteState.editMap.searchAbortController) {
            dashboardSiteState.editMap.searchAbortController.abort();
        }

        const abortController = new AbortController();
        dashboardSiteState.editMap.searchAbortController = abortController;

        if (findButton) {
            findButton.disabled = true;
        }

        setDashboardEditSiteMapFeedback('Searching location and loading map...', 'info');

        try {
            await loadLeafletAssets();
            const result = await geocodePhilippineLocation(locationText, abortController.signal);
            if (abortController.signal.aborted) {
                return;
            }

            if (!result) {
                setDashboardEditSiteMapFeedback('No Philippine location match found. Try adding city or province.', 'error');
                return;
            }

            await placeDashboardEditSiteMarker(result.latitude, result.longitude, true);
            setDashboardEditSiteMapFeedback('Location found. Drag the pin to place the exact site.', 'success');
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Dashboard edit site map search failed:', error);
                setDashboardEditSiteMapFeedback('Could not load the map location right now.', 'error');
            }
        } finally {
            if (dashboardSiteState.editMap.searchAbortController === abortController) {
                dashboardSiteState.editMap.searchAbortController = null;
            }
            if (findButton) {
                findButton.disabled = false;
            }
        }
    }

    function closeDashboardSiteDetailsModal() {
        destroyDashboardDetailsMap();
        setDashboardModalOpen('dashboardSiteDetailsModal', false);
        const body = document.getElementById('dashboardSiteDetailsBody');
        if (body) {
            body.innerHTML = '';
        }
    }

    function closeDashboardSiteEditModal() {
        setDashboardModalOpen('dashboardSiteEditModal', false);
        document.getElementById('dashboardSiteEditForm')?.reset();
        resetDashboardEditMapState();
        setDashboardSiteEditFeedback('');
        dashboardSiteState.activeSiteId = null;
        dashboardSiteState.saveInProgress = false;
    }

    function buildDashboardSiteDetailsMarkup(site) {
        const currentWorkers = Number(site.Current_Workers || 0);
        const requiredWorkers = Number(site.Required_Workers || 0);
        const capacity = requiredWorkers > 0 ? Math.min(100, Math.round((currentWorkers / requiredWorkers) * 100)) : 0;
        const parsedCoordinates = parseCoordinateValue(site.Coordinates || '');
        const mapQuery = parsedCoordinates
            ? `${parsedCoordinates.latitude.toFixed(6)},${parsedCoordinates.longitude.toFixed(6)}`
            : '';
        const openMapUrl = parsedCoordinates
            ? `https://www.openstreetmap.org/?mlat=${parsedCoordinates.latitude.toFixed(6)}&mlon=${parsedCoordinates.longitude.toFixed(6)}#map=16/${parsedCoordinates.latitude.toFixed(6)}/${parsedCoordinates.longitude.toFixed(6)}`
            : '';
        const detailsMap = parsedCoordinates ? `
            <div class="details-site-map">
                <div class="details-site-map-header">
                    <div class="details-site-map-title">Pinned Site Location</div>
                    <a class="details-site-map-link" href="${openMapUrl}" target="_blank" rel="noopener">
                        <i class="fas fa-up-right-from-square"></i>
                        <span>Open Map</span>
                    </a>
                </div>
                <div
                    id="dashboardSiteDetailsMapCanvas"
                    class="details-site-map-canvas"
                    data-latitude="${parsedCoordinates.latitude}"
                    data-longitude="${parsedCoordinates.longitude}"
                    aria-label="Detailed map for ${escapeHtml(site.Site_Name || 'site')}"
                ></div>
            </div>
        ` : `
            <div class="details-site-map empty">
                <div>
                    <i class="fas fa-map-pin"></i>
                    <strong>No pinned map location yet</strong>
                    <span>Add coordinates from the site pin map to show this preview.</span>
                </div>
            </div>
        `;

        return `
            <div class="details-site-grid">
                <div class="details-site-panel">
                    <div class="details-site-card">
                        <div class="details-site-top">
                            <div>
                                <div class="details-site-name">${escapeHtml(site.Site_Name || 'Unnamed Site')}</div>
                                <div class="details-site-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${escapeHtml(site.Location || 'No location provided')}</span>
                                </div>
                            </div>
                            <span class="site-status-badge ${escapeHtml(String(site.Status || 'Active').toLowerCase())}">${escapeHtml(site.Status || 'Active')}</span>
                        </div>
                        ${detailsMap}
                    </div>

                    <div class="details-site-stats">
                        <div class="details-site-stat">
                            <label>Assigned Workers</label>
                            <strong>${currentWorkers}</strong>
                        </div>
                        <div class="details-site-stat">
                            <label>Required Workers</label>
                            <strong>${requiredWorkers}</strong>
                        </div>
                        <div class="details-site-stat">
                            <label>Capacity Filled</label>
                            <strong>${capacity}%</strong>
                        </div>
                        <div class="details-site-stat">
                            <label>Schedule Length</label>
                            <strong>${escapeHtml(site.Total_Hours || '--')}</strong>
                        </div>
                    </div>
                </div>

                <div class="details-site-side">
                    <div class="details-site-summary">
                        <div class="details-site-list">
                            <div class="details-site-item">
                                <label>START DATE</label>
                                <div>${escapeHtml(formatDateLabel(site.Start_Date))}</div>
                            </div>
                            <div class="details-site-item">
                                <label>WORKING HOURS</label>
                                <div>${escapeHtml(site.Start_Time || 'Not set')} - ${escapeHtml(site.End_Time || 'Not set')}</div>
                            </div>
                            <div class="details-site-item">
                                <label>SITE MANAGER</label>
                                <div>${escapeHtml(site.Site_Manager || 'Not assigned')}</div>
                            </div>
                            <div class="details-site-item">
                                <label>TIMEKEEPER</label>
                                <div>${escapeHtml(site.Timekeeper || 'Not assigned')}</div>
                            </div>
                            <div class="details-site-item">
                                <label>COORDINATES</label>
                                <div>${escapeHtml(site.Coordinates || 'Not set')}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function openDashboardSiteDetails(siteId) {
        const site = getSiteById(siteId);
        const detailsBody = document.getElementById('dashboardSiteDetailsBody');
        const detailsTitle = document.getElementById('dashboardSiteDetailsTitle');
        const detailsSubtitle = document.getElementById('dashboardSiteDetailsSubtitle');

        if (!site || !detailsBody) {
            return;
        }

        if (detailsTitle) {
            detailsTitle.textContent = site.Site_Name || 'Site Details';
        }
        if (detailsSubtitle) {
            detailsSubtitle.textContent = site.Location || 'Review site information.';
        }

        detailsBody.innerHTML = buildDashboardSiteDetailsMarkup(site);
        setDashboardModalOpen('dashboardSiteDetailsModal', true);
        renderDashboardDetailsMap().catch((error) => {
            console.error('Failed to render dashboard site details map:', error);
        });
    }

    function openDashboardSiteEdit(siteId) {
        const site = getSiteById(siteId);
        if (!site) {
            return;
        }

        dashboardSiteState.activeSiteId = Number(siteId);
        document.getElementById('dashboardEditSiteId').value = site.SiteID || '';
        document.getElementById('dashboardEditSiteName').value = site.Site_Name || '';
        document.getElementById('dashboardEditSiteLocation').value = site.Location || '';
        document.getElementById('dashboardEditSiteStatus').value = String(site.Status || 'Active').toLowerCase() === 'inactive' ? 'Inactive' : 'Active';
        document.getElementById('dashboardEditRequiredWorkers').value = site.Required_Workers || '';
        document.getElementById('dashboardEditSiteManager').value = site.Site_Manager || '';
        document.getElementById('dashboardEditCoordinates').value = site.Coordinates || '';
        document.getElementById('dashboardEditStartDate').value = site.Start_Date || '';
        document.getElementById('dashboardEditEndDate').value = site.End_Date || '';
        document.getElementById('dashboardEditStartTime').value = site.Start_Time || '';
        document.getElementById('dashboardEditEndTime').value = site.End_Time || '';
        resetDashboardEditMapState();
        setDashboardSiteEditFeedback('');
        setDashboardEditSiteMapFeedback(site.Coordinates ? 'Saved pin loaded. Drag it or click the map to adjust.' : 'Search a location or click the map to place a new pin.', site.Coordinates ? 'info' : '');
        setDashboardModalOpen('dashboardSiteEditModal', true);
        ensureDashboardEditSiteLocationMap()
            .then(() => {
                if (site.Coordinates) {
                    return syncDashboardEditMapToCoordinates();
                }

                return geocodeDashboardEditSiteLocation();
            })
            .catch((error) => {
                console.error('Failed to prepare dashboard edit site map:', error);
                setDashboardEditSiteMapFeedback('Could not load the map right now.', 'error');
            });
    }

    async function saveDashboardSiteEdit(event) {
        event.preventDefault();
        if (dashboardSiteState.saveInProgress) {
            return;
        }

        const siteId = Number(document.getElementById('dashboardEditSiteId')?.value || 0);
        const siteName = document.getElementById('dashboardEditSiteName')?.value.trim() || '';
        const location = document.getElementById('dashboardEditSiteLocation')?.value.trim() || '';
        const coordinates = document.getElementById('dashboardEditCoordinates')?.value.trim() || '';
        const status = document.getElementById('dashboardEditSiteStatus')?.value || 'Active';
        const requiredWorkers = Number(document.getElementById('dashboardEditRequiredWorkers')?.value || 0);
        const siteManager = document.getElementById('dashboardEditSiteManager')?.value.trim() || '';
        const startDate = document.getElementById('dashboardEditStartDate')?.value || '';
        const endDate = document.getElementById('dashboardEditEndDate')?.value || '';
        const startTime = document.getElementById('dashboardEditStartTime')?.value || '';
        const endTime = document.getElementById('dashboardEditEndTime')?.value || '';

        if (!siteId || !siteName || !location || requiredWorkers <= 0) {
            setDashboardSiteEditFeedback('Complete the required fields first.', 'error');
            return;
        }

        if (coordinates && !parseCoordinateValue(coordinates)) {
            setDashboardSiteEditFeedback('Coordinates must use the format "latitude, longitude".', 'error');
            return;
        }

        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            setDashboardSiteEditFeedback('End date cannot be earlier than the start date.', 'error');
            return;
        }

        if (startTime && endTime && startTime >= endTime) {
            setDashboardSiteEditFeedback('End time must be later than the start time.', 'error');
            return;
        }

        dashboardSiteState.saveInProgress = true;
        setDashboardSiteEditFeedback('Saving site changes...', 'info');

        try {
            const payload = {
                site_id: siteId,
                site_name: siteName,
                location,
                coordinates,
                status,
                required_workers: requiredWorkers,
                site_manager: siteManager,
                start_date: startDate || null,
                end_date: endDate || null,
                start_time: startTime || null,
                end_time: endTime || null
            };

            const result = await fetch('../api/update_site.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await result.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to update site.');
            }

            const localSite = getSiteById(siteId);
            if (localSite) {
                localSite.Site_Name = siteName;
                localSite.Location = location;
                localSite.Status = status;
                localSite.Required_Workers = requiredWorkers;
                localSite.Site_Manager = siteManager;
                localSite.Coordinates = coordinates;
                localSite.Start_Date = startDate || '';
                localSite.End_Date = endDate || '';
                localSite.Start_Time = startTime || '';
                localSite.End_Time = endTime || '';
            }

            setDashboardSiteEditFeedback('');
            window.showCrudResultModal?.(
                true,
                data.message || 'Site updated successfully. Refreshing the dashboard view...',
                'Site Update',
                () => window.location.reload()
            );
        } catch (error) {
            console.error('Dashboard site update failed:', error);
            setDashboardSiteEditFeedback('');
            window.showCrudResultModal?.(
                false,
                error.message || 'Could not save the site changes.',
                'Site Update'
            );
        } finally {
            dashboardSiteState.saveInProgress = false;
        }
    }

    function setDashboardAssignFeedback(message, type = '') {
        const feedback = document.getElementById('dashboardAssignWorkersFeedback');
        if (!feedback) {
            return;
        }

        if (!message) {
            feedback.className = 'assign-workers-feedback';
            feedback.textContent = '';
            return;
        }

        feedback.className = `assign-workers-feedback ${type}`;
        feedback.textContent = message;
    }

    function closeDashboardAssignWorkersModal() {
        setDashboardModalOpen('dashboardAssignWorkersModal', false);
        dashboardSiteState.assignmentModal.siteId = null;
        dashboardSiteState.assignmentModal.workers = [];
        dashboardSiteState.assignmentModal.selectedWorkerIds = new Set();
        dashboardSiteState.assignmentModal.initialWorkerIds = new Set();
        dashboardSiteState.assignmentModal.roleByWorkerId = new Map();
        dashboardSiteState.assignmentModal.initialRoleByWorkerId = new Map();
        dashboardSiteState.assignmentModal.searchTerm = '';
        dashboardSiteState.assignmentModal.statusFilter = '';
        dashboardSiteState.assignmentModal.roleFilter = '';
        dashboardSiteState.assignmentModal.locationFilter = '';
        dashboardSiteState.assignmentModal.timekeepers = [];
        dashboardSiteState.assignmentModal.timekeeperUserId = null;
        dashboardSiteState.assignmentModal.initialTimekeeperUserId = null;
        dashboardSiteState.assignmentModal.saving = false;

        const timekeeperSelect = document.getElementById('dashboardAssignSiteTimekeeperSelect');
        if (timekeeperSelect) {
            timekeeperSelect.innerHTML = '<option value="">Not assigned</option>';
            timekeeperSelect.value = '';
        }

        const list = document.getElementById('dashboardAssignWorkersList');
        if (list) {
            list.innerHTML = '<div class="dashboard-assign-workers-empty">Select a site to manage worker assignments.</div>';
        }

        const search = document.getElementById('dashboardAssignWorkersSearch');
        const roleFilter = document.getElementById('dashboardAssignWorkersRoleFilter');
        const statusFilter = document.getElementById('dashboardAssignWorkersStatusFilter');
        const locationFilter = document.getElementById('dashboardAssignWorkersLocationFilter');
        if (search) search.value = '';
        if (roleFilter) roleFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        if (locationFilter) locationFilter.value = '';
        setDashboardAssignFeedback('');
    }

    function normalizeWorkers(rows) {
        const workersById = new Map();

        (rows || []).forEach((row) => {
            const workerId = Number(row.WorkerID);
            if (!workerId) {
                return;
            }

            if (!workersById.has(workerId)) {
                workersById.set(workerId, {
                    id: workerId,
                    name: row.full_name || `${row.First_Name || ''} ${row.Last_Name || ''}`.trim() || `Worker ${workerId}`,
                    status: row.worker_status || 'Active',
                    approvalStatus: row.approval_status || row.approval?.Approval_Status || 'Pending',
                    isApproved: (row.approval_status || row.approval?.Approval_Status) === 'Approved',
                    photoPath: row.photo_path || '',
                    address: [row.street_address, row.city, row.state_province, row.postal_code, row.country]
                        .map((part) => String(part || '').trim())
                        .filter(Boolean)
                        .join(', '),
                    assignments: []
                });
            }

            if (row.SiteID) {
                workersById.get(workerId).assignments.push({
                    siteId: Number(row.SiteID),
                    siteName: row.Site_Name || 'Unknown Site',
                    position: row.position || ''
                });
            }
        });

        return Array.from(workersById.values());
    }

    function getSelectedDashboardSite() {
        return getSiteById(dashboardSiteState.assignmentModal.siteId);
    }

    function workerHasExternalAssignment(worker, siteId) {
        return worker.assignments.some((assignment) => assignment.siteId !== siteId);
    }

    function getWorkerExternalAssignment(worker, siteId) {
        return worker.assignments.find((assignment) => assignment.siteId !== siteId) || null;
    }

    function getWorkerDisplayLocation(worker, siteId) {
        return worker.address || 'Address not provided';
    }

    function getWorkerAssignmentLabel(worker, siteId) {
        const currentAssignment = worker.assignments.find((assignment) => assignment.siteId === siteId) || null;
        if (currentAssignment?.position) {
            return currentAssignment.position;
        }

        const firstPosition = worker.assignments.find((assignment) => assignment.position)?.position;
        return firstPosition || 'Construction Worker';
    }

    function buildRoleOptions() {
        const roleSet = new Set([
            'Construction Worker',
            'Electrician',
            'Plumber',
            'Mason',
            'Carpenter',
            'Welder',
            'Heavy Equipment Operator',
            'Site Foreman',
            'Safety Officer'
        ]);

        dashboardSiteState.assignmentModal.workers.forEach((worker) => {
            worker.assignments.forEach((assignment) => {
                if (assignment.position) {
                    roleSet.add(assignment.position);
                }
            });
        });

        roleSet.delete('Site Timekeeper');

        return Array.from(roleSet).sort((a, b) => a.localeCompare(b));
    }

    function populateDashboardAssignmentFilters() {
        const roleFilter = document.getElementById('dashboardAssignWorkersRoleFilter');
        const statusFilter = document.getElementById('dashboardAssignWorkersStatusFilter');
        const locationFilter = document.getElementById('dashboardAssignWorkersLocationFilter');
        const roles = buildRoleOptions();
        const locationSet = new Set();

        dashboardSiteState.assignmentModal.workers.forEach((worker) => {
            const location = getWorkerDisplayLocation(worker, dashboardSiteState.assignmentModal.siteId);
            if (location) {
                locationSet.add(location);
            }
        });

        if (roleFilter) {
            const currentValue = dashboardSiteState.assignmentModal.roleFilter;
            roleFilter.innerHTML = '<option value="">All Positions</option>' + roles.map((role) => `<option value="${escapeHtml(role)}">${escapeHtml(role)}</option>`).join('');
            roleFilter.value = currentValue;
        }

        if (statusFilter) {
            const currentValue = dashboardSiteState.assignmentModal.statusFilter;
            statusFilter.innerHTML = `
                <option value="">All Status</option>
                <option value="assigned">Assigned To This Site</option>
                <option value="assigned_elsewhere">Assigned To Another Site</option>
                <option value="available">Available</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="OnLeave">On Leave</option>
            `;
            statusFilter.value = currentValue;
        }

        if (locationFilter) {
            const currentValue = dashboardSiteState.assignmentModal.locationFilter;
            locationFilter.innerHTML = '<option value="">All Locations</option>' + Array.from(locationSet).sort((a, b) => a.localeCompare(b)).map((location) => `<option value="${escapeHtml(location)}">${escapeHtml(location)}</option>`).join('');
            locationFilter.value = currentValue;
        }
    }

    function getWorkerStatusLabel(worker, isAssigned, role) {
        if (isAssigned) {
            return role || 'Assigned';
        }

        if (!worker.isApproved) {
            return 'Pending approval';
        }

        if (workerHasExternalAssignment(worker, dashboardSiteState.assignmentModal.siteId)) {
            const externalAssignment = getWorkerExternalAssignment(worker, dashboardSiteState.assignmentModal.siteId);
            return `Assigned to ${externalAssignment?.siteName || 'another site'}`;
        }

        const normalizedStatus = String(worker.status || '').toLowerCase();
        if (normalizedStatus === 'active') {
            return 'Available';
        }

        return worker.status || 'Unknown';
    }

    function getFilteredDashboardAssignmentWorkers() {
        const modalState = dashboardSiteState.assignmentModal;
        const searchTerm = modalState.searchTerm.trim().toLowerCase();

        return modalState.workers.filter((worker) => {
            const isAssigned = modalState.selectedWorkerIds.has(worker.id);
            const isLocked = workerHasExternalAssignment(worker, modalState.siteId) && !isAssigned;
            const role = modalState.roleByWorkerId.get(worker.id) || getWorkerAssignmentLabel(worker, modalState.siteId);
            const location = getWorkerDisplayLocation(worker, modalState.siteId);
            const assignmentSiteNames = worker.assignments.map((assignment) => assignment.siteName).join(' ');
            const matchesSearch = !searchTerm || [
                worker.name,
                String(worker.id),
                role,
                location,
                assignmentSiteNames
            ].join(' ').toLowerCase().includes(searchTerm);

            const workerStatus = String(worker.status || '');
            let matchesStatus = true;
            if (modalState.statusFilter === 'assigned') {
                matchesStatus = isAssigned;
            } else if (modalState.statusFilter === 'assigned_elsewhere') {
                matchesStatus = isLocked;
            } else if (modalState.statusFilter === 'available') {
                matchesStatus = !isAssigned && !isLocked && workerStatus.toLowerCase() === 'active';
            } else if (modalState.statusFilter) {
                matchesStatus = workerStatus === modalState.statusFilter;
            }

            const matchesRole = !modalState.roleFilter || role === modalState.roleFilter;
            const matchesLocation = !modalState.locationFilter || location === modalState.locationFilter;

            return matchesSearch && matchesStatus && matchesRole && matchesLocation;
        }).sort((a, b) => {
            const aAssigned = modalState.selectedWorkerIds.has(a.id) ? 0 : 1;
            const bAssigned = modalState.selectedWorkerIds.has(b.id) ? 0 : 1;
            if (aAssigned !== bAssigned) {
                return aAssigned - bAssigned;
            }
            return a.name.localeCompare(b.name);
        });
    }

    function renderDashboardAssignmentSummary() {
        const site = getSelectedDashboardSite();
        if (!site) {
            return;
        }

        const selectedWorkers = dashboardSiteState.assignmentModal.workers.filter((worker) => dashboardSiteState.assignmentModal.selectedWorkerIds.has(worker.id));
        const requiredWorkers = Number(site.Required_Workers || 0);
        const countCard = document.getElementById('dashboardAssignWorkersCountCard');
        const foremanCard = document.getElementById('dashboardAssignWorkersForemanCard');
        const locationCard = document.getElementById('dashboardAssignWorkersLocationCard');
        const title = document.getElementById('dashboardAssignWorkersTitle');

        const foreman = selectedWorkers.find((worker) => String(dashboardSiteState.assignmentModal.roleByWorkerId.get(worker.id) || '').toLowerCase().includes('foreman'));

        if (title) {
            title.textContent = `Assign Workers to ${site.Site_Name || 'Selected Site'}`;
        }
        if (countCard) {
            countCard.innerHTML = `${selectedWorkers.length} <span>/${requiredWorkers}</span>`;
        }
        if (foremanCard) {
            foremanCard.textContent = foreman?.name || 'Not assigned';
        }
        if (locationCard) {
            locationCard.textContent = site.Location || 'No location provided';
        }
    }

    function getDashboardTimekeeperDisplayLabel(timekeeper) {
        const name = String(timekeeper?.name || '').trim();
        if (name) {
            return name;
        }
        return String(timekeeper?.email || '').trim() || 'Unknown User';
    }

    async function loadDashboardTimekeeperOptions() {
        try {
            const response = await fetch('../api/get_timekeepers.php');
            const timekeeperData = await response.json();
            if (!response.ok || timekeeperData.success === false) {
                throw new Error(timekeeperData.message || 'Failed to load timekeepers');
            }
            dashboardSiteState.assignmentModal.timekeepers = Array.isArray(timekeeperData.timekeepers)
                ? timekeeperData.timekeepers
                : [];
            dashboardSiteState.assignmentModal.timekeeperLoadState = 'ok';
            return { ok: true, loadState: 'ok' };
        } catch (error) {
            console.error('Failed to load timekeeper accounts:', error);
            dashboardSiteState.assignmentModal.timekeepers = [];
            dashboardSiteState.assignmentModal.timekeeperLoadState = 'error';
            return { ok: false, message: error.message || 'Could not load Timekeeper accounts' };
        }
    }

    function renderDashboardTimekeeperSelect(loadState = 'ok') {
        const select = document.getElementById('dashboardAssignSiteTimekeeperSelect');
        if (!select) {
            return;
        }

        const modalState = dashboardSiteState.assignmentModal;
        const selectedId = Number(modalState.timekeeperUserId || 0);
        const options = ['<option value="">Not assigned</option>'];

        if (loadState === 'error') {
            options.push('<option value="" disabled>Unable to load</option>');
        } else if (!modalState.timekeepers.length) {
            options.push('<option value="" disabled>No timekeepers</option>');
        } else {
            modalState.timekeepers.forEach((timekeeper) => {
                const id = Number(timekeeper.id);
                if (!id) {
                    return;
                }
                const selected = id === selectedId ? ' selected' : '';
                options.push(
                    `<option value="${id}"${selected}>${escapeHtml(getDashboardTimekeeperDisplayLabel(timekeeper))}</option>`
                );
            });
        }

        select.innerHTML = options.join('');
        select.value = selectedId > 0 ? String(selectedId) : '';
        select.disabled = loadState === 'error';
    }

    function renderDashboardAssignmentWorkers() {
        const list = document.getElementById('dashboardAssignWorkersList');
        if (!list) {
            return;
        }

        renderDashboardAssignmentSummary();
        populateDashboardAssignmentFilters();

        const siteId = dashboardSiteState.assignmentModal.siteId;
        const roleOptions = buildRoleOptions();
        const workers = getFilteredDashboardAssignmentWorkers();

        if (workers.length === 0) {
            list.innerHTML = '<div class="assign-workers-empty">No workers match the current filters.</div>';
            return;
        }

        list.innerHTML = workers.map((worker) => {
            const isAssigned = dashboardSiteState.assignmentModal.selectedWorkerIds.has(worker.id);
            const isLocked = !isAssigned && (!worker.isApproved || workerHasExternalAssignment(worker, siteId));
            const externalAssignment = getWorkerExternalAssignment(worker, siteId);
            const role = dashboardSiteState.assignmentModal.roleByWorkerId.get(worker.id) || getWorkerAssignmentLabel(worker, siteId);
            const location = getWorkerDisplayLocation(worker, siteId);
            const statusLabel = getWorkerStatusLabel(worker, isAssigned, role);
            const avatar = worker.photoPath
                ? `<img src="../${escapeHtml(String(worker.photoPath).replace(/^\/+/, ''))}" alt="${escapeHtml(worker.name)}">`
                : escapeHtml((worker.name || 'W').charAt(0).toUpperCase());

            const roleSelect = isAssigned ? `
                <div class="assign-workers-role-editor">
                    <label for="dashboard-worker-role-${worker.id}">Site role</label>
                    <select class="assign-workers-role-select" id="dashboard-worker-role-${worker.id}" data-dashboard-worker-role-select="${worker.id}">
                        ${roleOptions.map((option) => `<option value="${escapeHtml(option)}" ${option === role ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
                    </select>
                </div>
            ` : '';

            return `
                <div class="assign-worker-card${isAssigned ? ' selected' : ''}${isLocked ? ' locked' : ''}" data-dashboard-worker-card="${worker.id}">
                    <div class="assign-worker-card-left">
                        <button type="button" class="assign-worker-toggle" data-dashboard-worker-toggle="${worker.id}" ${isLocked ? 'disabled' : ''}>
                            ${isAssigned ? '<i class="fas fa-check"></i>' : '<i class="fas fa-plus"></i>'}
                        </button>
                        <div class="assign-worker-avatar">${avatar}</div>
                        <div class="assign-worker-meta">
                            <div class="assign-worker-name-row">
                                <div class="assign-worker-name">${escapeHtml(worker.name)}</div>
                            </div>
                            <div class="assign-worker-role">${escapeHtml(role)}</div>
                            <div class="assign-worker-location">${escapeHtml(location)}</div>
                            ${isLocked ? `<div class="assign-worker-lock-note">${worker.isApproved ? `Already assigned to ${escapeHtml(externalAssignment?.siteName || 'another site')}` : 'Employee must be approved before site assignment'}</div>` : ''}
                            ${roleSelect}
                        </div>
                    </div>
                    <div class="assign-worker-card-right">
                        <span class="assign-worker-status${isAssigned ? ' assigned' : ''}${isLocked ? ' locked' : ''}">${escapeHtml(statusLabel)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function openDashboardAssignWorkers(siteId) {
        const site = getSiteById(siteId);
        const list = document.getElementById('dashboardAssignWorkersList');
        if (!site || !list) {
            return;
        }

        dashboardSiteState.assignmentModal.siteId = Number(siteId);
        setDashboardModalOpen('dashboardAssignWorkersModal', true);
        list.innerHTML = '<div class="assign-workers-empty">Loading workers...</div>';
        setDashboardAssignFeedback('');

        const timekeeperUserId = Number(site.Timekeeper_UserID || 0) || null;
        dashboardSiteState.assignmentModal.timekeeperUserId = timekeeperUserId;
        dashboardSiteState.assignmentModal.initialTimekeeperUserId = timekeeperUserId;

        const timekeeperLoad = await loadDashboardTimekeeperOptions();
        renderDashboardTimekeeperSelect(timekeeperLoad.ok ? 'ok' : 'error');
        if (!timekeeperLoad.ok) {
            setDashboardAssignFeedback(
                timekeeperLoad.message || 'Could not load Timekeeper accounts. Worker assignment is still available.',
                'error'
            );
        }

        try {
            const employeeResponse = await fetch('../api/get_employees.php');
            const data = await employeeResponse.json();
            if (!data.success) {
                throw new Error(data.message || 'Could not load workers.');
            }

            const workers = normalizeWorkers(data.employees || []);
            dashboardSiteState.assignmentModal.workers = workers;
            dashboardSiteState.assignmentModal.selectedWorkerIds = new Set();
            dashboardSiteState.assignmentModal.initialWorkerIds = new Set();
            dashboardSiteState.assignmentModal.roleByWorkerId = new Map();
            dashboardSiteState.assignmentModal.initialRoleByWorkerId = new Map();

            workers.forEach((worker) => {
                const currentAssignment = worker.assignments.find((assignment) => assignment.siteId === Number(siteId));
                if (currentAssignment) {
                    dashboardSiteState.assignmentModal.selectedWorkerIds.add(worker.id);
                    dashboardSiteState.assignmentModal.initialWorkerIds.add(worker.id);
                    const role = currentAssignment.position || getWorkerAssignmentLabel(worker, siteId);
                    dashboardSiteState.assignmentModal.roleByWorkerId.set(worker.id, role);
                    dashboardSiteState.assignmentModal.initialRoleByWorkerId.set(worker.id, role);
                }
            });

            if (timekeeperLoad.ok) {
                setDashboardAssignFeedback('');
            }
            renderDashboardAssignmentWorkers();
        } catch (error) {
            console.error('Failed to load workers for dashboard assignment modal:', error);
            list.innerHTML = '<div class="assign-workers-empty">Could not load workers for this site.</div>';
            if (timekeeperLoad.ok) {
                setDashboardAssignFeedback(error.message || 'Could not load workers for this site.', 'error');
            }
        }
    }

    async function saveDashboardAssignments() {
        const site = getSelectedDashboardSite();
        if (!site || dashboardSiteState.assignmentModal.saving) {
            return;
        }

        dashboardSiteState.assignmentModal.saving = true;
        setDashboardAssignFeedback('Saving assignments...', 'info');

        try {
            const selectedIds = Array.from(dashboardSiteState.assignmentModal.selectedWorkerIds);
            const initialIds = Array.from(dashboardSiteState.assignmentModal.initialWorkerIds);
            const toAdd = selectedIds.filter((workerId) => !dashboardSiteState.assignmentModal.initialWorkerIds.has(workerId));
            const toRemove = initialIds.filter((workerId) => !dashboardSiteState.assignmentModal.selectedWorkerIds.has(workerId));
            const roleUpdates = selectedIds.filter((workerId) => {
                const currentRole = dashboardSiteState.assignmentModal.roleByWorkerId.get(workerId) || '';
                const initialRole = dashboardSiteState.assignmentModal.initialRoleByWorkerId.get(workerId) || '';
                return currentRole && currentRole !== initialRole;
            });

            const currentTimekeeperUserId = dashboardSiteState.assignmentModal.timekeeperUserId;
            const initialTimekeeperUserId = dashboardSiteState.assignmentModal.initialTimekeeperUserId;
            if (Number(currentTimekeeperUserId || 0) !== Number(initialTimekeeperUserId || 0)) {
                const timekeeperResult = await fetch('../api/assign_site_timekeeper.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        site_id: Number(site.SiteID),
                        user_id: Number(currentTimekeeperUserId || 0)
                    })
                });
                const timekeeperPayload = await timekeeperResult.json();
                if (!timekeeperPayload.success) {
                    throw new Error(timekeeperPayload.message || 'Failed to assign site timekeeper');
                }
            }

            for (const workerId of toAdd) {
                const result = await fetch('../api/assign_worker.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ workerId, siteId: Number(site.SiteID) })
                });
                const data = await result.json();
                if (!data.success) {
                    throw new Error(data.message || `Failed to assign worker ${workerId}`);
                }
            }

            for (const workerId of roleUpdates) {
                const role = dashboardSiteState.assignmentModal.roleByWorkerId.get(workerId) || '';
                const result = await fetch('../api/update_employee.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        employee_id: workerId,
                        position: role,
                        site_id: Number(site.SiteID)
                    })
                });
                const data = await result.json();
                if (!data.success) {
                    throw new Error(data.message || `Failed to update role for worker ${workerId}`);
                }
            }

            for (const workerId of toRemove) {
                const result = await fetch('../api/remove_worker_assignment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        worker_id: workerId,
                        site_id: Number(site.SiteID)
                    })
                });
                const data = await result.json();
                if (!data.success) {
                    throw new Error(data.message || `Failed to remove worker ${workerId}`);
                }
            }

            setDashboardAssignFeedback('');
            window.showCrudResultModal?.(
                true,
                'Assignments saved successfully. Refreshing the dashboard view...',
                'Worker Assignment',
                () => window.location.reload()
            );
        } catch (error) {
            console.error('Failed to save dashboard assignments:', error);
            setDashboardAssignFeedback('');
            window.showCrudResultModal?.(
                false,
                error.message || 'Could not save assignments.',
                'Worker Assignment'
            );
        } finally {
            dashboardSiteState.assignmentModal.saving = false;
        }
    }

    function bindDashboardSiteModals() {
        document.addEventListener('click', (event) => {
            if (event.target.id === 'dashboardSiteDetailsModal') {
                closeDashboardSiteDetailsModal();
            }

            if (event.target.id === 'dashboardSiteEditModal') {
                closeDashboardSiteEditModal();
            }

            if (event.target.id === 'dashboardAssignWorkersModal') {
                closeDashboardAssignWorkersModal();
            }

            const toggleButton = event.target.closest('[data-dashboard-worker-toggle]');
            const workerCard = event.target.closest('[data-dashboard-worker-card]');
            if (toggleButton || (workerCard && !event.target.closest('[data-dashboard-worker-role-select]'))) {
                const workerId = Number((toggleButton || workerCard)?.dataset.dashboardWorkerToggle || workerCard?.dataset.dashboardWorkerCard || 0);
                if (workerId) {
                    const worker = dashboardSiteState.assignmentModal.workers.find((entry) => entry.id === workerId);
                    const isAssigned = dashboardSiteState.assignmentModal.selectedWorkerIds.has(workerId);
                    const isLocked = worker && !isAssigned && (!worker.isApproved || workerHasExternalAssignment(worker, dashboardSiteState.assignmentModal.siteId));

                    if (isLocked) {
                        setDashboardAssignFeedback(
                            worker.isApproved
                                ? `${worker.name} is already assigned to another site. Remove that assignment first.`
                                : `${worker.name} is pending approval and cannot be assigned yet.`,
                            'error'
                        );
                        return;
                    }

                    if (isAssigned) {
                        dashboardSiteState.assignmentModal.selectedWorkerIds.delete(workerId);
                    } else {
                        dashboardSiteState.assignmentModal.selectedWorkerIds.add(workerId);
                        if (!dashboardSiteState.assignmentModal.roleByWorkerId.has(workerId)) {
                            dashboardSiteState.assignmentModal.roleByWorkerId.set(workerId, getWorkerAssignmentLabel(worker, dashboardSiteState.assignmentModal.siteId));
                        }
                    }

                    setDashboardAssignFeedback('');
                    renderDashboardAssignmentWorkers();
                }
            }
        });

        document.querySelectorAll('[data-dashboard-close="details"]').forEach((button) => {
            button.addEventListener('click', closeDashboardSiteDetailsModal);
        });

        document.querySelectorAll('[data-dashboard-close="edit"]').forEach((button) => {
            button.addEventListener('click', closeDashboardSiteEditModal);
        });

        document.querySelectorAll('[data-dashboard-close="assign"]').forEach((button) => {
            button.addEventListener('click', closeDashboardAssignWorkersModal);
        });

        document.getElementById('dashboardCancelSiteEdit')?.addEventListener('click', closeDashboardSiteEditModal);
        document.getElementById('dashboardSiteEditForm')?.addEventListener('submit', saveDashboardSiteEdit);
        document.getElementById('dashboardFindEditLocationOnMapBtn')?.addEventListener('click', geocodeDashboardEditSiteLocation);
        document.getElementById('dashboardEditSiteLocation')?.addEventListener('input', () => {
            clearTimeout(dashboardSiteState.editMap.geocodeTimer);
            dashboardSiteState.editMap.geocodeTimer = setTimeout(() => {
                geocodeDashboardEditSiteLocation();
            }, 650);
        });
        document.getElementById('dashboardEditCoordinates')?.addEventListener('change', syncDashboardEditMapToCoordinates);
        document.getElementById('dashboardEditCoordinates')?.addEventListener('blur', syncDashboardEditMapToCoordinates);
        document.getElementById('dashboardCancelAssignWorkers')?.addEventListener('click', closeDashboardAssignWorkersModal);
        document.getElementById('dashboardSaveAssignWorkers')?.addEventListener('click', saveDashboardAssignments);
        document.getElementById('dashboardAssignWorkersSearch')?.addEventListener('input', (event) => {
            dashboardSiteState.assignmentModal.searchTerm = event.target.value || '';
            renderDashboardAssignmentWorkers();
        });
        document.getElementById('dashboardAssignWorkersRoleFilter')?.addEventListener('change', (event) => {
            dashboardSiteState.assignmentModal.roleFilter = event.target.value || '';
            renderDashboardAssignmentWorkers();
        });
        document.getElementById('dashboardAssignWorkersStatusFilter')?.addEventListener('change', (event) => {
            dashboardSiteState.assignmentModal.statusFilter = event.target.value || '';
            renderDashboardAssignmentWorkers();
        });
        document.getElementById('dashboardAssignWorkersLocationFilter')?.addEventListener('change', (event) => {
            dashboardSiteState.assignmentModal.locationFilter = event.target.value || '';
            renderDashboardAssignmentWorkers();
        });
        document.getElementById('dashboardAssignSiteTimekeeperSelect')?.addEventListener('change', (event) => {
            const value = Number(event.target.value || 0);
            dashboardSiteState.assignmentModal.timekeeperUserId = value > 0 ? value : null;
        });
        document.getElementById('dashboardAssignWorkersList')?.addEventListener('change', (event) => {
            const select = event.target.closest('[data-dashboard-worker-role-select]');
            if (!select) {
                return;
            }

            const workerId = Number(select.dataset.dashboardWorkerRoleSelect || 0);
            if (workerId) {
                dashboardSiteState.assignmentModal.roleByWorkerId.set(workerId, select.value || 'Construction Worker');
                renderDashboardAssignmentWorkers();
            }
        });
        document.getElementById('dashboardAssignWorkersTempBtn')?.addEventListener('click', () => {
            const currentPath = window.location.pathname || '';
            const dashboardFile = currentPath.split('/').pop() || '';
            if (dashboardRole === 'assistant') {
                window.location.href = '/capstone/assistant/dashboard?page=worker';
                return;
            }
            if (dashboardRole === 'payroll') {
                window.location.href = '/capstone/payroll/worker';
                return;
            }
            if (dashboardRole === 'hr') {
                window.location.href = '/capstone/hr/worker';
                return;
            }
            if (dashboardFile === 'payroll_dashboard.php') {
                window.location.href = 'payroll_dashboard.php?page=worker';
                return;
            }
            if (dashboardFile === 'ass_dashboard.php') {
                window.location.href = '/capstone/assistant/dashboard?page=worker';
                return;
            }
            window.location.href = 'dashboard.php?page=worker';
        });
    }

    function renderStatusBadge(status) {
        const normalized = String(status || 'Pending').toLowerCase();
        let className = 'yellow';

        if (normalized.includes('approve') || normalized.includes('complete') || normalized.includes('processed')) {
            className = 'green';
        } else if (normalized.includes('reject') || normalized.includes('cancel')) {
            className = 'red';
        }

        return `<span class="badge ${className}">${escapeHtml(status || 'Pending')}</span>`;
    }

    function renderReportsRows(items) {
        if (!Array.isArray(items) || items.length === 0) {
            return `
                <tr>
                    <td colspan="6" style="text-align:center;">No recent reports found.</td>
                </tr>
            `;
        }

        return items.map((item) => `
            <tr>
                <td>${escapeHtml(item.type)}</td>
                <td>${escapeHtml(item.site)}</td>
                <td>${escapeHtml(item.reported_by)}</td>
                <td>${escapeHtml(formatDate(item.date))}</td>
                <td>${renderStatusBadge(item.status)}</td>
                <td><span class="action-link">View</span></td>
            </tr>
        `).join('');
    }

    function renderLatestSiteCards(sites, emptyTitle, emptyText) {
        cacheSites(sites);
        if (!Array.isArray(sites) || sites.length === 0) {
            return `
                <div class="site-card">
                    <div class="site-card-header">
                        <div>
                            <div class="site-card-title">${escapeHtml(emptyTitle)}</div>
                            <span class="site-status-badge inactive">No Data</span>
                        </div>
                    </div>
                    <div class="site-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${escapeHtml(emptyText)}</span>
                    </div>
                </div>
            `;
        }

        return sites.map((site) => {
            const siteId = Number(site.SiteID || 0);
            const requiredWorkers = Number(site.Required_Workers || 0);
            const currentWorkers = Number(site.Current_Workers || 0);
            const attendanceRate = Number(site.attendance_rate || 0);
            const workerPercent = requiredWorkers > 0
                ? Math.min((currentWorkers / requiredWorkers) * 100, 100)
                : 0;
            const needsWorkers = Number(site.needs_workers || 0);
            const status = (site.Status || 'Active').toString();
            const statusClass = status.toLowerCase() === 'active' ? 'active' : 'inactive';
            const actionsHtml = `
                    <div class="site-card-actions">
                        <button class="btn-view-details" type="button" data-site-id="${siteId}" data-site-name="${escapeHtml(site.Site_Name)}" data-site-action="details">
                            <i class="fas fa-eye"></i>
                            <span>View Details</span>
                        </button>
                    </div>
            `;

            return `
                <div class="site-card">
                    <div class="site-card-header">
                        <div>
                            <div class="site-card-title">${escapeHtml(site.Site_Name)}</div>
                            <span class="site-status-badge ${statusClass}">${escapeHtml(status)}</span>
                        </div>
                    </div>

                    <div class="site-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${escapeHtml(site.Location || 'No location')}</span>
                    </div>

                    <div class="site-info-row">
                        <span class="site-info-label">Current Workers:</span>
                        <span class="site-info-value">${currentWorkers}</span>
                    </div>

                    <div class="site-info-row">
                        <span class="site-info-label">Target Capacity:</span>
                        <span class="site-info-value">${requiredWorkers}</span>
                    </div>

                    <div class="capacity-bar-container">
                        <div class="capacity-bar-label">
                            <span>Capacity</span>
                            <span>${Math.round(workerPercent)}%</span>
                        </div>
                        <div class="capacity-bar">
                            <div class="capacity-bar-fill ${needsWorkers > 0 ? 'red' : 'orange'}" style="width:${workerPercent}%;"></div>
                        </div>
                        ${needsWorkers > 0 ? `<div class="capacity-warning">Needs ${needsWorkers} more worker${needsWorkers > 1 ? 's' : ''}</div>` : ''}
                    </div>

                    <div class="site-info-row">
                        <span class="site-info-label">Attendance Rate:</span>
                        <span class="site-info-value site-info-value-green">${attendanceRate}%</span>
                    </div>
                    <div class="capacity-bar">
                        <div class="capacity-bar-fill green" style="width:${attendanceRate}%;"></div>
                    </div>

                    <div class="site-footer">
                        <div class="site-manager">
                            <i class="fas fa-briefcase"></i>
                            <span>Site Manager: ${escapeHtml(site.Site_Manager || 'Not assigned')}</span>
                        </div>
                        <div class="dashboard-site-badge ${needsWorkers > 0 ? 'warning' : 'stable'}">
                            <i class="fas ${needsWorkers > 0 ? 'fa-exclamation-circle' : 'fa-users'}"></i>
                            <span>${needsWorkers > 0 ? `Needs ${needsWorkers} Worker${needsWorkers > 1 ? 's' : ''}` : 'At Capacity'}</span>
                        </div>
                    </div>

                    ${actionsHtml}
                </div>
            `;
        }).join('');
    }

    function renderActiveSiteStyleCards(sites, emptyTitle, emptyText) {
        cacheSites(sites);
        if (!Array.isArray(sites) || sites.length === 0) {
            return `
                <div class="site-card dashboard-active-card">
                    <div class="card-header">
                        <div class="site-title">${escapeHtml(emptyTitle)}</div>
                        <span class="site-status-badge inactive">No Data</span>
                    </div>
                    <div class="site-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${escapeHtml(emptyText)}</span>
                    </div>
                </div>
            `;
        }

        return sites.map((site) => {
            const siteId = Number(site.SiteID || 0);
            const status = site.Status || 'Active';
            const requiredWorkers = Number(site.Required_Workers || 0);
            const currentWorkers = Number(site.Current_Workers || 0);
            const capacityPercent = requiredWorkers === 0 ? 0 : Math.round((currentWorkers / requiredWorkers) * 100);
            const safeCapacityPercent = Math.max(0, Math.min(capacityPercent, 100));
            const managerName = site.Site_Manager || 'Not assigned';
            const timekeeperName = site.Timekeeper || 'Not assigned';
            const startTime = site.Start_Time || 'Not set';
            const endTime = site.End_Time || 'Not set';
            const totalHours = site.Total_Hours || '--';
            const normalizedStatus = String(status).toLowerCase();

            return `
                <div class="site-card dashboard-active-card" data-site-id="${siteId}" data-site-name="${escapeHtml(site.Site_Name || '')}" data-site-status="${normalizedStatus}">
                    <div class="card-header">
                        <div class="site-title">${escapeHtml(site.Site_Name || 'Unnamed Site')}</div>
                        <span class="site-status-badge ${normalizedStatus}">${escapeHtml(status)}</span>
                    </div>

                    <div class="site-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${escapeHtml(site.Location || 'No location provided')}</span>
                    </div>

                    <div class="work-box">
                        <div class="work-top">
                            <div class="work-title">
                                <i class="far fa-clock"></i>
                                <span>Working Hours</span>
                            </div>
                            <div class="hours-badge">${escapeHtml(totalHours)}</div>
                        </div>

                        <div class="time-wrapper">
                            <div class="time-box">
                                <div class="time-label">Start</div>
                                <div class="time">${escapeHtml(startTime)}</div>
                            </div>

                            <div class="arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                            <div class="time-box">
                                <div class="time-label">End</div>
                                <div class="time">${escapeHtml(endTime)}</div>
                            </div>
                        </div>
                    </div>

                    <div class="site-stats">
                        <div class="stat-box">
                            <div class="stat-title">Assigned Workers</div>
                            <div class="stat-value site-info-value">${currentWorkers}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-title">Target Capacity</div>
                            <div class="stat-value site-info-value">${requiredWorkers}</div>
                        </div>
                    </div>

                    <div class="capacity-row">
                        <div class="capacity-title">Capacity</div>
                        <div class="capacity-percent">${safeCapacityPercent}%</div>
                    </div>

                    <div class="capacity-bar">
                        <div class="capacity-bar-fill" style="width: ${safeCapacityPercent}%;"></div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Site Manager</div>
                        <div class="info-name">${escapeHtml(managerName)}</div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Timekeeper</div>
                        <div class="info-name">${escapeHtml(timekeeperName)}</div>
                    </div>

                    <div class="site-started">
                        <i class="far fa-calendar"></i>
                        <span>Started ${escapeHtml(formatDateLabel(site.Start_Date))}</span>
                    </div>

                    <div class="site-card-actions">
                        <button class="btn-view-details" type="button" data-site-id="${siteId}" data-site-name="${escapeHtml(site.Site_Name || '')}" data-site-action="details">
                            <i class="far fa-eye"></i>
                            <span>View Details</span>
                        </button>
                    </div>

                </div>
            `;
        }).join('');
    }

    function renderAssistantSites(sites) {
        const container = document.querySelector('.site-grid');
        if (!container) {
            return;
        }

        container.innerHTML = renderLatestSiteCards(
            sites,
            'No assigned sites',
            'No site data is available for this dashboard.'
        );
    }

    function renderAssistantAdminStyleSites(sites) {
        const container = document.querySelector('.assistant-sites-panel');
        if (!container) {
            return;
        }

        if (!Array.isArray(sites) || sites.length === 0) {
            container.innerHTML = '<div class="empty-state site-list-empty">No active sites found.</div>';
            return;
        }

        cacheSites(sites);

        container.innerHTML = sites.map((site) => {
            const siteId = Number(site.SiteID || site.site_id || 0);
            const siteName = site.Site_Name || site.site_name || 'Unnamed Site';
            const status = site.Status || site.status || 'Active';
            const isActive = String(status).toLowerCase() === 'active';
            const assigned = Number(site.Current_Workers || site.assigned_workers || 0);
            const capacity = Number(site.Required_Workers || site.target_capacity || 0);
            const location = site.Location || site.location || 'No location';
            const staffingPercent = capacity > 0 ? Math.min(100, Math.round((assigned / capacity) * 100)) : 0;
            const workersNeeded = Math.max(0, capacity - assigned);
            const capacityState = capacity > 0 && assigned >= capacity ? 'at-capacity' : 'needs-workers';
            const capacityLabel = capacityState === 'at-capacity'
                ? 'At capacity'
                : `${workersNeeded} needed`;

            return `
                <button type="button" class="site-row" data-site-id="${siteId}" data-site-action="details">
                    <span class="site-row-top">
                        <span class="site-row-main">
                            <span class="site-row-name">${escapeHtml(siteName)}</span>
                            <span class="site-row-sub"><i class="fas fa-location-dot" aria-hidden="true"></i>${escapeHtml(location)}</span>
                        </span>
                        <span class="site-capacity-badge ${capacityState}">${capacityLabel}</span>
                    </span>
                    <span class="site-staffing-meta">
                        <span>Staffing</span>
                        <strong>${assigned}${capacity ? ` / ${capacity}` : ''} workers</strong>
                    </span>
                    <span class="site-progress" aria-label="${staffingPercent}% staffed">
                        <span class="site-progress-fill ${capacityState}" style="width:${staffingPercent}%"></span>
                    </span>
                    <span class="site-row-footer">
                        <span class="site-status-label"><span class="site-row-status ${isActive ? 'is-active' : 'is-inactive'}"></span>${escapeHtml(status)}</span>
                        <span class="site-view-label">View site <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    </span>
                </button>
            `;
        }).join('');
    }

    function bindSiteCardActions() {
        document.addEventListener('click', (event) => {
            const siteActionButton = event.target.closest('[data-site-action]');
            if (siteActionButton) {
                const action = siteActionButton.dataset.siteAction || '';
                const siteId = Number(siteActionButton.dataset.siteId || 0);
                if (siteId && action === 'details') {
                    event.preventDefault();
                    event.stopPropagation();
                    openDashboardSiteDetails(siteId);
                }
                return;
            }
        });
    }

    function renderPayrollSites(sites) {
        const container = document.querySelector('.payroll-sites-grid');
        if (!container) {
            return;
        }

        container.innerHTML = renderLatestSiteCards(
            sites,
            'No assigned sites',
            'Sites will appear here once assignments are available.'
        );
    }

    function renderAttendanceRows(attendance) {
        const rowsEl = document.querySelector('.attendance-site-rows');
        if (!rowsEl) {
            return;
        }

        const sites = attendance?.sites || [];
        if (sites.length === 0) {
            rowsEl.innerHTML = '<div class="bar-row"><div class="bar-label">No sites</div><div class="stacked-bar-track"></div><div class="bar-stats">0 <span>/ 0</span></div></div>';
            return;
        }

        rowsEl.innerHTML = sites.map((site) => {
            const total = Number(site.total || 0);
            const present = Number(site.present || 0);
            const late = Number(site.late || 0);
            const absent = Number(site.absent || 0);

            const presentPct = total > 0 ? (Math.max(0, present - late) / total) * 100 : 0;
            const latePct = total > 0 ? (late / total) * 100 : 0;
            const absentPct = total > 0 ? (absent / total) * 100 : 0;

            return `
                <div class="bar-row">
                    <div class="bar-label">${escapeHtml(site.site_name)}</div>
                    <div class="stacked-bar-track">
                        <div class="segment-green" style="width:${presentPct}%;"></div>
                        <div class="segment-yellow" style="width:${latePct}%;"></div>
                        <div class="segment-red" style="width:${absentPct}%;"></div>
                    </div>
                    <div class="bar-stats">${present} <span>/ ${total}</span></div>
                </div>
            `;
        }).join('');
    }

    function renderRecentActivity(items) {
        const tbody = document.querySelector('.table-card tbody');
        if (!tbody) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No recent activity found.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((item) => `
            <tr>
                <td>${escapeHtml(item.action)}</td>
                <td>${escapeHtml(item.user)}</td>
                <td>${escapeHtml(item.target || '-')}</td>
                <td>${escapeHtml(item.time || '-')}</td>
            </tr>
        `).join('');
    }

    let attendanceOverviewChart = null;

    function attendanceStatusBadge(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'present') return 'badge-present';
        if (normalized === 'late') return 'badge-late';
        if (normalized === 'pending') return 'badge-pending';
        return 'badge-absent';
    }

    function activityIconClass(type) {
        const map = {
            worker: 'fas fa-user-plus',
            attendance: 'fas fa-clipboard-check',
            qr: 'fas fa-qrcode',
            payroll: 'fas fa-file-invoice-dollar',
            overtime: 'far fa-clock',
            site: 'far fa-building',
            system: 'fas fa-cog'
        };
        return map[type] || map.system;
    }

    function renderAttendanceOverviewChart(overall, dateLabel) {
        const canvas = document.getElementById('attendanceOverviewChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const present = Number(overall?.present || 0);
        const late = Number(overall?.late || 0);
        const absent = Number(overall?.absent || 0);
        const offline = Number(overall?.offline || 0);

        if (attendanceOverviewChart) {
            attendanceOverviewChart.destroy();
        }

        attendanceOverviewChart = new Chart(canvas, {
            type: 'pie',
            data: {
                labels: ['Present', 'Late', 'Absent', 'Offline Attendance'],
                datasets: [{
                    label: dateLabel || 'Today',
                    data: [present, late, absent, offline],
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.9)',
                        'rgba(217, 122, 0, 0.9)',
                        'rgba(220, 53, 69, 0.9)',
                        'rgba(13, 148, 136, 0.9)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.15,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                const value = context.parsed || 0;
                                const pct = total > 0 ? Math.round((value / total) * 100) : 0;
                                return ` ${context.label}: ${value} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderSitesPanel(sites) {
        const grid = document.querySelector('.sites-panel-grid');
        if (!grid) {
            return;
        }

        if (!Array.isArray(sites) || sites.length === 0) {
            grid.innerHTML = '<div class="empty-state site-list-empty">No active sites found.</div>';
            return;
        }

        cacheSites(sites.map((site) => ({
            SiteID: site.site_id,
            Site_Name: site.site_name,
            Location: site.location,
            Status: site.status,
            Required_Workers: site.target_capacity,
            Current_Workers: site.assigned_workers,
            Timekeeper: site.timekeeper
        })));

        grid.innerHTML = sites.map((site) => {
            const siteId = Number(site.site_id || 0);
            const status = site.status || 'Active';
            const isActive = String(status).toLowerCase() === 'active';
            const assigned = Number(site.assigned_workers || 0);
            const capacity = Number(site.target_capacity || 0);
            const location = site.location || 'No location';

            return `
                <button type="button" class="site-row" data-site-id="${siteId}" data-site-action="details">
                    <span class="site-row-status ${isActive ? 'is-active' : 'is-inactive'}" title="${escapeHtml(status)}"></span>
                    <span class="site-row-main">
                        <span class="site-row-name">${escapeHtml(site.site_name)}</span>
                        <span class="site-row-sub">${escapeHtml(location)}</span>
                    </span>
                    <span class="site-row-workers">${assigned}${capacity ? ` / ${capacity}` : ''}</span>
                    <i class="fas fa-chevron-right site-row-chevron" aria-hidden="true"></i>
                </button>
            `;
        }).join('');
    }

    function renderTodayAttendanceTable(records) {
        const tbody = document.querySelector('.today-attendance-body');
        if (!tbody) {
            // The dashboard shell is also used by pages such as Settings,
            // which do not include the home-page attendance widget.
            return;
        }

        if (!Array.isArray(records) || records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No attendance records for today. Workers may not have clocked in yet.</td></tr>';
            return;
        }

        tbody.innerHTML = records.map((row) => `
            <tr>
                <td><strong>${escapeHtml(row.worker_name)}</strong></td>
                <td>${escapeHtml(row.site_name)}</td>
                <td>${escapeHtml(row.time_in || '--')}</td>
                <td><span class="badge-status ${attendanceStatusBadge(row.status)}">${escapeHtml(row.status)}</span></td>
            </tr>
        `).join('');
    }

    function renderPendingOvertimeTable(pending) {
        const tbody = document.querySelector('.pending-overtime-body');
        if (!tbody) {
            return;
        }

        const items = pending?.items || [];
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No pending overtime requests.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((item) => `
            <tr>
                <td>${escapeHtml(item.worker_name)}</td>
                <td>${escapeHtml(item.site_name)}</td>
                <td>${escapeHtml(String(item.hours))}</td>
                <td><span class="badge-status badge-pending">${escapeHtml(item.status)}</span></td>
                <td class="text-nowrap">
                    <button type="button" class="btn-ot-approve" data-ot-action="approve" data-ot-id="${Number(item.id)}">Approve</button>
                    <button type="button" class="btn-ot-reject" data-ot-action="reject" data-ot-id="${Number(item.id)}">Reject</button>
                </td>
            </tr>
        `).join('');
    }

    function renderActivityTimelineList(items) {
        const list = document.querySelector('.activity-timeline-list');
        if (!list) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            list.innerHTML = '<li class="empty-state">No recent system activity.</li>';
            return;
        }

        const maxItems = list.classList.contains('activity-timeline-balanced') ? 7 : 10;
        const visibleItems = items.slice(0, maxItems);

        list.innerHTML = visibleItems.map((item) => `
            <li>
                <span class="activity-icon"><i class="${activityIconClass(item.type)}"></i></span>
                <div class="activity-title">${escapeHtml(item.action)}</div>
                <div class="activity-detail">${escapeHtml(item.details || '-')}</div>
                <div class="activity-time">${escapeHtml(item.time || '')}</div>
            </li>
        `).join('');
    }

    async function handleDashboardOvertimeAction(action, overtimeId) {
        if (!overtimeId) {
            return;
        }

        const label = action === 'approve' ? 'approve' : 'reject';
        if (!(await window.showConfirmModal(`Are you sure you want to ${label} this overtime request?`, {
            title: 'Confirm Overtime Action',
            confirmText: label === 'approve' ? 'Approve' : 'Reject',
            type: label === 'approve' ? 'success' : 'error'
        }))) {
            return;
        }

        try {
            const response = await fetch('../api/overtime_request_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, overtime_id: overtimeId })
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to update overtime request.');
            }

            const refresh = await fetch('../api/get_dashboard_summary.php');
            const data = await refresh.json();
            if (data.success) {
                renderAdmin(data);
            }
            window.showCrudResultModal?.(
                true,
                result.message || `Overtime request ${action === 'approve' ? 'approved' : 'rejected'} successfully.`,
                'Overtime Request'
            );
        } catch (error) {
            console.error('Overtime action error:', error);
            window.showCrudResultModal?.(
                false,
                error.message || 'Unable to update overtime request.',
                'Overtime Request'
            );
        }
    }

    function bindDashboardOvertimeActions() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-ot-action]');
            if (!button || !document.querySelector('.dashboard-home')) {
                return;
            }
            const action = button.dataset.otAction;
            const overtimeId = Number(button.dataset.otId || 0);
            if (action && overtimeId) {
                handleDashboardOvertimeAction(action, overtimeId);
            }
        });
    }

    function populateCommon(data) {
        setText('.user-name', data.user?.name || 'User');
        setText('.user-role', data.user?.role || '');
    }

    function fitPayrollDueValue() {
        const value = document.querySelector('.kpi-payroll-due');
        if (!value) return;

        value.style.fontSize = '';
        let fontSize = Number.parseFloat(window.getComputedStyle(value).fontSize) || 16;
        const minimumFontSize = 10;

        while (value.scrollWidth > value.clientWidth && fontSize > minimumFontSize) {
            fontSize -= 0.5;
            value.style.fontSize = `${fontSize}px`;
        }
    }

    window.addEventListener('resize', () => {
        window.requestAnimationFrame(fitPayrollDueValue);
    });

    function renderAdmin(data) {
        try {
            const summary = data.summary || {};
            const overall = data.attendance?.overall || {};
            const payrollWeek = data.payroll_week || {};

            setText('.kpi-total-workers', summary.total_workers ?? 0);
            setText('.kpi-active-sites', summary.active_sites ?? 0);
            setText('.kpi-present-today', summary.present_today ?? overall.present_today ?? 0);
            setText('.kpi-payroll-due', formatCurrency(summary.payroll_due ?? summary.payroll_net ?? 0));
            window.requestAnimationFrame(fitPayrollDueValue);
            setText('.kpi-pending-overtime', summary.pending_overtime ?? 0);
            setText('.kpi-timekeepers', summary.active_timekeepers ?? 0);

            setText('.payroll-week-net', formatCurrency(payrollWeek.net ?? 0));
            setText('.payroll-week-net-row', formatCurrency(payrollWeek.net ?? 0));
            setText('.payroll-week-regular', formatCurrency(payrollWeek.regular ?? 0));
            setText('.payroll-week-overtime', formatCurrency(payrollWeek.overtime ?? 0));
            setText('.payroll-week-deductions', `-${formatCurrency(payrollWeek.deductions ?? 0)}`);
            setText('.payroll-week-label', payrollWeek.label ? `Week: ${payrollWeek.label}` : 'This week');

            const dateLabel = data.attendance?.date
                ? `Today — ${formatDate(data.attendance.date)}`
                : 'Today';
            setText('.attendance-date-label', dateLabel);

            renderAttendanceOverviewChart(overall, dateLabel);
            renderSitesPanel(data.active_sites_panel || []);
            renderTodayAttendanceTable(data.today_attendance || []);
            renderPendingOvertimeTable(data.pending_overtime || { items: [] });
            const timelineItems = Array.isArray(data.activity_timeline) && data.activity_timeline.length
                ? data.activity_timeline
                : (data.recent_activity || []).map((item) => ({
                    action: item.action,
                    details: item.target || item.details || '-',
                    time: item.time,
                    type: 'system'
                }));
            renderActivityTimelineList(timelineItems);

            if (Array.isArray(data.sites)) {
                cacheSites(data.sites);
            }

            const siteGrid = document.querySelector('.site-grid');
            if (siteGrid) {
                siteGrid.innerHTML = renderActiveSiteStyleCards(
                    data.sites,
                    'No sites available',
                    'Create and assign sites to see dashboard coverage.'
                );
            }
            renderRecentActivity(data.recent_activity);
        } catch (err) {
            console.error('Dashboard renderAdmin error:', err);
        }
    }

    function renderAssistant(data) {
        setText('.summary-total-users', data.summary.total_users);
        setText('.summary-attendance-rate', `${data.summary.avg_attendance}%`);
        setText('.summary-payroll-net', formatCurrency(data.summary.payroll_net));
        setText('.summary-active-users', data.summary.active_users);
        setText('.summary-labor-efficiency', `${data.summary.labor_efficiency}%`);
        setText('.summary-pending-reports', data.summary.pending_reports);
        setText('.summary-pending-incidents', data.summary.pending_incidents);
        setText('.kpi-active-sites', data.summary.active_sites);
        setText('.kpi-at-capacity', data.summary.at_capacity);
        setText('.kpi-needs-workers', data.summary.needs_workers);
        setText('.kpi-attendance', `${data.summary.avg_attendance}%`);
        setText('.reports-pending-badge', `${data.reports.pending_count} Pending`);
        setHtml('.reports-table-body', renderReportsRows(data.reports.items));
        renderAssistantAdminStyleSites(data.sites);
    }

    function renderHr(data) {
        setText('.metric-workers', data.summary.scoped_workers);
        setText('.metric-attendance', `${data.summary.avg_attendance}%`);
        setText('.metric-payroll-net', formatCurrency(data.summary.payroll_net));
        setText('.metric-labor-cost', formatCurrency(data.summary.payroll_gross));
        setText('.reports-pending-badge', `${data.reports.pending_count} Pending`);
        setHtml('.reports-table-body', renderReportsRows(data.reports.items));
        setText('.attendance-date-label', formatDate(data.attendance.date));
        setText('.overall-present-rate', `${data.attendance.overall.rate}%`);
        setText('.overall-present-count', `${data.attendance.overall.present} of ${data.attendance.overall.total} workers`);
        renderAttendanceRows(data.attendance);

        const budgetUsed = data.summary.payroll_gross > 0
            ? Math.min(100, Math.round((data.summary.payroll_net / data.summary.payroll_gross) * 100))
            : 0;

        setText('.payroll-net', formatCurrency(data.summary.payroll_net));
        setText('.payroll-gross', formatCurrency(data.summary.payroll_gross));
        setText('.payroll-overtime', formatCurrency(Math.max(0, data.summary.payroll_gross - data.summary.payroll_net)));
        setText('.payroll-deductions', `-${formatCurrency(data.summary.payroll_deductions)}`);
        setText('.budget-used', `${budgetUsed}%`);
        setText('.next-payroll-run', data.pay_period.next_run);

        const budgetFill = document.querySelector('.budget-fill');
        if (budgetFill) {
            budgetFill.style.width = `${budgetUsed}%`;
        }
    }

    function renderPayroll(data) {
        const isHrDashboard = dashboardRole === 'hr';
        const summary = data.summary || {};
        const attendance = data.attendance || {};
        const hr = data.hr || {};

        if (isHrDashboard) {
            const pendingApprovals = Number(summary.pending_employee_approvals || 0);
            setText('.hr-pending-approvals-text', `${pendingApprovals} Pending`);
            setText('.payroll-attendance-sync', summary.attendance_sync || 'Checking');
            setText('.payroll-sites-count', summary.total_workers ?? 0);
            setText('.payroll-workers-count', summary.active_workers ?? 0);
            setText('.hr-pending-approvals-count', pendingApprovals);
            setText('.hr-assigned-workers', summary.assigned_workers ?? 0);
            setText('.hr-unassigned-workers', summary.unassigned_workers ?? 0);
            setText('.hr-present-today', summary.present_today ?? attendance.overall?.present_today ?? 0);
            setText('.hr-attendance-rate', `${summary.avg_attendance ?? attendance.overall?.rate ?? 0}%`);
            setText(
                '.hr-status-breakdown',
                `${summary.employee_status_active ?? 0} / ${summary.employee_status_inactive ?? 0} / ${summary.employee_status_archived ?? 0}`
            );
            renderPayrollSites(data.sites);
            renderHrRecentEmployees(hr.recent_employees || []);
            renderHrUpdates(hr.updates || []);
            return;
        }

        setText('.payroll-current-queue', `${summary.pending_payrolls} Pending`);
        setText('.payroll-attendance-sync', summary.attendance_sync);
        setText('.payroll-sites-count', summary.assigned_sites);
        setText('.payroll-workers-count', summary.scoped_workers);
        setText('.payroll-pending-count', summary.pending_payrolls);
        setText('.reports-pending-badge', `${data.reports.pending_count} Pending`);
        setHtml('.reports-table-body', renderReportsRows(data.reports.items));
        renderPayrollSites(data.sites);
    }

    function renderHrRecentEmployees(items) {
        const tbody = document.querySelector('.hr-recent-employees-body');
        if (!tbody) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No recently added employees.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((item) => `
            <tr>
                <td><strong>${escapeHtml(item.name || 'Unnamed Employee')}</strong></td>
                <td><span class="badge-status ${employeeStatusBadge(item.status)}">${escapeHtml(item.status || 'Inactive')}</span></td>
                <td>${escapeHtml(item.site || 'Unassigned')}</td>
                <td>${escapeHtml(formatDate(item.date_hired))}</td>
            </tr>
        `).join('');
    }

    function renderHrUpdates(items) {
        const tbody = document.querySelector('.hr-updates-body');
        if (!tbody) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No employee or assignment updates yet.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((item) => `
            <tr>
                <td><strong>${escapeHtml(item.action || 'Update')}</strong></td>
                <td>${escapeHtml(item.details || '-')}</td>
                <td>${escapeHtml(item.time || '')}</td>
            </tr>
        `).join('');
    }

    function employeeStatusBadge(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'active' || normalized === 'onleave') {
            return 'badge-approved';
        }
        if (normalized === 'archived' || normalized === 'inactive') {
            return 'badge-rejected';
        }
        return 'badge-pending';
    }

    updateShellTitle();
    updateShellClock();
    ensureDashboardSiteModalStyles();
    ensureDashboardSiteModals();
    bindDashboardSiteModals();
    setInterval(updateShellClock, 1000);
    bindSiteCardActions();
    bindDashboardOvertimeActions();

    const role = dashboardRole;
    const params = new URLSearchParams(window.location.search);
    if (!role || params.has('page')) {
        return;
    }

    fetch('../api/get_dashboard_summary.php')
        .then((res) => res.json())
        .then((data) => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load dashboard data.');
            }

            populateCommon(data);

            if (role === 'admin') {
                renderAdmin(data);
            } else if (role === 'assistant') {
                renderAssistant(data);
            } else if (role === 'payroll' || role === 'hr') {
                renderPayroll(data);
            }
        })
        .catch((err) => console.error('Dashboard summary error:', err));
});

        document.addEventListener('DOMContentLoaded', () => {
            const dropdown = document.getElementById('userProfileDropdown');
            const toggle = document.getElementById('userProfileToggle');
            const menu = document.getElementById('userProfileMenu');

            // Some pages/roles embed the dashboard shell without the dropdown markup.
            // In those cases, do nothing.
            if (!toggle || !menu) return;

            // The payroll and assistant dashboard shells also load a role-specific
            // script. Bind this shared control only once so a single click cannot
            // be handled twice (open, then immediately close).
            if (toggle.dataset.profileDropdownBound === 'true') return;
            toggle.dataset.profileDropdownBound = 'true';

            // Ensure menu is not accidentally hidden by other scripts.
            menu.style.display = 'none';
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';

            function setOpen(isOpen) {
                // Always use inline style to avoid CSS/other scripts fighting display.
                menu.style.display = isOpen ? 'block' : 'none';
                toggle.setAttribute('aria-expanded', String(isOpen));
            }

            setOpen(false);

            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = menu.style.display === 'block';
                setOpen(!isOpen);
            });

            // Close on outside click only.
            document.addEventListener('click', (e) => {
                // If click is inside toggle or menu, ignore.
                if (toggle.contains(e.target) || menu.contains(e.target)) {
                    return;
                }
                setOpen(false);
            });

            // Prevent clicks inside the menu from bubbling (so links work).
            menu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });
