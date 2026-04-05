<?php
// admin/users.php
require_once '../includes/layout.php';
checkRole(['admin', 'management']);

$action = $_GET['action'] ?? 'list';
$message = '';

if (isset($_POST['save_user'])) {
    $username     = $_POST['username'];
    $full_name    = $_POST['full_name'];
    $role         = $_POST['role'];
    $employee_id  = $_POST['employee_id'];
    $qualification = $_POST['qualification'] ?? null;
    $doj          = $_POST['doj'] ?? null;
    $dol          = $_POST['dol'] ?? null;
    $category     = $_POST['category'] ?? null;
    $batch_number = ($role == 'trainee') ? ($_POST['batch_number'] ?? null) : null;
    $mobile_number = trim($_POST['mobile_number'] ?? '') ?: null;
    $aadhar_number = preg_replace('/\D/', '', $_POST['aadhar_number'] ?? '') ?: null;

    // Handle department
    $department = $_POST['department'] ?? null;
    if (!empty($_POST['new_department'])) {
        $department = trim($_POST['new_department']);
    }
    $photo_path = $_POST['existing_photo'] ?? null;

    // Handle photo capture
    if (!empty($_POST['photo_data'])) {
        $img_data = $_POST['photo_data'];
        $img_data = str_replace('data:image/jpeg;base64,', '', $img_data);
        $img_data = str_replace(' ', '+', $img_data);
        $data = base64_decode($img_data);
        $filename = 'profile_' . time() . '.jpg';
        $path = '../assets/img/profiles/' . $filename;
        if (file_put_contents($path, $data)) {
            $photo_path = 'assets/img/profiles/' . $filename;
        }
    }

    if ($_POST['user_id']) {
        // Update user
        $sql = "UPDATE users SET username=?, full_name=?, role=?, employee_id=?, qualification=?, doj=?, dol=?, category=?, batch_number=?, department=?, photo_path=?, mobile_number=?, aadhar_number=? WHERE id=?";
        $params = [$username, $full_name, $role, $employee_id, $qualification, $doj, $dol, $category, $batch_number, $department, $photo_path, $mobile_number, $aadhar_number, $_POST['user_id']];

        if (!empty($_POST['password'])) {
            $sql = "UPDATE users SET username=?, full_name=?, role=?, employee_id=?, qualification=?, doj=?, dol=?, category=?, batch_number=?, department=?, photo_path=?, mobile_number=?, aadhar_number=?, password=? WHERE id=?";
            $params = [$username, $full_name, $role, $employee_id, $qualification, $doj, $dol, $category, $batch_number, $department, $photo_path, $mobile_number, $aadhar_number, password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['user_id']];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $message = __("user_updated_successfully");
    } else {
        // Create user
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, employee_id, qualification, doj, dol, category, batch_number, department, photo_path, mobile_number, aadhar_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $full_name, $role, $employee_id, $qualification, $doj, $dol, $category, $batch_number, $department, $photo_path, $mobile_number, $aadhar_number]);
            $message = __("user_created_successfully");
        } catch (PDOException $e) {
            $message = __("Error") . ": " . $e->getMessage();
        }
    }
    $action = 'list';
}

if ($action == 'inactive' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = __("user_moved_inactive");
    $action = 'list';
}

