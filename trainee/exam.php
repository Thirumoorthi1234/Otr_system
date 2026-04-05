<?php
// trainee/exam.php
require_once '../includes/layout.php';
checkRole('trainee');

$exam_id = $_GET['id'] ?? null;
if (!$exam_id) die(__("exam_id_required"));

// Fetch Exam Details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
if (!$exam) die(__("exam_not_found"));



// Check for locked assignment
$stmt_lock = $pdo->prepare("
    SELECT a.is_locked 
    FROM assignments a 
    JOIN exams e ON a.module_id = e.module_id 
    WHERE a.trainee_id = ? AND e.id = ?
");
$stmt_lock->execute([$_SESSION['user_id'], $exam_id]);
$assignment_status = $stmt_lock->fetch();

if ($assignment_status && $assignment_status['is_locked']) {
    die("<div style='text-align:center; padding: 100px; font-family: sans-serif;'>
            <div style='background: #fff; padding: 50px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); max-width: 500px; margin: auto;'>
                <i class='fas fa-lock' style='font-size: 4rem; color: #ef4444; margin-bottom: 20px;'></i>
                <h1 style='color: #ef4444; font-weight: 900;'>" . __("exam_locked") . "</h1>
                <p style='color: #4a5568; line-height: 1.6;'>" . __("exam_locked_proctoring_desc") . "</p>
                <div style='background: #fff5f5; border-left: 4px solid #f56565; padding: 15px; margin: 25px 0; text-align: left;'>
                    <p style='margin:0; font-weight: 700; color: #c53030;'>" . __("required_action") . ":</p>
                    <p style='margin:0; font-size: 0.9rem; color: #742a2a;'>" . __("contact_trainer_unlock_desc") . "</p>
                </div>
                <a href='my-training.php' style='display: block; background: var(--primary-blue); color: white; padding: 15px; border-radius: 12px; text-decoration: none; font-weight: 800; margin-top: 20px;'>" . __("back_to_dashboard") . "</a>
            </div>
         </div>");
}

// Fetch Questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY RAND()");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

renderHeader(__('online_examination'));
renderSidebar('trainee');
?>

<!-- face-api.js & SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="exam-layout-container" style="display: flex; gap: 30px; max-width: 1400px; margin: auto; padding: 20px; align-items: flex-start;">
    <!-- Main Exam Form -->
    <div style="flex: 1;">
        <div class="card" style="margin-bottom: 30px; border-radius: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f8fafc; padding-bottom: 25px; margin-bottom: 30px;">
                <div>
                    <h2 style="margin:0; color: var(--brand-navy); font-weight: 800; font-size: 1.8rem;"><?php echo e($exam['title']); ?></h2>
                    <p style="margin:5px 0 0; color: var(--text-muted); font-size: 0.95rem;">
                        <i class="fas fa-list-check" style="margin-right: 5px;"></i> <?php echo count($questions); ?> <?php echo __('questions'); ?> &bull; 
                        <i class="fas fa-bullseye" style="margin-right: 5px; color: #38a169;"></i> <?php echo __('Passing'); ?>: <?php echo $exam['passing_score']; ?>%
                    </p>
                </div>
                <div style="text-align: right;">
                    <div class="timer-card" style="background: var(--primary-blue); padding: 10px 20px; border-radius: 16px; color: white; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                        <i class="fas fa-clock" style="font-size: 1.2rem; color: #f6ad55;"></i>
                        <div id="timer" style="font-size: 1.8rem; font-weight: 900; font-family: 'Outfit', sans-serif;">
                            <?php echo str_pad($exam['duration_minutes'], 2, '0', STR_PAD_LEFT); ?>:00
                        </div>
                    </div>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 25px; border-radius: 20px; margin-bottom: 40px; border: 1px solid #edf2f7; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="info-item">
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; display: block; margin-bottom: 5px;"><?php echo __('candidate_name'); ?></span>
                    <p style="margin:0; font-weight: 700; color: var(--text-main); font-size: 1.1rem;"><?php echo e($_SESSION['full_name']); ?></p>
                </div>
                <div class="info-item">
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; display: block; margin-bottom: 5px;"><?php echo __('employee_id'); ?></span>
                    <p style="margin:0; font-weight: 700; color: var(--text-main); font-size: 1.1rem;"><?php echo e($_SESSION['user_id']); ?></p>
                </div>
                <div class="info-item">
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; display: block; margin-bottom: 5px;"><?php echo __('department'); ?></span>
                    <input type="text" name="department_display" placeholder="Assigned Department" value="" required style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 2px solid #e2e8f0; font-weight: 600; font-size: 1rem; margin-top: 5px;">
                </div>
                <div class="info-item">
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; display: block; margin-bottom: 5px;"><?php echo __('exam_date'); ?></span>
                    <p style="margin:0; font-weight: 700; color: var(--text-main); font-size: 1.1rem;"><?php echo date('d M Y'); ?></p>
                </div>
            </div>

            <form id="exam-form" action="submit_exam.php" method="POST">
                <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                <input type="hidden" name="department" id="hidden-dept" value="">

                <?php 
                // Camera is no longer mandatory for exam phase as per revised requirement
                if (false && $exam['camera_enabled']): 
                ?>
                <!-- TOP PROCTORING & RULES DASHBOARD -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <!-- Camera Unit -->
                    <div class="card" style="padding: 15px; border-radius: 20px; border: 2px solid var(--primary-blue); background: #fcfdfe; display: flex; align-items: center; gap: 20px; margin: 0;">
                        <div style="flex: 0 0 160px; height: 120px; background: #000; border-radius: 12px; overflow: hidden; position: relative;">
                            <video id="webcam" autoplay playsinline muted style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                            <div id="camera-overlay" class="camera-overlay"><div class="scan-line"></div></div>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px;">
                                <h4 style="margin: 0; font-weight: 800; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                                    <span class="live-dot"></span> <?php echo __('proctoring_active'); ?>
                                </h4>
                            </div>
                            <p style="font-size: 0.72rem; color: #475569; line-height: 1.4; margin: 0; font-weight: 600;">
                                <?php echo __('proctoring_desc'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Guidelines Unit -->
                    <div class="card" style="padding: 15px; border-radius: 20px; border: 1px solid #E2E8F0; background: #fff5f5; margin: 0;">
                        <h4 style="margin: 0 0 10px 0; font-weight: 800; font-size: 0.85rem; color: #c53030;"><?php echo __('critical_rules'); ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.72rem; color: #742a2a; display: grid; grid-template-columns: 1fr 1fr; gap: 5px 15px;">
                            <li><i class="fas fa-eye" style="margin-right: 5px;"></i> <?php echo __('eyes_on_screen'); ?></li>
                            <li><i class="fas fa-volume-mute" style="margin-right: 8px;"></i> <?php echo __('maintain_silence'); ?></li>
                            <li><i class="fas fa-mobile-button" style="margin-right: 8px;"></i> <?php echo __('no_devices'); ?></li>
                            <li><i class="fas fa-user-secret" style="margin-right: 8px;"></i> <?php echo __('single_person_only'); ?></li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <?php foreach ($questions as $index => $q): ?>
                <div class="question-block" style="margin-bottom: 45px; padding: 35px; background: white; border-radius: 20px; border: 1px solid #eee; position: relative;">
                    <div style="position: absolute; top: -15px; left: 30px; background: var(--primary-blue); color: white; padding: 5px 15px; border-radius: 10px; font-weight: 800; font-size: 0.9rem;">
                        Question #<?php echo $index + 1; ?>
                    </div>
                    
                    <p style="font-size: 1.2rem; font-weight: 700; color: var(--text-main); margin: 15px 0 25px; line-height: 1.5;">
                        <?php echo e($q['question_text']); ?>
                    </p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                            <?php if (!empty($q['option_'.strtolower($opt)]) && $q['option_'.strtolower($opt)] != 'NONE'): ?>
                            <label class="option-label" style="display: flex; align-items: center; padding: 18px 25px; background: #f8fafc; border: 2px solid #f1f5f9; border-radius: 16px; cursor: pointer; transition: all 0.2s ease;">
                                <input type="radio" name="q<?php echo $q['id']; ?>" value="<?php echo $opt; ?>" style="margin-right: 18px; width: 22px; height: 22px; accent-color: var(--primary-blue);" required>
                                <span style="font-weight: 600; font-size: 1rem; color: #475569;">
                                    <strong style="color: var(--primary-blue); margin-right: 12px; font-size: 1.1rem;"><?php echo $opt; ?>.</strong> 
                                    <?php echo e($q['option_'.strtolower($opt)]); ?>
                                </span>
                            </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="margin-top: 50px; padding: 35px; background: #f8fafc; border-radius: 24px; border: 1px solid #edf2f7; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 -10px 40px rgba(0,0,0,0.02);">
                    <div style="display: flex; gap: 10px; align-items: center; color: var(--text-muted); font-weight: 600;">
                        <i class="fas fa-triangle-exclamation" style="color: #f6ad55; font-size: 1.5rem;"></i>
                        <span><?php echo __('do_not_close_tab_desc'); ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 15px 50px; font-weight: 800; font-size: 1.2rem; border-radius: 16px; background: var(--brand-navy); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: none;">
                        <?php echo __('FINISH EXAM'); ?> <i class="fas fa-check-double" style="margin-left: 12px;"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Timer Logic Refined
    let timeLeft = <?php echo $exam['duration_minutes'] * 60; ?>;
    const timerDisplay = document.getElementById('timer');
    const timerCard = document.querySelector('.timer-card');
    
    const countdown = setInterval(() => {
        timeLeft--;
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        
        timerDisplay.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 60) {
            timerCard.style.background = '#ef4444';
            timerCard.style.animation = 'pulse 1s infinite alternate';
        }
        
        if (timeLeft <= 0) {
            clearInterval(countdown);
            Swal.fire({
                title: '<?php echo __('time_is_up'); ?>',
                text: '<?php echo __('exam_auto_submit_desc'); ?>',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                document.getElementById('exam-form').submit();
            });
        }
    }, 1000);

    // Sync Department Display to Hidden Field
    const deptDisplay = document.querySelector('input[name="department_display"]');
    const hiddenDept = document.getElementById('hidden-dept');
    deptDisplay.addEventListener('input', (e) => {
        hiddenDept.value = e.target.value;
    });

    // Audio Beep Context
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    function playBeep() {
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.value = 0.5;
        osc.start();
        setTimeout(() => osc.stop(), 200);
    }

    // Proctoring Logic Refined
    let alertCount = 0;
    const maxAlerts = 3;
    let missingFrames = 0;
    let isAlertActive = false;

    async function initAIProctoring() {
        try {
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
            ]);
            console.log('AI Models Loaded');
            setInterval(detectFace, 1500); 
        } catch (e) {
            console.error('AI Error:', e);
        }
    }

    async function detectFace() {
        const video = document.getElementById('webcam');
        if (!video || video.paused || video.ended || isAlertActive) return;

        const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
        
        if (detections.length === 0) {
            missingFrames++;
            if (missingFrames >= 3) { // 3 consecutive missed frames (approx 4.5s)
                triggerProctorAlert('No Face Detected!', `Stay visible to the camera. Alert ${alertCount + 1} of ${maxAlerts}.`);
                missingFrames = 0;
            }
        } else if (detections.length > 1) {
            triggerProctorAlert('Multiple Faces Detected!', 'Malpractice Detected: Only one person is allowed during the exam.');
            missingFrames = 0;
        } else {
            missingFrames = 0;
        }
    }

    function triggerProctorAlert(title, text) {
        alertCount++;
        playBeep();
        isAlertActive = true;
        
        const swalConfig = {
            title: title,
            text: text,
            icon: 'error',
            confirmButtonText: 'Continue Exam',
            confirmButtonColor: '#0F172A',
            allowOutsideClick: false,
            backdrop: `rgba(239, 68, 68, 0.4)`
        };

        if (alertCount >= maxAlerts) {
            swalConfig.title = 'Malpractice Termination';
            swalConfig.text = 'Multiple security violations detected. The exam is now permanently locked.';
            swalConfig.confirmButtonText = 'Exit Exam';
        }

        Swal.fire(swalConfig).then(() => {
            isAlertActive = false;
            if (alertCount >= maxAlerts) {
                lockExam();
            }
        });
    }

    function handleFacePresent() {
        isFaceMissing = false;
        missingFaceStartTime = null;
    }

    async function lockExam() {
        clearInterval(countdown);
        try {
            await fetch('../api/lock_assignment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ exam_id: <?php echo $exam_id; ?> })
            });
            
            window.location.reload(); // Will trigger the PHP lock screen
        } catch (e) {
            alert('Security Violation: Your exam has been terminated.');
            window.location.href = 'my-training.php';
        }
    }

    // Webcam Proctoring Initialization
    async function initWebcam() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            const videoElement = document.getElementById('webcam');
            videoElement.srcObject = stream;
            
            // Start AI after webcam is up
            initAIProctoring();
        } catch (error) {
            console.error('Webcam Access Denied:', error);
            Swal.fire({
                title: 'Camera Required',
                text: 'This exam requires an active camera for proctoring.',
                icon: 'warning'
            });
        }
    }
    
    <?php if ($exam['camera_enabled']): ?>
    document.addEventListener('DOMContentLoaded', initWebcam);
    <?php endif; ?>
</script>

<style>
    @keyframes pulse {
        from { transform: scale(1); }
        to { transform: scale(1.05); }
    }
    
    .live-dot {
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        animation: blink 1s infinite;
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    
    .option-label:hover {
        border-color: var(--primary-blue);
        background: rgba(11, 112, 183, 0.05);
        transform: translateX(5px);
    }
    
    .option-label input:checked + span {
        color: var(--primary-blue);
    }
    
    .option-label:has(input:checked) {
        border-color: var(--primary-blue);
        background: rgba(11, 112, 183, 0.08);
        box-shadow: 0 4px 15px rgba(11, 112, 183, 0.1);
    }

    .camera-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 2px solid rgba(11, 112, 183, 0.3);
        border-radius: 16px;
        pointer-events: none;
    }

    .scan-line {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: rgba(11, 112, 183, 0.5);
        box-shadow: 0 0 10px rgba(11, 112, 183, 0.8);
        animation: scan 3s infinite linear;
    }

    @keyframes scan {
        0% { top: 0%; }
        100% { top: 100%; }
    }

    .info-item {
        padding: 5px;
    }
</style>

<?php renderFooter(); ?>
