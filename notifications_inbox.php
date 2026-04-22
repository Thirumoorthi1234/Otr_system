<?php
// notifications_inbox.php — Universal Notifications Inbox (all roles)
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/layout.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'];

// Handle mark-all-read POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")->execute([$user_id]);
    header('Location: ' . BASE_URL . 'notifications_inbox.php?success=1');
    exit();
}

// Handle single delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([(int)$_POST['delete_id'], $user_id]);
    header('Location: ' . BASE_URL . 'notifications_inbox.php');
    exit();
}

// Handle clear-all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all'])) {
    $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$user_id]);
    header('Location: ' . BASE_URL . 'notifications_inbox.php');
    exit();
}

// Filters
$filter = $_GET['filter'] ?? 'all';   // all | unread | info | success | warning | danger
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where = "WHERE user_id = ?";
$params = [$user_id];

if ($filter === 'unread') {
    $where .= " AND is_read = FALSE";
} elseif (in_array($filter, ['info','success','warning','danger'])) {
    $where .= " AND type = ?";
    $params[] = $filter;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications $where");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $limit));

$stmt = $pdo->prepare("SELECT * FROM notifications $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Unread total for badge
$unreadCount = (int)$pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE")->execute([$user_id]) ? $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $user_id AND is_read = FALSE")->fetchColumn() : 0;

// Mark visible as read
if (!empty($notifications)) {
    $ids = array_column($notifications, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id IN ($in) AND user_id = ?")->execute(array_merge($ids, [$user_id]));
}

renderHeader('Notifications Inbox');
renderSidebar($role);
?>

<style>
/* ── Inbox Page Styles ── */
.inbox-hero {
    background: linear-gradient(135deg, var(--primary-blue) 0%, #6366f1 100%);
    border-radius: 18px;
    padding: 32px 36px;
    color: #fff;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 8px 32px rgba(14,165,233,.25);
}
.inbox-hero h2 { margin: 0; font-size: 1.7rem; font-weight: 800; display:flex; align-items:center; gap:12px; }
.inbox-hero p  { margin: 4px 0 0; opacity: .85; font-size: .95rem; }
.inbox-hero .hero-badge {
    background: rgba(255,255,255,.18);
    border-radius: 50px;
    padding: 6px 16px;
    font-weight: 700;
    font-size: .9rem;
}

.inbox-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}
.filter-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.filter-tab {
    padding: 7px 18px;
    border-radius: 50px;
    font-size: .83rem;
    font-weight: 600;
    border: 1.5px solid var(--border-color);
    background: var(--card-bg);
    color: var(--text-secondary);
    text-decoration: none;
    transition: all .18s;
}
.filter-tab:hover, .filter-tab.active {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
    color: #fff;
    box-shadow: 0 4px 14px rgba(14,165,233,.3);
}
.filter-tab.danger.active   { background:#ef4444; border-color:#ef4444; }
.filter-tab.warning.active  { background:#f59e0b; border-color:#f59e0b; }
.filter-tab.success.active  { background:#10b981; border-color:#10b981; }
.filter-tab.info.active     { background:#3b82f6; border-color:#3b82f6; }

.toolbar-actions { display:flex; gap:10px; flex-wrap:wrap; }
.btn-sm {
    padding: 7px 16px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    border: 1.5px solid var(--border-color); background: var(--card-bg);
    color: var(--text-secondary); cursor: pointer; transition: all .18s;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-sm:hover { background: var(--hover-bg); }
.btn-sm.danger { border-color:#ef4444; color:#ef4444; }
.btn-sm.danger:hover { background:#ef4444; color:#fff; }

/* Notification Cards */
.notif-inbox-list { display: flex; flex-direction: column; gap: 10px; }
.notif-card {
    background: var(--card-bg);
    border-radius: 14px;
    padding: 18px 22px;
    border: 1.5px solid var(--border-color);
    display: flex;
    align-items: flex-start;
    gap: 16px;
    transition: all .2s;
    position: relative;
}
.notif-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-1px); }
.notif-card.unread { border-left: 4px solid var(--primary-blue); background: rgba(14,165,233,.04); }
.notif-card.unread.warning  { border-left-color: #f59e0b; }
.notif-card.unread.danger   { border-left-color: #ef4444; }
.notif-card.unread.success  { border-left-color: #10b981; }

.notif-icon-wrap {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.notif-icon-wrap.info    { background: rgba(59,130,246,.12); color: #3b82f6; }
.notif-icon-wrap.success { background: rgba(16,185,129,.12); color: #10b981; }
.notif-icon-wrap.warning { background: rgba(245,158,11,.12);  color: #f59e0b; }
.notif-icon-wrap.danger  { background: rgba(239,68,68,.12);   color: #ef4444; }

.notif-card-body { flex: 1; min-width: 0; }
.notif-card-title {
    font-weight: 700; font-size: .97rem;
    color: var(--text-primary); margin-bottom: 4px;
}
.notif-card-msg {
    font-size: .875rem; color: var(--text-secondary);
    margin-bottom: 8px; line-height: 1.5;
}
.notif-card-meta {
    display: flex; gap: 14px; align-items: center; flex-wrap: wrap;
}
.notif-card-time { font-size: .78rem; color: var(--text-muted); }
.notif-type-badge {
    font-size: .72rem; font-weight: 700; padding: 2px 10px;
    border-radius: 50px; text-transform: uppercase; letter-spacing:.5px;
}
.badge-info    { background: rgba(59,130,246,.12); color: #3b82f6; }
.badge-success { background: rgba(16,185,129,.12); color: #10b981; }
.badge-warning { background: rgba(245,158,11,.12);  color: #f59e0b; }
.badge-danger  { background: rgba(239,68,68,.12);   color: #ef4444; }

.notif-card-actions { display:flex; gap:8px; align-items:center; flex-shrink:0; }
.card-action-btn {
    width: 34px; height: 34px; border-radius: 8px; border: none;
    background: transparent; cursor: pointer; color: var(--text-muted);
    display:flex; align-items:center; justify-content:center;
    transition: all .18s; font-size: .9rem;
}
.card-action-btn:hover { background: var(--hover-bg); color: var(--primary-blue); }
.card-action-btn.delete:hover { background: rgba(239,68,68,.1); color: #ef4444; }
.card-action-btn.link:hover { background: rgba(14,165,233,.1); color: var(--primary-blue); }

.empty-inbox {
    text-align: center; padding: 70px 20px;
    background: var(--card-bg); border-radius: 18px;
    border: 2px dashed var(--border-color);
}
.empty-inbox i { font-size: 3.5rem; color: var(--text-muted); margin-bottom: 16px; display:block; }
.empty-inbox h3 { color: var(--text-primary); font-size: 1.2rem; margin-bottom: 8px; }
.empty-inbox p  { color: var(--text-muted); font-size: .9rem; }

/* Pagination */
.pagination { display:flex; gap:6px; justify-content:center; margin-top:24px; flex-wrap:wrap; }
.page-btn {
    padding: 8px 14px; border-radius: 8px; font-size: .85rem; font-weight: 600;
    border: 1.5px solid var(--border-color); background: var(--card-bg);
    color: var(--text-secondary); text-decoration: none; transition: all .18s;
}
.page-btn:hover, .page-btn.active {
    background: var(--primary-blue); border-color: var(--primary-blue); color: #fff;
}
.page-btn.disabled { opacity: .4; pointer-events: none; }

.success-banner {
    background: rgba(16,185,129,.1); border: 1.5px solid rgba(16,185,129,.3);
    color: #059669; border-radius: 10px; padding: 12px 18px;
    margin-bottom: 16px; font-weight: 600; font-size: .9rem;
    display: flex; align-items: center; gap: 10px;
}
</style>

<div class="content-wrapper">

    <!-- Hero -->
    <div class="inbox-hero">
        <div>
            <h2><i class="fas fa-inbox"></i> Notifications Inbox</h2>
            <p>All your system alerts, updates, and messages in one place.</p>
        </div>
        <div class="hero-badge">
            <i class="fas fa-bell" style="margin-right:6px;"></i>
            <?php echo $unreadCount; ?> unread
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="success-banner"><i class="fas fa-check-circle"></i> All notifications marked as read.</div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="inbox-toolbar">
        <div class="filter-tabs">
            <?php
            $tabs = [
                'all'     => ['label' => 'All',     'icon' => 'fa-list',       'cls' => ''],
                'unread'  => ['label' => 'Unread',  'icon' => 'fa-circle',     'cls' => ''],
                'info'    => ['label' => 'Info',    'icon' => 'fa-info-circle','cls' => 'info'],
                'success' => ['label' => 'Success', 'icon' => 'fa-check',      'cls' => 'success'],
                'warning' => ['label' => 'Warning', 'icon' => 'fa-exclamation','cls' => 'warning'],
                'danger'  => ['label' => 'Danger',  'icon' => 'fa-times',      'cls' => 'danger'],
            ];
            foreach ($tabs as $key => $tab):
                $isActive = $filter === $key;
            ?>
            <a href="?filter=<?php echo $key; ?>"
               class="filter-tab <?php echo $tab['cls']; ?> <?php echo $isActive ? 'active' : ''; ?>">
                <i class="fas <?php echo $tab['icon']; ?>" style="margin-right:5px;"></i>
                <?php echo $tab['label']; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="toolbar-actions">
            <form method="POST" style="display:inline;">
                <button type="submit" name="mark_all" value="1" class="btn-sm">
                    <i class="fas fa-check-double"></i> Mark All Read
                </button>
            </form>
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Clear all notifications? This cannot be undone.');">
                <button type="submit" name="clear_all" value="1" class="btn-sm danger">
                    <i class="fas fa-trash"></i> Clear All
                </button>
            </form>
        </div>
    </div>

    <!-- List -->
    <?php if (empty($notifications)): ?>
    <div class="empty-inbox">
        <i class="fas fa-bell-slash"></i>
        <h3>No notifications here</h3>
        <p>You're all caught up! Check back later for updates.</p>
    </div>
    <?php else: ?>
    <div class="notif-inbox-list">
        <?php foreach ($notifications as $n):
            $iconMap = [
                'info'    => 'fa-info-circle',
                'success' => 'fa-check-circle',
                'warning' => 'fa-exclamation-triangle',
                'danger'  => 'fa-times-circle',
            ];
            $icon = $iconMap[$n['type']] ?? 'fa-bell';
            $isUnread = !$n['is_read'];   // already marked read above, but style based on original
        ?>
        <div class="notif-card <?php echo $n['type']; ?>">
            <div class="notif-icon-wrap <?php echo $n['type']; ?>">
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
            <div class="notif-card-body">
                <div class="notif-card-title"><?php echo e($n['title']); ?></div>
                <div class="notif-card-msg"><?php echo e($n['message']); ?></div>
                <div class="notif-card-meta">
                    <span class="notif-card-time">
                        <i class="fas fa-clock" style="margin-right:4px;"></i>
                        <?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?>
                    </span>
                    <span class="notif-type-badge badge-<?php echo $n['type']; ?>">
                        <?php echo ucfirst($n['type']); ?>
                    </span>
                </div>
            </div>
            <div class="notif-card-actions">
                <?php if (!empty($n['link'])): ?>
                <a href="<?php echo BASE_URL . ltrim($n['link'], '/'); ?>"
                   class="card-action-btn link" title="Go to link">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <?php endif; ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $n['id']; ?>">
                    <button type="submit" class="card-action-btn delete" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>"
           class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <i class="fas fa-chevron-left"></i>
        </a>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"
           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>"
           class="page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>

    <p style="text-align:center; color:var(--text-muted); font-size:.82rem; margin-top:16px;">
        Showing <?php echo count($notifications); ?> of <?php echo $totalCount; ?> notifications
    </p>
    <?php endif; ?>

</div>

<?php renderFooter(); ?>
