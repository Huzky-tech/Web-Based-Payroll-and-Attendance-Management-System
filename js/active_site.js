const addSiteModal = document.getElementById('addSiteModal');
const btnAddSite = document.getElementById('btnAddSite');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn'); // Not present in current modal markup (kept for backward compatibility)
const addSiteForm = document.getElementById('addSiteForm');
const addSiteBackBtn = document.getElementById('addSiteBackBtn');
const addSiteNextBtn = document.getElementById('addSiteNextBtn');
const addSiteSubmitBtn = document.getElementById('addSiteSubmitBtn');
const addSiteStepIndicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
const addSiteStepLines = Array.from(document.querySelectorAll('[data-step-line]'));
const addSiteStepPanels = Array.from(document.querySelectorAll('[data-step-panel]'));
const siteLocationInput = document.getElementById('location');
const siteCoordinatesInput = document.getElementById('siteCoordinates');
const geofenceRadiusInput = document.getElementById('geofenceRadiusM');
const geofenceLatitudeInput = document.getElementById('geofenceLatitude');
const geofenceLongitudeInput = document.getElementById('geofenceLongitude');
const geofenceRadiusHiddenInput = document.getElementById('geofenceRadiusMHidden');

const findLocationOnMapBtn = document.getElementById('findLocationOnMapBtn');
const useMyLocationBtn = document.getElementById('useMyLocationBtn');
const siteMapFeedback = document.getElementById('siteMapFeedback');
const siteLocationMap = document.getElementById('siteLocationMap');
const siteMapPlaceholder = document.getElementById('siteMapPlaceholder');
const siteDetailsModal = document.getElementById('siteDetailsModal');
const closeSiteDetailsModalBtn = document.getElementById('closeSiteDetailsModal');
const siteDetailsTitle = document.getElementById('siteDetailsTitle');
const siteDetailsSubtitle = document.getElementById('siteDetailsSubtitle');
const siteDetailsBody = document.getElementById('siteDetailsBody');
const editSiteModal = document.getElementById('editSiteModal');
const closeEditSiteModalBtn = document.getElementById('closeEditSiteModal');
const cancelEditSiteBtn = document.getElementById('cancelEditSiteBtn');
const editSiteForm = document.getElementById('editSiteForm');
const saveEditSiteBtn = document.getElementById('saveEditSiteBtn');
const editSiteFeedback = document.getElementById('editSiteFeedback');
const editSiteLocationInput = document.getElementById('editSiteLocation');
const editSiteCoordinatesInput = document.getElementById('editSiteCoordinates');
const findEditLocationOnMapBtn = document.getElementById('findEditLocationOnMapBtn');
const useMyEditLocationBtn = document.getElementById('useMyEditLocationBtn');
const editSiteMapFeedback = document.getElementById('editSiteMapFeedback');
const editSiteLocationMap = document.getElementById('editSiteLocationMap');
const activeSiteDashboardRole = document.body?.dataset?.dashboardRole || '';
const activeSiteReadOnly = activeSiteDashboardRole === 'payroll';
const activeSiteIsAdmin = activeSiteDashboardRole === 'admin';
const activeSiteCanManageSites = activeSiteDashboardRole === 'admin' || activeSiteDashboardRole === 'assistant';
const activeSiteCanAssignWorkers = activeSiteCanManageSites || activeSiteDashboardRole === 'hr';
const activeSiteCanAssignTimekeepers = activeSiteCanManageSites;
const activeSiteCanPrioritize = ['admin', 'assistant', 'hr', 'payroll'].includes(activeSiteDashboardRole);
const archiveSiteModal = document.getElementById('archiveSiteModal');
const closeArchiveSiteModalBtn = document.getElementById('closeArchiveSiteModal');
const cancelArchiveSiteBtn = document.getElementById('cancelArchiveSiteBtn');
const confirmArchiveSiteBtn = document.getElementById('confirmArchiveSiteBtn');
const philippinesMapBounds = {
    north: 21.5,
    south: 4.2,
    west: 116.0,
    east: 127.3
};

const assignWorkersModal = document.getElementById('assignWorkersModal');
const closeAssignWorkersModalBtn = document.getElementById('closeAssignWorkersModal');
const cancelAssignWorkersBtn = document.getElementById('cancelAssignWorkersBtn');
const saveAssignWorkersBtn = document.getElementById('saveAssignWorkersBtn');
const assignWorkersSearch = document.getElementById('assignWorkersSearch');
const assignWorkersRoleFilter = document.getElementById('assignWorkersRoleFilter');
const assignWorkersStatusFilter = document.getElementById('assignWorkersStatusFilter');
const assignWorkersLocationFilter = document.getElementById('assignWorkersLocationFilter');
const assignWorkersList = document.getElementById('assignWorkersList');
const assignWorkersFeedback = document.getElementById('assignWorkersFeedback');
const assignWorkersTempBtn = document.getElementById('assignWorkersTempBtn');
const assignSiteTimekeeperSelect = document.getElementById('assignSiteTimekeeperSelect');
const siteSearchInput = document.getElementById('siteSearchInput');
const siteWorkerFilter = document.getElementById('siteWorkerFilter');
const siteStatusFilter = document.getElementById('siteStatusFilter');
const siteAssignmentSort = document.getElementById('siteAssignmentSort');

const defaultRoleOptions = [
    'Construction Worker',
    'Electrician',
    'Plumber',
    'Mason',
    'Carpenter',
    'Welder',
    'Heavy Equipment Operator',
    'Site Foreman',
    'Safety Officer'
];

const activeSiteState = {
    sites: [],
    siteMap: new Map(),
    siteFilters: { search: '', workers: '', status: '', sort: 'newest' },
    pendingDashboardAction: null,
    pendingArchiveSiteId: null,
    pendingArchiveSiteName: '',
    addSiteStep: 0,
    addSiteMap: {
        instance: null,
        marker: null,
        geofenceCircle: null,
        leafletReady: null,
        geocodeTimer: null,
        searchAbortController: null,
        defaultCenter: [12.8797, 121.7740],
        defaultZoom: 6
    },

    detailsMap: {
        instance: null,
        marker: null,
        geofenceCircle: null
    },
    editSiteMap: {
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

function initializePendingDashboardAction() {
    const params = new URLSearchParams(window.location.search);
    const siteId = Number(params.get('site_id') || 0);
    const action = params.get('site_action') || '';

    if (!siteId || !['details', 'edit'].includes(action)) {
        activeSiteState.pendingDashboardAction = null;
        return;
    }

    activeSiteState.pendingDashboardAction = { siteId, action };
}

function consumePendingDashboardAction() {
    const pendingAction = activeSiteState.pendingDashboardAction;
    if (!pendingAction) {
        return;
    }

    const site = activeSiteState.siteMap.get(Number(pendingAction.siteId));
    if (!site) {
        return;
    }

    activeSiteState.pendingDashboardAction = null;

    // Dashboard actions are one-time commands. Remove them so closing or
    // refreshing the page does not reopen the same modal.
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('site_id');
    cleanUrl.searchParams.delete('site_action');
    window.history.replaceState({}, document.title, cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);

    if (pendingAction.action === 'details') {
        openSiteDetails(pendingAction.siteId);
        return;
    }

    if (pendingAction.action === 'edit') {
        openEditSite(pendingAction.siteId);
        return;
    }

}

function updateDateTime() {
    const currentDateEl = document.getElementById('currentDate');
    const currentTimeEl = document.getElementById('currentTime');
    if (!currentDateEl || !currentTimeEl) return;

    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const day = days[now.getDay()];
    const month = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();

    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const minutesStr = minutes < 10 ? `0${minutes}` : `${minutes}`;

    currentDateEl.textContent = `${day}, ${month} ${date}, ${year}`;
    currentTimeEl.textContent = `${hours}:${minutesStr} ${ampm}`;
}

updateDateTime();
setInterval(updateDateTime, 60000);

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function toSafeClassName(value) {
    return String(value ?? '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
}

function releaseModalFocus(modal) {
    if (!modal || !modal.contains(document.activeElement)) {
        return;
    }

    if (typeof document.activeElement?.blur === 'function') {
        document.activeElement.blur();
    }
}

function formatDateLabel(dateString) {
    if (!dateString) return 'Not set';

    const parsedDate = new Date(dateString);
    if (Number.isNaN(parsedDate.getTime())) {
        return escapeHtml(dateString);
    }

    return parsedDate.toISOString().split('T')[0];
}

function parseSiteCoordinates(value) {
    const match = String(value || '').match(/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/);
    if (!match) {
        return null;
    }

    const latitude = Number(match[1]);
    const longitude = Number(match[2]);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
        return null;
    }

    return { latitude, longitude };
}

function timeToMinutes(time) {
    const match = String(time || '').match(/^(\d{1,2}):(\d{2})$/);
    if (!match) {
        return null;
    }

    const hours = Number(match[1]);
    const minutes = Number(match[2]);
    if (!Number.isInteger(hours) || !Number.isInteger(minutes) || hours > 23 || minutes > 59) {
        return null;
    }

    return (hours * 60) + minutes;
}

function buildSiteDetailsMap(coordinatesText, siteName, radiusM = null) {
    const parsed = parseSiteCoordinates(coordinatesText);
    if (!parsed) {
        return `
            <div class="details-site-map empty">
                <div>
                    <i class="fas fa-map-pin"></i>
                    <strong>No pinned map location yet</strong>
                    <span>Add coordinates from the site pin map to show this preview.</span>
                </div>
            </div>
        `;
    }

    const { latitude, longitude } = parsed;
    const mapQuery = `${latitude.toFixed(6)},${longitude.toFixed(6)}`;
    const openMapUrl = `https://www.openstreetmap.org/?mlat=${latitude.toFixed(6)}&mlon=${longitude.toFixed(6)}#map=16/${latitude.toFixed(6)}/${longitude.toFixed(6)}`;

    return `
        <div class="details-site-map">
            <div class="details-site-map-header">
                <div class="details-site-map-title">Pinned Site Location</div>
                <a class="details-site-map-link" href="${openMapUrl}" target="_blank" rel="noopener">
                    <i class="fas fa-up-right-from-square"></i>
                    <span>Open Map</span>
                </a>
            </div>
            <div
                id="siteDetailsMapCanvas"
                class="details-site-map-canvas"
                data-latitude="${latitude}"
                data-longitude="${longitude}"
                data-radius-m="${Number(radiusM) > 0 ? Number(radiusM) : ''}"
                aria-label="Detailed map for ${escapeHtml(siteName || 'site')}"
            ></div>
        </div>
    `;
}

function destroySiteDetailsMap() {
    if (activeSiteState.detailsMap.geofenceCircle) {
        activeSiteState.detailsMap.geofenceCircle.remove();
        activeSiteState.detailsMap.geofenceCircle = null;
    }
    if (activeSiteState.detailsMap.marker) {
        activeSiteState.detailsMap.marker.off();
        activeSiteState.detailsMap.marker.remove();
        activeSiteState.detailsMap.marker = null;
    }

    if (activeSiteState.detailsMap.instance) {
        activeSiteState.detailsMap.instance.off();
        activeSiteState.detailsMap.instance.remove();
    }

    activeSiteState.detailsMap.instance = null;
}

async function renderSiteDetailsMap() {
    const mapCanvas = document.getElementById('siteDetailsMapCanvas');
    if (!mapCanvas) {
        destroySiteDetailsMap();
        return;
    }

    const latitude = Number(mapCanvas.dataset.latitude || NaN);
    const longitude = Number(mapCanvas.dataset.longitude || NaN);
    const radiusM = Number(mapCanvas.dataset.radiusM || 0);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        destroySiteDetailsMap();
        return;
    }

    await loadLeafletAssets();
    destroySiteDetailsMap();

    const center = [latitude, longitude];
    const map = createLeafletMap(mapCanvas, center, 16);

    activeSiteState.detailsMap.marker = window.L.marker(center).addTo(map);
    if (radiusM > 0) {
        activeSiteState.detailsMap.geofenceCircle = window.L.circle(center, {
            radius: radiusM,
            color: '#f97316',
            weight: 2,
            fillColor: '#fb923c',
            fillOpacity: 0.25
        }).addTo(map);
        map.fitBounds(activeSiteState.detailsMap.geofenceCircle.getBounds(), { padding: [24, 24], maxZoom: 16 });
    }
    activeSiteState.detailsMap.instance = map;
    refreshLeafletMap(map, center);
}

async function fetchJson(url, options = {}) {
    const requestOptions = { ...options };
    if (!requestOptions.method || String(requestOptions.method).toUpperCase() === 'GET') {
        requestOptions.cache = 'no-store';
    }
    const response = await fetch(url, requestOptions);
    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }

    try {
        return await response.json();
    } catch (error) {
        throw new Error('Server returned invalid JSON.');
    }
}

const siteNameDuplicateState = {
    addController: null,
    editController: null,
    addLocationController: null,
    editLocationController: null
};

function markFieldForLiveValidation(field, message = '') {
    if (!field) {
        return;
    }

    field.setCustomValidity(message);
    field.dataset.liveValidationTouched = '1';
    field.classList.toggle('site-field-invalid', message !== '');
    const errorElement = document.getElementById(`${field.id}Error`);
    if (errorElement) errorElement.textContent = message;
    field.dispatchEvent(new Event('change', { bubbles: true }));
}

function handleSiteLetterOnlyInput(event) {
    const field = event.target;
    const originalValue = field.value;
    const hasNumber = /\d/u.test(originalValue);
    const hasSpecial = /[^\p{L}\s\d]/u.test(originalValue);
    field.value = originalValue.replace(/\d/gu, '').replace(/[^\p{L}\s]/gu, '');
    if (!field.value.trim() && field.id.toLowerCase().includes('sitename')) {
        delete field.dataset.characterError;
        markFieldForLiveValidation(field, 'Site name is required.');
    } else if (hasNumber || hasSpecial) {
        const label = field.id.toLowerCase().includes('manager') ? 'Manager name' : 'Site name';
        const reason = hasNumber && hasSpecial ? 'numbers or special characters' : (hasNumber ? 'numbers' : 'special characters');
        field.dataset.characterError = '1';
        markFieldForLiveValidation(field, `${label} cannot contain ${reason}.`);
    } else {
        delete field.dataset.characterError;
        markFieldForLiveValidation(field, '');
    }
}

function bindSiteLetterOnlyValidation() {
    ['siteName', 'editSiteName', 'siteManager', 'editSiteManager'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', handleSiteLetterOnlyInput);
    });
}

function validateSiteLocationField(field) {
    if (!field) return true;
    const originalValue = field.value;
    // Keep normal address separators while rejecting symbols such as @, #, $, %, and *.
    const cleanedValue = originalValue.replace(/[^\p{L}\p{N}\s,.-]/gu, '');
    if (originalValue !== cleanedValue) field.value = cleanedValue;
    const message = originalValue !== cleanedValue
        ? 'Location cannot contain special characters.'
        : (!field.value.trim()
            ? 'Site location is required.'
            : (field.value.trim().length > 255 ? 'Site location cannot exceed 255 characters.' : ''));
    if (originalValue !== cleanedValue) {
        field.dataset.characterError = '1';
    } else {
        delete field.dataset.characterError;
    }
    markFieldForLiveValidation(field, message);
    return message === '';
}

