<?php
// trainee/ojt_camera.php — OJT Evidence Photo Capture
require_once '../includes/layout.php';
checkRole('trainee');

$trainee_id = $_SESSION['user_id'];

// Get trainee's active assignments
$stmt = $pdo->prepare("
    SELECT a.id, a.status, m.title as module_name, a.assigned_date
    FROM assignments a
    JOIN training_modules m ON a.module_id = m.id
    WHERE a.trainee_id = ? AND a.status IN ('in_progress','not_started')
    ORDER BY a.assigned_date DESC
");
$stmt->execute([$trainee_id]);
$assignments = $stmt->fetchAll();

// Get previously captured photos
$photos_stmt = $pdo->prepare("
    SELECT oe.*, a_mod.title as module_name
    FROM ojt_evidence oe
    JOIN assignments a ON oe.assignment_id = a.id
    JOIN training_modules a_mod ON a.module_id = a_mod.id
    WHERE oe.trainee_id = ?
    ORDER BY oe.captured_at DESC
    LIMIT 20
");
$photos_stmt->execute([$trainee_id]);
$photos = $photos_stmt->fetchAll();

renderHeader('OJT Evidence Capture');
renderSidebar('trainee');
?>

<style>
    .camera-container { background: #0f172a; border-radius: 20px; padding: 25px; position: relative; }
    .camera-preview { width: 100%; max-width: 480px; aspect-ratio: 4/3; border-radius: 14px; background: #000; overflow: hidden; position: relative; display: block; margin: 0 auto; }
    .camera-preview video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block; }
    .camera-preview canvas { display: none; }
    .camera-overlay-ui { position: absolute; inset: 0; border-radius: 14px; border: 2px solid rgba(56,189,248,0.4); pointer-events: none; }
    .corner { position: absolute; width: 20px; height: 20px; border-color: #38bdf8; border-style: solid; }
    .corner.tl { top: 10px; left: 10px; border-width: 3px 0 0 3px; }
    .corner.tr { top: 10px; right: 10px; border-width: 3px 3px 0 0; }
    .corner.bl { bottom: 10px; left: 10px; border-width: 0 0 3px 3px; }
    .corner.br { bottom: 10px; right: 10px; border-width: 0 3px 3px 0; }

    .capture-btn { width: 64px; height: 64px; border-radius: 50%; background: #fff; border: 5px solid #38bdf8; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto; transition: transform 0.15s, box-shadow 0.15s; box-shadow: 0 0 0 4px rgba(56,189,248,0.2); }
    .capture-btn:hover { transform: scale(1.08); box-shadow: 0 0 0 8px rgba(56,189,248,0.25); }
    .capture-btn:active { transform: scale(0.95); }

    .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-top: 25px; }
    .photo-item { border-radius: 14px; overflow: hidden; border: 2px solid var(--border-color); background: var(--card-bg); position: relative; }
    .photo-item img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; }
    .photo-caption { padding: 8px 10px; font-size: 0.78rem; color: var(--text-muted); font-weight: 600; }
    .photo-date { font-size: 0.7rem; color: #94a3b8; }
    .snap-flash { position: absolute; inset: 0; background: white; opacity: 0; border-radius: 14px; pointer-events: none; transition: opacity 0.05s; }
</style>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; max-width: 1100px; margin: auto;">
    <!-- Camera Panel -->
    <div>
        <div class="card" style="border-radius: 20px; padding: 25px;">
            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-camera" style="color: #38bdf8;"></i> Capture OJT Evidence
            </h3>

            <?php if (empty($assignments)): ?>
            <div style="text-align:center; padding: 40px 20px; color: var(--text-muted);">
                <i class="fas fa-clipboard-list" style="font-size: 2.5rem; margin-bottom: 15px; opacity:0.4;"></i>
                <p>No active OJT assignments found.<br>Your trainer needs to assign you a module first.</p>
            </div>
            <?php else: ?>

            <div class="form-group">
                <label class="form-label">Select Assignment</label>
                <select id="assignment-select" class="form-control">
                    <?php foreach ($assignments as $a): ?>
                    <option value="<?php echo $a['id']; ?>"><?php echo e($a['module_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Caption / Activity Description</label>
                <input type="text" id="photo-caption" class="form-control" placeholder="e.g., Soldering operation at Station 4" value="OJT Activity Evidence">
            </div>

            <!-- Camera Preview -->
            <div class="camera-container" style="margin-bottom: 20px;">
                <div class="camera-preview" id="camera-wrap">
                    <video id="camera-video" autoplay playsinline muted></video>
                    <canvas id="camera-canvas"></canvas>
                    <div class="camera-overlay-ui">
                        <div class="corner tl"></div><div class="corner tr"></div>
                        <div class="corner bl"></div><div class="corner br"></div>
                    </div>
                    <div class="snap-flash" id="snap-flash"></div>
                </div>
                <div style="text-align: center; margin-top: 18px; display: flex; align-items: center; justify-content: center; gap: 20px;">
                    <button class="capture-btn" id="capture-btn" title="Take Photo">
                        <i class="fas fa-camera" style="color: #0f172a;"></i>
                    </button>
                </div>
                <div id="camera-status" style="text-align:center; color:#94a3b8; font-size:0.8rem; margin-top:10px;">
                    <i class="fas fa-spinner fa-spin"></i> Starting camera…
                </div>
            </div>

            <!-- Preview of last capture -->
            <div id="preview-area" style="display:none;">
                <div style="position:relative; border-radius: 12px; overflow:hidden; border: 3px solid #38bdf8;">
                    <img id="preview-img" src="" style="width:100%; display:block;">
                    <div style="position:absolute; top:10px; right:10px; display:flex; gap:8px;">
                        <button onclick="retake()" class="btn" style="background:rgba(15,23,42,0.8); color:#fff; padding: 6px 14px; font-size:0.8rem; border-radius:8px;">
                            <i class="fas fa-redo"></i> Retake
                        </button>
                        <button onclick="savePhoto()" id="save-btn" class="btn btn-primary" style="padding: 6px 14px; font-size:0.8rem; border-radius:8px;">
                            <i class="fas fa-save"></i> Save Evidence
                        </button>
                    </div>
                </div>
                <div id="save-status" style="margin-top:10px; font-size:0.85rem; font-weight:600; text-align:center;"></div>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- Gallery Panel -->
    <div>
        <div class="card" style="border-radius: 20px; padding: 25px;">
            <h3 style="margin-bottom: 5px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-images" style="color: #a78bfa;"></i> Captured Evidence
                <span class="badge badge-info"><?php echo count($photos); ?></span>
            </h3>
            <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom: 20px;">Photos captured during OJT for trainer review.</p>

            <?php if (empty($photos)): ?>
            <div style="text-align:center; padding:40px; color:var(--text-muted);">
                <i class="fas fa-camera-slash" style="font-size:2rem; opacity:0.3; margin-bottom:12px;"></i>
                <p>No photos captured yet.</p>
            </div>
            <?php else: ?>
            <div class="photo-grid">
                <?php foreach ($photos as $photo): ?>
                <div class="photo-item" id="photo-<?php echo $photo['id']; ?>">
                    <img src="<?php echo BASE_URL . e($photo['photo_path']); ?>" alt="OJT Evidence" loading="lazy">
                    <button onclick="deletePhoto(<?php echo $photo['id']; ?>)" 
                            style="position:absolute; top:8px; right:8px; background:rgba(239, 68, 68, 0.9); color:#fff; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 4px rgba(0,0,0,0.2); transition:transform 0.15s;"
                            title="Delete this image" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <i class="fas fa-trash-alt" style="font-size:0.85rem;"></i>
                    </button>
                    <div class="photo-caption">
                        <?php echo e($photo['caption'] ?? 'OJT Evidence'); ?>
                        <div class="photo-date"><?php echo date('d M Y, H:i', strtotime($photo['captured_at'])); ?></div>
                        <div style="font-size:0.7rem; color:#a78bfa;"><?php echo e($photo['module_name']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let stream = null;
let capturedBlob = null;

async function initCamera() {
    const statusEl = document.getElementById('camera-status');
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } });
        const video = document.getElementById('camera-video');
        video.srcObject = stream;
        if (statusEl) statusEl.innerHTML = '<i class="fas fa-circle" style="color:#22c55e;"></i> Camera Active';
    } catch (err) {
        if (statusEl) statusEl.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> Camera access denied. Please allow camera permissions.';
        if (document.getElementById('capture-btn')) document.getElementById('capture-btn').disabled = true;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const captureBtn = document.getElementById('capture-btn');
    if (captureBtn) {
        initCamera();
        captureBtn.addEventListener('click', capturePhoto);
    }
});

function capturePhoto() {
    const video   = document.getElementById('camera-video');
    const canvas  = document.getElementById('camera-canvas');
    const flash   = document.getElementById('snap-flash');

    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Flash animation
    if (flash) { flash.style.opacity = '1'; setTimeout(() => flash.style.opacity = '0', 150); }

    const dataURL = canvas.toDataURL('image/jpeg', 0.85);
    document.getElementById('preview-img').src = dataURL;
    capturedBlob = dataURL;

    document.getElementById('camera-wrap').style.display = 'none';
    document.getElementById('capture-btn').closest('div').style.display = 'none';
    document.getElementById('preview-area').style.display = 'block';
}

function retake() {
    document.getElementById('camera-wrap').style.display = 'block';
    document.getElementById('capture-btn').closest('div').style.display = 'flex';
    document.getElementById('preview-area').style.display = 'none';
    document.getElementById('save-status').textContent = '';
    capturedBlob = null;
}

async function savePhoto() {
    if (!capturedBlob) return;
    const btn = document.getElementById('save-btn');
    const status = document.getElementById('save-status');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
    status.textContent = '';

    const assignmentId = document.getElementById('assignment-select')?.value;
    const caption      = document.getElementById('photo-caption')?.value || 'OJT Evidence';

    try {
        const res = await fetch('<?php echo BASE_URL; ?>api/save_ojt_photo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: capturedBlob, assignment_id: parseInt(assignmentId), caption })
        });
        const data = await res.json();
        if (data.success) {
            status.innerHTML = '<span style="color:#22c55e;"><i class="fas fa-check-circle"></i> Photo saved successfully!</span>';
            btn.innerHTML = '<i class="fas fa-check"></i> Saved';
            setTimeout(() => { retake(); location.reload(); }, 1800);
        } else {
            status.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-times"></i> Error: ' + (data.error || 'Unknown error') + '</span>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Evidence';
        }
    } catch(e) {
        status.innerHTML = '<span style="color:#ef4444;">Network error. Please try again.</span>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Evidence';
    }
}

async function deletePhoto(id) {
    const result = await Swal.fire({
        title: 'Delete this image?',
        text: "You cannot undo this action.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        try {
            const res = await fetch('<?php echo BASE_URL; ?>api/delete_ojt_photo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById('photo-' + id);
                if (el) el.remove();
                Swal.fire({ icon: 'success', title: 'Deleted', text: 'Photo removed successfully.', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire('Error', data.error || 'Failed to delete.', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        }
    }
}
</script>

<?php renderFooter(); ?>
