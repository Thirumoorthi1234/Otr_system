/**
 * assets/js/camera-monitor.js
 * Continuous session proctoring & OJT evidence capture (automated background mode)
 */

(function() {
    let videoStream = null;
    let captureInterval = null;
    const INTERVAL_MS = 5 * 60 * 1000; // 5 minutes

    function initCameraMonitor() {
        if (document.getElementById('global-cam-monitor')) return;

        // Create UI
        const monitorDiv = document.createElement('div');
        monitorDiv.id = 'global-cam-monitor';
        monitorDiv.style.cssText = `
            position: fixed; bottom: 20px; right: 20px; width: 150px; height: 112px;
            background: #000; border-radius: 12px; overflow: hidden; border: 3px solid #38bdf8;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3); z-index: 9999;
            transition: transform 0.3s; cursor: move;
        `;

        const video = document.createElement('video');
        video.id = 'gc-video';
        video.autoplay = true;
        video.playsInline = true;
        video.muted = true;
        video.style.cssText = 'width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);';

        const canvas = document.createElement('canvas');
        canvas.style.display = 'none';

        const statusDot = document.createElement('div');
        statusDot.style.cssText = 'position: absolute; top: 8px; left: 8px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444;';

        const label = document.createElement('div');
        label.innerText = 'LIVE MONITORING';
        label.style.cssText = 'position: absolute; top: 6px; left: 22px; font-size: 0.6rem; color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;';

        monitorDiv.appendChild(video);
        monitorDiv.appendChild(canvas);
        monitorDiv.appendChild(statusDot);
        monitorDiv.appendChild(label);
        document.body.appendChild(monitorDiv);

        // Access Camera
        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(stream => {
                videoStream = stream;
                video.srcObject = stream;
                statusDot.style.background = '#10b981';
                statusDot.style.boxShadow = '0 0 10px #10b981';
                
                // Start capture interval
                startAutoCapture(video, canvas);
            })
            .catch(err => {
                console.warn('Camera access denied for monitoring:', err);
                monitorDiv.style.borderColor = '#ef4444';
                label.innerText = 'CAMERA BLOCKED';
            });

        // Draggable logic
        makeDraggable(monitorDiv);
    }

    function startAutoCapture(video, canvas) {
        if (captureInterval) clearInterval(captureInterval);
        
        // Initial capture after 10 seconds
        setTimeout(() => captureAndUpload(video, canvas), 10000);

        captureInterval = setInterval(() => {
            captureAndUpload(video, canvas);
        }, INTERVAL_MS);
    }

    function captureAndUpload(video, canvas) {
        if (!video.srcObject) return;
        
        canvas.width = 640;
        canvas.height = 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, 640, 480);
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        
        fetch(window.BASE_URL + 'trainee/upload_proctoring.php', {
            method: 'POST',
            body: JSON.stringify({ image: imageData }),
            headers: { 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('Session proctoring photo captured:', data.path);
            }
        })
        .catch(err => console.error('Proctoring upload failed:', err));
    }

    function makeDraggable(el) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        el.onmousedown = dragMouseDown;

        function dragMouseDown(e) {
            e = e || window.event;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }

        function elementDrag(e) {
            e = e || window.event;
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            el.style.top = (el.offsetTop - pos2) + "px";
            el.style.left = (el.offsetLeft - pos1) + "px";
            el.style.bottom = 'auto'; el.style.right = 'auto'; // Break initial positioning
        }

        function closeDragElement() {
            document.onmouseup = null;
            document.onmousemove = null;
        }
    }

    // Initialize if on dashboard or training pages
    const path = window.location.pathname;
    if (path.includes('/trainee/')) {
        // Delay initialization to ensure page is loaded
        window.addEventListener('load', initCameraMonitor);
    }
})();