function validateTargetCapacityField(field) {
    if (!field) return true;
    const rawValue = field.value.trim();
    let message = '';
    if (!rawValue) {
        message = 'Required workers is required.';
    } else if (!/^\d+$/.test(rawValue)) {
        message = 'Target capacity must be a whole number.';
    } else if (rawValue && (Number(rawValue) < 1 || Number(rawValue) > 10000)) {
        message = 'Target capacity must be between 1 and 10,000 workers.';
    }
    markFieldForLiveValidation(field, message);
    return message === '';
}

function bindSiteFieldValidation() {
    [document.getElementById('location'), document.getElementById('editSiteLocation')].forEach((field) => {
        field?.addEventListener('input', () => validateSiteLocationField(field));
    });
    [document.getElementById('requiredWorkers'), document.getElementById('editRequiredWorkers')].forEach((field) => {
        field?.addEventListener('input', () => validateTargetCapacityField(field));
    });

    let addLocationTimer = null;
    let editLocationTimer = null;
    document.getElementById('location')?.addEventListener('input', (event) => {
        window.clearTimeout(addLocationTimer);
        addLocationTimer = window.setTimeout(() => checkSiteLocationDuplicate(event.target, 0, 'add'), 250);
    });
    document.getElementById('editSiteLocation')?.addEventListener('input', (event) => {
        window.clearTimeout(editLocationTimer);
        const siteId = Number(document.getElementById('editSiteId')?.value || 0);
        editLocationTimer = window.setTimeout(() => checkSiteLocationDuplicate(event.target, siteId, 'edit'), 250);
    });

    ['siteCoordinates', 'editSiteCoordinates'].forEach((id) => {
        const field = document.getElementById(id);
        field?.addEventListener('input', () => {
            const originalValue = field.value;
            const cleanedValue = originalValue.replace(/[^0-9.,\-\s]/g, '');
            field.value = cleanedValue;
            const message = originalValue !== cleanedValue
                ? 'Coordinates may contain numbers, decimal points, a comma, and minus signs only.'
                : (cleanedValue.trim() && !parseSiteCoordinates(cleanedValue)
                    ? 'Enter valid coordinates as latitude, longitude.'
                    : '');
            markFieldForLiveValidation(field, message);
        });
    });

    ['geofenceRadiusM', 'editGeofenceRadiusM'].forEach((id) => {
        const field = document.getElementById(id);
        field?.addEventListener('input', () => {
            const originalValue = field.value;
            field.value = originalValue.replace(/\D/g, '');
            const numericValue = Number(field.value || 0);
            const message = originalValue !== field.value
                ? 'Location Lock Radius accepts whole numbers only.'
                : (field.value && (numericValue < 1 || numericValue > 20000)
                    ? 'Location Lock Radius must be between 1 and 20,000 meters.'
                    : '');
            markFieldForLiveValidation(field, message);
            if (id === 'geofenceRadiusM') syncAddGeofenceCircleWithInputs();
            if (id === 'editGeofenceRadiusM') syncEditGeofenceCircleWithInputs();
        });
    });
}

async function checkSiteNameDuplicate(field, excludeSiteId = 0, mode = 'add') {
    const siteName = field?.value.trim() || '';
    if (!field || !siteName) {
        markFieldForLiveValidation(field, '');
        return false;
    }
    if (field.dataset.characterError === '1') return false;

    const controllerKey = mode === 'edit' ? 'editController' : 'addController';
    siteNameDuplicateState[controllerKey]?.abort();
    const controller = new AbortController();
    siteNameDuplicateState[controllerKey] = controller;

    try {
        const params = new URLSearchParams({ site_name: siteName });
        if (excludeSiteId > 0) {
            params.set('exclude_site_id', String(excludeSiteId));
        }

        const result = await fetchJson(`../api/check_duplicate_site.php?${params.toString()}`, {
            signal: controller.signal
        });

        if (controller.signal.aborted) {
            return false;
        }

        const message = result.duplicate ? (result.message || 'A site with this name already exists.') : '';
        markFieldForLiveValidation(field, message);
        return Boolean(result.duplicate);
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.warn('Could not check duplicate site name:', error);
        }
        return false;
    }
}

async function checkSiteLocationDuplicate(field, excludeSiteId = 0, mode = 'add') {
    const location = field?.value.trim() || '';
    if (!field || !location) {
        if (field && field.dataset.characterError !== '1') markFieldForLiveValidation(field, '');
        return false;
    }
    if (field.dataset.characterError === '1') return false;
    const controllerKey = mode === 'edit' ? 'editLocationController' : 'addLocationController';
    siteNameDuplicateState[controllerKey]?.abort();
    const controller = new AbortController();
    siteNameDuplicateState[controllerKey] = controller;
    try {
        const params = new URLSearchParams({ location });
        if (excludeSiteId > 0) params.set('exclude_site_id', String(excludeSiteId));
        const result = await fetchJson(`../api/check_duplicate_site.php?${params.toString()}`, { signal: controller.signal });
        if (controller.signal.aborted) return false;
        const message = result.duplicate ? (result.message || 'A site with this exact location already exists.') : '';
        markFieldForLiveValidation(field, message);
        return Boolean(result.duplicate);
    } catch (error) {
        if (error.name !== 'AbortError') console.warn('Could not check duplicate site location:', error);
        return false;
    }
}

function bindSiteNameDuplicateValidation() {
    const addSiteNameInput = document.getElementById('siteName');
    const editSiteNameInput = document.getElementById('editSiteName');
    let addTimer = null;
    let editTimer = null;

    addSiteNameInput?.addEventListener('input', () => {
        window.clearTimeout(addTimer);
        addTimer = window.setTimeout(() => checkSiteNameDuplicate(addSiteNameInput, 0, 'add'), 250);
    });

    addSiteNameInput?.addEventListener('blur', () => {
        window.clearTimeout(addTimer);
        checkSiteNameDuplicate(addSiteNameInput, 0, 'add');
    });

    editSiteNameInput?.addEventListener('input', () => {
        window.clearTimeout(editTimer);
        const siteId = Number(document.getElementById('editSiteId')?.value || 0);
        editTimer = window.setTimeout(() => checkSiteNameDuplicate(editSiteNameInput, siteId, 'edit'), 250);
    });

    editSiteNameInput?.addEventListener('blur', () => {
        window.clearTimeout(editTimer);
        const siteId = Number(document.getElementById('editSiteId')?.value || 0);
        checkSiteNameDuplicate(editSiteNameInput, siteId, 'edit');
    });
}

function getAddSiteFormValues() {
    return {
        siteName: document.getElementById('siteName')?.value.trim() || '',
        location: document.getElementById('location')?.value.trim() || '',
        coordinates: document.getElementById('siteCoordinates')?.value.trim() || '',
        requiredWorkers: document.getElementById('requiredWorkers')?.value.trim() || '',
        startDate: document.getElementById('startDate')?.value || '',
        shiftStart: document.getElementById('shiftStart')?.value || '07:00',
        lunchStart: document.getElementById('lunchStart')?.value || '12:00',
        lunchEnd: document.getElementById('lunchEnd')?.value || '13:00',
        shiftEnd: document.getElementById('shiftEnd')?.value || '17:00',
        siteManager: document.getElementById('siteManager')?.value.trim() || '',
        status: 'inactive'
    };
}

function formatTimeDisplay(value) {
    if (!value) {
        return 'Not set';
    }

    const [hoursText = '0', minutesText = '00'] = String(value).split(':');
    let hours = Number(hoursText);
    const suffix = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${hours}:${minutesText.padStart(2, '0')} ${suffix}`;
}

function timeToMinutes(value) {
    if (!value) {
        return null;
    }

    const [hoursText = '0', minutesText = '0'] = String(value).split(':');
    return (Number(hoursText) * 60) + Number(minutesText);
}

function calculateWorkingHoursFromTimes(shiftStart, lunchStart, lunchEnd, shiftEnd) {
    const shiftStartMin = timeToMinutes(shiftStart);
    const lunchStartMin = timeToMinutes(lunchStart);
    const lunchEndMin = timeToMinutes(lunchEnd);
    const shiftEndMin = timeToMinutes(shiftEnd);

    if ([shiftStartMin, lunchStartMin, lunchEndMin, shiftEndMin].some((value) => value === null)) {
        return 0;
    }

    const shiftMinutes = Math.max(0, shiftEndMin - shiftStartMin);
    const lunchMinutes = Math.max(0, lunchEndMin - lunchStartMin);
    return Math.round(Math.max(0, shiftMinutes - lunchMinutes) / 60 * 10) / 10;
}

function validateSiteScheduleTimes(shiftStart, lunchStart, lunchEnd, shiftEnd) {
    const shiftStartMin = timeToMinutes(shiftStart);
    const lunchStartMin = timeToMinutes(lunchStart);
    const lunchEndMin = timeToMinutes(lunchEnd);
    const shiftEndMin = timeToMinutes(shiftEnd);

    if ([shiftStartMin, lunchStartMin, lunchEndMin, shiftEndMin].some((value) => value === null)) {
        return 'Please complete shift and lunch schedule times.';
    }

    if (shiftStartMin >= lunchStartMin) {
        return 'Lunch start must be after shift start.';
    }

    if (lunchStartMin >= lunchEndMin) {
        return 'Lunch end must be after lunch start.';
    }

    if (lunchEndMin >= shiftEndMin) {
        return 'Shift end must be after lunch end.';
    }

    return '';
}

function renderSiteScheduleTimeline(site) {
    const shiftStart = site.Shift_Start || site.Start_Time || '07:00';
    const lunchStart = site.Lunch_Start || '12:00';
    const lunchEnd = site.Lunch_End || '13:00';
    const shiftEnd = site.Shift_End || site.End_Time || '17:00';
    const workingHours = site.Working_Hours ?? calculateWorkingHoursFromTimes(shiftStart, lunchStart, lunchEnd, shiftEnd);
    const hoursLabel = site.Total_Hours || `${workingHours} hrs`;

    return `
        <div class="work-box">
            <div class="work-top">
                <div class="work-title">
                    <i class="far fa-clock"></i>
                    <span>Working Hours</span>
                </div>
                <div class="hours-badge">${escapeHtml(hoursLabel)}</div>
            </div>

            <div class="site-schedule-timeline">
                <div class="schedule-step">
                    <div class="schedule-step-label">Shift Start</div>
                    <div class="schedule-step-time">${escapeHtml(formatTimeDisplay(shiftStart))}</div>
                </div>
                <div class="schedule-arrow"><i class="fas fa-arrow-down"></i></div>
                <div class="schedule-step schedule-step-lunch">
                    <div class="schedule-step-label">Lunch Break</div>
                    <div class="schedule-step-time">${escapeHtml(formatTimeDisplay(lunchStart))} - ${escapeHtml(formatTimeDisplay(lunchEnd))}</div>
                </div>
                <div class="schedule-arrow"><i class="fas fa-arrow-down"></i></div>
                <div class="schedule-step">
                    <div class="schedule-step-label">Shift End</div>
                    <div class="schedule-step-time">${escapeHtml(formatTimeDisplay(shiftEnd))}</div>
                </div>
            </div>
        </div>
    `;
}

function updateAddSiteConfirmSummary() {
    const values = getAddSiteFormValues();
    const confirmSiteName = document.getElementById('confirmSiteName');
    const confirmSiteStatus = document.getElementById('confirmSiteStatus');
    const confirmSiteLocation = document.getElementById('confirmSiteLocation');
    const confirmSiteCoordinates = document.getElementById('confirmSiteCoordinates');
    const confirmSiteManager = document.getElementById('confirmSiteManager');
    const confirmSiteWorkers = document.getElementById('confirmSiteWorkers');
    const confirmSiteStartDate = document.getElementById('confirmSiteStartDate');
    const confirmSiteHours = document.getElementById('confirmSiteHours');

    if (confirmSiteName) confirmSiteName.textContent = values.siteName || '-';
    if (confirmSiteLocation) confirmSiteLocation.textContent = values.location || '-';
    if (confirmSiteCoordinates) {
        confirmSiteCoordinates.textContent = values.coordinates
            ? `Coordinates: ${values.coordinates}`
            : 'Coordinates: Not set';
    }
    if (confirmSiteManager) confirmSiteManager.textContent = values.siteManager || 'Not assigned';
    if (confirmSiteWorkers) confirmSiteWorkers.textContent = `${values.requiredWorkers || 0} required`;
    if (confirmSiteStartDate) confirmSiteStartDate.textContent = values.startDate || '-';
    if (confirmSiteHours) {
        const workingHours = calculateWorkingHoursFromTimes(
            values.shiftStart,
            values.lunchStart,
            values.lunchEnd,
            values.shiftEnd
        );
        confirmSiteHours.textContent = `${formatTimeDisplay(values.shiftStart)} → ${formatTimeDisplay(values.lunchStart)}-${formatTimeDisplay(values.lunchEnd)} → ${formatTimeDisplay(values.shiftEnd)} (${workingHours} hrs)`;
    }
    if (confirmSiteStatus) {
        confirmSiteStatus.textContent = 'Inactive until workers are assigned';
        confirmSiteStatus.className = 'badge-active badge-inactive';
    }
}

function setSiteMapFeedback(message, type = '') {
    if (!siteMapFeedback) {
        return;
    }

    siteMapFeedback.className = `site-map-feedback${type ? ` ${type}` : ''}`;
    siteMapFeedback.textContent = message || '';
}

const parseCoordinateValue = parseSiteCoordinates;

function updateCoordinateInput(latitude, longitude) {
    if (!siteCoordinatesInput) {
        return;
    }

    siteCoordinatesInput.value = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    if (activeSiteState.addSiteStep === 3) {
        updateAddSiteConfirmSummary();
    }
}

function ensureMapVisible() {
    if (siteLocationMap) {
        siteLocationMap.classList.add('active');
    }
    if (siteMapPlaceholder) {
        siteMapPlaceholder.style.display = 'none';
    }
}

function showMapPlaceholder(message) {
    if (siteMapPlaceholder) {
        const textNode = siteMapPlaceholder.querySelector('span');
        if (textNode && message) {
            textNode.textContent = message;
        }
        siteMapPlaceholder.style.display = 'flex';
    }
    if (siteLocationMap) {
        siteLocationMap.classList.remove('active');
    }
}

function loadLeafletAssets() {
    if (window.L?.map) {
        return Promise.resolve(window.L);
    }

    if (activeSiteState.addSiteMap.leafletReady) {
        return activeSiteState.addSiteMap.leafletReady;
    }

    activeSiteState.addSiteMap.leafletReady = new Promise((resolve, reject) => {
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

    return activeSiteState.addSiteMap.leafletReady;
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

    if (Array.isArray(center)) {
        return center;
    }

    return [center.lat, center.lng];
}

function refreshLeafletMap(map, center = null) {
    if (!window.L?.map || !map) {
        return;
    }

    const doResize = () => {
        map.invalidateSize();
        const normalizedCenter = normalizeLeafletCenter(center);
        if (normalizedCenter) {
            map.setView(normalizedCenter, map.getZoom(), { animate: false });
        }
    };

    setTimeout(doResize, 0);
    setTimeout(doResize, 100);
    setTimeout(doResize, 250);
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

    let results;
    try {
        results = await response.json();
    } catch (error) {
        throw new Error('Location search returned invalid JSON.');
    }

    const firstResult = Array.isArray(results) ? results[0] : null;
    const latitude = Number(firstResult?.lat);
    const longitude = Number(firstResult?.lon);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null;
    }

    return { latitude, longitude };
}

async function reverseGeocodePhilippineLocation(latitude, longitude) {
    const params = new URLSearchParams({
        format: 'jsonv2',
        lat: String(latitude),
        lon: String(longitude),
        addressdetails: '1',
        zoom: '18',
        countrycodes: 'ph'
    });

    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${params.toString()}`, {
        headers: { 'Accept-Language': 'en' }
    });
    if (!response.ok) {
        throw new Error(`Reverse location search failed: ${response.status}`);
    }

    const result = await response.json();
    return String(result?.display_name || '').trim();
}

