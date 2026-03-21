// assets/js/main.js — OTR System Core JS

// ── Theme Toggle ──────────────────────────────────────────
const savedTheme = localStorage.getItem('otr-theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('otr-theme', next);
    updateThemeIcon();
}

function updateThemeIcon() {
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    btn.innerHTML = isDark
        ? '<i class="fas fa-sun"></i>'
        : '<i class="fas fa-moon"></i>';
    btn.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
}

// ── Sidebar Collapse ──────────────────────────────────────
const savedCollapsed = localStorage.getItem('otr-sidebar') === 'collapsed';

function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    if (!sidebar || !mainContent) return;
    if (savedCollapsed) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
    }
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;

    if (window.innerWidth > 1024) {
        // Desktop: Toggle collapsed state
        const collapsed = sidebar.classList.toggle('collapsed');
        if (mainContent) mainContent.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem('otr-sidebar', collapsed ? 'collapsed' : 'expanded');
    } else {
        // Mobile: Toggle slide-in state
        const mobileOpen = sidebar.classList.toggle('mobile-open');
        if (overlay) overlay.classList.toggle('active', mobileOpen);
    }
}

// Handle window resize for responsive cleanup
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
    }
});

// ── Profile Dropdown ──────────────────────────────────────
function toggleProfileMenu() {
    const trigger = document.getElementById('profileTrigger');
    const menu = document.getElementById('profileMenu');
    if (!trigger || !menu) return;
    trigger.classList.toggle('open');
    menu.classList.toggle('open');
}

// ── Notification Dropdown ─────────────────────────────────
function toggleNotifMenu() {
    const menu = document.getElementById('notifMenu');
    if (!menu) return;
    menu.classList.toggle('open');
    if (menu.classList.contains('open')) loadNotifications();
}

async function loadNotifications() {
    const list = document.getElementById('notifList');
    if (!list) return;
    list.innerHTML = '<div class="notif-empty"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
        const resp = await fetch(window.BASE_URL + 'api/notifications.php?action=list');
        const data = await resp.json();
        renderNotifications(data.notifications || []);
        updateNotifBadge(data.unread_count || 0);
    } catch(e) {
        list.innerHTML = '<div class="notif-empty">Could not load notifications.</div>';
    }
}

function renderNotifications(notifications) {
    const list = document.getElementById('notifList');
    if (!list) return;
    if (!notifications.length) {
        list.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash" style="font-size:1.5rem;margin-bottom:8px;display:block;"></i> No notifications</div>';
        return;
    }
    list.innerHTML = notifications.map(n => `
        <div class="notif-item ${n.is_read == '0' ? 'unread' : ''}" onclick="markRead(${n.id}, this)" data-link="${n.link || ''}">
            <div class="notif-dot"></div>
            <div class="notif-body">
                <div class="notif-title">${escapeHtml(n.title)}</div>
                <div class="notif-msg">${escapeHtml(n.message || '')}</div>
                <div class="notif-time"><i class="fas fa-clock" style="margin-right:3px;"></i>${timeAgo(n.created_at)}</div>
            </div>
        </div>
    `).join('');
}

async function markRead(id, el) {
    el.classList.remove('unread');
    el.querySelector('.notif-dot').style.background = 'transparent';
    await fetch(window.BASE_URL + 'api/notifications.php?action=mark_read&id=' + id);
    const badge = document.getElementById('notifBadge');
    if (badge) {
        let count = parseInt(badge.dataset.count || 0);
        count = Math.max(0, count - 1);
        badge.dataset.count = count;
        badge.textContent = count > 0 ? count : '';
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
    const link = el.dataset.link;
    if (link) { setTimeout(() => { window.location.href = link; }, 200); }
}

async function markAllRead() {
    await fetch(window.BASE_URL + 'api/notifications.php?action=mark_all_read');
    document.querySelectorAll('.notif-item').forEach(el => {
        el.classList.remove('unread');
        el.querySelector('.notif-dot').style.background = 'transparent';
    });
    updateNotifBadge(0);
}

function updateNotifBadge(count) {
    const badge = document.getElementById('notifBadge');
    if (!badge) return;
    badge.dataset.count = count;
    badge.textContent = count > 0 ? (count > 9 ? '9+' : count) : '';
    badge.style.display = count > 0 ? 'flex' : 'none';
}

async function fetchUnreadCount() {
    try {
        const resp = await fetch(window.BASE_URL + 'api/notifications.php?action=unread_count');
        const data = await resp.json();
        updateNotifBadge(data.count || 0);
    } catch(e) {}
}

// ── Helpers ───────────────────────────────────────────────
function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function timeAgo(dateStr) {
    const now = new Date();
    const then = new Date(dateStr);
    const diff = Math.floor((now - then) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}

// ── Close dropdowns on outside click ─────────────────────
document.addEventListener('click', function(e) {
    // Profile dropdown
    const pdWrap = document.querySelector('.profile-dropdown-wrap');
    if (pdWrap && !pdWrap.contains(e.target)) {
        document.getElementById('profileTrigger')?.classList.remove('open');
        document.getElementById('profileMenu')?.classList.remove('open');
    }
    // Notification dropdown
    const ndWrap = document.querySelector('.notif-dropdown-wrap');
    if (ndWrap && !ndWrap.contains(e.target)) {
        document.getElementById('notifMenu')?.classList.remove('open');
    }
});

// ── Auto-dismiss alerts ───────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    updateThemeIcon();
    fetchUnreadCount();

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => { el.style.transition='opacity 0.5s'; el.style.opacity='0'; setTimeout(() => el.remove(), 500); }, 5000);
    });

    // Stagger card animations
    document.querySelectorAll('.stat-card').forEach((el, i) => {
        el.style.animationDelay = (i * 0.08) + 's';
    });
});

// Refresh unread count every 60s
setInterval(fetchUnreadCount, 60000);
