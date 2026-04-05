<?php
// admin/results.php
require_once '../includes/layout.php';
checkRole('admin');

$view_logs = $_GET['view_logs'] ?? null;

renderHeader(__('exam_results_proctoring', 'Exam Results & Proctoring'));
renderSidebar('admin');
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><?php echo __('overall_exam_performance'); ?></h3>
        <p style="font-size: 0.85rem; color: var(--text-muted);"><?php echo __('list_of_completed_exams_desc'); ?></p>
    </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;"><?php echo __('trainee'); ?></th>
                        <th style="text-align: left;"><?php echo __('examination'); ?></th>
                        <th style="text-align: center;"><?php echo __('score'); ?></th>
                        <th style="text-align: center;"><?php echo __('result'); ?></th>
                        <th style="text-align: right;"><?php echo __('date'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT r.*, u.full_name, e.title as exam_name 
                        FROM exam_results r 
                        JOIN users u ON r.trainee_id = u.id 
                        JOIN exams e ON r.exam_id = e.id 
                        ORDER BY r.exam_date DESC
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="text-align: left;"><strong><?php echo e($row['full_name']); ?></strong></td>
                        <td style="text-align: left;"><?php echo e($row['exam_name']); ?></td>
                        <td style="text-align: center;"><strong><?php echo $row['score']; ?>%</strong></td>
                        <td style="text-align: center;"><span class="badge <?php echo $row['status'] == 'pass' ? 'badge-success' : 'badge-danger'; ?>"><?php echo strtoupper(__($row['status'])); ?></span></td>
                        <td style="text-align: right;"><?php echo formatDate($row['exam_date']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
</div>

<?php renderFooter(); ?>    