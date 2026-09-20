const payrollApprovalState = {
    // Prefer the role declared by the approval page itself. This keeps the
    // approval controls correct when the shared Payroll/HR dashboard shell is
    // reused for different roles.
    role: document.querySelector('.payroll-approval-page')?.dataset?.payrollApprovalRole
        || document.body?.dataset?.dashboardRole
        || 'admin',
    items: [],
    summary: { pending: 0, approved: 0, rejected: 0, total: 0 },
    statusFilter: 'all',
    search: '',
    activeRecordId: null
};

function isPayrollSubmissionMode() {
    return document.querySelector('.payroll-approval-page')?.dataset?.payrollSubmissionMode === 'true';
}

function escapePayrollApprovalHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatPayrollApprovalCurrency(value) {
    return `PHP ${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

function formatPayrollApprovalHours(value) {
    return `${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    })}h`;
}

function isPayrollApprovalAdmin() {
    if (isPayrollSubmissionMode()) return false;
    return payrollApprovalState.role === 'admin'
        || payrollApprovalState.role === 'assistant'
        || payrollApprovalState.role === 'hr';
}

function getFilteredPayrollApprovalItems() {
    const search = payrollApprovalState.search.toLowerCase();

    return payrollApprovalState.items.filter((item) => {
        const matchesStatus = payrollApprovalState.statusFilter === 'all'
            || item.status === payrollApprovalState.statusFilter;

        const haystack = [
            item.site_name,
            item.submitted_by_name,
            item.period_label,
            item.period_start,
            item.period_end,
            item.status
        ].join(' ').toLowerCase();

        const matchesSearch = !search || haystack.includes(search);
        return matchesStatus && matchesSearch;
    });
}

function updatePayrollApprovalSummaryCards() {
    const summary = payrollApprovalState.summary || {};
    const pendingEl = document.getElementById('payrollApprovalPendingCount');
    const approvedEl = document.getElementById('payrollApprovalApprovedCount');
    const rejectedEl = document.getElementById('payrollApprovalRejectedCount');

    if (pendingEl) pendingEl.textContent = summary.pending ?? 0;
    if (approvedEl) approvedEl.textContent = summary.approved ?? 0;
    if (rejectedEl) rejectedEl.textContent = summary.rejected ?? 0;
}

function renderPayrollApprovalStatusBadge(status) {
    const normalized = String(status || 'Pending').toLowerCase();
    return `<span class="payroll-approval-status-badge ${normalized}">${escapePayrollApprovalHtml(status || 'Pending')}</span>`;
}

function renderPayrollApprovalCard(item) {
    const canReview = isPayrollApprovalAdmin();
    const isPayrollStaff = payrollApprovalState.role === 'payroll' || isPayrollSubmissionMode();
    const isPending = String(item.status || '') === 'Pending';
    const canAct = canReview && item.can_approve;
    const actionButtons = [];

    if (canReview || !isPayrollStaff || !isPending) {
        actionButtons.push(`
            <button type="button" class="payroll-approval-btn view" data-payroll-view="${item.id}">
                <i class="fa-regular fa-eye"></i> View Details
            </button>
        `);
    } else {
        actionButtons.push(`
            <span class="payroll-approval-meta">Awaiting approval</span>
        `);
    }

    if (canAct) {
        actionButtons.push(`
            <button type="button" class="payroll-approval-btn reject" data-payroll-reject="${item.id}">
                Reject
            </button>
            <button type="button" class="payroll-approval-btn approve" data-payroll-approve="${item.id}">
                Approve for Release
            </button>
        `);
    }

    return `
        <article class="payroll-approval-card" data-record-id="${item.id}">
            <div class="payroll-approval-card-header">
                <div>
                    <div class="payroll-approval-card-title-wrap">
                        <h3 class="payroll-approval-card-title">${escapePayrollApprovalHtml(item.site_name)}</h3>
                        ${renderPayrollApprovalStatusBadge(item.status)}
                    </div>
                    <div class="payroll-approval-card-submitted">
                        Submitted by ${escapePayrollApprovalHtml(item.submitted_by_name || 'Unknown')}
                    </div>
                </div>
                <div class="payroll-approval-card-amount">
                    <div class="amount">${formatPayrollApprovalCurrency(item.total_net_pay)}</div>
                    <div class="period">${escapePayrollApprovalHtml(item.period_label)}</div>
                </div>
            </div>

            <div class="payroll-approval-metrics">
                <div class="payroll-approval-metric">
                    <div class="payroll-approval-metric-label">Workers</div>
                    <div class="payroll-approval-metric-value">${Number(item.worker_count || 0)}</div>
                </div>
                <div class="payroll-approval-metric">
                    <div class="payroll-approval-metric-label">Regular Hours</div>
                    <div class="payroll-approval-metric-value">${formatPayrollApprovalHours(item.regular_hours)}</div>
                </div>
                <div class="payroll-approval-metric">
                    <div class="payroll-approval-metric-label">Overtime Hours</div>
                    <div class="payroll-approval-metric-value">${formatPayrollApprovalHours(item.overtime_hours)}</div>
                </div>
                <div class="payroll-approval-metric">
                    <div class="payroll-approval-metric-label">Deductions</div>
                    <div class="payroll-approval-metric-value">${formatPayrollApprovalCurrency(item.total_deductions)}</div>
                </div>
            </div>

            <div class="payroll-approval-card-footer">
                <div class="payroll-approval-meta">
                    ${item.submitted_at_label ? `Submitted on ${escapePayrollApprovalHtml(item.submitted_at_label)}` : 'Submission date unavailable'}
                </div>
                <div class="payroll-approval-actions">
                    ${actionButtons.join('')}
                </div>
            </div>
        </article>
    `;
}

function renderPayrollApprovalList() {
    const container = document.getElementById('payrollApprovalList');
    if (!container) {
        return;
    }

    const items = getFilteredPayrollApprovalItems();
    if (!items.length) {
        container.innerHTML = `
            <div class="payroll-approval-empty">
                <p>No payroll submissions match the current filters.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = items.map(renderPayrollApprovalCard).join('');
}

