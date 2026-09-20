document.addEventListener('DOMContentLoaded', () => {
    const center = document.getElementById('adminNotificationCenter');
    const button = document.getElementById('adminNotificationButton');
    const panel = document.getElementById('adminNotificationPanel');
    const badge = document.getElementById('adminNotificationBadge');
    const list = document.getElementById('adminNotificationList');
    if (!center || !button || !panel || !badge || !list) return;
    const role = document.body.dataset.dashboardRole || 'admin';
    const dashboardFile = role === 'assistant'
        ? '/capstone/assistant/dashboard'
        : (role === 'payroll'
            ? '/capstone/payroll/dashboard'
            : (role === 'hr' ? '/capstone/hr/dashboard' : '/capstone/admin/dashboard'));
    const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'})[c]);
    let activeFilter = 'all';
    function setPanelOpen(open) {
        panel.hidden = !open;
        button.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('admin-notifications-open', open);
    }
    async function load() {
        try {
            const data = await (await fetch(`../api/get_admin_notifications.php?limit=20${activeFilter === 'unread' ? '&unread_only=1' : ''}`, {cache:'no-store'})).json();
            const count = Number(data.unread_count || 0); badge.textContent = count > 99 ? '99+' : count; badge.hidden = !count;
            list.innerHTML = data.items?.length ? data.items.map(item => `<button type="button" class="admin-notification-item ${item.is_read ? '' : 'unread'}" data-id="${Number(item.id) || 0}" data-ref="${Number(item.reference_id || 0) || 0}" data-type="${esc(item.type)}"><i class="${item.type === 'User Activity' ? 'fas fa-user-clock' : 'fas fa-clipboard-list'}"></i><span><strong>${esc(item.title)}</strong><span>${esc(item.message)}</span><small>${esc(item.created_at)}</small></span>${item.is_read ? '' : '<b class="admin-notification-dot"></b>'}</button>`).join('') : '<div class="admin-notification-empty">No notifications yet.</div>';
        } catch { list.innerHTML = '<div class="admin-notification-empty">Could not load notifications.</div>'; }
    }
    async function mark(body) { await fetch('../api/mark_admin_notification_read.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}); }
    button.addEventListener('click', e => {
        e.stopPropagation();
        const open = panel.hidden;
        setPanelOpen(open);
        if (open) load();
    });
    list.addEventListener('click', async e => {
        const item = e.target.closest('[data-id]');
        if (!item) return;
        await mark({notification_id: Number(item.dataset.id)});
        if (item.dataset.type.startsWith('Payroll ')) {
            window.location.href = `${dashboardFile}?page=payroll_status`;
            return;
        }
        if (item.dataset.type === 'Site Assignment' || item.dataset.type === 'Site Assignment Removed') {
            window.location.href = `${dashboardFile}?page=active_site`;
            return;
        }
        if (item.dataset.type === 'User Activity') {
            if (role === 'hr') {
                window.location.href = `${dashboardFile}?page=worker`;
            } else if (role === 'payroll') {
                window.location.href = dashboardFile;
            } else {
                window.location.href = `${dashboardFile}?page=audit`;
            }
            return;
        }
        const reportPage = role === 'assistant' ? 'reports' : 'timekeeper_reports';
        window.location.href = `${dashboardFile}?page=${reportPage}${Number(item.dataset.ref) ? `&report_id=${item.dataset.ref}` : ''}`;
    });
    center.querySelectorAll('[data-notification-filter]').forEach(tab => tab.addEventListener('click', () => {
        activeFilter = tab.dataset.notificationFilter;
        center.querySelectorAll('[data-notification-filter]').forEach(button => button.classList.toggle('active', button === tab));
        load();
    }));
    document.getElementById('markAllNotificationsRead')?.addEventListener('click', async()=>{await mark({mark_all:true});load();});
    document.addEventListener('click', e => {
        if (!center.contains(e.target)) setPanelOpen(false);
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            setPanelOpen(false);
            button.focus();
        }
    });
    load(); window.setInterval(load, 30000);
});
