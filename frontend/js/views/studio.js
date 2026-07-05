import { API } from '../api.js';

export class CameraStudio {
    constructor() {
        this.video = document.getElementById('webcam');
        this.canvas = document.getElementById('layer-preview-canvas');
        this.captureBtn = document.getElementById('capture-btn');
        this.fallbackInput = document.getElementById('file-fallback-input');
        this.selectedMaskUrl = null;
        this.stream = null;
    }
    async init() {
        this.setupMasks();
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
            this.video.srcObject = this.stream;
        } catch {
            document.getElementById('camera-viewscreen').innerHTML = "<p style='padding:2rem;'>Camera error. Use file upload below.</p>";
        }
        this.captureBtn.onclick = () => this.capture();
    }
    setupMasks() {
        document.querySelectorAll('.mask-item').forEach(item => {
            item.onclick = (e) => {
                document.querySelectorAll('.mask-item').forEach(i => i.classList.remove('selected'));
                const target = e.target.closest('.mask-item');
                target.classList.add('selected');
                this.selectedMaskUrl = target.getAttribute('data-mask-src');
                this.captureBtn.disabled = false;
                
                const ctx = this.canvas.getContext('2d');
                const img = new Image();
                img.src = this.selectedMaskUrl;
                img.onload = () => { ctx.clearRect(0,0,640,480); ctx.drawImage(img,0,0,640,480); };
            };
        });
    }
    async capture() {
        const fd = new FormData();
        fd.append('mask', this.selectedMaskUrl);
        if (this.stream) {
            const c = document.createElement('canvas'); c.width = 640; c.height = 480;
            c.getContext('2d').drawImage(this.video, 0, 0, 640, 480);
            const blob = await new Promise(r => c.toBlob(r, 'image/jpeg'));
            fd.append('image', blob, 'pic.jpg');
        } else {
            if (!this.fallbackInput.files[0]) return alert('Upload an image');
            fd.append('image', this.fallbackInput.files[0]);
        }
        await API.post('/studio/composite', fd);
        alert('Image saved successfully!');
    }
}