function updatePayrollExportButton(item) {
    const exportBtn = document.getElementById('exportPayrollExcelBtn');
    if (!exportBtn) {
        return;
    }

    const isApproved = String(item?.status || '') === 'Approved';
    exportBtn.hidden = !isApproved;
    exportBtn.dataset.recordId = isApproved ? String(item.id) : '';
}

function updatePayrollCorrectionButton(item) {
    const button = document.getElementById('correctRejectedPayrollBtn');
    if (!button) return;
    const canCorrect = (payrollApprovalState.role === 'payroll' || isPayrollSubmissionMode())
        && String(item?.status || '') === 'Rejected';
    button.hidden = !canCorrect;
    button.dataset.siteId = canCorrect ? String(item.site_id || '') : '';
}

function openPayrollApprovalDetails(item) {
    const modal = document.getElementById('payrollApprovalDetailsModal');
    const body = document.getElementById('payrollApprovalDetailsBody');
    if (!modal || !body || !item) {
        return;
    }

    payrollApprovalState.activeDetailItem = item;
    updatePayrollExportButton(item);
    updatePayrollCorrectionButton(item);

    const reviewInfo = item.status === 'Approved'
        ? `<div class="payroll-approval-detail-row"><strong>Approved By</strong><span>${escapePayrollApprovalHtml(item.approved_by_name || '-')} ${item.approved_at_label ? `(${escapePayrollApprovalHtml(item.approved_at_label)})` : ''}</span></div>`
        : item.status === 'Rejected'
            ? `
                <div class="payroll-approval-detail-row"><strong>Rejected By</strong><span>${escapePayrollApprovalHtml(item.rejected_by_name || '-')} ${item.rejected_at_label ? `(${escapePayrollApprovalHtml(item.rejected_at_label)})` : ''}</span></div>
                <div class="payroll-approval-detail-row"><strong>Rejection Reason</strong><span>${escapePayrollApprovalHtml(item.rejection_reason || '-')}</span></div>
            `
            : '';

    body.innerHTML = `
        <div class="payroll-approval-detail-row"><strong>Site</strong><span>${escapePayrollApprovalHtml(item.site_name)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Payroll Staff</strong><span>${escapePayrollApprovalHtml(item.submitted_by_name || '-')}</span></div>
        <div class="payroll-approval-detail-row"><strong>Payroll Period</strong><span>${escapePayrollApprovalHtml(item.period_label)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Workers</strong><span>${Number(item.worker_count || 0)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Regular Hours</strong><span>${formatPayrollApprovalHours(item.regular_hours)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Overtime Hours</strong><span>${formatPayrollApprovalHours(item.overtime_hours)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Gross Pay</strong><span>${formatPayrollApprovalCurrency(item.total_gross_pay)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Deductions</strong><span>${formatPayrollApprovalCurrency(item.total_deductions)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Net Payroll</strong><span>${formatPayrollApprovalCurrency(item.total_net_pay)}</span></div>
        <div class="payroll-approval-detail-row"><strong>Status</strong><span>${escapePayrollApprovalHtml(item.status)}</span></div>
        ${reviewInfo}
    `;

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

function closePayrollApprovalDetails() {
    const modal = document.getElementById('payrollApprovalDetailsModal');
    if (!modal) {
        return;
    }
    payrollApprovalState.activeDetailItem = null;
    updatePayrollExportButton(null);
    updatePayrollCorrectionButton(null);
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
}

function downloadPayrollExcel(recordId) {
    const id = Number(recordId || 0);
    if (!id) {
        return;
    }

    // Dashboard pages use clean URLs such as /capstone/payroll/payroll_status.
    // A relative ../payroll URL is then resolved as /capstone/payroll/payroll/
    // and never reaches the export controller.  Build the application root from
    // the role route so the download works for every dashboard role.
    const roleRoutes = ['admin', 'assistant', 'hr', 'payroll', 'timekeeper', 'worker'];
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const roleIndex = pathParts.findIndex((part) => roleRoutes.includes(part.toLowerCase()));
    const appRoot = roleIndex > 0 ? `/${pathParts.slice(0, roleIndex).join('/')}` : '';
    const exportUrl = `${appRoot}/payroll/export_payroll_excel.php?id=${encodeURIComponent(String(id))}`;

    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = '';
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function openPayrollRejectModal(recordId) {
    payrollApprovalState.activeRecordId = Number(recordId);
    const modal = document.getElementById('payrollRejectModal');
    const textarea = document.getElementById('payrollRejectReason');
    if (!modal) {
        return;
    }
    if (textarea) {
        textarea.value = '';
    }
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

function closePayrollRejectModal() {
    payrollApprovalState.activeRecordId = null;
    const modal = document.getElementById('payrollRejectModal');
    if (!modal) {
        return;
    }
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
}

async function fetchPayrollApprovals() {
    const params = new URLSearchParams();
    if (payrollApprovalState.statusFilter !== 'all') {
        params.set('status', payrollApprovalState.statusFilter);
    }
    if (payrollApprovalState.search) {
        params.set('search', payrollApprovalState.search);
    }

    const response = await fetch(`../api/get_payroll_approvals.php?${params.toString()}`);
    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Failed to load payroll approvals');
    }

    payrollApprovalState.items = Array.isArray(result.items) ? result.items : [];
    payrollApprovalState.summary = result.summary || { pending: 0, approved: 0, rejected: 0, total: 0 };
    if (result.role) {
        const normalizedRole = String(result.role).toLowerCase();
        payrollApprovalState.role = normalizedRole === 'admin'
            ? 'admin'
            : normalizedRole.includes('assistant')
                ? 'assistant'
            : normalizedRole === 'hr'
                ? 'hr'
            : normalizedRole.includes('payroll')
                ? 'payroll'
                : payrollApprovalState.role;
    }

    updatePayrollApprovalSummaryCards();
    renderPayrollApprovalList();
}

async function submitPayrollApprovalAction(recordId, action, rejectionReason = '') {
    const response = await fetch('../api/payroll_approval_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            record_id: recordId,
            action,
            rejection_reason: rejectionReason
        })
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Failed to update payroll status');
    }

    if (result.summary) {
        payrollApprovalState.summary = result.summary;
        updatePayrollApprovalSummaryCards();
    }

    if (result.record) {
        const index = payrollApprovalState.items.findIndex((item) => Number(item.id) === Number(result.record.id));
        if (index >= 0) {
            payrollApprovalState.items[index] = result.record;
        }
        if (payrollApprovalState.activeDetailItem
            && Number(payrollApprovalState.activeDetailItem.id) === Number(result.record.id)) {
            payrollApprovalState.activeDetailItem = result.record;
            updatePayrollExportButton(result.record);
        }
    } else {
        await fetchPayrollApprovals();
        return result;
    }

    renderPayrollApprovalList();
    return result;
}

