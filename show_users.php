<?php
// show_users.php — Temporary credentials viewer (delete after use)
require_once 'includes/config.php';

$users = $pdo->query("
    SELECT employee_id, username, full_name, role, department, password
    FROM users 
    ORDER BY FIELD(role,'admin','management','trainer','trainee'), full_name
")->fetchAll(PDO::FETCH_ASSOC);

$roleColors = [
    'admin'      => ['#dc2626','#fef2f2'],
    'management' => ['#7c3aed','#f5f3ff'],
    'trainer'    => ['#0369a1','#eff6ff'],
    'trainee'    => ['#047857','#f0fdf4'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OTR — All User Credentials</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 30px; }
    h1 { color: #0f172a; margin-bottom: 5px; }
    .warn { background: #fef3c7; border: 2px dashed #f59e0b; border-radius: 10px; padding: 12px 18px; margin-bottom: 25px; font-weight: 700; color: #92400e; font-size: 0.9rem; }
    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    th { background: #0f172a; color: #fff; padding: 12px 16px; text-align: left; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.88rem; color: #1e293b; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f8fafc; }
    .role-badge { display: inline-block; padding: 3px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; }
    .pwd { font-family: 'Courier New', monospace; background: #f1f5f9; padding: 3px 8px; border-radius: 5px; font-size: 0.82rem; color: #334155; }
    .note { margin-top: 20px; color: #64748b; font-size: 0.82rem; }
    .count { font-size: 0.85rem; color: #64748b; margin-bottom: 20px; font-weight: 600; }
</style>
</head>
<body>
<h1>🔐 OTR System — All User Credentials</h1>
<div class="count"><?php echo count($users); ?> users found</div>
<div class="warn">⚠️ DEVELOPMENT USE ONLY — Delete this file before going to production!</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Employee ID <small style="opacity:.6;">(Login ID)</small></th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Department</th>
            <th>Default Password</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $i => $u):
        [$fg, $bg] = $roleColors[$u['role']] ?? ['#64748b','#f8fafc'];
        // Most systems seed with Password@123 or similar — check the hash
        // We'll show common defaults to try
        $commonPasswords = ['Password@123', 'Admin@123', 'Trainer@123', 'Trainee@123', 'password', '123456', 'Pass@1234', 'Admin@1234'];
        $foundPwd = null;
        foreach ($commonPasswords as $try) {
            if (password_verify($try, $u['password'])) { $foundPwd = $try; break; }
        }
    ?>
    <tr>
        <td style="color:#94a3b8; font-weight:700;"><?php echo $i + 1; ?></td>
        <td style="font-weight:800; font-family:'Courier New',monospace; color:#0f172a; font-size:0.95rem;">
            <?php echo htmlspecialchars($u['employee_id']); ?>
        </td>
        <td style="color:#475569;"><?php echo htmlspecialchars($u['username']); ?></td>
        <td style="font-weight:600;"><?php echo htmlspecialchars($u['full_name']); ?></td>
        <td>
            <span class="role-badge" style="color:<?php echo $fg; ?>; background:<?php echo $bg; ?>; border:1px solid <?php echo $fg; ?>30;">
                <?php echo $u['role']; ?>
            </span>
        </td>
        <td style="color:#64748b; font-size:0.82rem;"><?php echo htmlspecialchars($u['department'] ?? '-'); ?></td>
        <td>
            <?php if ($foundPwd): ?>
                <span class="pwd">✅ <?php echo htmlspecialchars($foundPwd); ?></span>
            <?php else: ?>
                <span style="color:#94a3b8; font-size:0.8rem;">🔒 Custom / unknown — reset via admin panel</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="note">
    💡 <strong>Tip:</strong> If a password shows "Custom / unknown", go to <strong>Admin → Users → Reset Password</strong>, or run:<br>
    <code style="background:#e2e8f0; padding:4px 10px; border-radius:5px; display:inline-block; margin-top:6px;">
        UPDATE users SET password = '$2y$10$...' WHERE employee_id = 'EMPXXX';
    </code><br>
    Use PHP's <code>password_hash('NewPassword@123', PASSWORD_DEFAULT)</code> to generate a new hash.
</div>

<div style="margin-top:20px; font-size:0.8rem; color:#94a3b8;">
    ⏱ Generated: <?php echo date('d M Y H:i:s'); ?>
</div>
</body>
</html>