function fillSiteLocationFromGps(address, latitude, longitude) {
    if (!siteLocationInput) return;

    siteLocationInput.value = address || `Current location (${latitude.toFixed(6)}, ${longitude.toFixed(6)})`;
    siteLocationInput.setCustomValidity('');
    siteLocationInput.dispatchEvent(new Event('input', { bubbles: true }));
    siteLocationInput.dispatchEvent(new Event('change', { bubbles: true }));
}

async function ensureSiteLocationMap() {
    if (!siteLocationMap) {
        return null;
    }

    if (activeSiteState.addSiteMap.instance) {
        ensureMapVisible();
        refreshLeafletMap(activeSiteState.addSiteMap.instance);
        return activeSiteState.addSiteMap.instance;
    }

    const center = activeSiteState.addSiteMap.defaultCenter;

    await loadLeafletAssets();

    const map = createLeafletMap(siteLocationMap, center, activeSiteState.addSiteMap.defaultZoom);

    map.on('click', (event) => {
        placeSiteMarker(event.latlng.lat, event.latlng.lng, true);
        setSiteMapFeedback('Pin moved. You can drag it again if needed.', 'success');
    });

    activeSiteState.addSiteMap.instance = map;
    ensureMapVisible();
    refreshLeafletMap(map, center);
    return map;
}

function parseRadiusMeters(value) {
    const n = Number(String(value ?? '').trim());
    if (!Number.isFinite(n)) return null;
    return n;
}

function getRadiusForAddSite() {
    const raw = geofenceRadiusInput?.value;
    const hidden = geofenceRadiusHiddenInput?.value;
    // The visible input is the source of truth. The hidden value is only a
    // submission fallback, otherwise an earlier value prevents edits here.
    return parseRadiusMeters(raw || hidden);
}

function getCenterForAddSite() {
    const lat = parseFloat(geofenceLatitudeInput?.value);
    const lng = parseFloat(geofenceLongitudeInput?.value);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    return { latitude: lat, longitude: lng };
}

function setAddGeofenceHidden(latitude, longitude) {
    if (geofenceLatitudeInput) geofenceLatitudeInput.value = String(latitude);
    if (geofenceLongitudeInput) geofenceLongitudeInput.value = String(longitude);
}

function renderAddGeofenceCircle() {
    const map = activeSiteState.addSiteMap.instance;
    if (!map) return;

    const radiusM = getRadiusForAddSite();
    const center = getCenterForAddSite();

    // Clear circle if invalid/incomplete.
    if (!radiusM || radiusM <= 0 || !center) {
        if (activeSiteState.addSiteMap.geofenceCircle) {
            activeSiteState.addSiteMap.geofenceCircle.remove();
            activeSiteState.addSiteMap.geofenceCircle = null;
        }
        return;
    }

    const centerLatLng = [center.latitude, center.longitude];

    if (!activeSiteState.addSiteMap.geofenceCircle) {
        activeSiteState.addSiteMap.geofenceCircle = window.L.circle(centerLatLng, {
            radius: radiusM,
            color: '#f97316',
            weight: 2,
            fillColor: '#fb923c',
            fillOpacity: 0.25
        }).addTo(map);
    } else {
        activeSiteState.addSiteMap.geofenceCircle.setLatLng(centerLatLng);
        activeSiteState.addSiteMap.geofenceCircle.setRadius(radiusM);
    }
}

function syncAddGeofenceCircleWithInputs() {
    // Keep hidden radius field synced.
    const radiusM = getRadiusForAddSite();
    if (geofenceRadiusHiddenInput) {
        geofenceRadiusHiddenInput.value = radiusM && radiusM > 0 ? String(radiusM) : '';
    }

    // If coordinates were typed/filled, sync hidden lat/lng.
    if (siteCoordinatesInput?.value) {
        const parsed = parseCoordinateValue(siteCoordinatesInput.value);
        if (parsed) {
            setAddGeofenceHidden(parsed.latitude, parsed.longitude);
        }
    }

    // If pin/marker already exists but hidden lat/lng weren't set yet,
    // use marker's current position as the circle center.
    if ((!geofenceLatitudeInput?.value || !geofenceLongitudeInput?.value) && activeSiteState.addSiteMap.marker) {
        const pos = activeSiteState.addSiteMap.marker.getLatLng();
        if (pos && Number.isFinite(pos.lat) && Number.isFinite(pos.lng)) {
            setAddGeofenceHidden(pos.lat, pos.lng);
        }
    }

    renderAddGeofenceCircle();
}

async function placeSiteMarker(latitude, longitude, centerMap = false) {
    const map = await ensureSiteLocationMap();
    if (!map) {
        return;
    }

    const position = [latitude, longitude];

    if (!activeSiteState.addSiteMap.marker) {
        activeSiteState.addSiteMap.marker = window.L.marker(position, {
            draggable: true
        }).addTo(map);
        activeSiteState.addSiteMap.marker.on('dragend', (event) => {
            const markerPosition = event.target.getLatLng();
            updateCoordinateInput(markerPosition.lat, markerPosition.lng);
            setAddGeofenceHidden(markerPosition.lat, markerPosition.lng);
            syncAddGeofenceCircleWithInputs();
            setSiteMapFeedback('Pin updated from the map.', 'success');
        });
    } else {
        activeSiteState.addSiteMap.marker.setLatLng(position);
    }

    // Update circle center whenever marker moves.
    setAddGeofenceHidden(latitude, longitude);
    syncAddGeofenceCircleWithInputs();


    if (centerMap) {
        map.setView(position, 16);
    }

    updateCoordinateInput(latitude, longitude);
    setAddGeofenceHidden(latitude, longitude);
    ensureMapVisible();
    refreshLeafletMap(map, position);

    // Update geofence circle after marker update.
    syncAddGeofenceCircleWithInputs();
}



async function syncMapToCoordinates() {
    const parsedCoordinates = parseCoordinateValue(siteCoordinatesInput?.value || '');
    if (!parsedCoordinates) {
        return;
    }

    await placeSiteMarker(parsedCoordinates.latitude, parsedCoordinates.longitude, true);
}

