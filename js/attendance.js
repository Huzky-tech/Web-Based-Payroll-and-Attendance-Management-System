

class AttendanceSystem {
    constructor() {
        const requestedFilters = new URLSearchParams(window.location.search);
        this.dashboardRole = String(document.body?.dataset?.dashboardRole || '').toLowerCase();
        this.canModifyAttendance = this.dashboardRole !== 'hr';
        this.currentDate = this.getLocalDateValue();
        this.currentRecords = [];
        this.siteStats = [];
        this.searchTimer = null;
        this.filters = {
            siteId: requestedFilters.get('site_id') || '',
            search: requestedFilters.get('search') || '',
            position: '',
            manager: '',
            status: ''
        };
        this.sort = {
            by: 'employee',
            dir: 'asc'
        };
        this.photoTypeOrder = ['Time In', 'Lunch In', 'Time Out'];
        this.photoLightboxState = {
            scale: 1,
            minScale: 1,
            maxScale: 4,
        };
        this.punchInProgress = new Set();
        this.cardsWrapper = null;
        this.prevButton = null;
        this.nextButton = null;
        this.attendancePerPage = 20;
        this.attendanceCurrentPage = 1;
    }

    async init() {
        this.bindEvents();
        this.setupSlider();
        this.updateDateFilter();
        this.syncFilterControls();
        const searchInput = document.getElementById('attendanceSearchInput');
        if (searchInput) searchInput.value = this.filters.search;
        await Promise.all([this.loadStats(), this.loadAttendance()]);
        window.setInterval(() => {
            if (this.currentDate === this.getLocalDateValue()) {
                Promise.all([this.loadStats(), this.loadAttendance()]);
            }
        }, 60000);
    }