renderHeader('User Management');
renderSidebar($_SESSION['role']);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><?php echo $action == 'add' ? __('add_new_user') : ($action == 'edit' ? __('edit_user') : __('system_users')); ?></h3>
        <?php if ($action == 'list'): ?>
            <a href="users.php?action=add" class="btn btn-primary"><?php echo __('add_user'); ?></a>
        <?php else: ?>
            <a href="users.php" class="btn" style="background: #4A5568; color: white;"><?php echo __('back_to_list'); ?></a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div style="background: rgba(56, 161, 105, 0.1); color: #48BB78; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(56, 161, 105, 0.2);">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div class="table-container">
            <table style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 12px 15px;"><?php echo __('full_name'); ?></th>
                        <th style="text-align: left; padding: 12px 15px;"><?php echo __('username'); ?></th>
                        <th style="text-align: center; padding: 12px 15px;"><?php echo __('role'); ?></th>
                        <th style="text-align: center; padding: 12px 15px;"><?php echo __('emp_id'); ?></th>
                        <th style="text-align: left; padding: 12px 15px;"><?php echo __('department'); ?></th>
                        <th style="text-align: center; padding: 12px 15px;"><i class="fas fa-mobile-alt"></i> <?php echo __('Mobile'); ?></th>
                        <th style="text-align: center; padding: 12px 15px;"><i class="fas fa-fingerprint"></i> <?php echo __('Aadhaar'); ?></th>
                        <th style="text-align: center; padding: 12px 15px;"><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM users WHERE status = 'active' ORDER BY created_at DESC");
                    while ($user = $stmt->fetch()):
                    ?>
                    <tr class="main-row" style="cursor: pointer; border-bottom: 1px solid var(--border-color); background: var(--white); transition: background 0.2s;">
                        <td style="text-align: left; padding: 12px 15px;">
                            <i class="fas fa-caret-right expand-icon" style="margin-right:8px; color:#a0aec0; transition: transform 0.2s;"></i>
                            <strong><?php echo e($user['full_name']); ?></strong>
                        </td>
                        <td style="text-align: left; color: var(--text-muted); padding: 12px 15px;"><?php echo e($user['username']); ?></td>
                        <td style="text-align: center; padding: 12px 15px;"><span class="badge" style="background: rgba(14, 165, 233, 0.1); color: var(--primary-blue);"><?php echo __($user['role'] == 'management' ? 'manager' : $user['role']); ?></span></td>
                        <td style="text-align: center; font-family: 'Outfit', sans-serif; font-weight: 600; padding: 12px 15px;"><?php echo e($user['employee_id'] ?? '-'); ?></td>
                        <td style="text-align: left; font-size: 0.8rem; padding: 12px 15px;"><?php echo e($user['department'] ?? '-'); ?></td>
                        <td style="text-align: center; font-size: 0.82rem; padding: 12px 15px;">
                            <?php if (!empty($user['mobile_number'])): ?>
                                <span style="color:#0369a1; font-weight:700;"><i class="fas fa-mobile-alt" style="margin-right:4px;"></i><?php echo e($user['mobile_number']); ?></span>
                            <?php else: ?>
                                <span style="color:#cbd5e1;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; font-size: 0.82rem; padding: 12px 15px;">
                            <?php if (!empty($user['aadhar_number'])): ?>
                                <?php $masked = 'XXXX XXXX ' . substr($user['aadhar_number'], -4); ?>
                                <span style="color:#7c3aed; font-weight:700; font-family:'Courier New',monospace;"><i class="fas fa-fingerprint" style="margin-right:4px;"></i><?php echo $masked; ?></span>
                            <?php else: ?>
                                <span style="color:#cbd5e1;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; padding: 12px 15px;" onclick="event.stopPropagation();">
                            <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" style="color: var(--primary-blue); margin-right: 15px;" title="<?php echo __('edit_user'); ?>"><i class="fas fa-edit"></i></a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?action=inactive&id=<?php echo $user['id']; ?>" style="color: var(--danger);" onclick="return confirm('<?php echo __('inactivate_user_confirm'); ?>')" title="<?php echo __('inactivate_user'); ?>"><i class="fas fa-user-slash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="details-row" style="display: none; background: #fafafa; border-bottom: 2px solid var(--border-color);">
                        <td colspan="8" style="padding: 20px 25px;">
                            <div style="display: flex; gap: 20px;">
                                <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 3px solid #e2e8f0; flex-shrink: 0;">
                                    <?php if (!empty($user['photo_path'])): ?>
                                        <img src="<?php echo BASE_URL . $user['photo_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #a0aec0;"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div style="flex-grow: 1;">
                                    <h4 style="margin: 0 0 10px 0; color: var(--text-main); font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;"><?php echo __('profile_details'); ?></h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9rem; color: #4a5568;">
                                        <div><strong><?php echo __('date_of_joining'); ?>:</strong> <?php echo $user['doj'] ? date('d M Y', strtotime($user['doj'])) : '-'; ?></div>
                                        <div><strong><?php echo __('qualification'); ?>:</strong> <?php echo e($user['qualification'] ? $user['qualification'] : '-'); ?></div>
                                        <div><strong><?php echo __('category'); ?>:</strong> <?php echo e($user['category'] ? $user['category'] : '-'); ?></div>
                                        <div><strong><?php echo __('batch_number'); ?>:</strong> <?php echo e($user['batch_number'] ? $user['batch_number'] : '-'); ?></div>
                                        <div><strong><i class="fas fa-mobile-alt" style="color:#0369a1;"></i> <?php echo __('Mobile'); ?>:</strong>
                                            <?php if (!empty($user['mobile_number'])): ?>
                                                <span style="color:#0369a1; font-weight:700;"><?php echo e($user['mobile_number']); ?></span>
                                                <?php if ($user['role'] === 'trainee'): ?><span style="background:#dbeafe; color:#1d4ed8; font-size:0.7rem; padding:1px 6px; border-radius:5px; margin-left:5px; font-weight:700;">OTP ✓</span><?php endif; ?>
                                            <?php else: ?><span style="color:#94a3b8;"><?php echo __('Not set'); ?></span><?php endif; ?>
                                        </div>
                                        <div><strong><i class="fas fa-fingerprint" style="color:#7c3aed;"></i> <?php echo __('Aadhaar'); ?>:</strong>
                                            <?php if (!empty($user['aadhar_number'])): ?>
                                                <span style="color:#7c3aed; font-weight:700; font-family:'Courier New',monospace;">XXXX XXXX <?php echo substr($user['aadhar_number'], -4); ?></span>
                                                <?php if ($user['role'] === 'trainee'): ?><span style="background:#ede9fe; color:#6d28d9; font-size:0.7rem; padding:1px 6px; border-radius:5px; margin-left:5px; font-weight:700;">OTP ✓</span><?php endif; ?>
                                            <?php else: ?><span style="color:#94a3b8;"><?php echo __('Not set'); ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="margin-top: 15px;">
                                        <?php
                                        $popupData = [
                                            'name'          => $user['full_name'],
                                            'role'          => __($user['role'] == 'management' ? 'manager' : $user['role']),
                                            'emp_id'        => $user['employee_id'] ?? '-',
                                            'dept'          => $user['department'] ?? '-',
                                            'username'      => $user['username'],
                                            'doj'           => $user['doj'] ? date('d M Y', strtotime($user['doj'])) : '-',
                                            'qualification' => $user['qualification'] ? $user['qualification'] : '-',
                                            'category'      => $user['category'] ? $user['category'] : '-',
                                            'batch'         => $user['batch_number'] ? $user['batch_number'] : '-',
                                            'photo'         => !empty($user['photo_path']) ? BASE_URL . $user['photo_path'] : '',
                                            'mobile'        => $user['mobile_number'] ?? '',
                                            'aadhar'        => !empty($user['aadhar_number']) ? 'XXXX XXXX ' . substr($user['aadhar_number'], -4) : '',
                                            'is_trainee'    => $user['role'] === 'trainee',
                                        ];
                                        ?>
                                        <button type="button" class="btn btn-primary" style="padding: 6px 15px; font-size: 0.85rem;" onclick="showProfilePopup(<?php echo htmlspecialchars(json_encode($popupData), ENT_QUOTES, 'UTF-8'); ?>)">
                                            <i class="fas fa-user-circle" style="margin-right: 5px;"></i> <?php echo __('view_profile'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <script>
        function showProfilePopup(data) {
            let imgHtml = data.photo 
                ? `<img src="${data.photo}" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 10px auto; display: block; border: 3px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">`
                : `<div style="width: 100px; height: 100px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #a0aec0; margin: 10px auto; border: 3px solid #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.05);"><i class="fas fa-user"></i></div>`;

            let html = `
                ${imgHtml}
                <h3 style="margin: 10px 0 5px 0; font-family: 'Outfit', sans-serif; color: var(--brand-navy);">${data.name}</h3>
                <p style="color: var(--text-muted); margin-bottom: 25px; font-weight: 500; font-size: 0.95rem;">
                    <span class="badge" style="background: rgba(14, 165, 233, 0.1); color: var(--primary-blue); margin-right: 5px;">${data.role}</span>
                    <i class="far fa-building"></i> ${data.dept}
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: left; padding: 20px; background: #f8fafc; border-radius: 12px; font-size: 0.9rem; border: 1px solid #e2e8f0;">
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-id-badge"></i> <?php echo __('emp_id'); ?></strong><br><span style="color: #1e293b; font-weight: 600; font-size: 1rem;">${data.emp_id}</span></div>
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-user-tag"></i> <?php echo __('username'); ?></strong><br><span style="color: #1e293b; font-weight: 600;">${data.username}</span></div>
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-calendar-alt"></i> <?php echo __('date_of_joining'); ?></strong><br><span style="color: #1e293b; font-weight: 600;">${data.doj}</span></div>
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-graduation-cap"></i> <?php echo __('qualification'); ?></strong><br><span style="color: #1e293b; font-weight: 600;">${data.qualification}</span></div>
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-briefcase"></i> <?php echo __('category'); ?></strong><br><span style="color: #1e293b; font-weight: 600;">${data.category}</span></div>
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-layer-group"></i> <?php echo __('batch_number'); ?></strong><br><span style="color: #1e293b; font-weight: 600;">${data.batch}</span></div>
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-mobile-alt" style="color:#0369a1;"></i> <?php echo __('Mobile'); ?></strong><br>
                        <span style="color:#0369a1; font-weight: 700;">${data.mobile || '—'}</span>
                        ${data.is_trainee && data.mobile ? '<span style="background:#dbeafe;color:#1d4ed8;font-size:0.68rem;padding:1px 7px;border-radius:5px;margin-left:5px;font-weight:700;"><?php echo __('OTP Login ✓'); ?></span>' : ''}
                    </div>
                    <div><strong style="color: #64748b; font-size: 0.8rem; text-transform: uppercase;"><i class="fas fa-fingerprint" style="color:#7c3aed;"></i> <?php echo __('Aadhaar'); ?></strong><br>
                        <span style="color:#7c3aed; font-weight: 700; font-family:\'Courier New\',monospace;">${data.aadhar || '—'}</span>
                        ${data.is_trainee && data.aadhar ? '<span style="background:#ede9fe;color:#6d28d9;font-size:0.68rem;padding:1px 7px;border-radius:5px;margin-left:5px;font-weight:700;"><?php echo __('OTP Login ✓'); ?></span>' : ''}
                    </div>
                </div>
            `;

            Swal.fire({
                html: html,
                showCloseButton: true,
                showConfirmButton: false,
                padding: '2em',
                width: '450px',
                customClass: {
                    popup: 'profile-popup'
                }
            });
        }
        </script>

    <?php else: 
        $user = ['id' => '', 'username' => '', 'full_name' => '', 'role' => 'trainee', 'employee_id' => '', 'photo_path' => '', 'batch_number' => ''];
        if ($action == 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $user = $stmt->fetch();
        }
    ?>
    <form method="POST" action="users.php" id="userForm">
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
        <input type="hidden" name="photo_data" id="photo_data">
        <input type="hidden" name="existing_photo" value="<?php echo $user['photo_path']; ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div class="card" style="background: var(--white); border: 1px solid var(--border-color); text-align: center; border-radius: 24px;">
                <label class="form-label" style="display: block; margin-bottom: 15px; font-weight: 700;"><?php echo __('profile_picture'); ?></label>
                <div id="camera-container" style="position: relative; width: 100%; aspect-ratio: 1; background: #f1f5f9; border-radius: 20px; overflow: hidden; margin-bottom: 20px; border: 2px dashed #cbd5e1;">
                    <video id="video" style="width: 100%; height: 100%; object-fit: cover;" autoplay playsinline></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <img id="photo-preview" src="<?php echo $user['photo_path'] ? BASE_URL . $user['photo_path'] : ''; ?>" style="width: 100%; height: 100%; object-fit: cover; <?php echo $user['photo_path'] ? '' : 'display: none;'; ?>">
                </div>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" id="startCam" class="btn" style="background: #edf2f7; color: var(--text-main); border: 1px solid var(--border-color);"><i class="fas fa-camera"></i> <?php echo __('start_camera'); ?></button>
                    <button type="button" id="capture" class="btn btn-primary" style="display: none;"><i class="fas fa-check"></i> <?php echo __('capture'); ?></button>
                    <button type="button" id="retake" class="btn" style="display: none; background: #E53E3E; color: white;"><i class="fas fa-undo"></i> <?php echo __('retake'); ?></button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label"><?php echo __('full_name'); ?></label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo e($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __('username'); ?></label>
                    <input type="text" name="username" class="form-control" value="<?php echo e($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __('role'); ?></label>
                    <select name="role" class="form-control" required>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>><?php echo __('admin'); ?></option>
                        <option value="trainer" <?php echo $user['role'] == 'trainer' ? 'selected' : ''; ?>><?php echo __('trainer'); ?></option>
                        <option value="trainee" <?php echo $user['role'] == 'trainee' ? 'selected' : ''; ?>><?php echo __('trainee'); ?></option>
                        <option value="management" <?php echo $user['role'] == 'management' ? 'selected' : ''; ?>><?php echo __('manager'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __('department'); ?></label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <div style="flex: 1;">
                            <select name="department" id="department-select" class="form-control" onchange="toggleNewDept()">
                                <option value=""><?php echo __('select_department'); ?></option>
                                <?php
                                $depts = $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department");
                                while ($d = $depts->fetch()) {
                                    $sel = (isset($user['department']) && $user['department'] == $d['department']) ? 'selected' : '';
                                    echo "<option value='" . e($d['department']) . "' {$sel}>" . e($d['department']) . "</option>";
                                }
                                ?>
                                <option value="__new__" style="font-weight: bold; color: var(--primary-blue);">➕ <?php echo __('add_new_department'); ?></option>
                            </select>
                            <input type="text" name="new_department" id="new-dept-input" class="form-control" placeholder="<?php echo __('enter_new_department_name'); ?>" style="display: none; margin-top: 8px;">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __('employee_id'); ?></label>
                    <input type="text" name="employee_id" class="form-control" value="<?php echo e($user['employee_id']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __('password'); ?> <?php echo $action == 'edit' ? '(' . __('leave_blank_to_keep_same') . ')' : ''; ?></label>
                    <input type="password" name="password" class="form-control" <?php echo $action == 'add' ? 'required' : ''; ?>>
                </div>

                <!-- ─── Contact & Identity ─── -->
                <div style="background: linear-gradient(135deg,rgba(3,105,161,0.05),rgba(124,58,237,0.05)); border:1.5px solid rgba(99,102,241,0.18); border-radius:14px; padding:18px 20px; margin-bottom:6px;">
                    <div style="font-size:0.8rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; color:#6366f1; margin-bottom:14px;">
                        <i class="fas fa-id-card"></i> <?php echo __('Contact & Identity'); ?>  
                        <span id="otp-hint" style="display:none; background:#dbeafe; color:#1d4ed8; font-size:0.7rem; padding:2px 9px; border-radius:6px; margin-left:8px; font-weight:700;">
                            <i class="fas fa-mobile-alt"></i> <?php echo __('OTP Login uses these fields'); ?>
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-mobile-alt" style="color:#0369a1;"></i> <?php echo __('Mobile Number'); ?>
                            </label>
                            <input type="tel" name="mobile_number" class="form-control"
                                   value="<?php echo e($user['mobile_number'] ?? ''); ?>"
                                   placeholder="<?php echo __('10-digit mobile number'); ?>"
                                   maxlength="15"
                                   pattern="[6-9][0-9]{9}"
                                   oninput="this.value=this.value.replace(/[^0-9]/,'')">
                            <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">
                                <?php echo __('Used for Trainee OTP login'); ?>
                            </small>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-fingerprint" style="color:#7c3aed;"></i> <?php echo __('Aadhaar Number'); ?>
                            </label>
                            <input type="text" name="aadhar_number" class="form-control"
                                   value="<?php echo e($user['aadhar_number'] ?? ''); ?>"
                                   placeholder="<?php echo __('12-digit Aadhaar number'); ?>"
                                   maxlength="12"
                                   pattern="[0-9]{12}"
                                   oninput="this.value=this.value.replace(/[^0-9]/,'').substring(0,12)"
                                   style="letter-spacing:3px; font-family:'Courier New',monospace; font-weight:700;">
                            <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">
                                <?php echo __('Stored securely · Used for Trainee OTP login'); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- OTR SPECIFIC FIELDS -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('qualification'); ?></label>
                        <input type="text" name="qualification" class="form-control" value="<?php echo e($user['qualification'] ?? ''); ?>" placeholder="<?php echo __('qualification_placeholder'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('category'); ?></label>
                        <input type="text" name="category" class="form-control" value="<?php echo e($user['category'] ?? ''); ?>" placeholder="<?php echo __('category_placeholder'); ?>">
                    </div>
                </div>
                <div class="form-group" id="batch-group" style="display: none;">
                    <label class="form-label"><?php echo __('batch_number'); ?></label>
                    <input type="text" name="batch_number" id="batch_number" class="form-control" value="<?php echo e($user['batch_number'] ?? ''); ?>" placeholder="<?php echo __('batch_placeholder'); ?>">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('date_of_joining'); ?> (DOJ)</label>
                        <input type="date" name="doj" class="form-control" value="<?php echo $user['doj'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('date_of_leaving'); ?> (DOL)</label>
                        <input type="date" name="dol" class="form-control" value="<?php echo $user['dol'] ?? ''; ?>">
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" name="save_user" class="btn btn-primary" style="width: 100%;"><?php echo __('save_user'); ?></button>
                </div>
            </div>
        </div>
    </form>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const photoPreview = document.getElementById('photo-preview');
        const startCamBtn = document.getElementById('startCam');
        const captureBtn = document.getElementById('capture');
        const retakeBtn = document.getElementById('retake');
        const photoDataInput = document.getElementById('photo_data');

        startCamBtn.onclick = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { aspectRatio: 1 } });
                video.srcObject = stream;
                video.style.display = 'block';
                photoPreview.style.display = 'none';
                startCamBtn.style.display = 'none';
                captureBtn.style.display = 'inline-block';
            } catch (err) {
                alert("Could not access camera: " + err);
            }
        };

        captureBtn.onclick = () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const data = canvas.toDataURL('image/jpeg', 0.8);
            photoDataInput.value = data;
            
            photoPreview.src = data;
            photoPreview.style.display = 'block';
            video.style.display = 'none';
            captureBtn.style.display = 'none';
            retakeBtn.style.display = 'inline-block';
            
            // Stop camera stream
            const stream = video.srcObject;
            const tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
        };

        retakeBtn.onclick = () => {
            retakeBtn.style.display = 'none';
            startCamBtn.click();
        };

        // Toggle Batch Number field + OTP hint based on role
        const roleSelect = document.querySelector('select[name="role"]');
        const batchGroup = document.getElementById('batch-group');
        const otpHint   = document.getElementById('otp-hint');

        function toggleBatchField() {
            const isTrainee = roleSelect.value === 'trainee';
            batchGroup.style.display = isTrainee ? 'block' : 'none';
            if (otpHint) otpHint.style.display = isTrainee ? 'inline-block' : 'none';
        }

        roleSelect.addEventListener('change', toggleBatchField);
        toggleBatchField(); // Run on page load

        // Toggle New Department Input
        function toggleNewDept() {
            const select = document.getElementById('department-select');
            const input = document.getElementById('new-dept-input');
            if (select.value === '__new__') {
                input.style.display = 'block';
                input.required = true;
            } else {
                input.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }
    </script>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
