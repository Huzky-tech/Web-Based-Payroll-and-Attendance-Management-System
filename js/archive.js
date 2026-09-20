const archiveState = {
    items: [],
    counts: {
        all: 0,
        employee: 0,
        site: 0,
        payroll: 0,
        report: 0,
        user: 0
    },
    activeType: 'all',
    search: ''
};

function normalizeArchiveText(value) {
    return String(value ?? '').toLowerCase();
}

function escapeArchiveHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function cleanArchiveDisplayText(value) {
    return String(value ?? '').replace(/\s*\[(employee|employees|worker|workers|site|sites|projectsite|project_site|payroll|payroll_record|payroll_records|report|reports|user|users|admin|assistantmanager|payrollstaff|timekeeper)\]\s*$/i, '').trim();
}

function getArchiveTypeKey(type) {
    const normalized = String(type || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
    const map = {
        worker: 'employee',
        workers: 'employee',
        employee: 'employee',
        employees: 'employee',
        projectsite: 'site',
        project_site: 'site',
        site: 'site',
        sites: 'site',
        payroll: 'payroll',
        payroll_record: 'payroll',
        payroll_records: 'payroll',
        report: 'report',
        reports: 'report',
        timekeeper_report: 'report',
        timekeeper_reports: 'report',
        user: 'user',
        users: 'user',
        admin: 'user',
        assistantmanager: 'user',
        payrollstaff: 'user',
        timekeeper: 'user'
    };

    return map[normalized] || normalized || 'archive';
}

function getArchiveTypeLabel(type) {
    const labels = {
        employee: 'Employee',
        site: 'Site',
        payroll: 'Payroll',
        report: 'Report',
        user: 'User',
        archive: 'Archive'
    };
    const key = getArchiveTypeKey(type);
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function archiveSearchHaystack(item) {
    return normalizeArchiveText([
        cleanArchiveDisplayText(item.title),
        getArchiveTypeLabel(item.type),
        cleanArchiveDisplayText(item.description),
        item.archived_by,
        item.archived_date,
        item.original_date,
        item.location,
        item.project_manager,
        item.date_created,
        item.date_archived,
        item.assigned_workers
    ].join(' '));
}

function renderArchiveSiteMeta(item) {
    return `
        <div class="archive-site-fields">
            <div class="archive-site-field"><span class="archive-site-field-label">Site Name</span><span class="archive-site-field-value">${escapeArchiveHtml(cleanArchiveDisplayText(item.title))}</span></div>
            <div class="archive-site-field"><span class="archive-site-field-label">Location</span><span class="archive-site-field-value">${escapeArchiveHtml(item.location || 'Not specified')}</span></div>
            <div class="archive-site-field"><span class="archive-site-field-label">Project Manager</span><span class="archive-site-field-value">${escapeArchiveHtml(item.project_manager || 'Not assigned')}</span></div>
            <div class="archive-site-field"><span class="archive-site-field-label">Date Created</span><span class="archive-site-field-value">${escapeArchiveHtml(item.date_created || item.original_date || '-')}</span></div>
            <div class="archive-site-field"><span class="archive-site-field-label">Date Archived</span><span class="archive-site-field-value">${escapeArchiveHtml(item.date_archived || item.archived_date || '-')}</span></div>
            <div class="archive-site-field"><span class="archive-site-field-label">Assigned Workers</span><span class="archive-site-field-value">${escapeArchiveHtml(String(item.assigned_workers ?? 0))}</span></div>
        </div>
    `;
}

function renderArchiveItemCard(item) {
    const typeKey = getArchiveTypeKey(item.type);
    const isSite = typeKey === 'site' && item.source === 'database';
    const metaBlock = isSite
        ? renderArchiveSiteMeta(item)
        : `
                    <div class="archive-desc">${escapeArchiveHtml(cleanArchiveDisplayText(item.description))}</div>
                    <div class="archive-meta">
                        <span><i class="fa-regular fa-calendar"></i>Archived: ${escapeArchiveHtml(item.archived_date)}</span>
                        <span><i class="fa-regular fa-user"></i>By: ${escapeArchiveHtml(item.archived_by)}</span>
                        <span><i class="fa-regular fa-clock"></i>Original: ${escapeArchiveHtml(item.original_date)}</span>
                    </div>`;

    const restoreButton = isSite
        ? `<button class="archive-action-btn archive-restore-site-btn" type="button" data-archive-action="restore" data-archive-id="${escapeArchiveHtml(item.id)}" title="Restore" ${item.can_restore === false ? 'disabled' : ''}>
                    <i class="fa-solid fa-trash-arrow-up"></i>
                    <span>Restore</span>
                </button>`
        : `<button class="archive-action-btn" type="button" data-archive-action="restore" data-archive-id="${escapeArchiveHtml(item.id)}" title="Restore" ${item.can_restore === false ? 'disabled' : ''}>
                    <i class="fa-solid fa-trash-arrow-up"></i>
                </button>`;

    return `
        <div class="archive-item${isSite ? ' archive-item-site' : ''}">
            <div class="archive-item-left">
                <div class="archive-item-icon ${escapeArchiveHtml(item.color || 'blue')}">
                    <i class="${escapeArchiveHtml(item.icon || 'fa-regular fa-file-lines')}"></i>
                </div>

                <div>
                    <div class="archive-item-title">
                        <h3>${escapeArchiveHtml(cleanArchiveDisplayText(item.title))}</h3>
                    </div>

                    ${metaBlock}
                </div>
            </div>

            <div class="archive-actions">
                <button class="archive-action-btn" type="button" data-archive-action="view" data-archive-id="${escapeArchiveHtml(item.id)}" title="View">
                    <i class="fa-regular fa-eye"></i>
                </button>
                ${restoreButton}

                ${item.can_delete === false ? '' : `<button class="archive-action-btn" type="button" data-archive-action="delete" data-archive-id="${escapeArchiveHtml(item.id)}" title="Delete">
                    <i class="fa-regular fa-trash-can"></i>
                </button>`}
            </div>
        </div>
    `;
}

function getVisibleArchiveItems() {
    return archiveState.items.filter((item) => {
        const matchesType = archiveState.activeType === 'all' || getArchiveTypeKey(item.type) === archiveState.activeType;
        const matchesSearch = !archiveState.search || archiveSearchHaystack(item).includes(archiveState.search);
        return matchesType && matchesSearch;
    });
}

function getArchiveItemById(id) {
    return archiveState.items.find((item) => String(item.id) === String(id)) || null;
}

function showArchiveToast(message, isError = false) {
    window.showCrudResultModal?.(
        !isError,
        message,
        'Archive Action'
    );
}

function updateArchiveBadge(count) {
    const badge = document.getElementById('archiveItemBadge');
    if (badge) {
        badge.textContent = `${count} item${count === 1 ? '' : 's'}`;
    }
}

function updateArchiveCountTags() {
    const mapping = {
        all: 'archiveTagAll',
        employee: 'archiveTagEmployee',
        site: 'archiveTagSite'
    };

    Object.entries(mapping).forEach(([type, id]) => {
        const button = document.getElementById(id);
        if (!button) {
            return;
        }

        const label = type.charAt(0).toUpperCase() + type.slice(1);
        button.textContent = `${label} (${Number(archiveState.counts[type] || 0)})`;
    });
}

function renderArchiveList() {
    const list = document.getElementById('archiveList');
    const emptyState = document.getElementById('archiveEmptyState');
    if (!list) {
        return;
    }

    const visibleItems = getVisibleArchiveItems();
    updateArchiveBadge(visibleItems.length);

    if (visibleItems.length === 0) {
        list.innerHTML = '';
        if (emptyState) {
            emptyState.hidden = false;
        }
        return;
    }

    if (emptyState) {
        emptyState.hidden = true;
    }

    list.innerHTML = visibleItems.map((item) => renderArchiveItemCard(item)).join('');
}

function openArchiveDetails(item) {
    const modal = document.getElementById('archiveDetailsModal');
    if (!modal || !item) {
        return;
    }

    document.getElementById('archiveDetailsTitle').textContent = cleanArchiveDisplayText(item.title) || 'Archived Record';
    const typeKey = getArchiveTypeKey(item.type);
    document.getElementById('archiveDetailsSubtitle').textContent = 'Archived record';
    document.getElementById('archiveDetailsName').textContent = cleanArchiveDisplayText(item.title) || '-';
    document.getElementById('archiveDetailsDescription').textContent = cleanArchiveDisplayText(item.description) || '-';
    document.getElementById('archiveDetailsArchivedDate').textContent = item.date_archived || item.archived_date || '-';
    document.getElementById('archiveDetailsArchivedBy').textContent = item.archived_by || '-';
    document.getElementById('archiveDetailsOriginalDate').textContent = item.date_created || item.original_date || '-';

    const descriptionEl = document.getElementById('archiveDetailsDescription');
    if (descriptionEl && typeKey === 'site') {
        descriptionEl.textContent = [
            `Location: ${item.location || 'Not specified'}`,
            `Project Manager: ${item.project_manager || 'Not assigned'}`,
            `Assigned Workers: ${item.assigned_workers ?? 0}`
        ].join(' | ');
    }

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('archive-modal-open');
}

function closeArchiveDetails() {
    const modal = document.getElementById('archiveDetailsModal');
    if (!modal) {
        return;
    }

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('archive-modal-open');
}

function confirmArchiveRestore(item) {
    const modal = document.getElementById('restoreArchiveModal');
    const confirmButton = document.getElementById('confirmRestoreArchive');
    const cancelButton = document.getElementById('cancelRestoreArchive');
    const itemName = document.getElementById('restoreArchiveItemName');
    if (!modal || !confirmButton || !cancelButton) {
        return Promise.resolve(false);
    }

    if (itemName) itemName.textContent = cleanArchiveDisplayText(item?.title) || 'this record';
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('archive-modal-open');

    return new Promise((resolve) => {
        const finish = (confirmed) => {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('archive-modal-open');
            confirmButton.removeEventListener('click', confirmRestore);
            cancelButton.removeEventListener('click', cancelRestore);
            modal.removeEventListener('click', cancelFromOverlay);
            document.removeEventListener('keydown', cancelFromEscape);
            resolve(confirmed);
        };
        const confirmRestore = () => finish(true);
        const cancelRestore = () => finish(false);
        const cancelFromOverlay = (event) => {
            if (event.target === modal) finish(false);
        };
        const cancelFromEscape = (event) => {
            if (event.key === 'Escape') finish(false);
        };

        confirmButton.addEventListener('click', confirmRestore);
        cancelButton.addEventListener('click', cancelRestore);
        modal.addEventListener('click', cancelFromOverlay);
        document.addEventListener('keydown', cancelFromEscape);
        cancelButton.focus();
    });
}

function downloadArchiveItem(item) {
    if (!item) {
        return;
    }

    const cleanTitle = cleanArchiveDisplayText(item.title);
    const fileName = `${String(cleanTitle || 'archive-item').replace(/[^a-z0-9]+/gi, '-').toLowerCase()}.txt`;
    const typeKey = getArchiveTypeKey(item.type);
    const content = typeKey === 'site'
        ? [
            `Site Name: ${cleanTitle || ''}`,
            `Location: ${item.location || ''}`,
            `Project Manager: ${item.project_manager || ''}`,
            `Date Created: ${item.date_created || item.original_date || ''}`,
            `Date Archived: ${item.date_archived || item.archived_date || ''}`,
            `Assigned Workers: ${item.assigned_workers ?? 0}`
        ].join('\n')
        : [
            `Title: ${cleanTitle || ''}`,
            `Description: ${cleanArchiveDisplayText(item.description) || ''}`,
            `Archived Date: ${item.archived_date || ''}`,
            `Archived By: ${item.archived_by || ''}`,
            `Original Date: ${item.original_date || ''}`
        ].join('\n');

    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
    showArchiveToast(`Downloaded ${cleanTitle || 'archive item'}.`);
}

async function fetchArchiveItems() {
    const list = document.getElementById('archiveList');

    try {
        const response = await fetch('../api/get_archive_items.php');
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to load archive items');
        }

        archiveState.items = Array.isArray(result.items) ? result.items : [];
        archiveState.counts = result.counts || archiveState.counts;
        updateArchiveCountTags();
        renderArchiveList();
    } catch (error) {
        if (list) {
            list.innerHTML = '<div class="archive-empty">Unable to load archive items right now.</div>';
        }
    }
}

async function runArchiveAction(action, id) {
    const item = getArchiveItemById(id);
    if (!item) {
        showArchiveToast('Archived item not found.', true);
        return;
    }

    if (action === 'download') {
        downloadArchiveItem(item);
        return;
    }

    if (action === 'view') {
        openArchiveDetails(item);
        return;
    }

    if (action === 'restore' && !await confirmArchiveRestore(item)) {
        return;
    }

    try {
        const response = await fetch('../api/archive_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action, id })
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Archive action failed');
        }

        archiveState.items = archiveState.items.filter((entry) => String(entry.id) !== String(id));
        archiveState.counts.all = archiveState.items.length;
        const typeKey = getArchiveTypeKey(item.type);
        if (archiveState.counts[typeKey] !== undefined) {
            archiveState.counts[typeKey] = Math.max(0, Number(archiveState.counts[typeKey]) - 1);
        }

        closeArchiveDetails();
        updateArchiveCountTags();
        renderArchiveList();
        showArchiveToast(result.message || `${cleanArchiveDisplayText(item.title) || 'Archive item'} updated.`);
    } catch (error) {
        showArchiveToast(error.message || 'Archive action failed.', true);
    }
}

function bindArchiveEvents() {
    document.getElementById('archiveSearchInput')?.addEventListener('input', (event) => {
        archiveState.search = normalizeArchiveText(event.target.value.trim());
        renderArchiveList();
    });

    document.querySelectorAll('.archive-tag').forEach((button) => {
        button.addEventListener('click', () => {
            archiveState.activeType = button.dataset.type || 'all';
            document.querySelectorAll('.archive-tag').forEach((tag) => {
                tag.classList.toggle('active', tag === button);
            });
            renderArchiveList();
        });
    });

    document.getElementById('archiveFilterButton')?.addEventListener('click', () => {
        showArchiveToast('Use the search box or category tags to filter archived items.');
    });

    document.getElementById('closeArchiveDetailsModal')?.addEventListener('click', closeArchiveDetails);
    document.getElementById('closeArchiveDetailsFooter')?.addEventListener('click', closeArchiveDetails);

    document.getElementById('archiveDetailsModal')?.addEventListener('click', (event) => {
        if (event.target.id === 'archiveDetailsModal') {
            closeArchiveDetails();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeArchiveDetails();
        }
    });

    document.addEventListener('click', (event) => {
        const actionButton = event.target.closest('.archive-action-btn');
        if (!actionButton) {
            return;
        }

        const action = actionButton.dataset.archiveAction || '';
        const id = actionButton.dataset.archiveId || '';
        runArchiveAction(action, id);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.archive-page')) {
        return;
    }

    bindArchiveEvents();
    fetchArchiveItems();
});
