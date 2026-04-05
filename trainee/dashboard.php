<?php
// trainee/dashboard.php
require_once '../includes/layout.php';
checkRole('trainee');

$trainee_id = $_SESSION['user_id'];

// Get pending modules
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignments WHERE trainee_id = ? AND status != 'completed'");
$stmt->execute([$trainee_id]);
$pendingModules = $stmt->fetch()['count'];

// Get exam results
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM exam_results WHERE trainee_id = ?");
$stmt->execute([$trainee_id]);
$totalExams = $stmt->fetch()['count'];

renderHeader(__('trainee_dashboard'));
renderSidebar('trainee');
?>

    <div class="company-info-section">
        <div class="card about-hero">
            <div class="hero-content">
                <h2><i class="fas fa-building"></i> About Syrma SGS</h2>
                <div class="about-grid">
                    <p class="about-text">
                        <strong>Syrma SGS Technology Limited</strong> is a leading Electronics Manufacturing Services (EMS) provider in India, offering end-to-end solutions for electronic system design and manufacturing.
                    </p>
                    <p class="about-text">
                        Headquartered in Chennai, India, the company operates multiple advanced manufacturing facilities across the country and serves both domestic and global customers. Syrma SGS focuses on delivering high-quality electronics through innovation, advanced engineering capabilities, and efficient supply chain management.
                    </p>
                </div>
            </div>
        </div>

        <div class="vision-mission-grid">
            <div class="vision-box">
                <h4><i class="fas fa-eye"></i> Our Vision</h4>
                <p>To become a globally recognized electronics manufacturing partner delivering innovative, reliable, and high-quality electronic solutions.</p>
            </div>
            <div class="mission-box">
                <h4><i class="fas fa-bullseye"></i> Our Mission</h4>
                <p>To empower customers with world-class electronics manufacturing services by combining cutting-edge technology, engineering expertise, and operational excellence.</p>
            </div>
        </div>

        <div class="company-info-grid">
            <div class="info-card">
                <h4><i class="fas fa-microchip"></i> Key Services</h4>
                <ul class="info-list">
                    <li><i class="fas fa-check-circle"></i> Electronics System Design & Manufacturing (ESDM)</li>
                    <li><i class="fas fa-check-circle"></i> Printed Circuit Board Assembly (PCBA)</li>
                    <li><i class="fas fa-check-circle"></i> Box Build Assembly</li>
                    <li><i class="fas fa-check-circle"></i> RF & Wireless Solutions</li>
                    <li><i class="fas fa-check-circle"></i> Magnetics Manufacturing</li>
                    <li><i class="fas fa-check-circle"></i> Memory and IT Hardware Solutions</li>
                    <li><i class="fas fa-check-circle"></i> Precision Manufacturing</li>
                </ul>
            </div>

            <div class="info-card">
                <h4><i class="fas fa-industry"></i> Industries Served</h4>
                <ul class="info-list">
                    <li><i class="fas fa-car-side"></i> Automotive Electronics</li>
                    <li><i class="fas fa-heartbeat"></i> Healthcare & Medical Devices</li>
                    <li><i class="fas fa-tools"></i> Industrial Electronics</li>
                    <li><i class="fas fa-tv"></i> Consumer Electronics</li>
                    <li><i class="fas fa-laptop-code"></i> IT Hardware & Data Storage</li>
                    <li><i class="fas fa-bolt"></i> Smart Metering & Power Management</li>
                </ul>
            </div>

            <div class="info-card">
                <h4><i class="fas fa-star"></i> Strengths</h4>
                <ul class="info-list">
                    <li><i class="fas fa-lightbulb"></i> Advanced R&D and product engineering capabilities</li>
                    <li><i class="fas fa-city"></i> Multiple state-of-the-art manufacturing facilities</li>
                    <li><i class="fas fa-globe"></i> Strong global customer base</li>
                    <li><i class="fas fa-award"></i> Focus on quality, reliability, and innovation</li>
                    <li><i class="fas fa-shield-alt"></i> Compliance with international quality and manufacturing standards</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label"><?php echo __('pending_modules'); ?></div>
        <div class="stat-value"><?php echo $pendingModules; ?></div>
        <div class="stat-trend" style="color: var(--warning); font-size: 0.8rem;"><?php echo __('to_be_completed'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?php echo __('tests_taken'); ?></div>
        <div class="stat-value"><?php echo $totalExams; ?></div>
        <div class="stat-trend" style="color: var(--primary-blue); font-size: 0.8rem;"><?php echo __('knowledge_check'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?php echo __('average_score'); ?></div>
        <div class="stat-value">85%</div>
        <div class="stat-trend" style="color: var(--success); font-size: 0.8rem;"><?php echo __('performance'); ?></div>
    </div>
    <?php if (!empty($_SESSION['batch_number'])): ?>
    <div class="stat-card">
        <div class="stat-label"><?php echo __('batch_number'); ?></div>
        <div class="stat-value" style="font-size: 1.6rem;"><?php echo e($_SESSION['batch_number']); ?></div>
        <div class="stat-trend" style="color: var(--primary-blue); font-size: 0.8rem;"><i class="fas fa-layer-group"></i> Assigned batch</div>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom: 20px;"><?php echo __('available_assessments'); ?></h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><?php echo __('exam_name'); ?></th>
                    <th><?php echo __('related_module'); ?></th>
                    <th><?php echo __('duration'); ?></th>
                    <th><?php echo __('action'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get exams for modules assigned to this trainee
                $stmt = $pdo->prepare("
                    SELECT e.*, m.title as module_name 
                    FROM exams e 
                    JOIN assignments a ON e.module_id = a.module_id 
                    JOIN training_modules m ON e.module_id = m.id
                    WHERE a.trainee_id = ? AND a.status = 'in_progress'
                ");
                $stmt->execute([$trainee_id]);
                while ($e = $stmt->fetch()):
                ?>
                <tr>
                    <td><strong><?php echo e($e['title']); ?></strong></td>
                    <td><?php echo e($e['module_name']); ?></td>
                    <td><?php echo e($e['duration_minutes']); ?> min</td>
                    <td>
                        <a href="exam.php?id=<?php echo $e['id']; ?>" class="btn btn-primary" style="padding: 5px 15px; font-size: 0.8rem;"><?php echo __('start_exam'); ?></a>
                    </td>
                </tr>
                <?php endwhile; ?>

            </tbody>
        </table>
    </div>
</div>



<?php renderFooter(); ?>
