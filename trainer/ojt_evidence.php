<?php
// trainer/ojt_evidence.php — Trainer view of OJT Evidence submitted by trainees
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'pending';
$assignment_filter = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;

// Get all assignments belonging to this trainer (for dropdown)
$stmt = $pdo->prepare("
    SELECT a.id, u.full_name, u.employee_id, m.title as module_name,
           (SELECT COUNT(*) FROM ojt_evidence oe WHERE oe.assignment_id = a.id AND oe.approved = 'pending') as pending_count
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    JOIN training_modules m ON a.module_id = m.id
    WHERE a.trainer_id = ? AND u.status = 'active'
    ORDER BY u.full_name ASC
");
$stmt->execute([$trainer_id]);
$trainer_assignments = $stmt->fetchAll();

// Build query for evidence photos
$params = [$trainer_id];
$whereExtra = '';
if ($assignment_filter > 0) {
    $whereExtra = ' AND a.id = ?';
    $params[] = $assignment_filter;
}
if ($filter !== 'all') {
    $whereExtra .= ' AND oe.approved = ?';
    $params[] = $filter;
}

$stmt = $pdo->prepare("
    SELECT oe.*, 
           u.full_name as trainee_name, u.employee_id as trainee_eno, u.photo_path as trainee_photo,
           m.title as module_name,
           a.id as assignment_id,
           approver.full_name as approver_name
    FROM ojt_evidence oe
    JOIN assignments a ON oe.assignment_id = a.id
    JOIN users u ON oe.trainee_id = u.id
    JOIN training_modules m ON a.module_id = m.id
    LEFT JOIN users approver ON oe.approved_by = approver.id
    WHERE a.trainer_id = ?
    $whereExtra
    ORDER BY oe.captured_at DESC
");
$stmt->execute($params);
$evidence_list = $stmt->fetchAll();

// Count per status for badges
$counts_stmt = $pdo->prepare("
    SELECT oe.approved, COUNT(*) as cnt
    FROM ojt_evidence oe
    JOIN assignments a ON oe.assignment_id = a.id
    WHERE a.trainer_id = ?
    GROUP BY oe.approved
");
$counts_stmt->execute([$trainer_id]);
$status_counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];
foreach ($counts_stmt->fetchAll() as $row) {
    $status_counts[$row['approved']] = $row['cnt'];
    $status_counts['all'] += $row['cnt'];
}

renderHeader('OJT Evidence Review');
renderSidebar('trainer');
?>

<style>
    .ev-filter-bar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 22px;
        align-items: center;
    }
    .ev-tab {
        padding: 8px 18px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.85rem;
        text-decoration: none;
        border: 2px solid transparent;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .ev-tab.active { border-color: var(--primary-blue); background: rgba(11,112,183,0.1); color: var(--primary-blue); }
    .ev-tab:not(.active) { background: var(--card-bg); color: var(--text-muted); border-color: var(--border-color); }
    .ev-tab:hover:not(.active) { border-color: var(--primary-blue); }
    .badge-count { background: rgba(0,0,0,0.1); padding: 2px 7px; border-radius: 6px; font-size: 0.75rem; }

    .evidence-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .ev-card {
        background: var(--card-bg);
        border-radius: 18px;
        border: 1.5px solid var(--border-color);
        overflow: hidden;
        transition: box-shadow 0.2s, transform 0.15s;
        position: relative;
    }
    .ev-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.1); transform: translateY(-2px); }

    .ev-img-wrap {
        position: relative;
        aspect-ratio: 4/3;
        overflow: hidden;
        background: #0f172a;
        cursor: zoom-in;
    }
    .ev-img-wrap img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.3s;
    }
    .ev-img-wrap:hover img { transform: scale(1.05); }

    .ev-status-badge {
        position: absolute;
        top: 10px; left: 10px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        backdrop-filter: blur(6px);
    }
    .status-pending  { background: rgba(245,158,11,0.9);  color: #fff; }
    .status-approved { background: rgba(16,185,129,0.9);  color: #fff; }
    .status-rejected { background: rgba(239,68,68,0.9);   color: #fff; }

    .ev-body { padding: 14px 16px; }
    .ev-trainee-row {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 10px;
    }
    .ev-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        overflow: hidden; background: var(--border-color);
        flex-shrink: 0; display: flex; align-items: center;
        justify-content: center; font-size: 1rem; color: var(--text-muted);
    }
    .ev-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .ev-trainee-name { font-weight: 800; font-size: 0.9rem; color: var(--text-main); }
    .ev-trainee-sub  { font-size: 0.75rem; color: var(--text-muted); }

    .ev-caption { font-size: 0.82rem; color: var(--text-main); font-weight: 600; margin-bottom: 4px; }
    .ev-meta    { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 12px; }
    .ev-module  { font-size: 0.72rem; color: #6366f1; font-weight: 700; }

    .ev-actions { display: flex; gap: 8px; }
    .btn-approve { background: linear-gradient(135deg,#10b981,#34d399); color:#fff; border:none; flex:1; padding:8px 0; border-radius:10px; font-weight:700; font-size:0.82rem; cursor:pointer; transition:opacity 0.2s; }
    .btn-reject  { background: linear-gradient(135deg,#ef4444,#f87171); color:#fff; border:none; flex:1; padding:8px 0; border-radius:10px; font-weight:700; font-size:0.82rem; cursor:pointer; transition:opacity 0.2s; }
    .btn-approve:hover, .btn-reject:hover { opacity: 0.88; }
    .btn-approved-done { background: rgba(16,185,129,0.1); color:#10b981; border:2px solid #10b981; flex:1; padding:8px 0; border-radius:10px; font-weight:700; font-size:0.82rem; }
    .btn-rejected-done { background: rgba(239,68,68,0.1); color:#ef4444; border:2px solid #ef4444; flex:1; padding:8px 0; border-radius:10px; font-weight:700; font-size:0.82rem; }

    .ev-note-row { margin-top: 8px; }
    .ev-note-input { width: 100%; border-radius: 8px; border: 1.5px solid var(--border-color); background: var(--bg-color); color: var(--text-main); padding: 6px 10px; font-size: 0.78rem; resize: none; font-family: inherit; }
    .ev-note-input:focus { outline: none; border-color: var(--primary-blue); }

    .approved-note { font-size: 0.75rem; color: #10b981; margin-top: 6px; font-style: italic; }
    .rejected-note { font-size: 0.75rem; color: #ef4444; margin-top: 6px; font-style: italic; }

    /* Lightbox */
    #lightbox-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.92); z-index: 9999;
        align-items: center; justify-content: center;
    }
    #lightbox-overlay.show { display: flex; }
    #lightbox-img { max-width: 90vw; max-height: 90vh; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
    #lightbox-close { position: absolute; top: 20px; right: 24px; background: rgba(255,255,255,0.1); border: none; color: #fff; font-size: 1.4rem; cursor: pointer; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
    #lightbox-close:hover { background: rgba(255,255,255,0.25); }
</style>

<!-- Lightbox -->
<div id="lightbox-overlay" onclick="closeLightbox()">
    <button id="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
    <img id="lightbox-img" src="" alt="OJT Evidence">
</div>

<!-- Header Row -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 style="margin:0; font-size:1.3rem; font-weight:800; color:var(--text-main);">
            <i class="fas fa-images" style="color:#a78bfa; margin-right:8px;"></i> OJT Evidence Review
        </h2>
        <p style="margin:4px 0 0; font-size:0.85rem; color:var(--text-muted);">Review and approve evidence photos submitted by your trainees.</p>
    </div>
    <!-- Trainee filter dropdown -->
    <form method="get" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        <select name="assignment_id" class="form-control" style="padding:8px 12px; font-size:0.85rem; border-radius:10px; min-width:200px;" onchange="this.form.submit()">
            <option value="0">All Trainees</option>
            <?php foreach ($trainer_assignments as $ta): ?>
            <option value="<?php echo $ta['id']; ?>" <?php echo $assignment_filter === $ta['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($ta['full_name']); ?> (<?php echo htmlspecialchars($ta['employee_id']); ?>)
                <?php if ($ta['pending_count'] > 0): ?> — <?php echo $ta['pending_count']; ?> pending<?php endif; ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Status Filter Tabs -->
<div class="ev-filter-bar">
    <a href="?filter=all&assignment_id=<?php echo $assignment_filter; ?>" class="ev-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
        <i class="fas fa-th-large"></i> All
        <span class="badge-count"><?php echo $status_counts['all']; ?></span>
    </a>
    <a href="?filter=pending&assignment_id=<?php echo $assignment_filter; ?>" class="ev-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
        <i class="fas fa-clock" style="color:#f59e0b;"></i> Pending
        <span class="badge-count" style="background:rgba(245,158,11,0.15); color:#f59e0b;"><?php echo $status_counts['pending']; ?></span>
    </a>
    <a href="?filter=approved&assignment_id=<?php echo $assignment_filter; ?>" class="ev-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
        <i class="fas fa-check-circle" style="color:#10b981;"></i> Approved
        <span class="badge-count" style="background:rgba(16,185,129,0.15); color:#10b981;"><?php echo $status_counts['approved']; ?></span>
    </a>
    <a href="?filter=rejected&assignment_id=<?php echo $assignment_filter; ?>" class="ev-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
        <i class="fas fa-times-circle" style="color:#ef4444;"></i> Rejected
        <span class="badge-count" style="background:rgba(239,68,68,0.15); color:#ef4444;"><?php echo $status_counts['rejected']; ?></span>
    </a>
</div>

<?php if (empty($evidence_list)): ?>
<div class="card" style="text-align:center; padding:70px 30px;">
    <i class="fas fa-camera-slash" style="font-size:3.5rem; color:var(--border-color); margin-bottom:20px; display:block;"></i>
    <h3 style="color:var(--text-muted); font-weight:700; margin:0 0 8px;">No evidence found</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
        <?php if ($filter === 'pending'): ?>
        All evidence has been reviewed. Great work!
        <?php else: ?>
        No evidence photos for this filter yet.
        <?php endif; ?>
    </p>
</div>
<?php else: ?>

<div class="evidence-grid">
    <?php foreach ($evidence_list as $ev): ?>
    <div class="ev-card" id="ev-card-<?php echo $ev['id']; ?>">
        <!-- Photo -->
        <div class="ev-img-wrap" onclick="openLightbox('<?php echo BASE_URL . htmlspecialchars($ev['photo_path']); ?>')">
            <img src="<?php echo BASE_URL . htmlspecialchars($ev['photo_path']); ?>" alt="OJT Evidence" loading="lazy">
            <div class="ev-status-badge status-<?php echo $ev['approved']; ?>">
                <?php
                $icons = ['pending' => 'fa-clock', 'approved' => 'fa-check-circle', 'rejected' => 'fa-times-circle'];
                echo '<i class="fas ' . ($icons[$ev['approved']] ?? 'fa-clock') . '"></i> ';
                echo ucfirst($ev['approved']);
                ?>
            </div>
            <div style="position:absolute; bottom:8px; right:8px; background:rgba(0,0,0,0.5); color:#fff; border-radius:6px; padding:3px 8px; font-size:0.7rem;">
                <i class="fas fa-search-plus"></i> View
            </div>
        </div>

        <div class="ev-body">
            <!-- Trainee Info -->
            <div class="ev-trainee-row">
                <div class="ev-avatar">
                    <?php if ($ev['trainee_photo']): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($ev['trainee_photo']); ?>">
                    <?php else: ?><i class="fas fa-user"></i><?php endif; ?>
                </div>
                <div>
                    <div class="ev-trainee-name"><?php echo htmlspecialchars($ev['trainee_name']); ?></div>
                    <div class="ev-trainee-sub"><?php echo htmlspecialchars($ev['trainee_eno']); ?></div>
                </div>
            </div>

            <!-- Caption & Meta -->
            <div class="ev-caption"><i class="fas fa-tag" style="color:#a78bfa; margin-right:4px;"></i><?php echo htmlspecialchars($ev['caption'] ?? 'OJT Evidence'); ?></div>
            <div class="ev-meta">
                <i class="fas fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($ev['captured_at'])); ?>
                &nbsp;·&nbsp;
                <span class="ev-module"><?php echo htmlspecialchars($ev['module_name']); ?></span>
            </div>

            <?php if ($ev['approved'] === 'pending'): ?>
            <!-- Note input -->
            <div class="ev-note-row">
                <textarea class="ev-note-input" id="note-<?php echo $ev['id']; ?>" rows="2" placeholder="Optional: add a note for the trainee…"></textarea>
            </div>
            <!-- Action Buttons -->
            <div class="ev-actions" style="margin-top:10px;">
                <button class="btn-approve" onclick="reviewEvidence(<?php echo $ev['id']; ?>, 'approved')">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn-reject" onclick="reviewEvidence(<?php echo $ev['id']; ?>, 'rejected')">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
            <?php elseif ($ev['approved'] === 'approved'): ?>
            <div class="ev-actions">
                <div class="btn-approved-done" style="text-align:center;">
                    <i class="fas fa-check-circle"></i> Approved
                    <?php if ($ev['approver_name']): ?>
                    by <?php echo htmlspecialchars($ev['approver_name']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($ev['trainer_note']): ?>
            <div class="approved-note"><i class="fas fa-quote-left"></i> <?php echo htmlspecialchars($ev['trainer_note']); ?></div>
            <?php endif; ?>
            <?php if ($ev['approved_at']): ?>
            <div style="font-size:0.7rem; color:var(--text-muted); margin-top:4px;"><i class="fas fa-calendar-check"></i> <?php echo date('d M Y, H:i', strtotime($ev['approved_at'])); ?></div>
            <?php endif; ?>
            <?php elseif ($ev['approved'] === 'rejected'): ?>
            <div class="ev-actions">
                <div class="btn-rejected-done" style="text-align:center;">
                    <i class="fas fa-times-circle"></i> Rejected
                </div>
            </div>
            <?php if ($ev['trainer_note']): ?>
            <div class="rejected-note"><i class="fas fa-quote-left"></i> <?php echo htmlspecialchars($ev['trainer_note']); ?></div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-overlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox-overlay').classList.remove('show');
    document.getElementById('lightbox-img').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

async function reviewEvidence(id, action) {
    const note   = document.getElementById('note-' + id)?.value || '';
    const card   = document.getElementById('ev-card-' + id);
    const label  = action === 'approved' ? 'Approve' : 'Reject';
    const color  = action === 'approved' ? '#10b981' : '#ef4444';

    const result = await Swal.fire({
        title: label + ' this evidence?',
        input: 'textarea',
        inputLabel: 'Trainer Note (optional)',
        inputValue: note,
        inputPlaceholder: 'Add a comment for the trainee…',
        inputAttributes: { rows: 2 },
        icon: action === 'approved' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: color,
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-' + (action === 'approved' ? 'check' : 'times') + '"></i> Yes, ' + label,
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetch('<?php echo BASE_URL; ?>api/approve_ojt_evidence.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, action: action, note: result.value || '' })
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: action === 'approved' ? 'Evidence Approved!' : 'Evidence Rejected',
                text: action === 'approved' ? 'The trainee has been notified.' : 'Trainee will be asked to resubmit.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.error || 'Something went wrong.', 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Network error. Please try again.', 'error');
    }
}
</script>

<?php renderFooter(); ?>