    getLocalDateValue(date = new Date()) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    bindEvents() {
        document.addEventListener('click', (event) => {
            const card = event.target.closest('.project-card');
            if (card) {
                this.filters.siteId = card.dataset.siteId || '';
                this.syncFilterControls();
                this.loadAttendance();
                this.highlightActiveCard();
                return;
            }

            const closeButton = event.target.closest('[data-attendance-close="true"]');
            if (closeButton) {
                this.closeAttendanceModal();
                return;
            }

            const modal = event.target.closest('#attendanceActionModal');
            if (modal && event.target === modal) {
                this.closeAttendanceModal();
                return;
            }

            const photoCard = event.target.closest('[data-photo-url]');
            if (photoCard) {
                this.openPhotoLightbox(
                    photoCard.dataset.photoUrl,
                    photoCard.dataset.photoLabel || 'Attendance Photo'
                );
                return;
            }

            const lightbox = event.target.closest('#attendancePhotoLightbox');
            if (lightbox && (event.target === lightbox || event.target.closest('[data-lightbox-close="true"]'))) {
                this.closePhotoLightbox();
                return;
            }

            const zoomButton = event.target.closest('[data-lightbox-zoom]');
            if (zoomButton) {
                this.adjustPhotoLightboxZoom(zoomButton.dataset.lightboxZoom === 'in' ? 0.25 : -0.25);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                if (document.getElementById('attendancePhotoLightbox')) {
                    this.closePhotoLightbox();
                    return;
                }
                this.closeAttendanceModal();
            }
        });

        document.addEventListener('wheel', (event) => {
            const lightbox = document.getElementById('attendancePhotoLightbox');
            if (!lightbox || !lightbox.contains(event.target)) {
                return;
            }

            event.preventDefault();
            this.adjustPhotoLightboxZoom(event.deltaY < 0 ? 0.15 : -0.15);
        }, { passive: false });

        document.getElementById('attendanceDateFilter')?.addEventListener('change', (event) => {
            this.currentDate = event.target.value || this.currentDate;
            Promise.all([this.loadStats(), this.loadAttendance()]);
        });

        document.getElementById('attendanceSearchInput')?.addEventListener('input', (event) => {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.filters.search = event.target.value.trim();
                this.loadAttendance();
            }, 180);
        });

        document.getElementById('attendanceSiteFilter')?.addEventListener('change', (event) => {
            this.filters.siteId = event.target.value;
            this.highlightActiveCard();
            this.loadAttendance();
        });

        document.getElementById('attendancePositionFilter')?.addEventListener('change', (event) => {
            this.filters.position = event.target.value;
            this.loadAttendance();
        });

        document.getElementById('attendanceManagerFilter')?.addEventListener('change', (event) => {
            this.filters.manager = event.target.value;
            this.loadAttendance();
        });

        document.getElementById('attendanceStatusFilter')?.addEventListener('change', (event) => {
            this.filters.status = event.target.value;
            this.loadAttendance();
        });

        document.getElementById('attendanceExportCsvBtn')?.addEventListener('click', () => this.exportAttendance());

        document.querySelectorAll('[data-sort-by]').forEach((button) => {
            button.dataset.label = button.textContent.trim();
            button.addEventListener('click', () => {
                const nextSort = button.dataset.sortBy || 'employee';
                if (this.sort.by === nextSort) {
                    this.sort.dir = this.sort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sort.by = nextSort;
                    this.sort.dir = 'asc';
                }
                this.renderSortButtons();
                this.loadAttendance();
            });
        });
    }

    setupSlider() {
        this.cardsWrapper = document.getElementById('statsCardsWrapper');
        this.prevButton = document.getElementById('statsPrevBtn');
        this.nextButton = document.getElementById('statsNextBtn');

        if (!this.cardsWrapper || !this.prevButton || !this.nextButton) {
            return;
        }

        this.prevButton.addEventListener('click', () => this.scrollCards('left'));
        this.nextButton.addEventListener('click', () => this.scrollCards('right'));
        this.cardsWrapper.addEventListener('scroll', () => this.updateSliderButtons());
        window.addEventListener('resize', () => this.updateSliderButtons());
        this.updateSliderButtons();
    }

    getScrollAmount() {
        const firstCard = document.querySelector('.project-card');
        if (!firstCard) {
            return 260;
        }

        const cardsGrid = document.getElementById('statsCards');
        const wrapperStyle = cardsGrid ? window.getComputedStyle(cardsGrid) : null;
        const gap = parseFloat(wrapperStyle?.columnGap || wrapperStyle?.gap || 0);
        return firstCard.getBoundingClientRect().width + gap;
    }

    scrollCards(direction) {
        if (!this.cardsWrapper) {
            return;
        }

        const scrollAmount = this.getScrollAmount();
        const maxScrollLeft = this.cardsWrapper.scrollWidth - this.cardsWrapper.clientWidth;
        const targetLeft = direction === 'right'
            ? Math.min(this.cardsWrapper.scrollLeft + scrollAmount, maxScrollLeft)
            : Math.max(this.cardsWrapper.scrollLeft - scrollAmount, 0);

        this.cardsWrapper.scrollTo({
            left: targetLeft,
            behavior: 'smooth'
        });
    }

    updateSliderButtons() {
        if (!this.cardsWrapper || !this.prevButton || !this.nextButton) {
            return;
        }

        const maxScrollLeft = Math.max(0, this.cardsWrapper.scrollWidth - this.cardsWrapper.clientWidth);
        const tolerance = 4;
        this.prevButton.disabled = this.cardsWrapper.scrollLeft <= tolerance;
        this.nextButton.disabled = this.cardsWrapper.scrollLeft >= maxScrollLeft - tolerance;
    }

    buildQuery(extra = {}) {
        const params = new URLSearchParams({
            date: this.currentDate,
            site_id: this.filters.siteId,
            search: this.filters.search,
            position: this.filters.position,
            manager: this.filters.manager,
            status: this.filters.status,
            sort_by: this.sort.by,
            sort_dir: this.sort.dir,
            per_page: String(this.attendancePerPage),
            page: String(this.attendanceCurrentPage),
            ...extra
        });

        for (const [key, value] of params.entries()) {
            if (value === '') {
                params.delete(key);
            }
        }

        return params.toString();
    }

    async loadStats() {
        const container = document.getElementById('statsCards');
        if (container) {
            container.innerHTML = '<div class="loading-spinner">Loading sites...</div>';
        }

        try {
            const response = await fetch(`../api/get_attendance_stats.php?date=${encodeURIComponent(this.currentDate)}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Failed to load site stats');
            }

            this.siteStats = Array.isArray(result.data) ? result.data : [];
            this.populateCards(this.siteStats);
            this.populateSiteFilter(this.siteStats);
            this.highlightActiveCard();
        } catch (error) {
            console.error('Failed to load stats:', error);
            if (container) {
                container.innerHTML = '<div class="loading-spinner">Could not load sites.</div>';
            }
        }
    }

    async loadAttendance() {
        const tbody = document.getElementById('attendance-body');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;">Loading attendance...</td></tr>';
        }

        try {
            const response = await fetch(`../api/get_attendance.php?${this.buildQuery()}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Failed to load attendance');
            }

            this.currentRecords = Array.isArray(result.data) ? result.data : [];
            this.populateFilterOptions(result.filters || {});
            this.displayAttendance(this.currentRecords);
            this.updateTableHeader();
        } catch (error) {
            console.error('Failed to load attendance:', error);
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;">Could not load attendance records.</td></tr>';
            }
            this.updateRecordCount(0);
        }
    }

    populateCards(stats) {
        const container = document.getElementById('statsCards');
        if (!container) return;

        if (!stats.length) {
            container.innerHTML = '<div class="loading-spinner">No sites available.</div>';
            this.updateSliderButtons();
            return;
        }

        container.innerHTML = stats.map((stat) => `
            <div class="project-card" data-site-id="${Number(stat.SiteID) || 0}">
                <h3><i class="fa-solid fa-city" style="color: #f39c12;"></i> ${this.escapeHtml(stat.Site_Name || 'Unknown Site')}</h3>
                <div class="total-count">${Number(stat.total_workers || 0)}</div>
                <div class="status-mini-row">
                    <span>Present</span>
                    <div class="status-bar"><div class="fill-bar" style="width: ${this.getPercent(stat.present_count, stat.total_workers)}%; background: var(--status-present);"></div></div>
                    <span>${Number(stat.present_count || 0)}</span>
                </div>
                <div class="status-mini-row">
                    <span>Late</span>
                    <div class="status-bar"><div class="fill-bar" style="width: ${this.getPercent(stat.late_count, stat.total_workers)}%; background: var(--status-late);"></div></div>
                    <span>${Number(stat.late_count || 0)}</span>
                </div>
                <div class="status-mini-row">
                    <span>Absent</span>
                    <div class="status-bar"><div class="fill-bar" style="width: ${this.getPercent(stat.absent_count, stat.total_workers)}%; background: var(--status-absent);"></div></div>
                    <span>${Number(stat.absent_count || 0)}</span>
                </div>
            </div>
        `).join('');

        this.updateSliderButtons();
    }

    populateSiteFilter(stats) {
        const siteFilter = document.getElementById('attendanceSiteFilter');
        if (!siteFilter) return;

        siteFilter.innerHTML = '<option value="">All Sites</option>' + stats.map((stat) => (
            `<option value="${Number(stat.SiteID) || 0}">${this.escapeHtml(stat.Site_Name || 'Unknown Site')}</option>`
        )).join('');
        siteFilter.value = this.filters.siteId;
    }

    populateFilterOptions(filters) {
        this.populateSelect(
            document.getElementById('attendancePositionFilter'),
            filters.positions || [],
            'All Positions',
            this.filters.position
        );
        this.populateSelect(
            document.getElementById('attendanceManagerFilter'),
            filters.managers || [],
            'All Site Managers',
            this.filters.manager
        );
    }

    populateSelect(select, options, defaultLabel, currentValue) {
        if (!select) return;

        select.innerHTML = `<option value="">${defaultLabel}</option>` + options.map((option) => (
            `<option value="${this.escapeHtml(option)}">${this.escapeHtml(option)}</option>`
        )).join('');
        select.value = options.includes(currentValue) ? currentValue : '';
        if (select.value !== currentValue) {
            if (select.id === 'attendancePositionFilter') this.filters.position = '';
            if (select.id === 'attendanceManagerFilter') this.filters.manager = '';
        }
    }

    syncFilterControls() {
        const siteFilter = document.getElementById('attendanceSiteFilter');
        if (siteFilter) siteFilter.value = this.filters.siteId;
    }

    highlightActiveCard() {
        document.querySelectorAll('.project-card').forEach((card) => {
            card.classList.toggle('active', String(card.dataset.siteId || '') === String(this.filters.siteId || ''));
        });
    }

    updateTableHeader() {
        const header = document.getElementById('tableSiteHeader');
        if (!header) return;

        const selectedSite = this.siteStats.find((site) => String(site.SiteID) === String(this.filters.siteId));
        const label = selectedSite ? selectedSite.Site_Name : 'All Sites';
        header.innerHTML = `<i class="fa-solid fa-city" style="color: #f39c12;"></i> ${this.escapeHtml(label)}`;
    }

    displayAttendance(records) {
        const tbody = document.getElementById('attendance-body');
        if (!tbody) return;

        if (!records.length) {
            tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;">No attendance records found.</td></tr>';
            this.updateRecordCount(0);
            return;
        }

        tbody.innerHTML = records.map((record) => {
            const scanLog = Array.isArray(record.photo_logs)
                ? record.photo_logs.find((log) => log.attendance_type === 'Time In') || record.photo_logs[0]
                : null;
            const latitude = Number(scanLog?.latitude);
            const longitude = Number(scanLog?.longitude);
            const hasCoordinates = scanLog?.latitude != null && scanLog?.longitude != null
                && Number.isFinite(latitude) && Number.isFinite(longitude);
            const coordinates = hasCoordinates ? `${latitude.toFixed(6)}, ${longitude.toFixed(6)}` : 'Not recorded';
            const mapLink = hasCoordinates
                ? `<a href="https://www.openstreetmap.org/?mlat=${latitude}&mlon=${longitude}#map=18/${latitude}/${longitude}" target="_blank" rel="noopener" title="Open scan location on map">${this.escapeHtml(coordinates)}</a>`
                : this.escapeHtml(coordinates);
            return `
            <tr>
                <td class="emp-info">
                    <strong>${this.escapeHtml(`${record.First_Name || ''} ${record.Last_Name || ''}`.trim() || 'Unknown Worker')}</strong>
                </td>
                <td>${this.escapeHtml(this.currentDate)}</td>
                <td>${this.escapeHtml(this.formatAttendanceTime(record.Time_In))}</td>
                <td>${this.escapeHtml(this.formatAttendanceTime(record.Lunch_Out))}</td>
                <td>${this.escapeHtml(this.formatAttendanceTime(record.Lunch_In))}</td>
                <td>${this.escapeHtml(this.formatAttendanceTime(record.Time_Out))}</td>
                <td><span class="status-pill ${this.escapeHtml(String(record.AttendanceStatus || 'Not Started').toLowerCase().replace(/\s+/g, '-'))}">${this.escapeHtml((record.AttendanceStatus || 'Not Started') + (Number(record.IsLate) === 1 ? ' (Late)' : ''))}</span></td>
                <td>${this.escapeHtml(record.position || 'Construction Worker')}</td>
                <td>${this.escapeHtml(scanLog?.timekeeper_name || 'Not recorded')}</td>
                <td class="scan-coordinate-cell">${mapLink}</td>
                <td class="photo-evidence-cell">${this.renderPhotoEvidenceCell(record)}</td>
                <td class="action-btns">
                    ${this.canModifyAttendance && (!record.Time_In || record.Time_In === '00:00:00') ? `<i class="fa-solid fa-play-circle" title="Clock In" onclick="attendanceSystem.clockIn(${Number(record.WorkerID || record.ID) || 0}, ${Number(record.SiteID || this.filters.siteId || 0) || 0})" style="cursor:pointer; color:#27AE60; font-size:1.2em;"></i>` : ''}
                    ${this.canModifyAttendance && record.Time_In && record.Time_In !== '00:00:00' && (!record.Lunch_Out || record.Lunch_Out === '00:00:00') ? `<i class="fa-solid fa-utensils" title="Lunch Out" onclick="attendanceSystem.clockLunchOut(${Number(record.AttendanceID) || 0})" style="cursor:pointer; color:#f59e0b; font-size:1.1em;"></i>` : ''}
                    ${this.canModifyAttendance && record.Lunch_Out && record.Lunch_Out !== '00:00:00' && (!record.Lunch_In || record.Lunch_In === '00:00:00') ? `<i class="fa-solid fa-arrow-right-to-bracket" title="PM Time In" onclick="attendanceSystem.clockLunchIn(${Number(record.AttendanceID) || 0})" style="cursor:pointer; color:#2563eb; font-size:1.1em;"></i>` : ''}
                    ${this.canModifyAttendance && record.Lunch_In && record.Lunch_In !== '00:00:00' && (!record.Time_Out || record.Time_Out === '00:00:00') ? `<i class="fa-solid fa-stop-circle" title="Clock Out" onclick="attendanceSystem.clockOut(${Number(record.AttendanceID) || 0})" style="cursor:pointer; color:#E74C3C; font-size:1.2em;"></i>` : ''}
                    <i class="fa-regular fa-eye" title="View" onclick="attendanceSystem.viewAttendance(${Number(record.WorkerID || record.ID) || 0})" style="cursor:pointer;"></i>
                    ${this.canModifyAttendance ? (record.AttendanceID ? `<i class="fa-solid fa-pencil" title="Edit" onclick="attendanceSystem.editAttendance(${Number(record.AttendanceID) || 0})" style="cursor:pointer;"></i>` : `<i class="fa-solid fa-pencil" title="No attendance record to edit" style="cursor:not-allowed; opacity:0.4;"></i>`) : ''}
                </td>
            </tr>
        `;
        }).join('');

        this.updateRecordCount(records.length);
    }

    updateRecordCount(count) {
        const recordCount = document.getElementById('recordCount');
        if (recordCount) {
            recordCount.textContent = `${count} record${count === 1 ? '' : 's'}`;
        }
    }

    renderSortButtons() {
        document.querySelectorAll('[data-sort-by]').forEach((button) => {
            const isActive = button.dataset.sortBy === this.sort.by;
            const label = button.dataset.label || button.textContent.replace(/[↑↓]/g, '').trim();
            button.classList.toggle('active', isActive);
            button.innerHTML = `${this.escapeHtml(label)}${isActive ? ` ${this.sort.dir === 'asc' ? '↑' : '↓'}` : ''}`;
        });
    }

    async clockIn(workerId, siteId) {
        const punchKey = `clock-in:${workerId}:${siteId}`;
        if (this.punchInProgress.has(punchKey)) return;
        this.punchInProgress.add(punchKey);

        try {
            const today = this.getLocalDateValue();
            if (this.currentDate !== today) {
                this.currentDate = today;
                this.updateDateFilter();
            }
            console.log('[attendance] punch start', { fn: 'clockIn', workerId, siteId });
            const response = await fetch('../api/clock_in.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ worker_id: workerId, site_id: siteId })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                // Success should not be followed by an “unsuccessful” modal.
                // Some browsers/pages may still trigger alert() based modals from other code;
                // ensure we only alert on the correct branch.
                this.showPunchResult(true, result.message || `Clocked in successfully at ${result.time_in}`, 'Clock In');
                await Promise.all([this.loadStats(), this.loadAttendance()]);
                return;
            }

            this.showPunchResult(false, result.error || result.message || 'Unable to clock in.', 'Clock In');
        } catch (error) {
            this.showPunchResult(false, error.message || 'Unable to clock in.', 'Clock In');
        } finally {
            this.punchInProgress.delete(punchKey);
        }
    }

    showPunchResult(success, message, actionLabel) {
        if (typeof window.showCrudResultModal === 'function') {
            window.showCrudResultModal(success, message, actionLabel);
            return;
        }

        alert(message);
    }

    formatAttendanceTime(timeStr) {
        if (!timeStr || timeStr === '00:00:00') {
            return '--:--';
        }

        const value = String(timeStr).slice(0, 5);
        const [hoursText = '0', minutesText = '00'] = value.split(':');
        let hours = Number(hoursText);
        const suffix = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutesText} ${suffix}`;
    }

    async clockLunchOut(attendanceId) {
        try {
            const today = this.getLocalDateValue();
            if (this.currentDate !== today) {
                this.currentDate = today;
                this.updateDateFilter();
                await this.loadAttendance();
                const todayRecord = this.getRecordByAttendanceId(attendanceId);
                if (!todayRecord) {
                    this.showPunchResult(false, 'Lunch Out can only be recorded from today\'s attendance record.', 'Lunch Out');
                    return;
                }
            }
            const response = await fetch('../api/clock_lunch_out.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attendance_id: attendanceId })
            });
            const result = await response.json();

            if (result.success) {
                alert(`Lunch out recorded at ${this.formatAttendanceTime(result.lunch_out)}`);
                await Promise.all([this.loadStats(), this.loadAttendance()]);
                return;
            }

            alert('Error: ' + (result.error || 'Unknown error'));
        } catch (error) {
            alert('Lunch out failed: ' + error.message);
        }
    }

    async clockLunchIn(attendanceId) {
        try {
            const today = this.getLocalDateValue();
            if (this.currentDate !== today) {
                this.currentDate = today;
                this.updateDateFilter();
                await this.loadAttendance();
                const todayRecord = this.getRecordByAttendanceId(attendanceId);
                if (!todayRecord) {
                    this.showPunchResult(false, 'PM Time In can only be recorded from today\'s attendance record.', 'PM Time In');
                    return;
                }
            }
            const response = await fetch('../api/clock_lunch_in.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attendance_id: attendanceId })
            });
            const result = await response.json();

            if (result.success) {
                alert(`PM time in recorded at ${this.formatAttendanceTime(result.lunch_in)}`);
                await Promise.all([this.loadStats(), this.loadAttendance()]);
                return;
            }

            alert('Error: ' + (result.error || 'Unknown error'));
        } catch (error) {
            alert('PM time in failed: ' + error.message);
        }
    }

    async clockOut(attendanceId) {
        const punchKey = `clock-out:${attendanceId}`;
        if (this.punchInProgress.has(punchKey)) return;
        this.punchInProgress.add(punchKey);

        try {
            const today = this.getLocalDateValue();
            if (this.currentDate !== today) {
                this.currentDate = today;
                this.updateDateFilter();
                await this.loadAttendance();
                const todayRecord = this.getRecordByAttendanceId(attendanceId);
                if (!todayRecord) {
                    this.showPunchResult(false, 'Time Out can only be recorded from today\'s attendance record.', 'Clock Out');
                    return;
                }
            }
            const response = await fetch('../api/clock_out.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attendance_id: attendanceId })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                this.showPunchResult(true, result.message || `Clocked out successfully: ${result.hours_worked}h (${result.status})`, 'Clock Out');
                await Promise.all([this.loadStats(), this.loadAttendance()]);
                return;
            }

            this.showPunchResult(false, result.error || result.message || 'Unable to clock out.', 'Clock Out');
        } catch (error) {
            this.showPunchResult(false, error.message || 'Unable to clock out.', 'Clock Out');
        } finally {
            this.punchInProgress.delete(punchKey);
        }
    }

    getRecordByAttendanceId(attendanceId) {
        return this.currentRecords.find((record) => Number(record.AttendanceID) === Number(attendanceId)) || null;
    }

    getRecordByWorkerId(workerId) {
        return this.currentRecords.find((record) => Number(record.WorkerID || record.ID) === Number(workerId)) || null;
    }

    buildPhotoUrl(photoPath) {
        if (!photoPath) {
            return '';
        }

        const normalized = String(photoPath).replace(/^\/+/, '');
        if (normalized.startsWith('uploads/')) {
            return `../${normalized}`;
        }

        return `../uploads/attendance_photos/${normalized}`;
    }

    getPhotoLogs(record) {
        const allowedTypes = new Set(this.photoTypeOrder);
        let logs = [];

        if (Array.isArray(record.photo_logs) && record.photo_logs.length) {
            logs = record.photo_logs.filter((entry) => allowedTypes.has(entry.attendance_type));
        }

        const columnMap = {
            'Time In': { path: record.AMTimeInPhoto, time: record.Time_In, label: 'AM Time In Photo' },
            'Lunch In': { path: record.PMTimeInPhoto, time: record.Lunch_In, label: 'PM Time In Photo' },
            'Time Out': { path: record.TimeOutPhoto, time: record.Time_Out, label: 'Time Out Photo' },
        };

        this.photoTypeOrder.forEach((type) => {
            const config = columnMap[type];
            const path = String(config?.path || '').trim();
            if (!path) {
                return;
            }

            const alreadyPresent = logs.some((entry) => entry.attendance_type === type);
            if (alreadyPresent) {
                return;
            }

            logs.push({
                label: config.label,
                attendance_type: type,
                photo_path: path,
                event_time: config.time || '',
            });
        });

        if (!logs.length) {
            const fallbackPath = String(record.PhotoPath || '').trim();
            if (fallbackPath) {
                logs.push({
                    label: 'AM Time In Photo',
                    attendance_type: 'Time In',
                    photo_path: fallbackPath,
                    event_time: record.Time_In || '',
                });
            }
        }

        return logs.sort((left, right) => {
            const leftOrder = this.photoTypeOrder.indexOf(left.attendance_type);
            const rightOrder = this.photoTypeOrder.indexOf(right.attendance_type);
            if (leftOrder !== rightOrder) {
                return leftOrder - rightOrder;
            }

            return String(left.event_time || '').localeCompare(String(right.event_time || ''));
        });
    }

    formatDisplayDate(dateStr) {
        if (!dateStr) {
            return '--';
        }

        const parsed = new Date(`${dateStr}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) {
            return dateStr;
        }

        return parsed.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        });
    }

    renderPhotoEvidenceCell(record) {
        const photoLogs = this.getPhotoLogs(record);
        const workerId = Number(record.WorkerID || record.ID || 0);

        if (!photoLogs.length) {
            return '<span class="photo-evidence-empty">No photo</span>';
        }

        return `
            <button type="button" class="photo-view-btn" onclick="attendanceSystem.openAttendancePhotoDetails(${workerId})">
                <i class="fa-solid fa-images"></i> View Photos
            </button>
        `;
    }

    renderAttendanceEvidenceGallery(photoLogs) {
        if (!photoLogs.length) {
            return '<div class="photo-evidence-empty">No photo evidence uploaded for this date.</div>';
        }

        return photoLogs.map((entry, index) => {
            const photoUrl = this.buildPhotoUrl(entry.photo_path);
            const timeLabel = entry.event_time ? this.formatAttendanceTime(entry.event_time) : '--';
            const connector = index < photoLogs.length - 1
                ? '<div class="attendance-photo-connector" aria-hidden="true"><i class="fa-solid fa-arrow-down"></i></div>'
                : '';

            return `
                <article class="attendance-photo-card">
                    <div class="attendance-photo-card-label">${this.escapeHtml(entry.label || 'Attendance Photo')}</div>
                    <button type="button" class="attendance-photo-card-image" data-photo-url="${this.escapeHtml(photoUrl)}" data-photo-label="${this.escapeHtml(entry.label || 'Attendance Photo')}" title="Open full-size photo">
                        <img src="${this.escapeHtml(photoUrl)}" alt="${this.escapeHtml(entry.label || 'Attendance photo')}" class="attendance-photo-detail">
                    </button>
                    <div class="attendance-photo-card-meta">Captured: ${this.escapeHtml(timeLabel)}</div>
                </article>
                ${connector}
            `;
        }).join('');
    }

    openAttendancePhotoDetails(workerId) {
        const record = this.getRecordByWorkerId(workerId);
        const photoLogs = record ? this.getPhotoLogs(record) : [];
        if (!record) {
            alert('Attendance record not found.');
            return;
        }

        if (!photoLogs.length) {
            alert('No photo evidence available for this attendance record.');
            return;
        }

        const workerName = `${record.First_Name || ''} ${record.Last_Name || ''}`.trim() || 'Unknown Worker';
        const employeeId = record.WorkerID || record.ID || '--';
        const attendanceDate = record.Date || this.currentDate;
        const status = (record.AttendanceStatus || 'Not Started') + (Number(record.IsLate) === 1 ? ' (Late)' : '');

        this.openAttendanceModal({
            title: 'Attendance Details',
            wide: true,
            body: `
                <div class="attendance-details-summary">
                    <div><strong>Worker</strong><div>${this.escapeHtml(workerName)}</div></div>
                    <div><strong>Employee ID</strong><div>${this.escapeHtml(employeeId)}</div></div>
                    <div><strong>Site</strong><div>${this.escapeHtml(record.Site_Name || '-')}</div></div>
                    <div><strong>Date</strong><div>${this.escapeHtml(this.formatDisplayDate(attendanceDate))}</div></div>
                    <div><strong>Status</strong><div><span class="status-pill ${String(status).toLowerCase().replace(/\s+/g, '-')}">${this.escapeHtml(status)}</span></div></div>
                </div>
                <section class="attendance-evidence-section">
                    <h4 class="attendance-evidence-title">Attendance Evidence</h4>
                    <div class="attendance-photo-stack">
                        ${this.renderAttendanceEvidenceGallery(photoLogs)}
                    </div>
                    <p class="attendance-evidence-hint">Click any photo to open it full screen. Use zoom controls or scroll to inspect details.</p>
                </section>
            `
        });
    }

    openPhotoViewer(photoUrl, title = 'Attendance Photo') {
        this.openPhotoLightbox(photoUrl, title);
    }

    openPhotoViewerForWorker(workerId) {
        this.openAttendancePhotoDetails(workerId);
    }

    openPhotoLightbox(photoUrl, title = 'Attendance Photo') {
        if (!photoUrl) {
            return;
        }

        this.closePhotoLightbox();
        this.photoLightboxState.scale = 1;

        const overlay = document.createElement('div');
        overlay.className = 'attendance-photo-lightbox';
        overlay.id = 'attendancePhotoLightbox';
        overlay.innerHTML = `
            <div class="attendance-photo-lightbox-toolbar">
                <span class="attendance-photo-lightbox-title">${this.escapeHtml(title)}</span>
                <div class="attendance-photo-lightbox-actions">
                    <button type="button" class="lightbox-action-btn" data-lightbox-zoom="out" title="Zoom out"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <button type="button" class="lightbox-action-btn" data-lightbox-zoom="in" title="Zoom in"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <button type="button" class="lightbox-action-btn" data-lightbox-close="true" title="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="attendance-photo-lightbox-stage">
                <img src="${this.escapeHtml(photoUrl)}" alt="${this.escapeHtml(title)}" class="attendance-photo-lightbox-image" style="transform: scale(1);">
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.classList.add('attendance-lightbox-open');
    }

    adjustPhotoLightboxZoom(delta) {
        const image = document.querySelector('#attendancePhotoLightbox .attendance-photo-lightbox-image');
        if (!image) {
            return;
        }

        const nextScale = Math.min(
            this.photoLightboxState.maxScale,
            Math.max(this.photoLightboxState.minScale, this.photoLightboxState.scale + delta)
        );
        this.photoLightboxState.scale = nextScale;
        image.style.transform = `scale(${nextScale})`;
    }

    closePhotoLightbox() {
        const lightbox = document.getElementById('attendancePhotoLightbox');
        if (lightbox) {
            lightbox.remove();
        }
        document.body.classList.remove('attendance-lightbox-open');
    }

    resolveAttendanceType(record) {
        const timeOut = String(record.Time_Out || '');
        const lunchIn = String(record.Lunch_In || '');
        const lunchOut = String(record.Lunch_Out || '');
        const timeIn = String(record.Time_In || '');

        if (timeOut && timeOut !== '00:00:00') return 'Clock Out';
        if (lunchIn && lunchIn !== '00:00:00') return 'PM Time In';
        if (lunchOut && lunchOut !== '00:00:00') return 'Lunch Out';
        if (timeIn && timeIn !== '00:00:00') return 'Clock In';
        return record.AttendanceStatus || 'Not Started';
    }

    formatGpsCoordinates(record) {
        const latitude = record.Latitude;
        const longitude = record.Longitude;
        if (latitude === null || latitude === undefined || longitude === null || longitude === undefined || latitude === '' || longitude === '') {
            return 'Not recorded';
        }

        return `${Number(latitude).toFixed(6)}, ${Number(longitude).toFixed(6)}`;
    }

    formatDistanceFromSite(distance) {
        if (distance === null || distance === undefined || distance === '') {
            return 'Not recorded';
        }

        return `${Number(distance).toFixed(2)} m`;
    }

    formatAttendanceTimestamp(record) {
        const timeIn = record.Time_In;
        if (!timeIn || timeIn === '00:00:00') {
            return '--';
        }

        return `${this.currentDate} ${this.formatAttendanceTime(timeIn)}`;
    }

    renderAttendancePhotoSection(record) {
        const photoLogs = this.getPhotoLogs(record);
        if (!photoLogs.length) {
            return `
                <div class="attendance-photo-section">
                    <strong>Daily Photo Evidence</strong>
                    <div class="photo-evidence-empty">No photo evidence uploaded.</div>
                </div>
            `;
        }

        const workerId = Number(record.WorkerID || record.ID || 0);
        const galleryItems = photoLogs.map((entry) => {
            const photoUrl = this.buildPhotoUrl(entry.photo_path);
            const timeLabel = entry.event_time ? this.formatAttendanceTime(entry.event_time) : '';
            return `
                <div class="attendance-photo-gallery-item">
                    <div class="attendance-photo-gallery-label">${this.escapeHtml(entry.label || 'Attendance Photo')}</div>
                    ${timeLabel ? `<div class="attendance-photo-gallery-meta">Captured: ${this.escapeHtml(timeLabel)}</div>` : ''}
                    <button type="button" class="attendance-photo-card-image" data-photo-url="${this.escapeHtml(photoUrl)}" data-photo-label="${this.escapeHtml(entry.label || 'Attendance Photo')}" title="Open full-size photo">
                        <img src="${this.escapeHtml(photoUrl)}" alt="${this.escapeHtml(entry.label || 'Attendance photo')}" class="attendance-photo-detail">
                    </button>
                </div>
            `;
        }).join('');

        return `
            <div class="attendance-photo-section">
                <strong>Daily Photo Evidence</strong>
                <div class="attendance-photo-gallery">${galleryItems}</div>
                <button type="button" class="photo-view-btn" onclick="attendanceSystem.openAttendancePhotoDetails(${workerId})">View Photos</button>
            </div>
        `;
    }

    viewAttendance(workerId) {
        const record = this.getRecordByWorkerId(workerId);
        if (!record) {
            alert('Attendance record not found.');
            return;
        }

        this.openAttendanceModal({
            title: `${record.First_Name || ''} ${record.Last_Name || ''}`.trim() || 'Attendance Details',
            body: `
                <div class="attendance-modal-grid">
                    <div><strong>Worker</strong><div>${this.escapeHtml(`${record.First_Name || ''} ${record.Last_Name || ''}`.trim() || 'Unknown Worker')}</div></div>
                    <div><strong>Site</strong><div>${this.escapeHtml(record.Site_Name || '-')}</div></div>
                    <div><strong>Attendance Type</strong><div>${this.escapeHtml(this.resolveAttendanceType(record))}</div></div>
                    <div><strong>Date and Time</strong><div>${this.escapeHtml(this.formatAttendanceTimestamp(record))}</div></div>
                    <div><strong>Time In</strong><div>${this.escapeHtml(this.formatAttendanceTime(record.Time_In))}</div></div>
                    <div><strong>Lunch Out</strong><div>${this.escapeHtml(this.formatAttendanceTime(record.Lunch_Out))}</div></div>
                    <div><strong>PM Time In</strong><div>${this.escapeHtml(this.formatAttendanceTime(record.Lunch_In))}</div></div>
                    <div><strong>Time Out</strong><div>${this.escapeHtml(this.formatAttendanceTime(record.Time_Out))}</div></div>
                    <div><strong>Status</strong><div>${this.escapeHtml((record.AttendanceStatus || 'Not Started') + (Number(record.IsLate) === 1 ? ' (Late)' : ''))}</div></div>
                    <div><strong>Position</strong><div>${this.escapeHtml(record.position || 'Construction Worker')}</div></div>
                    <div><strong>Site Manager</strong><div>${this.escapeHtml(record.site_manager || 'Not assigned')}</div></div>
                    <div><strong>Hours Worked</strong><div>${this.escapeHtml(record.Hours_Worked || 0)}</div></div>
                    <div><strong>GPS Coordinates</strong><div>${this.escapeHtml(this.formatGpsCoordinates(record))}</div></div>
                    <div><strong>Distance From Site</strong><div>${this.escapeHtml(this.formatDistanceFromSite(record.DistanceFromSite))}</div></div>
                </div>
                ${this.renderAttendancePhotoSection(record)}
            `
        });
    }

    editAttendance(attendanceId) {
        const record = this.getRecordByAttendanceId(attendanceId);
        if (!record) {
            alert('Only existing attendance records can be edited.');
            return;
        }

        this.openAttendanceModal({
            title: 'Edit Attendance',
            body: `
                <form id="attendanceEditForm" data-attendance-id="${Number(record.AttendanceID) || 0}">
                    <div class="attendance-modal-grid">
                        <label>
                            <strong>Time In</strong>
                            <input type="time" name="time_in" value="${record.Time_In && record.Time_In !== '00:00:00' ? this.escapeHtml(String(record.Time_In).slice(0, 5)) : ''}">
                        </label>
                        <label>
                            <strong>Lunch Out</strong>
                            <input type="time" name="lunch_out" value="${record.Lunch_Out && record.Lunch_Out !== '00:00:00' ? this.escapeHtml(String(record.Lunch_Out).slice(0, 5)) : ''}">
                        </label>
                        <label>
                            <strong>PM Time In</strong>
                            <input type="time" name="lunch_in" value="${record.Lunch_In && record.Lunch_In !== '00:00:00' ? this.escapeHtml(String(record.Lunch_In).slice(0, 5)) : ''}">
                        </label>
                        <label>
                            <strong>Time Out</strong>
                            <input type="time" name="time_out" value="${record.Time_Out && record.Time_Out !== '00:00:00' ? this.escapeHtml(String(record.Time_Out).slice(0, 5)) : ''}">
                        </label>
                        <label>
                            <strong>Status</strong>
                            <input type="text" value="Automatically calculated" disabled>
                        </label>
                    </div>
                    <div class="attendance-modal-actions">
                        <button type="button" data-attendance-close="true">Cancel</button>
                        <button type="submit">Save Changes</button>
                    </div>
                </form>
            `
        });
    }

    openAttendanceModal({ title, body, wide = false }) {
        this.closeAttendanceModal();

        const modal = document.createElement('div');
        modal.className = 'attendance-modal-overlay';
        modal.id = 'attendanceActionModal';
        modal.innerHTML = `
            <div class="attendance-modal-card ${wide ? 'attendance-modal-card-wide' : ''}" role="dialog" aria-modal="true" aria-label="${this.escapeHtml(title)}">
                <div class="attendance-modal-header">
                    <h3>${this.escapeHtml(title)}</h3>
                    <button type="button" class="attendance-modal-close" data-attendance-close="true">&times;</button>
                </div>
                <div class="attendance-modal-body">${body}</div>
            </div>
        `;

        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';

        const form = modal.querySelector('#attendanceEditForm');
        if (form) {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.saveAttendanceEdit(form);
            });
        }
    }

    closeAttendanceModal() {
        this.closePhotoLightbox();
        const modal = document.getElementById('attendanceActionModal');
        if (modal) {
            modal.remove();
        }
        document.body.style.overflow = '';
    }

    async saveAttendanceEdit(form) {
        const attendanceId = Number(form.dataset.attendanceId || 0);
        if (!attendanceId) {
            alert('Invalid attendance record.');
            return;
        }

        const formData = new FormData(form);
        const updates = {
            Time_In: formData.get('time_in') ? `${formData.get('time_in')}:00` : null,
            Lunch_Out: formData.get('lunch_out') ? `${formData.get('lunch_out')}:00` : null,
            Lunch_In: formData.get('lunch_in') ? `${formData.get('lunch_in')}:00` : null,
            Time_Out: formData.get('time_out') ? `${formData.get('time_out')}:00` : null
        };

        try {
            const response = await fetch('../api/update_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attendance_id: attendanceId, updates })
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Failed to update attendance');
            }

            this.closeAttendanceModal();
            await Promise.all([this.loadStats(), this.loadAttendance()]);
            alert('Attendance updated successfully.');
        } catch (error) {
            alert(`Could not update attendance: ${error.message}`);
        }
    }

    async exportAttendance() {
        try {
            const response = await fetch(`../api/get_attendance.php?${this.buildQuery({ export: '1' })}`);
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Export failed');
            }

            const records = Array.isArray(result.data) ? result.data : [];
            if (!records.length) {
                alert('No attendance records available for export.');
                return;
            }

            const exportRows = [];
            records.forEach((record) => {
                const hasGps = record.Latitude !== null && record.Latitude !== undefined && record.Latitude !== ''
                    && record.Longitude !== null && record.Longitude !== undefined && record.Longitude !== '';
                exportRows.push([
                    `${record.First_Name || ''} ${record.Last_Name || ''}`.trim(),
                    record.Site_Name || '',
                    this.currentDate,
                    record.Time_In || '',
                    record.Time_Out || '',
                    (record.AttendanceStatus || 'Not Started') + (Number(record.IsLate) === 1 ? ' (Late)' : ''),
                    record.position || '',
                    record.site_manager || '',
                    record.Hours_Worked || 0,
                    record.PhotoPath || '',
                    this.formatAttendanceTimestamp(record),
                    hasGps ? this.formatGpsCoordinates(record) : '',
                    record.DistanceFromSite ?? '',
                    hasGps ? (record.DistanceFromSite !== null && record.DistanceFromSite !== undefined && record.DistanceFromSite !== '' ? 'GPS Recorded' : 'No Distance') : 'No GPS'
                ]);
            });

            const exportResponse = await fetch('../api/export_attendance_excel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ date: this.currentDate, rows: exportRows })
            });
            if (!exportResponse.ok) {
                const errorData = await exportResponse.json().catch(() => ({}));
                throw new Error(errorData.message || 'Excel export failed');
            }

            const blob = await exportResponse.blob();
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `attendance-${this.currentDate}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } catch (error) {
            alert(`Could not export attendance: ${error.message}`);
        }
    }

    updateDateFilter() {
        const dateInput = document.getElementById('attendanceDateFilter');
        if (dateInput) {
            dateInput.value = this.currentDate;
        }
    }

    getPercent(value, total) {
        const numericTotal = Number(total || 0);
        if (!numericTotal) return 0;
        return Math.round((Number(value || 0) / numericTotal) * 100);
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.attendanceSystem = new AttendanceSystem();
        window.attendanceSystem.init();
    });
} else {
    window.attendanceSystem = new AttendanceSystem();
    window.attendanceSystem.init();
}