function bindPayrollApprovalEvents() {
    document.querySelectorAll('.payroll-approval-filter-btn').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.payroll-approval-filter-btn').forEach((entry) => entry.classList.remove('active'));
            button.classList.add('active');
            payrollApprovalState.statusFilter = button.dataset.status || 'all';
            renderPayrollApprovalList();
        });
    });

    const searchInput = document.getElementById('payrollApprovalSearch');
    let searchTimer = null;
    searchInput?.addEventListener('input', (event) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(async () => {
            payrollApprovalState.search = event.target.value.trim();
            try {
                await fetchPayrollApprovals();
            } catch (error) {
                console.error(error);
            }
        }, 250);
    });

    document.addEventListener('click', async (event) => {
        const viewButton = event.target.closest('[data-payroll-view]');
        if (viewButton) {
            const recordId = Number(viewButton.dataset.payrollView || 0);
            const item = payrollApprovalState.items.find((entry) => Number(entry.id) === recordId);
            if (item) {
                openPayrollApprovalDetails(item);
            }
            return;
        }

        const approveButton = event.target.closest('[data-payroll-approve]');
        if (approveButton) {
            const recordId = Number(approveButton.dataset.payrollApprove || 0);
            if (!recordId) {
                return;
            }

            const confirmed = await window.showConfirmModal('Approve this payroll for release?', {
                title: 'Confirm Payroll Approval',
                confirmText: 'Approve',
                type: 'success'
            });
            if (!confirmed) {
                return;
            }

            approveButton.disabled = true;
            try {
                const result = await submitPayrollApprovalAction(recordId, 'approve');
                window.showCrudResultModal?.(
                    true,
                    result?.message || 'Payroll approved successfully.',
                    'Payroll Approval'
                );
            } catch (error) {
                window.showCrudResultModal?.(
                    false,
                    error.message || 'Failed to approve payroll',
                    'Payroll Approval'
                );
            } finally {
                approveButton.disabled = false;
            }
            return;
        }

        const rejectButton = event.target.closest('[data-payroll-reject]');
        if (rejectButton) {
            openPayrollRejectModal(rejectButton.dataset.payrollReject);
        }
    });

    document.getElementById('closePayrollApprovalDetailsBtn')?.addEventListener('click', closePayrollApprovalDetails);
    document.getElementById('exportPayrollExcelBtn')?.addEventListener('click', () => {
        const recordId = document.getElementById('exportPayrollExcelBtn')?.dataset?.recordId || '';
        downloadPayrollExcel(recordId);
    });
    document.getElementById('correctRejectedPayrollBtn')?.addEventListener('click', (event) => {
        const siteId = Number(event.currentTarget.dataset.siteId || 0);
        if (!siteId) return;
        const payrollPath = payrollApprovalState.role === 'hr' || window.location.pathname.includes('/hr/')
            ? '/capstone/hr/payroll'
            : '/capstone/payroll/payroll';
        window.location.href = `${payrollPath}?site_id=${siteId}&correction=1`;
    });
    document.getElementById('cancelPayrollRejectBtn')?.addEventListener('click', closePayrollRejectModal);

    document.getElementById('confirmPayrollRejectBtn')?.addEventListener('click', async () => {
        const recordId = Number(payrollApprovalState.activeRecordId || 0);
        const reason = document.getElementById('payrollRejectReason')?.value.trim() || '';
        if (!recordId) {
            return;
        }
        if (!reason) {
            window.alert('Please provide a rejection reason.');
            return;
        }

        const button = document.getElementById('confirmPayrollRejectBtn');
        if (button) {
            button.disabled = true;
        }

        try {
            const result = await submitPayrollApprovalAction(recordId, 'reject', reason);
            closePayrollRejectModal();
            window.showCrudResultModal?.(
                true,
                result?.message || 'Payroll rejected successfully.',
                'Payroll Rejection'
            );
        } catch (error) {
            window.showCrudResultModal?.(
                false,
                error.message || 'Failed to reject payroll',
                'Payroll Rejection'
            );
        } finally {
            if (button) {
                button.disabled = false;
            }
        }
    });

    document.querySelectorAll('.payroll-approval-modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                overlay.classList.remove('active');
                overlay.setAttribute('aria-hidden', 'true');
                payrollApprovalState.activeRecordId = null;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('payrollApprovalList')) {
        return;
    }

    bindPayrollApprovalEvents();
    fetchPayrollApprovals().catch((error) => {
        console.error('Payroll approval load error:', error);
        const container = document.getElementById('payrollApprovalList');
        if (container) {
            container.innerHTML = `
                <div class="payroll-approval-empty">
                    <p>${escapePayrollApprovalHtml(error.message || 'Unable to load payroll approvals.')}</p>
                </div>
            `;
        }
    });
});
