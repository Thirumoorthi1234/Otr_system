<?php
// trainee/course-material.php
require_once '../includes/layout.php';
checkRole('trainee');

$module_id = $_GET['id'] ?? null;
if (!$module_id) die(__("module_id_required"));

// Fetch Module Details
$stmt = $pdo->prepare("SELECT * FROM training_modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch();
if (!$module) die(__("module_not_found"));

renderHeader($module['title']);
renderSidebar('trainee');

// Detailed topics for Induction
$induction_topics = [
    [
        'title' => __('induction_topic_1_title'),
        'text' => __('induction_topic_1_text'),
        'points' => [__('induction_topic_1_p1'), __('induction_topic_1_p2'), __('induction_topic_1_p3'), __('induction_topic_1_p4')],
        'exam_id' => null,
        'icon' => 'fa-comments'
    ],
    [
        'title' => __('induction_topic_2_title'),
        'text' => __('induction_topic_2_text'),
        'points' => [__('induction_topic_2_p1'), __('induction_topic_2_p2'), __('induction_topic_2_p3'), __('induction_topic_2_p4')],
        'exam_id' => 2, // Safety Test
        'icon' => 'fa-shield-halved'
    ],
    [
        'title' => __('induction_topic_3_title'),
        'text' => __('induction_topic_3_text'),
        'points' => [__('induction_topic_3_p1'), __('induction_topic_3_p2'), __('induction_topic_3_p3'), __('induction_topic_3_p4')],
        'exam_id' => 3, // PPE Test
        'icon' => 'fa-hard-hat'
    ],
    [
        'title' => __('induction_topic_4_title'),
        'text' => __('induction_topic_4_text'),
        'points' => [__('induction_topic_4_p1'), __('induction_topic_4_p2'), __('induction_topic_4_p3'), __('induction_topic_4_p4'), __('induction_topic_4_p5')],
        'exam_id' => 1, // 5S Test
        'icon' => 'fa-broom'
    ],
    [
        'title' => __('induction_topic_5_title'),
        'text' => __('induction_topic_5_text'),
        'points' => [__('induction_topic_5_p1'), __('induction_topic_5_p2'), __('induction_topic_5_p3'), __('induction_topic_5_p4')],
        'exam_id' => 4, // ESD Test
        'icon' => 'fa-bolt'
    ],
    [
        'title' => __('induction_topic_6_title'),
        'text' => __('induction_topic_6_text'),
        'points' => [__('induction_topic_6_p1'), __('induction_topic_6_p2'), __('induction_topic_6_p3'), __('induction_topic_6_p4')],
        'exam_id' => null, // Placeholder or specific e-module test
        'icon' => 'fa-laptop-code'
    ]
];
?>

<div class="course-container" style="max-width: 1100px; margin: auto; padding: 20px;">
    <div style="margin-bottom: 40px; text-align: center;">
        <h1 style="font-weight: 900; color: var(--brand-navy); font-size: 2.5rem;"><?php echo e($module['title']); ?></h1>
        <?php
        // Fetch current assignment status and ID
        $stmt_status = $pdo->prepare("SELECT id, status FROM assignments WHERE trainee_id = ? AND module_id = ?");
        $stmt_status->execute([$_SESSION['user_id'], $module_id]);
        $assignment = $stmt_status->fetch();
        $curr_status = $assignment['status'] ?? 'not_assigned';
        $asg_id = $assignment['id'] ?? '';
        
        $badge_class = ($curr_status == 'completed') ? 'badge-success' : (($curr_status == 'in_progress') ? 'badge-info' : 'badge-warning');
        $status_text = strtoupper(str_replace('_', ' ', $curr_status));
        ?>
        <div style="margin-top: 15px; margin-bottom: 10px;">
            <span class="badge <?php echo $badge_class; ?>" style="padding: 10px 20px; font-size: 0.9rem; letter-spacing: 1px; border-radius: 50px;">
                <?php echo __('status'); ?>: <?php echo $status_text; ?>
            </span>
        </div>
        <p style="color: var(--text-muted); font-size: 1.1rem;"><?php echo __('complete_all_topics_desc_start'); ?> <a href="feedback.php?assignment_id=<?php echo $asg_id; ?>" style="color: var(--primary-blue); text-decoration: underline; font-weight: 700;"><?php echo __('my_feedback'); ?></a> <?php echo __('complete_all_topics_desc_end'); ?></p>
    </div>



    <?php if ($module_id == 1): ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <?php foreach ($induction_topics as $topic): ?>
                <div class="topic-card" style="background: white; padding: 35px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.03); display: flex; flex-direction: column; transition: all 0.3s ease;">
                    <div style="display: flex; align-items: flex-start; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 60px; height: 60px; background: rgba(11, 112, 183, 0.1); color: var(--primary-blue); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                            <i class="fas <?php echo $topic['icon']; ?>"></i>
                        </div>
                        <div>
                            <h3 style="font-weight: 800; color: #1a365d; margin-bottom: 10px;"><?php echo $topic['title']; ?></h3>
                            <p style="color: #4a5568; line-height: 1.6; font-size: 1rem; font-weight: 500;"><?php echo $topic['text']; ?></p>
                        </div>
                    </div>

                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 30px; flex-grow: 1;">
                        <ul style="list-style: none; padding-left: 0; margin: 0;">
                            <?php foreach ($topic['points'] as $p): ?>
                                <li style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; font-size: 0.9rem;">
                                    <i class="fas fa-check-circle" style="color: #38a169; margin-top: 3px;"></i>
                                    <span><?php echo $p; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div style="padding-top: 20px; border-top: 1px dashed var(--border-color); text-align: right;">
                        <?php if ($topic['exam_id']): ?>
                            <?php
                            // Check if already passed
                            $stmt_res = $pdo->prepare("SELECT status, score FROM exam_results WHERE trainee_id = ? AND exam_id = ? ORDER BY exam_date DESC LIMIT 1");
                            $stmt_res->execute([$_SESSION['user_id'], $topic['exam_id']]);
                            $result = $stmt_res->fetch();
                            ?>
                            
                            <?php if ($result && $result['status'] == 'pass'): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="color: #38a169; font-weight: 700; font-size: 0.9rem;">
                                        <i class="fas fa-check-circle"></i> <?php echo __('Passed'); ?> (<?php echo $result['score']; ?>%)
                                    </span>
                                    <a href="exam.php?id=<?php echo $topic['exam_id']; ?>" class="btn" style="padding: 8px 15px; font-size: 0.85rem; background: #edf2f7; color: var(--text-main); font-weight: 700;"><?php echo __('Retake Test'); ?></a>
                                </div>
                            <?php else: ?>
                                <a href="exam.php?id=<?php echo $topic['exam_id']; ?>" class="btn btn-primary" style="padding: 12px 25px; border-radius: 10px; font-weight: 800; box-shadow: 0 5px 15px rgba(11, 112, 183, 0.2);">
                                    <?php echo __('TAKE TEST'); ?> <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 0.8rem;"></i>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-size: 0.85rem; font-style: italic;"><?php echo __('material_only_sign_off'); ?></span>
                        <?php endif; ?>


                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 40px; border-radius: 24px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.06);">
            <?php if (!empty($module['curriculum_path'])): ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="background: #e0f2fe; width: 100px; height: 100px; border-radius: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; color: #0284c7; font-size: 3rem;">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h2 style="font-weight: 800; color: #0f172a; margin-bottom: 10px;"><?php echo __('curriculum_learning_material'); ?></h2>
                    <p style="color: #64748b; margin-bottom: 30px;"><?php echo __('curriculum_learning_material_desc'); ?></p>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="<?php echo BASE_URL . $module['curriculum_path']; ?>" target="_blank" class="btn btn-primary" style="padding: 15px 35px; border-radius: 12px; font-weight: 800; font-size: 1rem;">
                            <i class="fas fa-eye"></i> <?php echo __('View Online'); ?>
                        </a>
                        <a href="<?php echo BASE_URL . $module['curriculum_path']; ?>" download class="btn" style="padding: 15px 35px; border-radius: 12px; font-weight: 800; background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0;">
                            <i class="fas fa-download"></i> <?php echo __('Download PDF'); ?>
                        </a>
                    </div>

                    <!-- Embed Preview -->
                    <div style="margin-top: 50px; border-radius: 15px; overflow: hidden; border: 1.5px solid #e2e8f0; background: #fff;">
                        <div style="padding: 12px; background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; font-weight: 700; color: #64748b; font-size: 0.85rem; text-align: left; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-desktop"></i> <?php echo __('Document Preview'); ?>
                        </div>
                        <iframe src="<?php echo BASE_URL . $module['curriculum_path']; ?>" width="100%" height="800px" style="border: none;"></iframe>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 0; background: #f8fafc; border-radius: 20px; border: 2px dashed #cbd5e1;">
                    <i class="fas fa-file-pdf" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                    <h3 style="color: #64748b;"><?php echo __('curriculum_content_for'); ?> <?php echo e($module['title']); ?></h3>
                    <p style="color: #94a3b8;"><?php echo __('detailed_material_under_preparation'); ?></p>
                </div>
            <?php endif; ?>


        </div>
    <?php endif; ?>
</div>

<style>
    .topic-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.06) !important; border-color: var(--primary-blue); }
</style>

<?php renderFooter(); ?>