function useCurrentLocationForSite() {
    if (!navigator.geolocation) {
        setSiteMapFeedback('Your browser does not support location access.', 'error');
        return;
    }

    if (useMyLocationBtn) useMyLocationBtn.disabled = true;
    setSiteMapFeedback('Getting your current location...', 'info');
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            try {
                await placeSiteMarker(latitude, longitude, true);
                let address = '';
                try {
                    address = await reverseGeocodePhilippineLocation(latitude, longitude);
                } catch (reverseError) {
                    console.warn('Could not retrieve the address for the current location:', reverseError);
                }
                fillSiteLocationFromGps(address, latitude, longitude);
                setSiteMapFeedback(
                    address
                        ? 'Current location and site address added. Drag the marker to adjust the exact site entrance.'
                        : 'Current location pinned and coordinates added. You can edit the site location text if needed.',
                    'success'
                );
            } catch (error) {
                setSiteMapFeedback('Unable to place your current location on the map.', 'error');
            } finally {
                if (useMyLocationBtn) useMyLocationBtn.disabled = false;
            }
        },
        (error) => {
            const messages = {
                1: 'Location permission was denied. Allow location access and try again.',
                2: 'Your current location is unavailable.',
                3: 'Getting your location took too long. Please try again.'
            };
            setSiteMapFeedback(messages[error.code] || 'Unable to get your current location.', 'error');
            if (useMyLocationBtn) useMyLocationBtn.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

useMyLocationBtn?.addEventListener('click', useCurrentLocationForSite);

async function geocodeSiteLocation() {
    const locationText = siteLocationInput?.value.trim() || '';
    if (!locationText) {
        await ensureSiteLocationMap();
        setSiteMapFeedback('Map loaded in the Philippines. Click the map to pin the site location.', 'info');
        return;
    }

    if (activeSiteState.addSiteMap.searchAbortController) {
        activeSiteState.addSiteMap.searchAbortController.abort();
    }

    const abortController = new AbortController();
    activeSiteState.addSiteMap.searchAbortController = abortController;

    if (findLocationOnMapBtn) {
        findLocationOnMapBtn.disabled = true;
    }

    setSiteMapFeedback('Searching location and loading map...', 'info');

    try {
        await loadLeafletAssets();
        const result = await geocodePhilippineLocation(locationText, abortController.signal);
        if (abortController.signal.aborted) {
            return;
        }

        if (!result) {
            await ensureSiteLocationMap();
            setSiteMapFeedback('No Philippine location match found. Try adding city or province.', 'error');
            return;
        }

        await placeSiteMarker(result.latitude, result.longitude, true);
        setSiteMapFeedback('Map loaded. Drag the pin or click the map to set the exact site location.', 'success');
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error('Failed to geocode site location:', error);
        await ensureSiteLocationMap().catch(() => {});
        setSiteMapFeedback('Could not search that location right now. Please try again.', 'error');
    } finally {
        if (activeSiteState.addSiteMap.searchAbortController === abortController) {
            activeSiteState.addSiteMap.searchAbortController = null;
        }
        if (findLocationOnMapBtn) {
            findLocationOnMapBtn.disabled = false;
        }
    }
}

function queueLocationSearch() {
    clearTimeout(activeSiteState.addSiteMap.geocodeTimer);

    const locationText = siteLocationInput?.value.trim() || '';
    if (locationText.length < 5) {
        return;
    }

    activeSiteState.addSiteMap.geocodeTimer = setTimeout(() => {
        geocodeSiteLocation();
    }, 700);
}

function renderAddSiteStep() {
    const currentStep = activeSiteState.addSiteStep;

    addSiteStepPanels.forEach((panel, index) => {
        panel.classList.toggle('active', index === currentStep);
    });

    addSiteStepIndicators.forEach((indicator, index) => {
        indicator.classList.remove('completed', 'current');
        const circle = indicator.querySelector('.step-circle');

        if (index < currentStep) {
            indicator.classList.add('completed');
            if (circle) circle.textContent = '✓';
        } else if (index === currentStep) {
            indicator.classList.add('current');
            if (circle) circle.textContent = String(index + 1);
        } else if (circle) {
            circle.textContent = String(index + 1);
        }
    });

    addSiteStepLines.forEach((line, index) => {
        line.classList.toggle('active', index < currentStep);
    });

    if (addSiteModal) {
        // Some earlier logic depended on a CSS selector on the modal overlay.
        // Enforce it directly so Step 4 reliably shows the Confirm button.
        addSiteModal.classList.toggle('confirm-step-active', currentStep === 3);

        // Safety: also directly enforce visibility of the two footer buttons.
        const isConfirm = currentStep === 3;
        if (addSiteNextBtn) {
            addSiteNextBtn.style.setProperty('display', isConfirm ? 'none' : 'inline-flex', 'important');
        }
        if (addSiteSubmitBtn) {
            addSiteSubmitBtn.style.setProperty('display', isConfirm ? 'inline-flex' : 'none', 'important');
        }

        // Ensure buttons are not hidden by other CSS/layout rules.
        if (addSiteNextBtn) addSiteNextBtn.style.visibility = isConfirm ? 'hidden' : 'visible';
        if (addSiteSubmitBtn) addSiteSubmitBtn.style.visibility = isConfirm ? 'visible' : 'hidden';
    }

    if (addSiteBackBtn) {
        addSiteBackBtn.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
    }

    if (currentStep === 3) {
        updateAddSiteConfirmSummary();
    }

    if (currentStep === 1) {
        setTimeout(() => {
            ensureSiteLocationMap()
                .then(() => syncMapToCoordinates())
                .then(() => {
                    if (!siteCoordinatesInput?.value.trim()) {
                        setSiteMapFeedback('Map loaded in the Philippines. Click the map to pin the site location.', 'info');
                    }
                    if (activeSiteState.addSiteMap.instance) {
                        refreshLeafletMap(activeSiteState.addSiteMap.instance);
                    }
                })
                .catch((error) => {
                    console.error('Failed to load site picker map:', error);
                    setSiteMapFeedback('Could not load the map right now. Please try again.', 'error');
                });
        }, 120);
    }
}

function validateAddSiteStep(stepIndex) {
    const values = getAddSiteFormValues();


    if (stepIndex === 0 && !values.siteName) {
        alert('Please enter the site name.');
        return false;
    }

    if (stepIndex === 0 && document.getElementById('siteName')?.validationMessage) {
        alert(document.getElementById('siteName').validationMessage);
        return false;
    }

    if (stepIndex === 1 && !values.location) {
        alert('Please enter the site location.');
        return false;
    }

    if (stepIndex === 1 && !validateSiteLocationField(document.getElementById('location'))) {
        alert(document.getElementById('location').validationMessage);
        return false;
    }

    if (stepIndex === 1 && document.getElementById('location')?.validationMessage) {
        alert(document.getElementById('location').validationMessage);
        return false;
    }

    // Geofence validation (required for location locking)
    if (stepIndex === 1) {
        const coordinatesField = document.getElementById('siteCoordinates');
        const radiusField = document.getElementById('geofenceRadiusM');
        if (coordinatesField?.value.trim() && !parseSiteCoordinates(coordinatesField.value)) {
            markFieldForLiveValidation(coordinatesField, 'Enter valid numeric coordinates as latitude, longitude.');
            alert(coordinatesField.validationMessage);
            return false;
        }
        if (radiusField?.validationMessage) {
            alert(radiusField.validationMessage);
            return false;
        }
        const radiusM = parseRadiusMeters(geofenceRadiusHiddenInput?.value || geofenceRadiusInput?.value);
        const center = getCenterForAddSite();
        if (!center) {
            alert('Please set the pin location on the map for geofence center.');
            return false;
        }
        if (!radiusM || radiusM <= 0) {
            alert('Please enter a valid Location Lock radius in meters.');
            return false;
        }
    }


    if (stepIndex === 2) {
        const managerField = document.getElementById('siteManager');
        if (!values.siteManager) {
            alert('Please enter the manager name.');
            return false;
        }
        if (managerField?.validationMessage) {
            alert(managerField.validationMessage);
            return false;
        }

        if (!values.requiredWorkers || !validateTargetCapacityField(document.getElementById('requiredWorkers'))) {
            alert(document.getElementById('requiredWorkers')?.validationMessage || 'Please enter the target capacity.');
            return false;
        }

        if (!values.startDate) {
            alert('Please select the start date.');
            return false;
        }

        if (isPastDateValue(values.startDate)) {
            alert('Start date cannot be in the past.');
            return false;
        }

        const scheduleError = validateSiteScheduleTimes(
            values.shiftStart,
            values.lunchStart,
            values.lunchEnd,
            values.shiftEnd
        );
        if (scheduleError) {
            alert(scheduleError);
            return false;
        }
    }

    return true;
}

function getTodayDateValue() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function isPastDateValue(value) {
    return Boolean(value) && value < getTodayDateValue();
}

function applySiteDateMinimums() {
    const today = getTodayDateValue();
    ['startDate', 'editStartDate', 'editEndDate'].forEach((id) => {
        const input = document.getElementById(id);
        if (input) {
            input.min = today;
        }
    });
}

applySiteDateMinimums();

function closeModalFunc() {
    releaseModalFocus(addSiteModal);

    if (addSiteModal) {
        addSiteModal.classList.remove('active');
        addSiteModal.setAttribute('aria-hidden', 'true');
    }

    // Restore scrolling
    document.body.style.overflow = '';

    if (addSiteForm) addSiteForm.reset();
    markFieldForLiveValidation(document.getElementById('siteName'), '');
    activeSiteState.addSiteStep = 0;

    const startDateEl = document.getElementById('startDate');
    if (startDateEl) startDateEl.value = getTodayDateValue();
    const shiftStartEl = document.getElementById('shiftStart');
    const lunchStartEl = document.getElementById('lunchStart');
    const lunchEndEl = document.getElementById('lunchEnd');
    const shiftEndEl = document.getElementById('shiftEnd');
    if (shiftStartEl) shiftStartEl.value = '07:00';
    if (lunchStartEl) lunchStartEl.value = '12:00';
    if (lunchEndEl) lunchEndEl.value = '13:00';
    if (shiftEndEl) shiftEndEl.value = '17:00';

    clearTimeout(activeSiteState.addSiteMap.geocodeTimer);
    if (activeSiteState.addSiteMap.searchAbortController) {
        activeSiteState.addSiteMap.searchAbortController.abort();
        activeSiteState.addSiteMap.searchAbortController = null;
    }
    showMapPlaceholder('The map will appear here after you search for a location.');
    setSiteMapFeedback('Search for a location in the Philippines to load the map.');
    if (activeSiteState.addSiteMap.marker) {
        activeSiteState.addSiteMap.marker.off();
        activeSiteState.addSiteMap.marker.remove();
        activeSiteState.addSiteMap.marker = null;
    }
    if (activeSiteState.addSiteMap.instance) {
        activeSiteState.addSiteMap.instance.off();
        activeSiteState.addSiteMap.instance.remove();
        activeSiteState.addSiteMap.instance = null;
        if (siteLocationMap) {
            siteLocationMap.innerHTML = '';
        }
    }

    renderAddSiteStep();
}



if (btnAddSite && addSiteModal) {
    btnAddSite.hidden = !activeSiteCanManageSites;
    btnAddSite.addEventListener('click', () => {
        if (!activeSiteCanManageSites) return;
        applySiteDateMinimums();
        addSiteModal.classList.add('active');
        addSiteModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        activeSiteState.addSiteStep = 0;
        const startDateEl = document.getElementById('startDate');
        if (startDateEl) startDateEl.value = getTodayDateValue();
        const shiftStartEl = document.getElementById('shiftStart');
        const lunchStartEl = document.getElementById('lunchStart');
        const lunchEndEl = document.getElementById('lunchEnd');
        const shiftEndEl = document.getElementById('shiftEnd');
        if (shiftStartEl && !shiftStartEl.value) shiftStartEl.value = '07:00';
        if (lunchStartEl && !lunchStartEl.value) lunchStartEl.value = '12:00';
        if (lunchEndEl && !lunchEndEl.value) lunchEndEl.value = '13:00';
        if (shiftEndEl && !shiftEndEl.value) shiftEndEl.value = '17:00';
        renderAddSiteStep();

        // Focus first field for accessibility
        const firstInput = document.getElementById('siteName');
        firstInput?.focus?.();
    });
}

if (closeModal) closeModal.addEventListener('click', closeModalFunc);
// cancelBtn doesn't exist in the current modal HTML; ignore if absent
if (cancelBtn) cancelBtn.addEventListener('click', closeModalFunc);

if (addSiteBackBtn) {
    addSiteBackBtn.addEventListener('click', () => {
        if (activeSiteState.addSiteStep > 0) {
            activeSiteState.addSiteStep -= 1;
            renderAddSiteStep();
        }
    });
}

if (addSiteNextBtn) {
    addSiteNextBtn.addEventListener('click', async () => {
        if (activeSiteState.addSiteStep === 0) {
            const siteNameField = document.getElementById('siteName');
            const isDuplicate = await checkSiteNameDuplicate(siteNameField, 0, 'add');
            if (isDuplicate) {
                alert(siteNameField.validationMessage || 'A site with this name already exists.');
                return;
            }
        }

        if (activeSiteState.addSiteStep === 1) {
            const locationField = document.getElementById('location');
            const isDuplicate = await checkSiteLocationDuplicate(locationField, 0, 'add');
            if (isDuplicate) {
                alert(locationField.validationMessage || 'A site with this exact location already exists.');
                return;
            }
        }

        if (!validateAddSiteStep(activeSiteState.addSiteStep)) {
            return;
        }

        if (activeSiteState.addSiteStep < 3) {
            activeSiteState.addSiteStep += 1;
            renderAddSiteStep();
        }
    });
}

if (addSiteModal) {
    addSiteModal.addEventListener('click', (e) => {
        if (e.target === addSiteModal) {
            closeModalFunc();
        }
    });
}

renderAddSiteStep();

if (findLocationOnMapBtn) {
    findLocationOnMapBtn.addEventListener('click', () => {
        geocodeSiteLocation();
    });
}

if (siteLocationInput) {
    siteLocationInput.addEventListener('input', queueLocationSearch);
    siteLocationInput.addEventListener('change', queueLocationSearch);
}

if (siteCoordinatesInput) {
    siteCoordinatesInput.addEventListener('change', () => {
        syncMapToCoordinates();
    });
    siteCoordinatesInput.addEventListener('blur', () => {
        syncMapToCoordinates();
    });
}

// Geofence radius updates in real-time (Add)
if (geofenceRadiusInput) {
    geofenceRadiusInput.addEventListener('input', () => {
        if (activeSiteState.addSiteStep >= 1) {
            syncAddGeofenceCircleWithInputs();
        }
    });
    geofenceRadiusInput.addEventListener('change', () => {
        syncAddGeofenceCircleWithInputs();
    });
}


// ESC closes modal + basic focus trap for accessibility
(function setupAddSiteAccessibility() {
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    function getFocusable() {
        const modal = document.getElementById('addSiteModal');
        if (!modal) return [];
        return Array.from(modal.querySelectorAll(focusableSelector))
            .filter((el) => el.offsetParent !== null || el === document.activeElement);
    }

    function onKeyDown(e) {
        if (!addSiteModal || !addSiteModal.classList.contains('active')) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            closeModalFunc();
            return;
        }

        if (e.key === 'Tab') {
            const focusable = getFocusable();
            if (!focusable.length) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const active = document.activeElement;

            if (e.shiftKey) {
                if (active === first || !modalContains(active)) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (active === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }
    }

    function modalContains(el) {
        return !!(addSiteModal && el && addSiteModal.contains(el));
    }

    document.addEventListener('keydown', onKeyDown);
})();

function getSelectedSite() {
    return activeSiteState.siteMap.get(activeSiteState.assignmentModal.siteId) || null;
}

function setAssignmentFeedback(message, type = '') {
    if (!assignWorkersFeedback) {
        return;
    }

    assignWorkersFeedback.className = `assign-workers-feedback${type ? ` ${type}` : ''}`;
    assignWorkersFeedback.textContent = message || '';
}

function setEditSiteFeedback(message, type = '') {
    if (!editSiteFeedback) {
        return;
    }

    editSiteFeedback.className = `edit-site-feedback${type ? ` ${type}` : ''}`;
    editSiteFeedback.textContent = message || '';
}

function setEditSiteMapFeedback(message, type = '') {
    if (!editSiteMapFeedback) {
        return;
    }

    editSiteMapFeedback.className = `site-map-feedback${type ? ` ${type}` : ''}`;
    editSiteMapFeedback.textContent = message || '';
}

function updateEditCoordinateInput(latitude, longitude) {
    if (!editSiteCoordinatesInput) {
        return;
    }

    editSiteCoordinatesInput.value = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
}

async function ensureEditSiteLocationMap() {
    // Ensure geofence circle exists if inputs were already set.

    if (!editSiteLocationMap) {
        return null;
    }

    if (activeSiteState.editSiteMap.instance) {
        refreshLeafletMap(activeSiteState.editSiteMap.instance);
        return activeSiteState.editSiteMap.instance;
    }

    const center = activeSiteState.editSiteMap.defaultCenter;

    await loadLeafletAssets();

    const map = createLeafletMap(editSiteLocationMap, center, activeSiteState.editSiteMap.defaultZoom);

    map.on('click', (event) => {
        placeEditSiteMarker(event.latlng.lat, event.latlng.lng, true);
        setEditSiteMapFeedback('Pin moved. You can drag it again if needed.', 'success');
    });

    activeSiteState.editSiteMap.instance = map;
    refreshLeafletMap(map, center);
    syncEditGeofenceCircleWithInputs();
    return map;
}


function parseRadiusMeters(value) {
    const n = Number(String(value ?? '').trim());
    if (!Number.isFinite(n)) return null;
    return n;
}

function getRadiusForEditSite() {
    const raw = document.getElementById('editGeofenceRadiusM')?.value;
    const hidden = document.getElementById('editGeofenceRadiusMHidden')?.value;
    return parseRadiusMeters(raw || hidden);
}

function getCenterForEditSite() {
    const lat = parseFloat(document.getElementById('editGeofenceLatitude')?.value);
    const lng = parseFloat(document.getElementById('editGeofenceLongitude')?.value);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    return { latitude: lat, longitude: lng };
}

function setEditGeofenceHidden(latitude, longitude) {
    const latEl = document.getElementById('editGeofenceLatitude');
    const lngEl = document.getElementById('editGeofenceLongitude');
    if (latEl) latEl.value = String(latitude);
    if (lngEl) lngEl.value = String(longitude);
}

function renderEditGeofenceCircle() {
    const map = activeSiteState.editSiteMap.instance;
    if (!map) return;

    const radiusM = getRadiusForEditSite();
    const center = getCenterForEditSite();

    if (!radiusM || radiusM <= 0 || !center) {
        // no dedicated state holder for edit circle yet; reuse add circle holder is wrong.
        // We'll attach to the map via property.
        if (activeSiteState.editSiteMap.geofenceCircle) {
            activeSiteState.editSiteMap.geofenceCircle.remove();
            activeSiteState.editSiteMap.geofenceCircle = null;
        }
        return;
    }

    const centerLatLng = [center.latitude, center.longitude];

    if (!activeSiteState.editSiteMap.geofenceCircle) {
        activeSiteState.editSiteMap.geofenceCircle = window.L.circle(centerLatLng, {
            radius: radiusM,
            color: '#f97316',
            weight: 2,
            fillColor: '#fb923c',
            fillOpacity: 0.25
        }).addTo(map);
    } else {
        activeSiteState.editSiteMap.geofenceCircle.setLatLng(centerLatLng);
        activeSiteState.editSiteMap.geofenceCircle.setRadius(radiusM);
    }
}

function syncEditGeofenceCircleWithInputs() {
    const radiusM = getRadiusForEditSite();
    const hidden = document.getElementById('editGeofenceRadiusMHidden');
    if (hidden) {
        hidden.value = radiusM && radiusM > 0 ? String(radiusM) : '';
    }

    // Ensure hidden lat/lng exists if coordinates were parsed/edited.
    if (editSiteCoordinatesInput?.value) {
        const parsed = parseCoordinateValue(editSiteCoordinatesInput.value);
        if (parsed) {
            setEditGeofenceHidden(parsed.latitude, parsed.longitude);
        }
    }

    renderEditGeofenceCircle();
}

async function placeEditSiteMarker(latitude, longitude, centerMap = false) {

    const map = await ensureEditSiteLocationMap();


    if (!map) {
        return;
    }

    const position = [latitude, longitude];

    if (!activeSiteState.editSiteMap.marker) {
        activeSiteState.editSiteMap.marker = window.L.marker(position, {
            draggable: true
        }).addTo(map);
        activeSiteState.editSiteMap.marker.on('dragend', (event) => {
            const markerPosition = event.target.getLatLng();
            updateEditCoordinateInput(markerPosition.lat, markerPosition.lng);
            setEditGeofenceHidden(markerPosition.lat, markerPosition.lng);
            syncEditGeofenceCircleWithInputs();
            setEditSiteMapFeedback('Pin updated from the map.', 'success');
        });
    } else {
        activeSiteState.editSiteMap.marker.setLatLng(position);
    }

    if (centerMap) {
        map.setView(position, 16);
    }

    updateEditCoordinateInput(latitude, longitude);
    setEditGeofenceHidden(latitude, longitude);
    syncEditGeofenceCircleWithInputs();
    refreshLeafletMap(map, position);
}

function useCurrentLocationForEditSite() {
    if (!navigator.geolocation) {
        setEditSiteMapFeedback('Your browser does not support location access.', 'error');
        return;
    }

    useMyEditLocationBtn.disabled = true;
    setEditSiteMapFeedback('Getting your current location...', 'info');
    navigator.geolocation.getCurrentPosition(async (position) => {
        const { latitude, longitude } = position.coords;
        try {
            await placeEditSiteMarker(latitude, longitude, true);
            let address = '';
            try {
                address = await reverseGeocodePhilippineLocation(latitude, longitude);
            } catch (error) {
                console.warn('Could not retrieve the current location address:', error);
            }
            if (editSiteLocationInput) {
                editSiteLocationInput.value = address || `Current location ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                editSiteLocationInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            setEditSiteMapFeedback('Current location pinned. Drag the pin to adjust the exact site entrance.', 'success');
        } catch (error) {
            setEditSiteMapFeedback('Unable to place your current location on the map.', 'error');
        } finally {
            useMyEditLocationBtn.disabled = false;
        }
    }, (error) => {
        const messages = { 1: 'Location permission was denied. Allow location access and try again.', 2: 'Your current location is unavailable.', 3: 'Getting your location took too long. Please try again.' };
        setEditSiteMapFeedback(messages[error.code] || 'Unable to get your current location.', 'error');
        useMyEditLocationBtn.disabled = false;
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
}


async function syncEditMapToCoordinates() {
    const parsedCoordinates = parseCoordinateValue(editSiteCoordinatesInput?.value || '');
    if (!parsedCoordinates) {
        return;
    }

    await placeEditSiteMarker(parsedCoordinates.latitude, parsedCoordinates.longitude, true);
}

async function geocodeEditSiteLocation() {
    const locationText = editSiteLocationInput?.value.trim() || '';
    if (!locationText) {
        setEditSiteMapFeedback('Enter a location first so we can search it on the map.', 'error');
        return;
    }

    if (activeSiteState.editSiteMap.searchAbortController) {
        activeSiteState.editSiteMap.searchAbortController.abort();
    }

    const abortController = new AbortController();
    activeSiteState.editSiteMap.searchAbortController = abortController;

    if (findEditLocationOnMapBtn) {
        findEditLocationOnMapBtn.disabled = true;
    }

    setEditSiteMapFeedback('Searching location and loading map...', 'info');

    try {
        await loadLeafletAssets();
        const result = await geocodePhilippineLocation(locationText, abortController.signal);
        if (abortController.signal.aborted) {
            return;
        }

        if (!result) {
            setEditSiteMapFeedback('No Philippine location match found. Try adding city or province.', 'error');
            return;
        }

        await placeEditSiteMarker(result.latitude, result.longitude, true);
        setEditSiteMapFeedback('Location found. Drag the pin to place the exact site.', 'success');
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error('Edit site map search failed:', error);
            setEditSiteMapFeedback('Could not load the map location right now.', 'error');
        }
    } finally {
        if (activeSiteState.editSiteMap.searchAbortController === abortController) {
            activeSiteState.editSiteMap.searchAbortController = null;
        }
        if (findEditLocationOnMapBtn) {
            findEditLocationOnMapBtn.disabled = false;
        }
    }
}

function queueEditSiteLocationSearch() {
    clearTimeout(activeSiteState.editSiteMap.geocodeTimer);

    if (!editSiteLocationInput?.value.trim()) {
        setEditSiteMapFeedback('', '');
        return;
    }

    activeSiteState.editSiteMap.geocodeTimer = setTimeout(() => {
        geocodeEditSiteLocation();
    }, 650);
}

function resetEditSiteMapState() {
    clearTimeout(activeSiteState.editSiteMap.geocodeTimer);
    if (activeSiteState.editSiteMap.searchAbortController) {
        activeSiteState.editSiteMap.searchAbortController.abort();
        activeSiteState.editSiteMap.searchAbortController = null;
    }

    if (activeSiteState.editSiteMap.marker) {
        activeSiteState.editSiteMap.marker.off();
        activeSiteState.editSiteMap.marker.remove();
        activeSiteState.editSiteMap.marker = null;
    }

    if (activeSiteState.editSiteMap.instance) {
        activeSiteState.editSiteMap.instance.off();
        activeSiteState.editSiteMap.instance.remove();
        activeSiteState.editSiteMap.instance = null;
        if (editSiteLocationMap) {
            editSiteLocationMap.innerHTML = '';
        }
    }

    setEditSiteMapFeedback('');
}

function closeSiteDetailsModal() {
    if (!siteDetailsModal) {
        return;
    }

    releaseModalFocus(siteDetailsModal);
    destroySiteDetailsMap();
    siteDetailsModal.classList.remove('active');
    siteDetailsModal.setAttribute('aria-hidden', 'true');
    if (siteDetailsBody) {
        siteDetailsBody.innerHTML = '';
    }
}

function closeEditSiteModal() {
    if (!editSiteModal) {
        return;
    }

    releaseModalFocus(editSiteModal);
    editSiteModal.classList.remove('active');
    editSiteModal.setAttribute('aria-hidden', 'true');
    if (editSiteForm) {
        editSiteForm.reset();
    }
    resetEditSiteMapState();
    setEditSiteFeedback('');
}

function openSiteDetails(siteId) {
    const site = activeSiteState.siteMap.get(Number(siteId));
    if (!site || !siteDetailsModal || !siteDetailsBody) {
        return;
    }

    closeEditSiteModal();

    const currentWorkers = Number(site.Current_Workers ?? site.currentWorkers ?? 0);
    const requiredWorkers = Number(site.Required_Workers || 0);
    const attendanceRate = requiredWorkers > 0 ? Math.round((currentWorkers / requiredWorkers) * 100) : 0;
    const detailsMap = buildSiteDetailsMap(site.Coordinates || '', site.Site_Name || 'Site', site.Geofence_Radius_M);

    if (siteDetailsTitle) {
        siteDetailsTitle.textContent = site.Site_Name || 'Site Details';
    }
    if (siteDetailsSubtitle) {
        siteDetailsSubtitle.textContent = site.Location || 'No location provided';
    }

    siteDetailsBody.innerHTML = `
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
                        <span class="site-status-badge ${toSafeClassName(site.Status || 'Active')}">${escapeHtml(site.Status || 'Active')}</span>
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
                        <strong>${requiredWorkers > 0 ? Math.min(100, Math.round((currentWorkers / requiredWorkers) * 100)) : 0}%</strong>
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
                            <label>WORK SCHEDULE</label>
                            <div>${escapeHtml(formatTimeDisplay(site.Shift_Start || site.Start_Time))} → ${escapeHtml(formatTimeDisplay(site.Lunch_Start || '12:00'))}-${escapeHtml(formatTimeDisplay(site.Lunch_End || '13:00'))} → ${escapeHtml(formatTimeDisplay(site.Shift_End || site.End_Time))}</div>
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

    siteDetailsModal.classList.add('active');
    siteDetailsModal.setAttribute('aria-hidden', 'false');
    renderSiteDetailsMap().catch((error) => {
        console.error('Failed to render site details map:', error);
    });
}

function openEditSite(siteId) {
    const site = activeSiteState.siteMap.get(Number(siteId));
    if (!site || !editSiteModal || !editSiteForm) {
        return;
    }

    closeSiteDetailsModal();

    const editFields = {
        editSiteId: site.SiteID || '',
        editSiteName: site.Site_Name || '',
        editSiteLocation: site.Location || '',
        editSiteCoordinates: site.Coordinates || '',
        editRequiredWorkers: site.Required_Workers || '',
        editSiteManager: site.Site_Manager || '',
        editSiteStatus: String(site.Status || 'Active').toLowerCase() === 'inactive' ? 'Inactive' : 'Active',
        editStartDate: site.Start_Date || '',
        editEndDate: site.End_Date || '',
        editShiftStart: site.Shift_Start || site.Start_Time || '07:00',
        editLunchStart: site.Lunch_Start || '12:00',
        editLunchEnd: site.Lunch_End || '13:00',
        editShiftEnd: site.Shift_End || site.End_Time || '17:00'
    };

    Object.entries(editFields).forEach(([id, value]) => {
        const field = document.getElementById(id);
        if (field) {
            field.value = value;
        }
    });
    const editRadiusInput = document.getElementById('editGeofenceRadiusM');
    const editRadiusHidden = document.getElementById('editGeofenceRadiusMHidden');
    const editLatitude = document.getElementById('editGeofenceLatitude');
    const editLongitude = document.getElementById('editGeofenceLongitude');
    if (editRadiusInput) editRadiusInput.value = Number(site.Geofence_Radius_M) > 0 ? String(site.Geofence_Radius_M) : '';
    if (editRadiusHidden) editRadiusHidden.value = Number(site.Geofence_Radius_M) > 0 ? String(site.Geofence_Radius_M) : '';
    if (editLatitude) editLatitude.value = site.Geofence_Lat ?? '';
    if (editLongitude) editLongitude.value = site.Geofence_Lng ?? '';
    applySiteDateMinimums();
    resetEditSiteMapState();
    setEditSiteFeedback('');
    setEditSiteMapFeedback(site.Coordinates ? 'Saved pin loaded. Drag it or click the map to adjust.' : 'Search a location or click the map to place a new pin.', site.Coordinates ? 'info' : '');

    editSiteModal.classList.add('active');
    editSiteModal.setAttribute('aria-hidden', 'false');
    ensureEditSiteLocationMap()
        .then(() => {
            if (site.Coordinates) {
                return syncEditMapToCoordinates();
            }

            return geocodeEditSiteLocation();
        })
        .catch((error) => {
            console.error('Failed to prepare edit site map:', error);
            setEditSiteMapFeedback('Could not load the map right now.', 'error');
        });
}

if (editSiteForm) {
    editSiteForm.addEventListener('submit', async (e) => {

        e.preventDefault();

        const siteId = Number(document.getElementById('editSiteId')?.value || 0);
        const siteName = document.getElementById('editSiteName')?.value.trim() || '';
        const location = document.getElementById('editSiteLocation')?.value.trim() || '';
        const coordinates = document.getElementById('editSiteCoordinates')?.value.trim() || '';
        const status = document.getElementById('editSiteStatus')?.value || 'Active';
        const requiredWorkers = Number(document.getElementById('editRequiredWorkers')?.value || 0);
        const siteManager = document.getElementById('editSiteManager')?.value.trim() || '';
        const startDate = document.getElementById('editStartDate')?.value || '';
        const endDate = document.getElementById('editEndDate')?.value || '';
        const shiftStart = document.getElementById('editShiftStart')?.value || '';
        const lunchStart = document.getElementById('editLunchStart')?.value || '';
        const lunchEnd = document.getElementById('editLunchEnd')?.value || '';
        const shiftEnd = document.getElementById('editShiftEnd')?.value || '';

        if (!siteId || !siteName || !location) {
            setEditSiteFeedback('Site name and location are required.', 'error');
            return;
        }

        if (!validateSiteLocationField(document.getElementById('editSiteLocation'))) {
            setEditSiteFeedback('Site location cannot exceed 255 characters.', 'error');
            return;
        }

        if (!validateTargetCapacityField(document.getElementById('editRequiredWorkers'))) {
            setEditSiteFeedback(document.getElementById('editRequiredWorkers').validationMessage, 'error');
            return;
        }

        if (coordinates && !parseSiteCoordinates(coordinates)) {
            setEditSiteFeedback('Coordinates must use the format "latitude, longitude".', 'error');
            return;
        }

        if (isPastDateValue(startDate)) {
            setEditSiteFeedback('Start date cannot be in the past.', 'error');
            return;
        }

        if (await checkSiteNameDuplicate(document.getElementById('editSiteName'), siteId, 'edit')) {
            setEditSiteFeedback('A site with this name already exists.', 'error');
            return;
        }

        if (await checkSiteLocationDuplicate(document.getElementById('editSiteLocation'), siteId, 'edit')) {
            setEditSiteFeedback('A site with this exact location already exists.', 'error');
            return;
        }

        if (isPastDateValue(endDate)) {
            setEditSiteFeedback('End date cannot be in the past.', 'error');
            return;
        }

        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            setEditSiteFeedback('End date cannot be earlier than the start date.', 'error');
            return;
        }

        const scheduleError = validateSiteScheduleTimes(shiftStart, lunchStart, lunchEnd, shiftEnd);
        if (scheduleError) {
            setEditSiteFeedback(scheduleError, 'error');
            return;
        }

        // Geofence validation (location locking)
        const editRadiusM = parseRadiusMeters(
            document.getElementById('editGeofenceRadiusMHidden')?.value || document.getElementById('editGeofenceRadiusM')?.value
        );
        let editCenter = {
            latitude: parseFloat(document.getElementById('editGeofenceLatitude')?.value),
            longitude: parseFloat(document.getElementById('editGeofenceLongitude')?.value)
        };
        if (!Number.isFinite(editCenter.latitude) || !Number.isFinite(editCenter.longitude)) {
            const coordinateCenter = parseSiteCoordinates(coordinates);
            if (coordinateCenter) {
                editCenter = coordinateCenter;
                document.getElementById('editGeofenceLatitude').value = String(coordinateCenter.latitude);
                document.getElementById('editGeofenceLongitude').value = String(coordinateCenter.longitude);
            }
        }
        if (!Number.isFinite(editCenter.latitude) || !Number.isFinite(editCenter.longitude)) {
            setEditSiteFeedback('Please set the pin location on the map for the geofence center.', 'error');
            return;
        }
        if (!editRadiusM || editRadiusM <= 0) {
            setEditSiteFeedback('Please enter a valid Location Lock radius in meters.', 'error');
            return;
        }


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
            shift_start: shiftStart || null,
            lunch_start: lunchStart || null,
            lunch_end: lunchEnd || null,
            shift_end: shiftEnd || null,
            start_time: shiftStart || null,
            end_time: shiftEnd || null,

            geofenceRadiusM: document.getElementById('editGeofenceRadiusMHidden')?.value || document.getElementById('editGeofenceRadiusM')?.value || null,
            geofenceLatitude: document.getElementById('editGeofenceLatitude')?.value || null,
            geofenceLongitude: document.getElementById('editGeofenceLongitude')?.value || null
        };

        const confirmed = typeof window.showConfirmModal === 'function'
            ? await window.showConfirmModal('Do you want to save these site changes?', {
                title: 'Confirm Save Changes',
                confirmText: 'Save Changes',
                cancelText: 'Cancel'
            })
            : window.confirm('Do you want to save these site changes?');
        if (!confirmed) return;


        const originalButtonText = saveEditSiteBtn?.innerHTML || 'Save Changes';
        if (saveEditSiteBtn) {
            saveEditSiteBtn.disabled = true;
            saveEditSiteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Saving...</span>';
        }
        setEditSiteFeedback('Saving site changes...', 'info');

        try {
            const result = await fetchJson('../api/update_site.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!result.success) {
                throw new Error(result.message || 'Failed to update site.');
            }

            setEditSiteFeedback('');
            window.showCrudResultModal?.(
                true,
                result.message || 'Site updated successfully.',
                'Site Update'
            );
            await loadSites();
            setTimeout(() => {
                closeEditSiteModal();
            }, 250);
        } catch (error) {
            console.error('Failed to update site:', error);
            setEditSiteFeedback('');
            window.showCrudResultModal?.(
                false,
                error.message || 'Could not update this site right now.',
                'Site Update'
            );
        } finally {
            if (saveEditSiteBtn) {
                saveEditSiteBtn.disabled = false;
                saveEditSiteBtn.innerHTML = originalButtonText;
            }
        }
    });
}

function closeAssignWorkersModal() {
    if (!assignWorkersModal) {
        return;
    }

    releaseModalFocus(assignWorkersModal);
    assignWorkersModal.classList.remove('active');
    assignWorkersModal.setAttribute('aria-hidden', 'true');
    activeSiteState.assignmentModal.siteId = null;
    activeSiteState.assignmentModal.workers = [];
    activeSiteState.assignmentModal.selectedWorkerIds = new Set();
    activeSiteState.assignmentModal.initialWorkerIds = new Set();
    activeSiteState.assignmentModal.roleByWorkerId = new Map();
    activeSiteState.assignmentModal.initialRoleByWorkerId = new Map();
    activeSiteState.assignmentModal.timekeepers = [];
    activeSiteState.assignmentModal.timekeeperUserId = null;
    activeSiteState.assignmentModal.initialTimekeeperUserId = null;
    activeSiteState.assignmentModal.searchTerm = '';
    activeSiteState.assignmentModal.statusFilter = '';
    activeSiteState.assignmentModal.roleFilter = '';
    activeSiteState.assignmentModal.locationFilter = '';
    activeSiteState.assignmentModal.saving = false;

    if (assignWorkersSearch) assignWorkersSearch.value = '';
    if (assignWorkersRoleFilter) assignWorkersRoleFilter.value = '';
    if (assignWorkersStatusFilter) assignWorkersStatusFilter.value = '';
    if (assignWorkersLocationFilter) assignWorkersLocationFilter.value = '';
    if (assignSiteTimekeeperSelect) {
        assignSiteTimekeeperSelect.innerHTML = '<option value="">Select a timekeeper</option>';
        assignSiteTimekeeperSelect.value = '';
        assignSiteTimekeeperSelect.disabled = false;
    }

    setAssignmentFeedback('');
}

function getTimekeeperDisplayLabel(timekeeper) {
    const name = String(timekeeper?.name || '').trim();
    if (name) {
        return name;
    }
    return String(timekeeper?.email || '').trim() || 'Unknown User';
}

function renderTimekeeperSelect(loadState = 'ok') {
    if (!assignSiteTimekeeperSelect) {
        return;
    }

    const modalState = activeSiteState.assignmentModal;
    const selectedId = Number(modalState.timekeeperUserId || 0);
    const currentSiteId = Number(modalState.siteId || 0);
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

            const assignedSiteId = Number(timekeeper.assigned_site_id || 0);
            if (assignedSiteId > 0 && assignedSiteId !== currentSiteId && id !== selectedId) {
                return;
            }

            const selected = id === selectedId ? ' selected' : '';
            options.push(
                `<option value="${id}"${selected}>${escapeHtml(getTimekeeperDisplayLabel(timekeeper))}</option>`
            );
        });
    }

    assignSiteTimekeeperSelect.innerHTML = options.join('');
    assignSiteTimekeeperSelect.value = selectedId > 0 ? String(selectedId) : '';
    assignSiteTimekeeperSelect.disabled = !activeSiteCanAssignTimekeepers || loadState === 'error';
}

async function loadTimekeeperOptions() {
    try {
        const timekeeperData = await fetchJson('../api/get_timekeepers.php');
        if (timekeeperData.success === false) {
            throw new Error(timekeeperData.message || 'Failed to load timekeepers');
        }
        activeSiteState.assignmentModal.timekeepers = Array.isArray(timekeeperData.timekeepers)
            ? timekeeperData.timekeepers
            : [];
        activeSiteState.assignmentModal.timekeeperLoadState = 'ok';
        return { ok: true, loadState: 'ok' };
    } catch (error) {
        console.error('Failed to load timekeeper accounts:', error);
        activeSiteState.assignmentModal.timekeepers = [];
        activeSiteState.assignmentModal.timekeeperLoadState = 'error';
        return { ok: false, message: error.message || 'Could not load Timekeeper accounts' };
    }
}

if (closeAssignWorkersModalBtn) {
    closeAssignWorkersModalBtn.addEventListener('click', closeAssignWorkersModal);
}

if (cancelAssignWorkersBtn) {
    cancelAssignWorkersBtn.addEventListener('click', closeAssignWorkersModal);
}

if (assignWorkersModal) {
    assignWorkersModal.addEventListener('click', (e) => {
        if (e.target === assignWorkersModal) {
            closeAssignWorkersModal();
        }
    });
}

if (closeSiteDetailsModalBtn) {
    closeSiteDetailsModalBtn.addEventListener('click', closeSiteDetailsModal);
}

if (siteDetailsModal) {
    siteDetailsModal.addEventListener('click', (e) => {
        if (e.target === siteDetailsModal) {
            closeSiteDetailsModal();
        }
    });
}

if (closeEditSiteModalBtn) {
    closeEditSiteModalBtn.addEventListener('click', closeEditSiteModal);
}

if (cancelEditSiteBtn) {
    cancelEditSiteBtn.addEventListener('click', closeEditSiteModal);
}

if (editSiteModal) {
    editSiteModal.addEventListener('click', (e) => {
        if (e.target === editSiteModal) {
            closeEditSiteModal();
        }
    });
}

if (closeArchiveSiteModalBtn) {
    closeArchiveSiteModalBtn.addEventListener('click', closeArchiveSiteModal);
}

if (cancelArchiveSiteBtn) {
    cancelArchiveSiteBtn.addEventListener('click', closeArchiveSiteModal);
}

if (confirmArchiveSiteBtn) {
    confirmArchiveSiteBtn.addEventListener('click', confirmArchiveSite);
}

if (archiveSiteModal) {
    archiveSiteModal.addEventListener('click', (e) => {
        if (e.target === archiveSiteModal) {
            closeArchiveSiteModal();
        }
    });
}

if (findEditLocationOnMapBtn) {
    findEditLocationOnMapBtn.addEventListener('click', () => {
        geocodeEditSiteLocation();
    });
}

useMyEditLocationBtn?.addEventListener('click', useCurrentLocationForEditSite);

if (editSiteLocationInput) {
    editSiteLocationInput.addEventListener('input', () => {
        queueEditSiteLocationSearch();
    });
}

if (editSiteCoordinatesInput) {
    editSiteCoordinatesInput.addEventListener('change', () => {
        syncEditMapToCoordinates();
    });
    editSiteCoordinatesInput.addEventListener('blur', () => {
        syncEditMapToCoordinates();
    });
}

// Geofence radius updates in real-time (Edit)
const editGeofenceRadiusInput = document.getElementById('editGeofenceRadiusM');
if (editGeofenceRadiusInput) {
    editGeofenceRadiusInput.addEventListener('input', () => {
        if (activeSiteState.addSiteMap.instance) {
            // no-op; avoid cross-modal sync
        }
        syncEditGeofenceCircleWithInputs();
    });
    editGeofenceRadiusInput.addEventListener('change', () => {
        syncEditGeofenceCircleWithInputs();
    });
}


function getWorkerDisplayLocation(worker, siteId) {
    return worker.address || 'Address not provided';
}

function getWorkerAssignmentLabel(worker, siteId) {
    const currentAssignment = worker.assignments.find((assignment) => assignment.siteId === siteId) || null;
    if (currentAssignment?.position) {
        return currentAssignment.position;
    }

    if (worker.position) {
        return worker.position;
    }

    const firstPosition = worker.assignments.find((assignment) => assignment.position)?.position;
    return firstPosition || 'Not specified';
}

function workerHasExternalAssignment(worker, siteId) {
    return worker.assignments.some((assignment) => assignment.siteId !== siteId);
}

function getWorkerExternalAssignment(worker, siteId) {
    return worker.assignments.find((assignment) => assignment.siteId !== siteId) || null;
}

function getWorkerStatusLabel(worker, isAssigned, role) {
    if (isAssigned) {
        return role || 'Assigned';
    }

    if (!worker.isApproved) {
        return 'Pending approval';
    }

    if (workerHasExternalAssignment(worker, activeSiteState.assignmentModal.siteId)) {
        const externalAssignment = getWorkerExternalAssignment(worker, activeSiteState.assignmentModal.siteId);
        return `Assigned to ${externalAssignment?.siteName || 'another site'}`;
    }

    const normalizedStatus = String(worker.status || '').toLowerCase();
    if (normalizedStatus === 'active') {
        return 'Available';
    }

    return worker.status || 'Unknown';
}

function buildRoleOptions() {
    const roleSet = new Set(defaultRoleOptions);

    activeSiteState.assignmentModal.workers.forEach((worker) => {
        worker.assignments.forEach((assignment) => {
            if (assignment.position) {
                roleSet.add(assignment.position);
            }
        });
    });

    roleSet.delete('Site Timekeeper');

    return Array.from(roleSet).sort((a, b) => a.localeCompare(b));
}

function populateAssignmentFilters() {
    const roles = buildRoleOptions();
    const locationSet = new Set();

    activeSiteState.assignmentModal.workers.forEach((worker) => {
        const location = getWorkerDisplayLocation(worker, activeSiteState.assignmentModal.siteId);
        if (location) {
            locationSet.add(location);
        }
    });

    if (assignWorkersRoleFilter) {
        const currentValue = activeSiteState.assignmentModal.roleFilter;
        assignWorkersRoleFilter.innerHTML = '<option value="">All Positions</option>' +
            roles.map((role) => `<option value="${escapeHtml(role)}">${escapeHtml(role)}</option>`).join('');
        assignWorkersRoleFilter.value = currentValue;
    }

    if (assignWorkersLocationFilter) {
        const currentValue = activeSiteState.assignmentModal.locationFilter;
        assignWorkersLocationFilter.innerHTML = '<option value="">All Locations</option>' +
            Array.from(locationSet).sort((a, b) => a.localeCompare(b))
                .map((location) => `<option value="${escapeHtml(location)}">${escapeHtml(location)}</option>`)
                .join('');
        assignWorkersLocationFilter.value = currentValue;
    }
}

function getFilteredAssignmentWorkers() {
    const modalState = activeSiteState.assignmentModal;
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
        const hasAnyAssignment = isAssigned || worker.assignments.length > 0;
        let matchesStatus = true;
        if (modalState.statusFilter === 'assigned') {
            matchesStatus = hasAnyAssignment;
        } else if (modalState.statusFilter === 'not_assigned') {
            matchesStatus = !isAssigned && !workerHasExternalAssignment(worker, modalState.siteId);
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

function renderAssignmentSummary() {
    const site = getSelectedSite();
    if (!site) {
        return;
    }

    const selectedWorkers = activeSiteState.assignmentModal.workers.filter((worker) => activeSiteState.assignmentModal.selectedWorkerIds.has(worker.id));
    const requiredWorkers = Number(site.Required_Workers || 0);

    const foreman = selectedWorkers.find((worker) => {
        const role = String(activeSiteState.assignmentModal.roleByWorkerId.get(worker.id) || '').toLowerCase();
        return role.includes('foreman');
    });

    const countCard = document.getElementById('assignWorkersCountCard');
    const foremanCard = document.getElementById('assignWorkersForemanCard');
    const locationCard = document.getElementById('assignWorkersLocationCard');
    const title = document.getElementById('assignWorkersTitle');

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

    const activationHint = document.getElementById('assignSiteActivationHint');
    if (activationHint) {
        const hasTimekeeper = Number(activeSiteState.assignmentModal.timekeeperUserId || 0) > 0;
        const workersNeeded = Math.max(0, 3 - selectedWorkers.length);
        activationHint.classList.toggle('ready', hasTimekeeper && workersNeeded === 0);
        activationHint.textContent = hasTimekeeper && workersNeeded === 0
            ? 'Ready to activate: this site has a Timekeeper and at least 3 assigned workers.'
            : `This site remains Inactive until it has ${hasTimekeeper ? '' : 'an assigned Timekeeper'}${!hasTimekeeper && workersNeeded > 0 ? ' and ' : ''}${workersNeeded > 0 ? `${workersNeeded} more worker${workersNeeded === 1 ? '' : 's'} (minimum 3)` : ''}.`;
    }
}

function renderAssignmentWorkers() {
    if (!assignWorkersList) {
        return;
    }

    renderAssignmentSummary();
    populateAssignmentFilters();

    const siteId = activeSiteState.assignmentModal.siteId;
    const roleOptions = buildRoleOptions();
    const workers = getFilteredAssignmentWorkers();

    if (assignWorkersStatusFilter) {
        const assignedCount = activeSiteState.assignmentModal.workers.filter((worker) => (
            activeSiteState.assignmentModal.selectedWorkerIds.has(worker.id)
            || worker.assignments.length > 0
        )).length;
        const notAssignedCount = activeSiteState.assignmentModal.workers.filter((worker) => (
            !activeSiteState.assignmentModal.selectedWorkerIds.has(worker.id)
            && !workerHasExternalAssignment(worker, siteId)
        )).length;
        const assignedOption = assignWorkersStatusFilter.querySelector('option[value="assigned"]');
        const notAssignedOption = assignWorkersStatusFilter.querySelector('option[value="not_assigned"]');
        if (assignedOption) assignedOption.textContent = `Assigned (${assignedCount})`;
        if (notAssignedOption) notAssignedOption.textContent = `Not Assigned (${notAssignedCount})`;
    }

    if (workers.length === 0) {
        let emptyMessage = 'No workers match the current filters.';
        if (activeSiteState.assignmentModal.statusFilter === 'assigned') {
            emptyMessage = 'No workers are currently assigned to this site.';
        } else if (activeSiteState.assignmentModal.statusFilter === 'not_assigned') {
            emptyMessage = 'No unassigned workers are currently available.';
        }
        assignWorkersList.innerHTML = `<div class="assign-workers-empty">${emptyMessage}</div>`;
        return;
    }

    assignWorkersList.innerHTML = workers.map((worker) => {
        const isAssigned = activeSiteState.assignmentModal.selectedWorkerIds.has(worker.id);
        const isLocked = !isAssigned && (!worker.isApproved || workerHasExternalAssignment(worker, siteId));
        const externalAssignment = getWorkerExternalAssignment(worker, siteId);
        const role = activeSiteState.assignmentModal.roleByWorkerId.get(worker.id) || getWorkerAssignmentLabel(worker, siteId);
        const location = getWorkerDisplayLocation(worker, siteId);
        const statusLabel = getWorkerStatusLabel(worker, isAssigned, role);
        const avatar = worker.photoPath
            ? `<img src="../${escapeHtml(String(worker.photoPath).replace(/^\/+/, ''))}" alt="${escapeHtml(worker.name)}">`
            : escapeHtml((worker.name || 'W').charAt(0).toUpperCase());

        const lockNote = isLocked ? `
            <div class="assign-worker-lock-note">
                ${worker.isApproved
                    ? `Already assigned to ${escapeHtml(externalAssignment?.siteName || 'another site')}`
                    : 'Employee must be approved before site assignment'}
            </div>
        ` : '';

        const roleSelect = `
            <div class="assign-workers-role-editor">
                <label>Position</label>
                <div class="assign-workers-role-value">${escapeHtml(role || 'Not specified')}</div>
            </div>
        `;

        return `
            <div class="assign-worker-card${isAssigned ? ' selected' : ''}${isLocked ? ' locked' : ''}" data-worker-card="${worker.id}">
                <div class="assign-worker-card-left">
                    <button type="button" class="assign-worker-toggle" data-worker-toggle="${worker.id}" aria-pressed="${isAssigned ? 'true' : 'false'}" ${isLocked ? 'disabled' : ''}>
                        ${isAssigned ? '<i class="fas fa-check"></i>' : '<i class="fas fa-plus"></i>'}
                    </button>
                    <div class="assign-worker-avatar">${avatar}</div>
                    <div class="assign-worker-meta">
                        <div class="assign-worker-name-row">
                            <div class="assign-worker-name">${escapeHtml(worker.name)}</div>
                        </div>
                        <div class="assign-worker-role">${escapeHtml(role)}</div>
                        <div class="assign-worker-location"><i class="fas fa-location-dot" aria-hidden="true"></i> <span>Address: ${escapeHtml(location)}</span></div>
                        ${lockNote}
                        ${roleSelect}
                    </div>
                </div>

                <div class="assign-worker-card-right">
                    <span class="assign-worker-status${isAssigned ? ' assigned' : ''}${isLocked ? ' locked' : ''}">${escapeHtml(statusLabel)}</span>
                    ${isAssigned ? `
                        <button type="button" class="assign-worker-unassign" data-worker-toggle="${worker.id}" aria-label="Unassign ${escapeHtml(worker.name)} from this site">
                            <i class="fas fa-user-minus"></i>
                            <span>Unassign</span>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function normalizeWorkers(rows) {
    const workersById = new Map();

    rows.forEach((row) => {
        const workerId = Number(row.WorkerID);
        if (!workerId) {
            return;
        }

        if (!workersById.has(workerId)) {
            const rawApprovalStatus = String(row.approval_status || row.approval?.Approval_Status || '').trim();
            const workerStatus = String(row.worker_status || 'Active').trim();
            workersById.set(workerId, {
                id: workerId,
                name: row.full_name || `${row.First_Name || ''} ${row.Last_Name || ''}`.trim() || `Worker ${workerId}`,
                status: workerStatus,
                approvalStatus: rawApprovalStatus || (workerStatus.toLowerCase() === 'active' ? 'Approved' : 'Pending'),
                isApproved: rawApprovalStatus
                    ? rawApprovalStatus.toLowerCase() === 'approved'
                    : workerStatus.toLowerCase() === 'active',
                photoPath: row.photo_path || '',
                phone: row.Phone || '',
                position: row.position || row.Position || '',
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
                position: row.position || '',
                assignedDate: row.Assigned_Date || ''
            });
        }
    });

    return Array.from(workersById.values());
}

async function openAssignWorkersModal(siteId) {
    const site = activeSiteState.siteMap.get(Number(siteId));
    if (!site || !assignWorkersModal) {
        return;
    }

    if (assignWorkersModal.classList.contains('active') || assignWorkersModal.dataset.opening === 'true') {
        return;
    }

    assignWorkersModal.dataset.opening = 'true';

    assignWorkersModal.classList.add('active');
    assignWorkersModal.setAttribute('aria-hidden', 'false');
    assignWorkersList.innerHTML = '<div class="assign-workers-empty">Loading workers...</div>';
    setAssignmentFeedback('');

    activeSiteState.assignmentModal.siteId = Number(siteId);
    activeSiteState.assignmentModal.searchTerm = '';
    // Show assigned workers and available workers together. Assigned workers
    // must remain visible so they can be removed from this site.
    activeSiteState.assignmentModal.statusFilter = '';
    activeSiteState.assignmentModal.roleFilter = '';
    activeSiteState.assignmentModal.locationFilter = '';
    assignWorkersModal.dataset.opening = 'false';

    if (assignWorkersSearch) assignWorkersSearch.value = '';

    const timekeeperUserId = Number(site.Timekeeper_UserID || 0) || null;
    activeSiteState.assignmentModal.timekeeperUserId = timekeeperUserId;
    activeSiteState.assignmentModal.initialTimekeeperUserId = timekeeperUserId;

    const timekeeperLoad = await loadTimekeeperOptions();
    renderTimekeeperSelect(timekeeperLoad.ok ? 'ok' : 'error');
    if (!timekeeperLoad.ok) {
        setAssignmentFeedback(timekeeperLoad.message || 'Could not load Timekeeper accounts. Worker assignment is still available.', 'error');
    }

    try {
        const employeeData = await fetchJson('../api/get_employees.php');
        const rows = Array.isArray(employeeData.employees) ? employeeData.employees : [];
        const workers = normalizeWorkers(rows);
        const selectedWorkerIds = new Set();
        const roleByWorkerId = new Map();

        workers.forEach((worker) => {
            const siteAssignment = worker.assignments.find((assignment) => assignment.siteId === Number(siteId));
            if (siteAssignment) {
                selectedWorkerIds.add(worker.id);
                roleByWorkerId.set(worker.id, siteAssignment.position || 'Construction Worker');
            }
        });

        activeSiteState.assignmentModal.workers = workers;
        activeSiteState.assignmentModal.selectedWorkerIds = new Set(selectedWorkerIds);
        activeSiteState.assignmentModal.initialWorkerIds = new Set(selectedWorkerIds);
        activeSiteState.assignmentModal.roleByWorkerId = new Map(roleByWorkerId);
        activeSiteState.assignmentModal.initialRoleByWorkerId = new Map(roleByWorkerId);

        if (timekeeperLoad.ok) {
            setAssignmentFeedback('');
        }
        renderAssignmentWorkers();
    } catch (error) {
        console.error('Failed to load workers for assignment modal:', error);
        assignWorkersList.innerHTML = '<div class="assign-workers-empty">Could not load workers for this site.</div>';
        if (timekeeperLoad.ok) {
            setAssignmentFeedback('Could not load workers. Please try again.', 'error');
        }
    }
}

async function loadSites() {
    try {
        const sites = await fetchJson('../api/get_sites.php');
        if (!Array.isArray(sites)) {
            throw new Error('Invalid site payload');
        }

        const filters = activeSiteState.siteFilters;
        const normalizedSearch = filters.search.trim().toLowerCase();
        const sortedSites = sites.filter((site) => {
            const workers = Number(site.Current_Workers ?? site.currentWorkers ?? 0);
            const matchesSearch = !normalizedSearch || [site.Site_Name, site.Location]
                .join(' ').toLowerCase().includes(normalizedSearch);
            const matchesWorkers = !filters.workers
                || (filters.workers === 'assigned' ? workers > 0 : workers === 0);
            const matchesStatus = !filters.status || String(site.Status || '').toLowerCase() === filters.status;
            return matchesSearch && matchesWorkers && matchesStatus;
        }).sort((a, b) => {
            const priorityDifference = Number(b.Is_Priority || 0) - Number(a.Is_Priority || 0);
            if (priorityDifference) return priorityDifference;
            const aWorkers = Number(a.Current_Workers ?? a.currentWorkers ?? 0);
            const bWorkers = Number(b.Current_Workers ?? b.currentWorkers ?? 0);
            if (filters.sort === 'most_assigned') return bWorkers - aWorkers || Number(b.SiteID || 0) - Number(a.SiteID || 0);
            if (filters.sort === 'least_assigned') return aWorkers - bWorkers || Number(b.SiteID || 0) - Number(a.SiteID || 0);
            return Number(b.SiteID || 0) - Number(a.SiteID || 0);
        });

        activeSiteState.sites = sites;
        activeSiteState.siteMap = new Map(sites.map((site) => [Number(site.SiteID), site]));

        const siteCards = document.getElementById('siteCards');
        if (!siteCards) return;
        siteCards.innerHTML = '';

        if (sortedSites.length === 0) {
            siteCards.innerHTML = `
                <div class="site-filter-empty">
                    <i class="fas fa-filter-circle-xmark"></i>
                    <strong>No sites match these filters.</strong>
                    <span>Sites with no assigned workers are automatically inactive.</span>
                    <button type="button" id="resetSiteFiltersBtn">Reset filters</button>
                </div>`;
            updateSummaryCards();
            return;
        }

        sortedSites.forEach((site) => {
            const card = document.createElement('div');
            card.className = 'site-card';

            const siteId = Number(site.SiteID);
            const status = site.Status || 'Active';
            const requiredWorkers = parseInt(site.Required_Workers, 10) || 0;
            const currentWorkers = parseInt(site.Current_Workers ?? site.currentWorkers, 10) || 0;
            const capacityPercent = requiredWorkers === 0 ? 0 : Math.round((currentWorkers / requiredWorkers) * 100);
            const safeCapacityPercent = Math.max(0, Math.min(capacityPercent, 100));
            const managerName = site.Site_Manager || 'Not assigned';
            const timekeeperName = site.Timekeeper || 'Not assigned';
            const normalizedStatus = toSafeClassName(status);
            const isPriority = Number(site.Is_Priority || 0) === 1;
            if (isPriority) card.classList.add('priority-site');
            card.innerHTML = `
                <div class="card-header">
                    <div class="site-heading">
                        <div class="site-title">${escapeHtml(site.Site_Name || 'Unnamed Site')}</div>
                        ${isPriority ? '<span class="priority-label"><i class="fas fa-thumbtack"></i> Priority</span>' : ''}
                    </div>
                    <div class="site-header-actions">
                        ${activeSiteCanPrioritize ? `
                        <button class="btn-pin-site ${isPriority ? 'active' : ''}" type="button" data-site-action="priority" data-site-id="${siteId}" aria-pressed="${isPriority}" title="${isPriority ? 'Unpin priority site' : 'Pin as priority site'}">
                            <i class="fas fa-thumbtack"></i>
                        </button>` : ''}
                        <span class="site-status-badge ${normalizedStatus}">${escapeHtml(status)}</span>
                    </div>
                </div>

                <div class="site-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${escapeHtml(site.Location || 'No location provided')}</span>
                </div>

                ${renderSiteScheduleTimeline(site)}

                <div class="site-stats">
                    <div class="stat-box">
                        <div class="stat-title">Assigned Workers</div>
                        <div class="stat-value site-info-value" data-type="currentWorkers">${currentWorkers}</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-title">Target Capacity</div>
                        <div class="stat-value site-info-value" data-type="requiredWorkers">${requiredWorkers}</div>
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
                    <span>Started ${formatDateLabel(site.Start_Date)}</span>
                </div>

                <div class="site-card-actions">
                    <button class="btn-view-details" type="button" data-site-action="details" data-site-id="${siteId}">
                        <i class="far fa-eye"></i>
                        <span>View Details</span>
                    </button>
                    ${activeSiteCanManageSites ? `<button class="btn-manage" type="button" data-site-action="edit" data-site-id="${siteId}">
                        <i class="far fa-pen-to-square"></i>
                        <span>Edit</span>
                    </button>` : ''}
                    ${activeSiteIsAdmin ? `
                    <button class="btn-archive-site" type="button" data-site-action="archive" data-site-id="${siteId}" title="Archive Site">
                        <i class="fa-solid fa-box-archive"></i>
                        <span>Archive</span>
                    </button>
                    ` : ''}
                </div>

                ${activeSiteCanAssignWorkers ? `<button class="btn-assign-workers" type="button" data-site-action="assign" data-site-id="${siteId}">
                    <i class="fas fa-users"></i>
                    <span>Assign Workers</span>
                </button>` : ''}
            `;

            card.dataset.siteId = String(siteId);
            card.dataset.siteName = site.Site_Name || '';
            card.dataset.siteStatus = normalizedStatus;

            if (!activeSiteCanManageSites && !activeSiteCanAssignWorkers) {
                const manageButton = card.querySelector('[data-site-action="edit"]');
                const assignButton = card.querySelector('[data-site-action="assign"]');
                if (manageButton) {
                    manageButton.remove();
                }
                if (assignButton) {
                    assignButton.remove();
                }
            }

            card.querySelectorAll('[data-site-action]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    handleSiteActionButtonClick(button);
                });
            });

            siteCards.appendChild(card);
        });

        updateSummaryCards();
        consumePendingDashboardAction();
    } catch (err) {
        console.error(err);
        alert('Could not load sites. Please check the server.');
    }
}

if (addSiteForm) {
    addSiteForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const resetAddSiteSubmitButton = () => {
            if (!addSiteSubmitBtn) {
                return;
            }

            addSiteSubmitBtn.disabled = false;
            if (addSiteSubmitBtn.dataset.originalText) {
                addSiteSubmitBtn.innerHTML = addSiteSubmitBtn.dataset.originalText;
            }
        };

        // Safety: only allow submit on Confirm (Step index 3)
        if (activeSiteState.addSiteStep !== 3) {
            alert('Please complete all steps first.');
            return;
        }

        // Validate the Operations step (Step index 2) before final submit
        if (!validateAddSiteStep(2)) {
            return;
        }

        // Loading/disable to prevent double-submit
        if (addSiteSubmitBtn) {
            addSiteSubmitBtn.disabled = true;
            addSiteSubmitBtn.dataset.originalText = addSiteSubmitBtn.dataset.originalText || addSiteSubmitBtn.innerHTML;
            addSiteSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Saving...</span>';
        }



        const siteNameEl = document.getElementById('siteName');
        const locationEl = document.getElementById('location');
        const requiredWorkersEl = document.getElementById('requiredWorkers');
        const startDateEl = document.getElementById('startDate');
        const siteManagerEl = document.getElementById('siteManager');
        if (!siteNameEl || !locationEl || !requiredWorkersEl || !startDateEl || !siteManagerEl) {
            alert('Form elements not found.');
            resetAddSiteSubmitButton();
            return;
        }

        const siteName = siteNameEl.value.trim();
        const location = locationEl.value.trim();
        const requiredWorkersStr = requiredWorkersEl.value.trim();
        const startDate = startDateEl.value;
        const siteManager = siteManagerEl.value.trim();
        const status = 'inactive';

        if (!siteName || !location || !requiredWorkersStr || !startDate) {
            alert('Please fill in all required fields.');
            resetAddSiteSubmitButton();
            return;
        }

        if (await checkSiteNameDuplicate(siteNameEl, 0, 'add')) {
            alert(siteNameEl.validationMessage || 'A site with this name already exists.');
            resetAddSiteSubmitButton();
            return;
        }

        if (await checkSiteLocationDuplicate(locationEl, 0, 'add')) {
            alert(locationEl.validationMessage || 'A site with this exact location already exists.');
            resetAddSiteSubmitButton();
            return;
        }

        if (!validateSiteLocationField(locationEl) || !validateTargetCapacityField(requiredWorkersEl)) {
            alert(locationEl.validationMessage || requiredWorkersEl.validationMessage);
            resetAddSiteSubmitButton();
            return;
        }
        const requiredWorkers = Number(requiredWorkersStr);

        const coordinates = document.getElementById('siteCoordinates')?.value.trim() || '';

        const geofenceRadiusM = document.getElementById('geofenceRadiusM')?.value || '';
        const geofenceLatitude = document.getElementById('geofenceLatitude')?.value || '';
        const geofenceLongitude = document.getElementById('geofenceLongitude')?.value || '';
        const geofenceRadiusMHidden = document.getElementById('geofenceRadiusMHidden')?.value || '';

        const formData = {
            siteName,

            location,
            coordinates,
            requiredWorkers,
            startDate,
            shiftStart: document.getElementById('shiftStart')?.value || '07:00',
            lunchStart: document.getElementById('lunchStart')?.value || '12:00',
            lunchEnd: document.getElementById('lunchEnd')?.value || '13:00',
            shiftEnd: document.getElementById('shiftEnd')?.value || '17:00',
            siteManager,
            status,

            geofenceRadiusM: geofenceRadiusMHidden || geofenceRadiusM,
            geofenceLatitude,
            geofenceLongitude
        };




        try {
            const result = await fetchJson('../api/add_site.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            if (result.status === 'success') {
                closeModalFunc();
                await loadSites();
                window.showCrudResultModal?.(
                    true,
                    result.message || 'Site added successfully.',
                    'Site Creation'
                );
            } else {
                window.showCrudResultModal?.(
                    false,
                    result.message || 'Error saving site.',
                    'Site Creation'
                );
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            window.showCrudResultModal?.(
                false,
                error.message || 'An error occurred while saving the site.',
                'Site Creation'
            );
        } finally {
            resetAddSiteSubmitButton();
        }
    });
}

function updateSummaryCards() {
    const siteCards = document.querySelectorAll('.site-card');
    let totalSites = siteCards.length;
    let activeSites = 0;
    let totalWorkers = 0;
    let totalRequiredWorkers = 0;

    siteCards.forEach((card) => {
        const status = (card.dataset.siteStatus || '').toLowerCase();
        const currentWorkers = parseInt(card.querySelector('.site-info-value[data-type="currentWorkers"]').textContent, 10) || 0;
        const requiredWorkers = parseInt(card.querySelector('.site-info-value[data-type="requiredWorkers"]').textContent, 10) || 0;

        if (status === 'active') activeSites++;
        totalWorkers += currentWorkers;
        totalRequiredWorkers += requiredWorkers;
    });

    const utilization = totalRequiredWorkers === 0 ? 0 : Math.round((totalWorkers / totalRequiredWorkers) * 100);
    const summaryCards = document.querySelectorAll('.summary-card');
    if (summaryCards.length >= 4) {
        summaryCards[0].querySelector('.summary-card-value').textContent = totalSites;
        summaryCards[1].querySelector('.summary-card-value').textContent = activeSites;
        summaryCards[2].querySelector('.summary-card-value').textContent = totalWorkers;
        summaryCards[3].querySelector('.summary-card-value').textContent = `${utilization}%`;
    }
}

function handleSiteActionButtonClick(button) {
    const siteId = Number(button?.dataset.siteId || 0);
    const action = button?.dataset.siteAction || '';
    if (!siteId) {
        return;
    }

    if (action === 'details') {
        openSiteDetails(siteId);
        return;
    }

    if (action === 'priority') {
        if (!activeSiteCanPrioritize) return;
        toggleSitePriority(siteId, button);
        return;
    }

    if (action === 'edit') {
        if (!activeSiteCanManageSites) {
            return;
        }
        openEditSite(siteId);
        return;
    }

    if (action === 'assign') {
        if (!activeSiteCanAssignWorkers) {
            return;
        }
        openAssignWorkersModal(siteId);
        return;
    }

    if (action === 'archive') {
        if (!activeSiteIsAdmin) {
            return;
        }
        openArchiveSiteModal(siteId);
    }
}

async function toggleSitePriority(siteId, button) {
    const site = activeSiteState.siteMap.get(Number(siteId));
    if (!site || button.disabled) return;

    const nextPriority = Number(site.Is_Priority || 0) !== 1;
    button.disabled = true;
    try {
        const result = await fetchJson('../api/toggle_site_priority.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ site_id: siteId, is_priority: nextPriority })
        });
        if (!result.success) throw new Error(result.message || 'Could not update site priority.');
        site.Is_Priority = result.is_priority ? 1 : 0;
        await loadSites();
        window.showCrudResultModal?.(true, result.message, 'Site Priority');
    } catch (error) {
        button.disabled = false;
        window.showCrudResultModal?.(false, error.message || 'Could not update site priority.', 'Site Priority');
    }
}

function openArchiveSiteModal(siteId) {
    const site = activeSiteState.sites.find((entry) => Number(entry.SiteID) === Number(siteId));
    if (!site || !archiveSiteModal) {
        return;
    }

    activeSiteState.pendingArchiveSiteId = siteId;
    activeSiteState.pendingArchiveSiteName = site.Site_Name || 'this site';
    archiveSiteModal.classList.add('active');
    archiveSiteModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('archive-site-modal-open');
}

function closeArchiveSiteModal() {
    if (!archiveSiteModal) {
        return;
    }

    activeSiteState.pendingArchiveSiteId = null;
    activeSiteState.pendingArchiveSiteName = '';
    archiveSiteModal.classList.remove('active');
    archiveSiteModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('archive-site-modal-open');
}

async function confirmArchiveSite() {
    const siteId = Number(activeSiteState.pendingArchiveSiteId || 0);
    if (!siteId) {
        return;
    }

    if (confirmArchiveSiteBtn) {
        confirmArchiveSiteBtn.disabled = true;
    }

    try {
        const response = await fetch('../api/archive_site.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ site_id: siteId })
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to archive site');
        }

        closeArchiveSiteModal();
        await loadSites();
        window.showCrudResultModal?.(
            true,
            result.message || 'Site archived successfully.',
            'Site Archive'
        );
    } catch (error) {
        console.error(error);
        window.showCrudResultModal?.(
            false,
            error.message || 'Could not archive site. Please try again.',
            'Site Archive'
        );
    } finally {
        if (confirmArchiveSiteBtn) {
            confirmArchiveSiteBtn.disabled = false;
        }
    }
}

document.addEventListener('click', (e) => {
    const siteActionButton = e.target.closest('[data-site-action]');
    if (siteActionButton) {
        e.preventDefault();
        e.stopPropagation();
        handleSiteActionButtonClick(siteActionButton);
        return;
    }

    const toggleButton = e.target.closest('[data-worker-toggle]');
    const workerCard = e.target.closest('[data-worker-card]');
    if (toggleButton || workerCard) {
        const workerId = Number((toggleButton || workerCard)?.dataset.workerToggle || workerCard?.dataset.workerCard || 0);
        if (workerId) {
            const worker = activeSiteState.assignmentModal.workers.find((entry) => entry.id === workerId);
            const isAssigned = activeSiteState.assignmentModal.selectedWorkerIds.has(workerId);
            const isLocked = worker && !isAssigned && (!worker.isApproved || workerHasExternalAssignment(worker, activeSiteState.assignmentModal.siteId));

            if (isLocked) {
                setAssignmentFeedback(
                    worker.isApproved
                        ? `${worker.name} is already assigned to another site. Remove that assignment first.`
                        : `${worker.name} is pending approval and cannot be assigned yet.`,
                    'error'
                );
                return;
            }

            if (activeSiteState.assignmentModal.selectedWorkerIds.has(workerId)) {
                activeSiteState.assignmentModal.selectedWorkerIds.delete(workerId);
                setAssignmentFeedback(`${worker.name} will be unassigned when you save assignments.`, 'info');
            } else {
                activeSiteState.assignmentModal.selectedWorkerIds.add(workerId);
                if (!activeSiteState.assignmentModal.roleByWorkerId.has(workerId)) {
                    activeSiteState.assignmentModal.roleByWorkerId.set(workerId, getWorkerAssignmentLabel(worker, activeSiteState.assignmentModal.siteId));
                }
                setAssignmentFeedback(`${worker.name} will be assigned when you save assignments.`, 'info');
            }
            renderAssignmentWorkers();
        }
    }
});

if (assignWorkersSearch) {
    assignWorkersSearch.addEventListener('input', (e) => {
        activeSiteState.assignmentModal.searchTerm = e.target.value || '';
        renderAssignmentWorkers();
    });
}

if (assignWorkersRoleFilter) {
    assignWorkersRoleFilter.addEventListener('change', (e) => {
        activeSiteState.assignmentModal.roleFilter = e.target.value || '';
        setAssignmentFeedback('');
        renderAssignmentWorkers();
    });
}

if (assignWorkersStatusFilter) {
    assignWorkersStatusFilter.addEventListener('change', (e) => {
        activeSiteState.assignmentModal.statusFilter = e.target.value || '';
        setAssignmentFeedback('');
        renderAssignmentWorkers();
    });
}

function updateSiteFilters() {
    activeSiteState.siteFilters.search = siteSearchInput?.value || '';
    activeSiteState.siteFilters.workers = siteWorkerFilter?.value || '';
    activeSiteState.siteFilters.status = siteStatusFilter?.value || '';
    activeSiteState.siteFilters.sort = siteAssignmentSort?.value || 'newest';
    loadSites();
}

siteSearchInput?.addEventListener('input', updateSiteFilters);
siteWorkerFilter?.addEventListener('change', () => {
    // Keep the status filter compatible with the automatic site-status rule.
    if (siteWorkerFilter.value === 'unassigned' && siteStatusFilter?.value === 'active') {
        siteStatusFilter.value = 'inactive';
    }
    if (siteWorkerFilter.value === 'assigned' && siteStatusFilter?.value === 'inactive') {
        siteStatusFilter.value = 'active';
    }
    updateSiteFilters();
});
siteStatusFilter?.addEventListener('change', () => {
    if (siteWorkerFilter?.value === 'unassigned' && siteStatusFilter.value === 'active') {
        siteStatusFilter.value = 'inactive';
    }
    if (siteWorkerFilter?.value === 'assigned' && siteStatusFilter.value === 'inactive') {
        siteStatusFilter.value = 'active';
    }
    updateSiteFilters();
});
siteAssignmentSort?.addEventListener('change', updateSiteFilters);

document.addEventListener('click', (event) => {
    if (event.target.closest('#resetSiteFiltersBtn')) {
        if (siteSearchInput) siteSearchInput.value = '';
        if (siteWorkerFilter) siteWorkerFilter.value = '';
        if (siteStatusFilter) siteStatusFilter.value = '';
        if (siteAssignmentSort) siteAssignmentSort.value = 'newest';
        updateSiteFilters();
    }
});

if (assignWorkersLocationFilter) {
    assignWorkersLocationFilter.addEventListener('change', (e) => {
        activeSiteState.assignmentModal.locationFilter = e.target.value || '';
        setAssignmentFeedback('');
        renderAssignmentWorkers();
    });
}

if (assignSiteTimekeeperSelect) {
    assignSiteTimekeeperSelect.addEventListener('change', (e) => {
        const value = Number(e.target.value || 0);
        const currentSiteId = Number(activeSiteState.assignmentModal.siteId || 0);
        const timekeeper = activeSiteState.assignmentModal.timekeepers.find(
            (entry) => Number(entry.id) === value
        );
        const assignedSiteId = Number(timekeeper?.assigned_site_id || 0);

        if (
            value > 0
            && assignedSiteId > 0
            && assignedSiteId !== currentSiteId
        ) {
            const siteName = timekeeper?.assigned_site_name || 'another site';
            setAssignmentFeedback(
                `This Timekeeper is already assigned to ${siteName}. Remove that assignment first.`,
                'error'
            );
            e.target.value = activeSiteState.assignmentModal.timekeeperUserId
                ? String(activeSiteState.assignmentModal.timekeeperUserId)
                : '';
            return;
        }

        activeSiteState.assignmentModal.timekeeperUserId = value > 0 ? value : null;
        setAssignmentFeedback(value > 0 ? '' : 'Please select a timekeeper before saving assignments.', value > 0 ? '' : 'error');
    });
}

if (assignWorkersTempBtn) {
    assignWorkersTempBtn.addEventListener('click', () => {
        const currentPath = window.location.pathname || '';
        const dashboardFile = currentPath.split('/').pop() || '';

        if (activeSiteDashboardRole === 'assistant') {
            window.location.href = '/capstone/assistant/dashboard?page=worker';
            return;
        }

        if (activeSiteDashboardRole === 'payroll') {
            window.location.href = '/capstone/payroll/employee';
            return;
        }

        if (activeSiteDashboardRole === 'hr') {
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

        if (dashboardFile === 'dashboard.php') {
            window.location.href = 'dashboard.php?page=worker&action=add';
            return;
        }

        window.location.href = '/capstone/admin/dashboard.php?page=worker&action=add';
    });
}

async function saveAssignments() {
    const site = getSelectedSite();
    if (!site || activeSiteState.assignmentModal.saving) {
        return;
    }

    if (activeSiteCanAssignTimekeepers && Number(activeSiteState.assignmentModal.timekeeperUserId || 0) <= 0) {
        setAssignmentFeedback('Please select a timekeeper before saving assignments.', 'error');
        assignSiteTimekeeperSelect?.focus();
        return;
    }

    activeSiteState.assignmentModal.saving = true;
    setAssignmentFeedback('Saving assignments...', 'info');
    if (saveAssignWorkersBtn) {
        saveAssignWorkersBtn.disabled = true;
    }

    try {
        const selectedIds = Array.from(activeSiteState.assignmentModal.selectedWorkerIds);
        const initialIds = Array.from(activeSiteState.assignmentModal.initialWorkerIds);
        const toAdd = selectedIds.filter((workerId) => !activeSiteState.assignmentModal.initialWorkerIds.has(workerId));
        const toRemove = initialIds.filter((workerId) => !activeSiteState.assignmentModal.selectedWorkerIds.has(workerId));
        const roleUpdates = selectedIds.filter((workerId) => {
            const currentRole = activeSiteState.assignmentModal.roleByWorkerId.get(workerId) || '';
            const initialRole = activeSiteState.assignmentModal.initialRoleByWorkerId.get(workerId) || '';
            return currentRole && currentRole !== initialRole;
        });

        const currentTimekeeperUserId = activeSiteState.assignmentModal.timekeeperUserId;
        const initialTimekeeperUserId = activeSiteState.assignmentModal.initialTimekeeperUserId;
        const timekeeperChanged = Number(currentTimekeeperUserId || 0) !== Number(initialTimekeeperUserId || 0);

        if (timekeeperChanged && activeSiteCanAssignTimekeepers) {
            const timekeeperResult = await fetchJson('../api/assign_site_timekeeper.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    site_id: Number(site.SiteID),
                    user_id: Number(currentTimekeeperUserId || 0)
                })
            });

            if (!timekeeperResult.success) {
                throw new Error(timekeeperResult.message || 'Failed to assign site timekeeper');
            }
        }

        for (const workerId of toAdd) {
            const result = await fetchJson('../api/assign_worker.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ workerId, siteId: Number(site.SiteID) })
            });

            if (!result.success) {
                throw new Error(result.message || `Failed to assign worker ${workerId}`);
            }
        }

        for (const workerId of roleUpdates) {
            const role = activeSiteState.assignmentModal.roleByWorkerId.get(workerId) || '';
            const result = await fetchJson('../api/update_worker_assignment_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    worker_id: workerId,
                    role,
                    site_id: Number(site.SiteID)
                })
            });

            if (!result.success) {
                throw new Error(result.message || `Failed to update role for worker ${workerId}`);
            }
        }

        for (const workerId of toRemove) {
            const result = await fetchJson('../api/remove_worker_assignment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    worker_id: workerId,
                    site_id: Number(site.SiteID)
                })
            });

            if (!result.success) {
                throw new Error(result.message || `Failed to remove worker ${workerId}`);
            }
        }

        setAssignmentFeedback('');
        window.showCrudResultModal?.(
            true,
            'Assignments saved successfully.',
            'Worker Assignment'
        );
        site.Current_Workers = activeSiteState.assignmentModal.selectedWorkerIds.size;
        site.currentWorkers = activeSiteState.assignmentModal.selectedWorkerIds.size;
        await loadSites();
        setTimeout(() => {
            closeAssignWorkersModal();
        }, 700);
    } catch (error) {
        console.error('Failed to save worker assignments:', error);
        setAssignmentFeedback('');
        window.showCrudResultModal?.(
            false,
            error.message || 'Could not save assignments.',
            'Worker Assignment'
        );
    } finally {
        activeSiteState.assignmentModal.saving = false;
        if (saveAssignWorkersBtn) {
            saveAssignWorkersBtn.disabled = false;
        }
    }
}

if (saveAssignWorkersBtn) {
    saveAssignWorkersBtn.addEventListener('click', saveAssignments);
}

initializePendingDashboardAction();
bindSiteLetterOnlyValidation();
bindSiteNameDuplicateValidation();
bindSiteFieldValidation();
// The assignment dialog must always start closed. It may only be opened by
// the Assign Workers button after sites have loaded.
if (assignWorkersModal) {
    assignWorkersModal.classList.remove('active');
    assignWorkersModal.setAttribute('aria-hidden', 'true');
    assignWorkersModal.dataset.opening = 'false';
}
loadSites();
