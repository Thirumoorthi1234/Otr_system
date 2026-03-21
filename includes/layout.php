<?php
// includes/layout.php
require_once 'config.php';
require_once 'functions.php';

function renderHeader($title) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>
</head>
<body class="animate-in">
    <div class="app-container">
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php
}

function renderSidebar($role) {
    $current_page = basename($_SERVER['PHP_SELF']);
?>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-wrap" onclick="toggleSidebar()">
                <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" alt="Syrma SGS">
            </div>
            <button class="hamburger-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <nav class="sidebar-menu">
            <a href="<?php echo BASE_URL . $role; ?>/dashboard.php" class="menu-item <?php echo isActive('dashboard.php'); ?>" data-label="Dashboard">
                <i class="fas fa-th-large"></i>
                <span class="menu-text">Dashboard</span>
            </a>

            <?php if ($role == 'admin'): ?>
            <div class="sidebar-label">Management</div>
            <a href="<?php echo BASE_URL; ?>admin/users.php" class="menu-item <?php echo isActive('users.php'); ?>" data-label="Users">
                <i class="fas fa-users"></i>
                <span class="menu-text">User Management</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/modules.php" class="menu-item <?php echo isActive('modules.php'); ?>" data-label="Modules">
                <i class="fas fa-book"></i>
                <span class="menu-text">Training Modules</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/exams.php" class="menu-item <?php echo isActive('exams.php'); ?>" data-label="Exams">
                <i class="fas fa-file-alt"></i>
                <span class="menu-text">Exams</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/results.php" class="menu-item <?php echo isActive('results.php'); ?>" data-label="Results">
                <i class="fas fa-chart-pie"></i>
                <span class="menu-text">Exam Results</span>
            </a>
            
            <div class="sidebar-label">Training</div>
            <a href="<?php echo BASE_URL; ?>admin/induction_topics.php" class="menu-item <?php echo isActive('induction_topics.php'); ?>" data-label="Induction">
                <i class="fas fa-list-check"></i>
                <span class="menu-text">Induction Topics</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/induction_records.php" class="menu-item <?php echo isActive('induction_records.php'); ?>" data-label="Records">
                <i class="fas fa-address-book"></i>
                <span class="menu-text">Induction Records</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/score_sheet_entry.php" class="menu-item <?php echo isActive('score_sheet_entry.php'); ?>" data-label="Scores">
                <i class="fas fa-star"></i>
                <span class="menu-text">Induction Scores</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/assignments.php" class="menu-item <?php echo isActive('assignments.php'); ?>" data-label="Assignments">
                <i class="fas fa-clipboard-list"></i>
                <span class="menu-text">Assignments</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/training_hub.php" class="menu-item <?php echo isActive('training_hub.php'); ?>" data-label="Training Hub">
                <i class="fas fa-laptop-code"></i>
                <span class="menu-text">Training Hub</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/reports.php" class="menu-item <?php echo isActive('reports.php'); ?>" data-label="Reports">
                <i class="fas fa-file-export"></i>
                <span class="menu-text">Reports</span>
            </a>
            <?php endif; ?>

            <?php if ($role == 'trainer'): ?>
            <a href="<?php echo BASE_URL; ?>trainer/trainees.php" class="menu-item <?php echo isActive('trainees.php'); ?>" data-label="Trainees">
                <i class="fas fa-user-graduate"></i>
                <span class="menu-text">My Trainees</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/induction_records.php" class="menu-item <?php echo isActive('induction_records.php'); ?>" data-label="Induction">
                <i class="fas fa-id-badge"></i>
                <span class="menu-text">Induction Records</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/score_sheet_entry.php" class="menu-item <?php echo isActive('score_sheet_entry.php'); ?>" data-label="Scores">
                <i class="fas fa-star"></i>
                <span class="menu-text">Induction Scores</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/progress.php" class="menu-item <?php echo isActive('progress.php'); ?>" data-label="Progress">
                <i class="fas fa-tasks"></i>
                <span class="menu-text">Training Progress</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/training_hub.php" class="menu-item <?php echo isActive('training_hub.php'); ?>" data-label="Training Hub">
                <i class="fas fa-laptop-code"></i>
                <span class="menu-text">Training Hub</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/questions.php" class="menu-item <?php echo isActive('questions.php'); ?>" data-label="Questions">
                <i class="fas fa-question-circle"></i>
                <span class="menu-text">Question Bank</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/results.php" class="menu-item <?php echo isActive('results.php'); ?>" data-label="Results">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Exam Results</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/effectiveness.php" class="menu-item <?php echo isActive('effectiveness.php'); ?>" data-label="Effectiveness">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text">Effectiveness</span>
            </a>
            <?php endif; ?>

            <?php if ($role == 'trainee'): ?>
            <a href="<?php echo BASE_URL; ?>trainee/my-training.php" class="menu-item <?php echo isActive('my-training.php'); ?>" data-label="Training">
                <i class="fas fa-graduation-cap"></i>
                <span class="menu-text">My Training</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainee/results.php" class="menu-item <?php echo isActive('results.php'); ?>" data-label="Results">
                <i class="fas fa-award"></i>
                <span class="menu-text">My Results</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainee/feedback.php" class="menu-item <?php echo isActive('feedback.php'); ?>" data-label="Feedback">
                <i class="fas fa-comment-dots"></i>
                <span class="menu-text">My Feedback</span>
            </a>
            <?php endif; ?>

            <?php if ($role == 'management'): ?>
            <a href="<?php echo BASE_URL; ?>management/employees.php" class="menu-item <?php echo isActive('employees.php'); ?>" data-label="Employees">
                <i class="fas fa-users"></i>
                <span class="menu-text">All Employees</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/users.php" class="menu-item <?php echo isActive('users.php'); ?>" data-label="Users">
                <i class="fas fa-user-plus"></i>
                <span class="menu-text">User Management</span>
            </a>
            <a href="<?php echo BASE_URL; ?>management/reports.php" class="menu-item <?php echo isActive('reports.php'); ?>" data-label="Reports">
                <i class="fas fa-file-invoice"></i>
                <span class="menu-text">Reports</span>
            </a>
            <a href="<?php echo BASE_URL; ?>management/effectiveness.php" class="menu-item <?php echo isActive('effectiveness.php'); ?>" data-label="Effectiveness">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text">Effectiveness</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="powered-by-bar">
            <div class="pb-label">Powered by</div>
            <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" alt="Learnlike">
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="hamburger-btn mobile-only-menu" onclick="toggleSidebar()" style="display:none; margin:0; border-color:var(--border-color); background:var(--card-bg);">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="page-subtitle">
                    <i class="fas fa-sun" style="margin-right: 5px; color: var(--warning);"></i>
                    <?php 
                    $hour = date('H');
                    if ($hour < 12) echo "Top of the morning";
                    elseif ($hour < 17) echo "Brilliant Afternoon";
                    else echo "Excellent Evening";
                    ?>
                </div>
                <h1 class="page-title" style="margin:0;">
                    <?php 
                    $page = basename($_SERVER['PHP_SELF'], '.php');
                    echo ucwords(str_replace(['_', '-'], ' ', $page)); 
                    ?>
                </h1>
            </div>
        </div>

        <div class="header-actions">
                <div class="header-clock">
                    <div class="time" id="clock-time">00:00:00</div>
                    <div class="date"><?php echo date('d M Y'); ?></div>
                </div>

                <!-- Theme Toggle -->
                <button class="icon-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Switch Theme">
                    <i class="fas fa-moon"></i>
                </button>

                <!-- Notifications -->
                <div class="notif-dropdown-wrap">
                    <button class="icon-btn" onclick="toggleNotifMenu()">
                        <i class="fas fa-bell"></i>
                        <span class="notif-badge" id="notifBadge" data-count="0"></span>
                    </button>
                    <div class="notif-menu" id="notifMenu">
                        <div class="notif-header">
                            <h4>Notifications</h4>
                            <a href="javascript:void(0)" onclick="markAllRead()">Mark all as read</a>
                        </div>
                        <div class="notif-list" id="notifList">
                            <!-- JS loaded -->
                        </div>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="profile-dropdown-wrap">
                    <div class="profile-trigger" id="profileTrigger" onclick="toggleProfileMenu()">
                        <div style="text-align: right; display: block;">
                            <div class="pm-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                            <div class="pm-role"><?php echo strtoupper($_SESSION['role'] == 'management' ? 'Manager' : $_SESSION['role']); ?></div>
                        </div>
                        <div class="user-avatar">
                            <?php if (!empty($_SESSION['photo_path'])): ?>
                                <img src="<?php echo BASE_URL . $_SESSION['photo_path']; ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user-shield"></i>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-chevron-down chevron"></i>
                    </div>

                    <div class="profile-menu" id="profileMenu">
                        <div class="profile-menu-header">
                            <div class="pm-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                            <div class="pm-role"><?php echo ucfirst($_SESSION['role'] == 'management' ? 'Manager' : $_SESSION['role']); ?> Account</div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>profile.php" class="profile-menu-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="<?php echo BASE_URL; ?>settings.php" class="profile-menu-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="menu-divider"></div>
                        <a href="<?php echo BASE_URL; ?>includes/auth.php?logout=1" class="profile-menu-item logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
<?php
}

function renderFooter() {
?>
    </main>
    </div>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js?v=1.6"></script>
    <script>
        // Global Notification System
        <?php if (isset($_SESSION['success_msg'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $_SESSION['success_msg']; ?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $_SESSION['error_msg']; ?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        // Update clock in real-time
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const clockEl = document.getElementById('clock-time');
            if (clockEl) clockEl.textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
<?php
}
?>
