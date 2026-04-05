<?php
// includes/layout.php
require_once 'config.php';
require_once 'functions.php';

function renderHeader($title) {
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&family=Noto+Sans+Devanagari:wght@400;600;700&family=Noto+Sans+Tamil:wght@400;600;700&family=Noto+Sans+Kannada:wght@400;600;700&family=Noto+Sans+Telugu:wght@400;600;700&family=Noto+Sans+Malayalam:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.6">
    <style>
        :root {
            --hindi-font: 'Noto Sans Devanagari', sans-serif;
            --tamil-font: 'Noto Sans Tamil', sans-serif;
            --kannada-font: 'Noto Sans Kannada', sans-serif;
            --telugu-font: 'Noto Sans Telugu', sans-serif;
            --malayalam-font: 'Noto Sans Malayalam', sans-serif;
        }
        body.lang-hi { font-family: var(--hindi-font); }
        body.lang-ta { font-family: var(--tamil-font); }
        body.lang-kn { font-family: var(--kannada-font); }
        body.lang-te { font-family: var(--telugu-font); }
        body.lang-ml { font-family: var(--malayalam-font); }
        
        /* Specific font overrides for Indic scripts */
        .lang-hi .menu-text, .lang-ta .menu-text, .lang-kn .menu-text, .lang-te .menu-text, .lang-ml .menu-text { font-weight: 600; }
        .lang-hi .page-title, .lang-ta .page-title, .lang-kn .page-title, .lang-te .page-title, .lang-ml .page-title { font-weight: 700; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/img/profiles/favicon.svg">
    <script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>
</head>
<body class="animate-in lang-<?php echo getCurrentLang(); ?>">
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
                <span class="menu-text"><?php echo __('dashboard'); ?></span>
            </a>

            <?php if ($role == 'admin' || $role == 'management'): ?>
            <div class="sidebar-label"><?php echo __('management'); ?></div>
            <?php endif; ?>

            <?php if ($role == 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>admin/users.php" class="menu-item <?php echo isActive('users.php'); ?>" data-label="Users">
                <i class="fas fa-users"></i>
                <span class="menu-text"><?php echo __('user_management'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/inactive_users.php" class="menu-item <?php echo isActive('inactive_users.php'); ?>" data-label="Inactive Users">
                <i class="fas fa-users-slash"></i>
                <span class="menu-text"><?php echo __('inactive_members'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/modules.php" class="menu-item <?php echo isActive('modules.php'); ?>" data-label="Modules">
                <i class="fas fa-book"></i>
                <span class="menu-text"><?php echo __('training_modules'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/exams.php" class="menu-item <?php echo isActive('exams.php'); ?>" data-label="Exams">
                <i class="fas fa-file-alt"></i>
                <span class="menu-text"><?php echo __('exams'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/results.php" class="menu-item <?php echo isActive('results.php'); ?>" data-label="Results">
                <i class="fas fa-chart-pie"></i>
                <span class="menu-text"><?php echo __('exam_results'); ?></span>
            </a>
            
            <div class="sidebar-label"><?php echo __('training'); ?></div>
            <a href="<?php echo BASE_URL; ?>admin/induction_topics.php" class="menu-item <?php echo isActive('induction_topics.php'); ?>" data-label="Induction">
                <i class="fas fa-list-check"></i>
                <span class="menu-text"><?php echo __('induction_topics'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/induction_records.php" class="menu-item <?php echo isActive('induction_records.php'); ?>" data-label="Records">
                <i class="fas fa-address-book"></i>
                <span class="menu-text"><?php echo __('induction_records'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/score_sheet_entry.php" class="menu-item <?php echo isActive('score_sheet_entry.php'); ?>" data-label="Scores">
                <i class="fas fa-star"></i>
                <span class="menu-text"><?php echo __('induction_scores'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/assignments.php" class="menu-item <?php echo isActive('assignments.php'); ?>" data-label="Assignments">
                <i class="fas fa-clipboard-list"></i>
                <span class="menu-text"><?php echo __('assignments'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/training_hub.php" class="menu-item <?php echo isActive('training_hub.php'); ?>" data-label="Training Hub">
                <i class="fas fa-laptop-code"></i>
                <span class="menu-text"><?php echo __('training_hub'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/reports.php" class="menu-item <?php echo isActive('reports.php'); ?>" data-label="Reports">
                <i class="fas fa-file-export"></i>
                <span class="menu-text"><?php echo __('reports'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/sms_settings.php" class="menu-item <?php echo isActive('sms_settings.php'); ?>" data-label="SMS Settings">
                <i class="fas fa-sms"></i>
                <span class="menu-text">SMS / OTP Settings</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/refresher_training.php" class="menu-item <?php echo isActive('refresher_training.php'); ?>" data-label="Refresher">
                <i class="fas fa-redo"></i>
                <span class="menu-text">Refresher Training</span>
            </a>
            <?php endif; ?>

            <?php if ($role == 'trainer'): ?>
            <a href="<?php echo BASE_URL; ?>trainer/trainees.php" class="menu-item <?php echo isActive('trainees.php'); ?>" data-label="Trainees">
                <i class="fas fa-user-graduate"></i>
                <span class="menu-text"><?php echo __('my_trainees'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/mapping_status.php" class="menu-item <?php echo isActive('mapping_status.php'); ?>" data-label="Mapping Status">
                <i class="fas fa-sitemap"></i>
                <span class="menu-text">Mapping Status</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/induction_records.php" class="menu-item <?php echo isActive('induction_records.php'); ?>" data-label="Induction">
                <i class="fas fa-id-badge"></i>
                <span class="menu-text"><?php echo __('induction_records'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/score_sheet_entry.php" class="menu-item <?php echo isActive('score_sheet_entry.php'); ?>" data-label="Scores">
                <i class="fas fa-star"></i>
                <span class="menu-text"><?php echo __('induction_scores'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/assignments.php" class="menu-item <?php echo isActive('assignments.php'); ?>" data-label="Assignments">
                <i class="fas fa-clipboard-list"></i>
                <span class="menu-text"><?php echo __('assignments'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/progress.php" class="menu-item <?php echo isActive('progress.php'); ?>" data-label="Progress">
                <i class="fas fa-tasks"></i>
                <span class="menu-text"><?php echo __('training_progress'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/skill_matrix.php" class="menu-item <?php echo isActive('skill_matrix.php'); ?>" data-label="Skill Matrix">
                <i class="fas fa-table"></i>
                <span class="menu-text">Skill Matrix</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/training_hub.php" class="menu-item <?php echo isActive('training_hub.php'); ?>" data-label="Training Hub">
                <i class="fas fa-laptop-code"></i>
                <span class="menu-text"><?php echo __('training_hub'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/refresher_training.php" class="menu-item <?php echo isActive('refresher_training.php'); ?>" data-label="Refresher">
                <i class="fas fa-redo"></i>
                <span class="menu-text">Refresher Training</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/questions.php" class="menu-item <?php echo isActive('questions.php'); ?>" data-label="Questions">
                <i class="fas fa-question-circle"></i>
                <span class="menu-text"><?php echo __('question_bank'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/results.php" class="menu-item <?php echo isActive('results.php'); ?>" data-label="Results">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text"><?php echo __('exam_results'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainer/effectiveness.php" class="menu-item <?php echo isActive('effectiveness.php'); ?>" data-label="Effectiveness">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text"><?php echo __('effectiveness'); ?></span>
            </a>
            <?php endif; ?>

            <?php if ($role == 'trainee'): ?>
            <a href="<?php echo BASE_URL; ?>trainee/my-training.php" class="menu-item <?php echo isActive('my-training.php'); ?>" data-label="Training">
                <i class="fas fa-graduation-cap"></i>
                <span class="menu-text"><?php echo __('my_training'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainee/ojt_camera.php" class="menu-item <?php echo isActive('ojt_camera.php'); ?>" data-label="OJT Evidence">
                <i class="fas fa-camera"></i>
                <span class="menu-text">OJT Evidence</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainee/refresher.php" class="menu-item <?php echo isActive('refresher.php'); ?>" data-label="Refresher">
                <i class="fas fa-redo"></i>
                <span class="menu-text">Refresher Training</span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainee/results.php" class="menu-item <?php echo isActive('results.php'); ?>" data-label="Results">
                <i class="fas fa-award"></i>
                <span class="menu-text"><?php echo __('my_results'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>trainee/feedback.php" class="menu-item <?php echo isActive('feedback.php'); ?>" data-label="Feedback">
                <i class="fas fa-comment-dots"></i>
                <span class="menu-text"><?php echo __('my_feedback'); ?></span>
            </a>
            <?php endif; ?>

            <?php if ($role == 'management'): ?>
            <a href="<?php echo BASE_URL; ?>management/employees.php" class="menu-item <?php echo isActive('employees.php'); ?>" data-label="Employees">
                <i class="fas fa-users"></i>
                <span class="menu-text"><?php echo __('all_employees'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/users.php" class="menu-item <?php echo isActive('users.php'); ?>" data-label="Users">
                <i class="fas fa-user-plus"></i>
                <span class="menu-text"><?php echo __('user_management'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>management/reports.php" class="menu-item <?php echo isActive('reports.php'); ?>" data-label="Reports">
                <i class="fas fa-file-invoice"></i>
                <span class="menu-text"><?php echo __('reports'); ?></span>
            </a>
            <a href="<?php echo BASE_URL; ?>management/effectiveness.php" class="menu-item <?php echo isActive('effectiveness.php'); ?>" data-label="Effectiveness">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text"><?php echo __('effectiveness'); ?></span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="powered-by-bar">
            <div class="pb-label"><?php echo __('powered_by'); ?></div>
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
                    if ($hour < 12) echo __('top_morning');
                    elseif ($hour < 17) echo __('brilliant_afternoon');
                    else echo __('excellent_evening');
                    ?>
                </div>
                <h1 class="page-title" style="margin:0;">
                    <?php 
                    $pageKey = basename($_SERVER['PHP_SELF'], '.php');
                    // Map file names to translation keys
                    $titleMap = [
                        'users' => 'user_management',
                        'inactive_users' => 'inactive_members',
                        'modules' => 'training_modules',
                        'induction_topics' => 'induction_topics',
                        'induction_records' => 'induction_records',
                        'score_sheet_entry' => 'induction_scores',
                        'training_hub' => 'training_hub',
                        'trainees' => 'my_trainees',
                        'progress' => 'training_progress',
                        'questions' => 'question_bank',
                        'my-training' => 'my_training',
                        'employees' => 'all_employees',
                        'my-results' => 'my_results'
                    ];
                    $tKey = $titleMap[$pageKey] ?? str_replace(['-', ' '], '_', strtolower($pageKey));
                    echo __($tKey, ucwords(str_replace(['_', '-'], ' ', $pageKey))); 
                    ?>
                </h1>
            </div>
        </div>

        <div class="header-actions">
                <div class="header-clock">
                    <div class="time" id="clock-time">00:00:00</div>
                    <div class="date"><?php echo date('d M Y'); ?></div>
                </div>

                <!-- Language Selector -->
                <div class="notif-dropdown-wrap">
                    <button class="icon-btn" onclick="toggleLangMenu()" title="<?php echo __('switch_language'); ?>">
                        <i class="fas fa-language"></i>
                    </button>
                    <div class="notif-menu" id="langMenu" style="width: 180px;">
                        <div class="notif-header">
                            <h4><?php echo __('switch_language'); ?></h4>
                        </div>
                        <div class="notif-list">
                            <?php 
                            global $available_languages;
                            foreach ($available_languages as $code => $name): ?>
                            <a href="?lang=<?php echo $code; ?>" class="notif-item <?php echo getCurrentLang() == $code ? 'active' : ''; ?>" style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: block; font-size: 14px;">
                                <?php echo $name; ?>
                                <?php if (getCurrentLang() == $code): ?>
                                    <i class="fas fa-check" style="float: right; color: var(--primary); margin-top: 3px;"></i>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
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
                            <h4><?php echo __('notifications'); ?></h4>
                            <a href="javascript:void(0)" onclick="markAllRead()"><?php echo __('mark_all_read'); ?></a>
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
                            <div class="pm-role"><?php 
                                $roleKey = $_SESSION['role'];
                                if ($roleKey == 'management') $roleKey = 'manager';
                                echo strtoupper(__($roleKey)); 
                            ?></div>
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
                            <div class="pm-role"><?php 
                                $roleKey = $_SESSION['role'];
                                if ($roleKey == 'management') $roleKey = 'manager';
                                echo ucfirst(__($roleKey)); 
                            ?> <?php echo __('login_account'); ?></div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>profile.php" class="profile-menu-item">
                            <i class="fas fa-user"></i> <?php echo __('my_profile'); ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>settings.php" class="profile-menu-item">
                            <i class="fas fa-cog"></i> <?php echo __('settings'); ?>
                        </a>
                        <div class="menu-divider"></div>
                        <a href="<?php echo BASE_URL; ?>includes/auth.php?logout=1" class="profile-menu-item logout">
                            <i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?>
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
    <script>
        window.I18N = {
            search: "<?php echo __('search'); ?>",
            lengthMenu: "<?php echo __('length_menu'); ?>",
            zeroRecords: "<?php echo __('zero_records'); ?>",
            info: "<?php echo __('info'); ?>",
            infoEmpty: "<?php echo __('info_empty'); ?>",
            infoFiltered: "<?php echo __('info_filtered'); ?>",
            paginate: {
                first: "<?php echo __('first'); ?>",
                last: "<?php echo __('last'); ?>",
                next: "<?php echo __('next'); ?>",
                previous: "<?php echo __('previous'); ?>"
            }
        };
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js?v=1.6"></script>
    <?php 
        // Only load live monitoring camera when actively rendering course material/OJT
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'trainee' && basename($_SERVER['PHP_SELF']) === 'course-material.php'): 
    ?>
    <script src="<?php echo BASE_URL; ?>assets/js/camera-monitor.js?v=1.1"></script>
    <?php endif; ?>
    <script>
        // Global Notification System
        <?php if (isset($_SESSION['success_msg'])): ?>
            Swal.fire({
                icon: 'success',
                title: '<?php echo __('success'); ?>',
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
                title: '<?php echo __('error'); ?>',
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
