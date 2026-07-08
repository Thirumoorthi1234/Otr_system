<?php
// trainer/skill_matrix.php — Syrma SGS Format Skill Matrix
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];
$trainer_name = $_SESSION['full_name'];

// ── Handle AJAX requests ──────────────────────────────────────
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    // Save/Update a score entry
    if ($_POST['ajax_action'] === 'save_score') {
        $trainee_id = (int)$_POST['trainee_id'];
        $skill_id   = (int)$_POST['skill_id'];
        $report_id  = (int)$_POST['report_id'];
        $score      = $_POST['score'] === '' || $_POST['score'] === 'NA' ? null : (int)$_POST['score'];
        
        if ($score !== null && ($score < 1 || $score > 4)) {
            echo json_encode(['success' => false, 'error' => 'Score must be 1-4 or NA']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO skill_matrix_entries (trainee_id, skill_id, report_id, score, scored_by) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score), scored_by = VALUES(scored_by), scored_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$trainee_id, $skill_id, $report_id, $score, $trainer_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Create a new report
    if ($_POST['ajax_action'] === 'create_report') {
        $title      = trim($_POST['report_title'] ?? 'Skill Matrix');
        $date       = $_POST['report_date'] ?? date('Y-m-d');
        $site       = trim($_POST['site_name'] ?? '');
        $dept       = trim($_POST['department'] ?? '');
        $supervisor = trim($_POST['supervisor_name'] ?? '');
        
        $stmt = $pdo->prepare("
            INSERT INTO skill_matrix_reports (report_title, report_date, site_name, department, supervisor_name, trainer_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $date, $site, $dept, $supervisor, $trainer_id]);
        $newId = $pdo->lastInsertId();
        
        // Copy global template skills into this report
        $templateSkills = $pdo->query("SELECT * FROM skill_matrix_skills WHERE report_id IS NULL ORDER BY sort_order")->fetchAll();
        $insertSkill = $pdo->prepare("
            INSERT INTO skill_matrix_skills (report_id, skill_name, category_group, sub_category, required_level, sort_order, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($templateSkills as $ts) {
            $insertSkill->execute([$newId, $ts['skill_name'], $ts['category_group'], $ts['sub_category'], $ts['required_level'], $ts['sort_order'], $trainer_id]);
        }
        
        echo json_encode(['success' => true, 'report_id' => $newId]);
        exit;
    }
    
    // Update report header
    if ($_POST['ajax_action'] === 'update_report') {
        $report_id  = (int)$_POST['report_id'];
        $title      = trim($_POST['report_title'] ?? '');
        $date       = $_POST['report_date'] ?? '';
        $site       = trim($_POST['site_name'] ?? '');
        $dept       = trim($_POST['department'] ?? '');
        $supervisor = trim($_POST['supervisor_name'] ?? '');
        
        $stmt = $pdo->prepare("
            UPDATE skill_matrix_reports SET report_title=?, report_date=?, site_name=?, department=?, supervisor_name=?
            WHERE id=? AND trainer_id=?
        ");
        $stmt->execute([$title, $date, $site, $dept, $supervisor, $report_id, $trainer_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Add a new skill to report
    if ($_POST['ajax_action'] === 'add_skill') {
        $report_id   = (int)$_POST['report_id'];
        $skill_name  = trim($_POST['skill_name']);
        $cat_group   = trim($_POST['category_group']);
        $sub_cat     = trim($_POST['sub_category'] ?? '');
        $req_level   = (int)($_POST['required_level'] ?? 4);
        
        // Get max sort_order
        $maxOrder = $pdo->prepare("SELECT MAX(sort_order) FROM skill_matrix_skills WHERE report_id = ?");
        $maxOrder->execute([$report_id]);
        $nextOrder = ($maxOrder->fetchColumn() ?? 0) + 1;
        
        $stmt = $pdo->prepare("
            INSERT INTO skill_matrix_skills (report_id, skill_name, category_group, sub_category, required_level, sort_order, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$report_id, $skill_name, $cat_group, $sub_cat, $req_level, $nextOrder, $trainer_id]);
        echo json_encode(['success' => true, 'skill_id' => $pdo->lastInsertId()]);
        exit;
    }
    
    // Delete a skill from report
    if ($_POST['ajax_action'] === 'delete_skill') {
        $skill_id  = (int)$_POST['skill_id'];
        $report_id = (int)$_POST['report_id'];
        $stmt = $pdo->prepare("DELETE FROM skill_matrix_skills WHERE id = ? AND report_id = ?");
        $stmt->execute([$skill_id, $report_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    exit;
}

// ── Load Report ──────────────────────────────────────────────
$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

// Get all reports for this trainer
$allReports = $pdo->prepare("SELECT * FROM skill_matrix_reports WHERE trainer_id = ? ORDER BY report_date DESC");
$allReports->execute([$trainer_id]);
$allReports = $allReports->fetchAll();

$report = null;
if ($report_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM skill_matrix_reports WHERE id = ? AND trainer_id = ?");
    $stmt->execute([$report_id, $trainer_id]);
    $report = $stmt->fetch();
}

// If no report selected but reports exist, pick the latest
if (!$report && count($allReports) > 0) {
    $report = $allReports[0];
    $report_id = $report['id'];
}

// ── Load Skills for this report ───────────────────────────────
$skills = [];
if ($report_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM skill_matrix_skills WHERE report_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$report_id]);
    $skills = $stmt->fetchAll();
}

// ── Organize skills by category ─────────────────────────────
$processSkills = [];
$operatingSkills = [];
$processSubCats = [];

foreach ($skills as $sk) {
    if ($sk['category_group'] === 'Process Knowledge') {
        $processSkills[] = $sk;
        $sub = $sk['sub_category'] ?: 'Other';
        if (!isset($processSubCats[$sub])) $processSubCats[$sub] = [];
        $processSubCats[$sub][] = $sk;
    } else {
        $operatingSkills[] = $sk;
    }
}

// ── Load Trainees ──────────────────────────────────────────
$trainees = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.employee_id, u.department
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    WHERE a.trainer_id = ? AND u.status = 'active'
    ORDER BY u.full_name ASC
");
$trainees->execute([$trainer_id]);
$trainees = $trainees->fetchAll();

// ── Load Scores ──────────────────────────────────────────
$scoreMap = []; // [trainee_id][skill_id] => score
if ($report_id > 0) {
    $stmt = $pdo->prepare("SELECT trainee_id, skill_id, score FROM skill_matrix_entries WHERE report_id = ?");
    $stmt->execute([$report_id]);
    foreach ($stmt->fetchAll() as $row) {
        $scoreMap[$row['trainee_id']][$row['skill_id']] = $row['score'];
    }
}

renderHeader('Skill Matrix');
renderSidebar('trainer');
?>

<style>
    /* ═══════════════════════════════════════════
       SYRMA SGS SKILL MATRIX — Corporate Design
       ═══════════════════════════════════════════ */
    
    /* Override main-content to prevent it from being stretched by the wide table */
    .main-content { min-width: 0; }
    
    .sm-page { 
        padding: 20px; 
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; 
        width: 100%; 
        box-sizing: border-box; 
    }
    
    /* ── Report Selector ── */
    .sm-report-bar {
        display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
        margin-bottom: 20px; padding: 16px 20px;
        background: #fff; border-radius: 14px; border: 1px solid #E2E8F0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .sm-report-bar select { padding: 9px 14px; border-radius: 10px; border: 1px solid #E2E8F0; font-size: 0.88rem; font-weight: 600; min-width: 200px; }
    .sm-btn {
        padding: 9px 18px; border-radius: 10px; font-size: 0.82rem; font-weight: 700;
        border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        transition: all 0.2s ease;
    }
    .sm-btn-primary { background: #1E40AF; color: #fff; }
    .sm-btn-primary:hover { background: #1E3A8A; transform: translateY(-1px); }
    .sm-btn-success { background: #059669; color: #fff; }
    .sm-btn-success:hover { background: #047857; transform: translateY(-1px); }
    .sm-btn-outline { background: #fff; color: #475569; border: 1px solid #CBD5E1; }
    .sm-btn-outline:hover { background: #F8FAFC; border-color: #94A3B8; }
    .sm-btn-danger { background: #DC2626; color: #fff; }
    .sm-btn-danger:hover { background: #B91C1C; }
    
    /* ── Matrix Container ── */
    .sm-matrix-wrap {
        background: #fff; border-radius: 16px; border: 1px solid #E2E8F0;
        overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.05);
    }
    
    /* ── Report Header (Syrma SGS style) ── */
    .sm-report-header {
        padding: 20px 28px;
        border-bottom: 2px solid #1E40AF;
        background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%);
    }
    .sm-rh-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .sm-rh-logo { display: flex; align-items: center; gap: 12px; }
    .sm-rh-logo img { height: 40px; }
    .sm-rh-logo .company-name { font-size: 1.1rem; font-weight: 900; color: #1E40AF; letter-spacing: 0.5px; }
    .sm-rh-title { font-size: 1.4rem; font-weight: 900; color: #0F172A; text-align: center; }
    .sm-rh-fields {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px 24px; margin-top: 12px;
    }
    .sm-rh-field { display: flex; align-items: center; gap: 8px; }
    .sm-rh-field label { font-size: 0.75rem; font-weight: 800; color: #1E40AF; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    .sm-rh-field input {
        flex: 1; padding: 6px 12px; border: 1px solid #CBD5E1; border-radius: 8px;
        font-size: 0.85rem; font-weight: 600; color: #0F172A;
        background: rgba(255,255,255,0.8);
    }
    .sm-rh-field input:focus { outline: none; border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    
    /* ── Scroll Container ── */
    .sm-scroll { height: calc(100vh - 280px); min-height: 300px; overflow: auto; }
    .sm-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
    .sm-scroll::-webkit-scrollbar-track { background: #F1F5F9; border-radius: 4px; }
    .sm-scroll::-webkit-scrollbar-thumb { background: #94A3B8; border-radius: 4px; }
    .sm-scroll::-webkit-scrollbar-thumb:hover { background: #64748B; }
    
    /* ── Matrix Table ── */
    .sm-table { border-collapse: separate; border-spacing: 0; width: max-content; min-width: 100%; }
    .sm-table thead { position: sticky; top: 0; z-index: 40; }
    .sm-table th, .sm-table td {
        border-right: 1px solid #CBD5E1; border-bottom: 1px solid #CBD5E1; 
        padding: 0; text-align: center; vertical-align: middle;
        font-size: 0.78rem; position: relative;
    }
    .sm-table th { border-top: 1px solid #CBD5E1; }
    .sm-table th:first-child, .sm-table td:first-child { border-left: 1px solid #CBD5E1; }
    
    /* ── Sticky columns (S.No, Emp no, Name) ── */
    .sm-table .col-sno   { position: sticky; left: 0; z-index: 20; width: 40px; min-width: 40px; background: #fff; }
    .sm-table .col-empno  { position: sticky; left: 40px; z-index: 20; width: 70px; min-width: 70px; background: #fff; }
    .sm-table .col-name   { position: sticky; left: 110px; z-index: 20; width: 130px; min-width: 130px; background: #fff; text-align: left; }
    .sm-table thead .col-sno,
    .sm-table thead .col-empno,
    .sm-table thead .col-name { z-index: 50; }
    
    /* ── Category Header Rows ── */
    .sm-cat-header {
        background: #1E40AF !important; color: #fff !important;
        font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.8px;
        padding: 8px 6px !important; white-space: nowrap;
    }
    .sm-subcat-header {
        background: #3B82F6 !important; color: #fff !important;
        font-weight: 700; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;
        padding: 6px 4px !important; white-space: nowrap;
    }
    .sm-skill-header {
        background: #EFF6FF !important; color: #1E3A8A;
        padding: 0 !important;
        height: 80px;
        min-width: 45px;
        vertical-align: bottom;
    }
    .vertical-text {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        height: 80px;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 700;
        font-size: 0.7rem;
        padding: 4px 0;
        margin: 0 auto;
        display: inline-block;
    }
    .sm-required-row td {
        background: #DBEAFE !important; color: #1E40AF;
        font-weight: 800; font-size: 0.82rem; padding: 6px 4px !important;
    }
    
    /* ── Skill Index Headers ── */
    .sm-idx-header {
        background: #F59E0B !important; color: #78350F !important;
        font-weight: 800; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.3px;
        padding: 6px 4px !important;
    }
    .sm-idx-header.process { background: #10B981 !important; color: #064E3B !important; }
    .sm-idx-header.operating { background: #F59E0B !important; color: #78350F !important; }
    .sm-idx-header.individual { background: #8B5CF6 !important; color: #fff !important; }
    
    /* ── Data Cells ── */
    .sm-data-row td { padding: 5px 2px !important; height: 36px; }
    .sm-data-row:nth-child(even) td { background: #F8FAFC; }
    .sm-data-row:nth-child(even) .col-sno,
    .sm-data-row:nth-child(even) .col-empno,
    .sm-data-row:nth-child(even) .col-name { background: #F8FAFC; }
    .sm-data-row:hover td { background: #EFF6FF !important; }
    .sm-data-row:hover .col-sno,
    .sm-data-row:hover .col-empno,
    .sm-data-row:hover .col-name { background: #EFF6FF !important; }
    
    .sm-score-cell {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-weight: 700; font-size: 0.85rem; min-height: 28px;
        border-radius: 4px; transition: all 0.15s;
    }
    .sm-score-cell:hover { background: rgba(59,130,246,0.12); }
    .sm-score-cell.score-4 { color: #059669; }
    .sm-score-cell.score-3 { color: #0284C7; }
    .sm-score-cell.score-2 { color: #D97706; }
    .sm-score-cell.score-1 { color: #DC2626; }
    .sm-score-cell.score-na { color: #94A3B8; font-size: 0.72rem; font-weight: 600; }
    
    .sm-score-input {
        width: 32px; height: 28px; text-align: center; border: 2px solid #3B82F6;
        border-radius: 6px; font-weight: 700; font-size: 0.85rem; outline: none;
        background: #EFF6FF;
    }
    
    /* ── Index cells ── */
    .sm-idx-cell { font-weight: 800; font-size: 0.82rem; }
    .sm-idx-cell.total-process { color: #059669; }
    .sm-idx-cell.total-operating { color: #D97706; }
    .sm-idx-cell.total-individual { color: #7C3AED; font-size: 0.9rem; }
    
    .sm-sno  { font-weight: 700; color: #64748B; font-size: 0.8rem; }
    .sm-empno { font-weight: 700; color: #0F172A; font-size: 0.78rem; }
    .sm-name  { font-weight: 700; color: #0F172A; font-size: 0.82rem; padding-left: 8px !important; }
    
    /* ── Create Report Modal ── */
    .sm-modal-bg {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15,23,42,0.5); backdrop-filter: blur(4px);
        z-index: 9999; align-items: center; justify-content: center;
    }
    .sm-modal-bg.active { display: flex; }
    .sm-modal {
        background: #fff; border-radius: 20px; padding: 32px; width: 520px; max-width: 95vw;
        box-shadow: 0 25px 60px rgba(0,0,0,0.15); animation: smSlideUp 0.3s ease;
    }
    @keyframes smSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .sm-modal h3 { font-size: 1.2rem; font-weight: 800; color: #0F172A; margin-bottom: 20px; }
    .sm-modal label { font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px; margin-top: 14px; }
    .sm-modal input, .sm-modal select {
        width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 10px;
        font-size: 0.88rem; font-weight: 600;
    }
    .sm-modal input:focus, .sm-modal select:focus { outline: none; border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    .sm-modal-actions { display: flex; gap: 10px; margin-top: 24px; justify-content: flex-end; }
    
    /* ── Add Skill Modal ── */
    .sm-skill-modal { width: 480px; }
    
    /* ── Empty State ── */
    .sm-empty {
        text-align: center; padding: 80px 30px;
        background: #fff; border-radius: 16px; border: 1px solid #E2E8F0;
    }
    .sm-empty-icon { width: 80px; height: 80px; border-radius: 50%; background: #EFF6FF; color: #3B82F6; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 20px; }
    .sm-empty h2 { font-size: 1.3rem; font-weight: 800; color: #0F172A; margin-bottom: 8px; }
    .sm-empty p { color: #64748B; font-size: 0.95rem; margin-bottom: 24px; }
    
    /* ── Legend ── */
    .sm-legend {
        display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 20px;
        border-top: 1px solid #E2E8F0; background: #F8FAFC;
        font-size: 0.76rem; font-weight: 700;
    }
    .sm-legend-item { display: flex; align-items: center; gap: 6px; }
    .sm-legend-dot { width: 14px; height: 14px; border-radius: 4px; display: inline-block; }
</style>

<div class="sm-page">
    
    <!-- ═══ Report Selector Bar ═══ -->
    <div class="sm-report-bar">
        <i class="fas fa-table" style="color:#1E40AF; font-size:1.1rem;"></i>
        <select id="reportSelector" onchange="switchReport(this.value)">
            <?php if (empty($allReports)): ?>
                <option value="">No reports yet</option>
            <?php else: ?>
                <?php foreach ($allReports as $r): ?>
                <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == $report_id ? 'selected' : ''; ?>>
                    <?php echo e($r['report_title']); ?> — <?php echo date('d.m.Y', strtotime($r['report_date'])); ?>
                </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        
        <button class="sm-btn sm-btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus"></i> New Report
        </button>
        
        <?php if ($report): ?>
        <button class="sm-btn sm-btn-outline" onclick="showAddSkillModal()">
            <i class="fas fa-columns"></i> Manage Skills
        </button>
        <a href="export_skill_matrix.php?report_id=<?php echo $report_id; ?>" class="sm-btn sm-btn-success" style="margin-left:auto; text-decoration:none;">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (!$report): ?>
    <!-- ═══ Empty State ═══ -->
    <div class="sm-empty">
        <div class="sm-empty-icon"><i class="fas fa-table-cells"></i></div>
        <h2>No Skill Matrix Report</h2>
        <p>Create your first Skill Matrix report to start tracking trainee skills.</p>
        <button class="sm-btn sm-btn-primary" onclick="showCreateModal()" style="font-size:0.95rem; padding:12px 28px;">
            <i class="fas fa-plus"></i> Create Skill Matrix
        </button>
    </div>
    
    <?php else: ?>
    <!-- ═══ Matrix Container ═══ -->
    <div class="sm-matrix-wrap">
        
        <!-- ── Report Header (Responsive) ── -->
        <div class="sm-report-header">
            <div class="sm-rh-top">
                <div class="sm-rh-logo">
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" alt="Syrma SGS" onerror="this.style.display='none'">
                    <span class="company-name">SYRMA SGS</span>
                </div>
                <div class="sm-rh-title" id="reportTitleDisplay"><?php echo e($report['report_title']); ?></div>
                <button class="sm-btn sm-btn-outline" onclick="showEditHeaderModal()" style="font-size:0.75rem; padding:6px 12px;">
                    <i class="fas fa-pen"></i> Edit
                </button>
            </div>
            <div class="sm-rh-fields">
                <div class="sm-rh-field">
                    <label>Date</label>
                    <span style="font-weight:700; color:#0F172A; font-size:0.88rem;"><?php echo date('d.m.Y', strtotime($report['report_date'])); ?></span>
                </div>
                <div class="sm-rh-field">
                    <label>Site Name</label>
                    <span style="font-weight:700; color:#0F172A; font-size:0.88rem;"><?php echo e($report['site_name']); ?></span>
                </div>
                <div class="sm-rh-field">
                    <label>Department</label>
                    <span style="font-weight:700; color:#0F172A; font-size:0.88rem;"><?php echo e($report['department']); ?></span>
                </div>
                <div class="sm-rh-field">
                    <label>Supervisor Name</label>
                    <span style="font-weight:700; color:#0F172A; font-size:0.88rem;"><?php echo e($report['supervisor_name']); ?></span>
                </div>
            </div>
        </div>
        
        <!-- ── Scrollable Matrix ── -->
        <div class="sm-scroll">
            <table class="sm-table" id="skillMatrixTable">
                <thead>
                <!-- ROW 1: Category Groups -->
                    <tr>
                        <th class="col-sno sm-cat-header" rowspan="3" style="background:#0F172A !important;">S.No</th>
                        <th class="col-empno sm-cat-header" rowspan="3" style="background:#0F172A !important;">Emp no.</th>
                        <th class="col-name sm-cat-header" rowspan="3" style="background:#0F172A !important; text-align:left; padding-left:8px !important;">Name</th>
                        
                        <?php if (count($processSkills) > 0): ?>
                        <th colspan="<?php echo count($processSkills); ?>" class="sm-cat-header">Process Knowledge</th>
                        <?php endif; ?>
                        
                        <?php if (count($operatingSkills) > 0): ?>
                        <th colspan="<?php echo count($operatingSkills); ?>" class="sm-cat-header" style="background:#B45309 !important;">Operating Knowledge</th>
                        <?php endif; ?>
                        
                        <!-- Skill Index columns -->
                        <th colspan="3" class="sm-cat-header" style="background:#7C3AED !important;">Skill Index</th>
                    </tr>
                    
                    <!-- ROW 2: Sub-Categories -->
                    <tr>
                        <?php 
                        // Process Knowledge sub-categories
                        foreach ($processSubCats as $subName => $subSkills): 
                        ?>
                        <th colspan="<?php echo count($subSkills); ?>" class="sm-subcat-header"><?php echo e($subName); ?></th>
                        <?php endforeach; ?>
                        
                        <?php if (count($operatingSkills) > 0): ?>
                        <th colspan="<?php echo count($operatingSkills); ?>" class="sm-subcat-header" style="background:#D97706 !important;"></th>
                        <?php endif; ?>
                        
                        <th class="sm-idx-header process" rowspan="2" style="writing-mode:vertical-rl; transform:rotate(180deg);">Total Process skill</th>
                        <th class="sm-idx-header operating" rowspan="2" style="writing-mode:vertical-rl; transform:rotate(180deg);">Total Operating skill</th>
                        <th class="sm-idx-header individual" rowspan="2" style="writing-mode:vertical-rl; transform:rotate(180deg);">Individual Skill Index</th>
                    </tr>
                    
                    <!-- ROW 3: Individual Skill Names (vertical) -->
                    <tr>
                        <?php foreach ($processSkills as $sk): ?>
                        <th class="sm-skill-header" title="<?php echo e($sk['skill_name']); ?>">
                            <div class="vertical-text"><?php echo e($sk['skill_name']); ?></div>
                        </th>
                        <?php endforeach; ?>
                        
                        <?php foreach ($operatingSkills as $sk): ?>
                        <th class="sm-skill-header" title="<?php echo e($sk['skill_name']); ?>">
                            <div class="vertical-text"><?php echo e($sk['skill_name']); ?></div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- ROW 4: Required Skill Levels -->
                    <tr class="sm-required-row">
                        <td class="col-sno" style="background:#DBEAFE !important;"></td>
                        <td class="col-empno" style="background:#DBEAFE !important;"></td>
                        <td class="col-name" style="background:#DBEAFE !important; text-align:left; padding-left:8px !important; font-weight:800; color:#1E40AF; font-size:0.72rem; text-transform:uppercase;">Required Skill Levels</td>
                        <?php foreach ($processSkills as $sk): ?>
                        <td><?php echo $sk['required_level']; ?></td>
                        <?php endforeach; ?>
                        <?php foreach ($operatingSkills as $sk): ?>
                        <td><?php echo $sk['required_level']; ?></td>
                        <?php endforeach; ?>
                        <td style="background:#D1FAE5 !important; font-weight:800; color:#059669;"><?php echo array_sum(array_column($processSkills, 'required_level')); ?></td>
                        <td style="background:#FEF3C7 !important; font-weight:800; color:#D97706;"><?php echo array_sum(array_column($operatingSkills, 'required_level')); ?></td>
                        <td style="background:#EDE9FE !important; font-weight:800; color:#7C3AED;">100</td>
                    </tr>
                </thead>
                <tbody>
                    <?php $sno = 0; foreach ($trainees as $t): $sno++; ?>
                    <tr class="sm-data-row">
                        <td class="col-sno"><span class="sm-sno"><?php echo $sno; ?></span></td>
                        <td class="col-empno"><span class="sm-empno"><?php echo e($t['employee_id']); ?></span></td>
                        <td class="col-name"><span class="sm-name"><?php echo e($t['full_name']); ?></span></td>
                        
                        <?php 
                        $processTotal = 0; $processMax = 0; $processCount = 0;
                        foreach ($processSkills as $sk): 
                            $score = $scoreMap[$t['id']][$sk['id']] ?? null;
                            $processMax += $sk['required_level'];
                            if ($score !== null) { $processTotal += $score; $processCount++; }
                        ?>
                        <td>
                            <div class="sm-score-cell <?php echo $score !== null ? 'score-'.$score : 'score-na'; ?>"
                                 onclick="editScore(this, <?php echo $t['id']; ?>, <?php echo $sk['id']; ?>, <?php echo $report_id; ?>)"
                                 data-trainee="<?php echo $t['id']; ?>"
                                 data-skill="<?php echo $sk['id']; ?>"
                                 data-score="<?php echo $score ?? ''; ?>">
                                <?php echo $score !== null ? $score : 'NA'; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                        
                        <?php 
                        $opTotal = 0; $opMax = 0; $opCount = 0;
                        foreach ($operatingSkills as $sk): 
                            $score = $scoreMap[$t['id']][$sk['id']] ?? null;
                            $opMax += $sk['required_level'];
                            if ($score !== null) { $opTotal += $score; $opCount++; }
                        ?>
                        <td>
                            <div class="sm-score-cell <?php echo $score !== null ? 'score-'.$score : 'score-na'; ?>"
                                 onclick="editScore(this, <?php echo $t['id']; ?>, <?php echo $sk['id']; ?>, <?php echo $report_id; ?>)"
                                 data-trainee="<?php echo $t['id']; ?>"
                                 data-skill="<?php echo $sk['id']; ?>"
                                 data-score="<?php echo $score ?? ''; ?>">
                                <?php echo $score !== null ? $score : 'NA'; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                        
                        <?php 
                        $totalMax = $processMax + $opMax;
                        $totalScore = $processTotal + $opTotal;
                        $skillIndex = $totalMax > 0 ? round(($totalScore / $totalMax) * 100) : 0;
                        ?>
                        <td><span class="sm-idx-cell total-process"><?php echo $processTotal; ?></span></td>
                        <td><span class="sm-idx-cell total-operating"><?php echo $opTotal; ?></span></td>
                        <td><span class="sm-idx-cell total-individual" style="<?php 
                            if ($skillIndex >= 80) echo 'color:#059669;';
                            elseif ($skillIndex >= 60) echo 'color:#D97706;';
                            else echo 'color:#DC2626;';
                        ?>"><?php echo $skillIndex; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($trainees)): ?>
                    <tr>
                        <td colspan="<?php echo 3 + count($skills) + 3; ?>" style="padding: 40px !important; color: #94A3B8; font-weight: 600;">
                            <i class="fas fa-users" style="font-size: 1.5rem; display: block; margin-bottom: 10px;"></i>
                            No trainees assigned. Assign trainees from the Assignments page.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div> <!-- Close sm-scroll -->
        
        <!-- ── Legend ── -->
        <div class="sm-legend">
            <div class="sm-legend-item"><span class="sm-legend-dot" style="background:#059669;"></span> 4 - Expert</div>
            <div class="sm-legend-item"><span class="sm-legend-dot" style="background:#0284C7;"></span> 3 - Proficient</div>
            <div class="sm-legend-item"><span class="sm-legend-dot" style="background:#D97706;"></span> 2 - Basic</div>
            <div class="sm-legend-item"><span class="sm-legend-dot" style="background:#DC2626;"></span> 1 - Beginner</div>
            <div class="sm-legend-item"><span class="sm-legend-dot" style="background:#CBD5E1;"></span> NA - Not Assessed</div>
            <div style="margin-left:auto; font-size:0.72rem; color:#94A3B8; font-weight:600;">
                <i class="fas fa-mouse-pointer"></i> Click any cell to enter/edit score
            </div>
        </div>
    </div> <!-- Close sm-matrix-wrap -->
    <?php endif; ?>
</div>

<!-- ═══ Create Report Modal ═══ -->
<div class="sm-modal-bg" id="createReportModal">
    <div class="sm-modal">
        <h3><i class="fas fa-plus-circle" style="color:#1E40AF;"></i> Create Skill Matrix Report</h3>
        <label>Report Title</label>
        <input type="text" id="newReportTitle" placeholder="e.g. Skill Matrix for RFID" value="Skill Matrix for RFID">
        <label>Date</label>
        <input type="date" id="newReportDate" value="<?php echo date('Y-m-d'); ?>">
        <label>Site Name</label>
        <input type="text" id="newSiteName" placeholder="e.g. Chennai - Unit 2">
        <label>Department</label>
        <input type="text" id="newDepartment" placeholder="e.g. POHC - Quality">
        <label>Supervisor Name</label>
        <input type="text" id="newSupervisor" placeholder="e.g. R.Praveena">
        <div class="sm-modal-actions">
            <button class="sm-btn sm-btn-outline" onclick="closeModal('createReportModal')">Cancel</button>
            <button class="sm-btn sm-btn-primary" onclick="createReport()"><i class="fas fa-check"></i> Create</button>
        </div>
    </div>
</div>

<!-- ═══ Edit Header Modal ═══ -->
<div class="sm-modal-bg" id="editHeaderModal">
    <div class="sm-modal">
        <h3><i class="fas fa-pen" style="color:#1E40AF;"></i> Edit Report Header</h3>
        <label>Report Title</label>
        <input type="text" id="editReportTitle" value="<?php echo e($report['report_title'] ?? ''); ?>">
        <label>Date</label>
        <input type="date" id="editReportDate" value="<?php echo $report['report_date'] ?? date('Y-m-d'); ?>">
        <label>Site Name</label>
        <input type="text" id="editSiteName" value="<?php echo e($report['site_name'] ?? ''); ?>">
        <label>Department</label>
        <input type="text" id="editDepartment" value="<?php echo e($report['department'] ?? ''); ?>">
        <label>Supervisor Name</label>
        <input type="text" id="editSupervisor" value="<?php echo e($report['supervisor_name'] ?? ''); ?>">
        <div class="sm-modal-actions">
            <button class="sm-btn sm-btn-outline" onclick="closeModal('editHeaderModal')">Cancel</button>
            <button class="sm-btn sm-btn-primary" onclick="updateReportHeader()"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>
</div>

<!-- ═══ Add Skill Modal ═══ -->
<div class="sm-modal-bg" id="addSkillModal">
    <div class="sm-modal sm-skill-modal">
        <h3><i class="fas fa-columns" style="color:#1E40AF;"></i> Add Skill Column</h3>
        <label>Skill Name</label>
        <input type="text" id="newSkillName" placeholder="e.g. Soldering">
        <label>Category Group</label>
        <select id="newCatGroup">
            <option value="Process Knowledge">Process Knowledge</option>
            <option value="Operating Knowledge">Operating Knowledge</option>
        </select>
        <label>Sub-Category</label>
        <select id="newSubCat">
            <option value="">None</option>
            <option value="Basic Process Knowledge">Basic Process Knowledge</option>
            <option value="Basic Operating Skill-Theoretical">Basic Operating Skill-Theoretical</option>
            <option value="Knowledge/Awareness">Knowledge/Awareness</option>
        </select>
        <label>Required Level</label>
        <input type="number" id="newReqLevel" value="4" min="1" max="4">
        <div class="sm-modal-actions">
            <button class="sm-btn sm-btn-outline" onclick="closeModal('addSkillModal')">Cancel</button>
            <button class="sm-btn sm-btn-primary" onclick="addSkill()"><i class="fas fa-plus"></i> Add Skill</button>
        </div>
    </div>
</div>

<script>
const REPORT_ID = <?php echo $report_id ?: 0; ?>;

// ── Score Editing ──
function editScore(cell, traineeId, skillId, reportId) {
    // Already editing?
    if (cell.querySelector('input')) return;
    
    const currentScore = cell.dataset.score || '';
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'sm-score-input';
    input.value = currentScore;
    input.maxLength = 2;
    input.placeholder = '1-4';
    
    cell.textContent = '';
    cell.appendChild(input);
    input.focus();
    input.select();
    
    function saveScore() {
        let val = input.value.trim().toUpperCase();
        let numVal = null;
        
        if (val === '' || val === 'NA') {
            numVal = null;
        } else {
            numVal = parseInt(val);
            if (isNaN(numVal) || numVal < 1 || numVal > 4) {
                Swal.fire({ icon: 'error', title: 'Invalid Score', text: 'Enter 1-4 or leave blank for NA', toast: true, position: 'top-end', timer: 2500, showConfirmButton: false });
                input.focus();
                return;
            }
        }
        
        // AJAX save
        const formData = new FormData();
        formData.append('ajax_action', 'save_score');
        formData.append('trainee_id', traineeId);
        formData.append('skill_id', skillId);
        formData.append('report_id', reportId);
        formData.append('score', numVal !== null ? numVal : 'NA');
        
        fetch(window.location.pathname, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    cell.dataset.score = numVal !== null ? numVal : '';
                    cell.className = 'sm-score-cell ' + (numVal !== null ? 'score-' + numVal : 'score-na');
                    cell.textContent = numVal !== null ? numVal : 'NA';
                    recalculateRow(cell.closest('tr'));
                }
            });
    }
    
    input.addEventListener('blur', saveScore);
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); saveScore(); }
        if (e.key === 'Escape') {
            cell.className = 'sm-score-cell ' + (currentScore ? 'score-' + currentScore : 'score-na');
            cell.textContent = currentScore || 'NA';
        }
        // Tab navigation
        if (e.key === 'Tab') {
            e.preventDefault();
            saveScore();
            // Move to next cell
            const td = cell.closest('td');
            const nextTd = e.shiftKey ? td.previousElementSibling : td.nextElementSibling;
            if (nextTd) {
                const nextCell = nextTd.querySelector('.sm-score-cell');
                if (nextCell) nextCell.click();
            }
        }
    });
}

// ── Recalculate Row Totals ──
function recalculateRow(tr) {
    const cells = tr.querySelectorAll('.sm-score-cell');
    let processTotal = 0, opTotal = 0;
    const processCount = <?php echo count($processSkills); ?>;
    const opCount = <?php echo count($operatingSkills); ?>;
    const processMax = <?php echo array_sum(array_column($processSkills, 'required_level')); ?>;
    const opMax = <?php echo array_sum(array_column($operatingSkills, 'required_level')); ?>;
    
    cells.forEach((cell, idx) => {
        const score = parseInt(cell.dataset.score);
        if (!isNaN(score)) {
            if (idx < processCount) processTotal += score;
            else opTotal += score;
        }
    });
    
    const totalMax = processMax + opMax;
    const skillIndex = totalMax > 0 ? Math.round(((processTotal + opTotal) / totalMax) * 100) : 0;
    
    const idxCells = tr.querySelectorAll('.sm-idx-cell');
    if (idxCells[0]) idxCells[0].textContent = processTotal;
    if (idxCells[1]) idxCells[1].textContent = opTotal;
    if (idxCells[2]) {
        idxCells[2].textContent = skillIndex;
        if (skillIndex >= 80) idxCells[2].style.color = '#059669';
        else if (skillIndex >= 60) idxCells[2].style.color = '#D97706';
        else idxCells[2].style.color = '#DC2626';
    }
}

// ── Report Management ──
function switchReport(id) {
    if (id) window.location.href = '?report_id=' + id;
}

function showCreateModal() { document.getElementById('createReportModal').classList.add('active'); }
function showEditHeaderModal() { document.getElementById('editHeaderModal').classList.add('active'); }
function showAddSkillModal() { document.getElementById('addSkillModal').classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// Close modal on backdrop click
document.querySelectorAll('.sm-modal-bg').forEach(bg => {
    bg.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});

function createReport() {
    const formData = new FormData();
    formData.append('ajax_action', 'create_report');
    formData.append('report_title', document.getElementById('newReportTitle').value);
    formData.append('report_date', document.getElementById('newReportDate').value);
    formData.append('site_name', document.getElementById('newSiteName').value);
    formData.append('department', document.getElementById('newDepartment').value);
    formData.append('supervisor_name', document.getElementById('newSupervisor').value);
    
    fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = '?report_id=' + data.report_id;
            }
        });
}

function updateReportHeader() {
    const formData = new FormData();
    formData.append('ajax_action', 'update_report');
    formData.append('report_id', REPORT_ID);
    formData.append('report_title', document.getElementById('editReportTitle').value);
    formData.append('report_date', document.getElementById('editReportDate').value);
    formData.append('site_name', document.getElementById('editSiteName').value);
    formData.append('department', document.getElementById('editDepartment').value);
    formData.append('supervisor_name', document.getElementById('editSupervisor').value);
    
    fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) window.location.reload();
        });
}

function addSkill() {
    const name = document.getElementById('newSkillName').value.trim();
    if (!name) { Swal.fire({ icon: 'error', text: 'Enter skill name', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false }); return; }
    
    const formData = new FormData();
    formData.append('ajax_action', 'add_skill');
    formData.append('report_id', REPORT_ID);
    formData.append('skill_name', name);
    formData.append('category_group', document.getElementById('newCatGroup').value);
    formData.append('sub_category', document.getElementById('newSubCat').value);
    formData.append('required_level', document.getElementById('newReqLevel').value);
    
    fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) window.location.reload();
        });
}
</script>

<?php renderFooter(); ?>
