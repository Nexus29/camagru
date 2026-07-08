import { store, navigate } from '../app.js';
import { api } from '../api.js';

export default class Studio {
    constructor(container) {
        this.container = container;
        this.stream = null;
        this.selectedOverlay = null;
    }

    async render() {
        if (!store.token) {
            navigate('/login');
            return;
        }

        this.container.innerHTML = `
            <div class="studio-workspace">
                <div class="capture-panel">
                    <h2>Creative Capture Studio</h2>
                    <div class="video-container" style="position: relative;">
                        <video id=\"webcam-preview\" autoplay playsinline></video>
                        <img id="active-overlay-preview" class="hidden-preview" src="" alt="Overlay Layer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; object-fit: contain;" />
                        <canvas id="fallback-canvas" style="display:none;"></canvas>
                    </div>
                    
                    <div class="controls-row">
                        <button id="btn-snap" class="action-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Take Snapshot</button>
                        <input type="file" id="file-upload" accept="image/*" class="file-input-hidden" />
                        <label for="file-upload" class="action-btn-secondary">Upload Image File</label>
                    </div>

                    <div class="overlay-selection-grid">
                        <h3>Choose Your Overlay Asset</h3>
                        <div class="sticker-options" style="display: flex; gap: 10px; margin-top: 10px;">
                            <img src="/images/overlays/frame1.png" class="selectable-sticker" data-src="/images/overlays/frame1.png" alt="Frame 1" style="width: 70px; height: 70px; cursor: pointer; border: 2px solid transparent; border-radius: 4px;" />
                            <img src="/images/overlays/frame2.png" class="selectable-sticker" data-src="/images/overlays/frame2.png" alt="Frame 2" style="width: 70px; height: 70px; cursor: pointer; border: 2px solid transparent; border-radius: 4px;" />
                            <img src="/images/overlays/frame3.png" class="selectable-sticker" data-src="/images/overlays/frame3.png" alt="Frame 3" style="width: 70px; height: 70px; cursor: pointer; border: 2px solid transparent; border-radius: 4px;" />
                        </div>
                    </div>
                </div>

                <div class="sidebar-panel">
                    <h3>Your Precious Gallery Snapshots</h3>
                    <div id="user-snapshots-tray" class="snapshots-tray">
                        <p class="tray-placeholder-notice">Loading your canvas vault history...</p>
                    </div>
                </div>
            </div>
        `;

        await this.initializeWebcamStream();
        await this.loadUserSnapshots();
        this.bindStudioEvents();
    }

    async initializeWebcamStream() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            const video = document.getElementById('webcam-preview');
            if (video) {
                video.srcObject = this.stream;
            }
        } catch (err) {
            console.warn("Webcam fallback initialization failed: ", err.message);
        }
    }

    async loadUserSnapshots() {
        const tray = document.getElementById('user-snapshots-tray');
        if (!tray) return;

        try {
            const data = await api.get('/posts');
            const userHistory = data.filter(post => post.username === store.user);

            if (userHistory.length === 0) {
                tray.innerHTML = `<p class="tray-placeholder-notice">No snapshot artifacts captured yet.</p>`;
                return;
            }

            tray.innerHTML = userHistory.map(post => `
                <div class="snapshot-card">
                    <img src="${post.image_path}" alt="User creation" />
                    <button class="delete-post-btn" data-id="${post.id}">&times;</button>
                </div>
            `).join('');

            // Bind individual card actions
            tray.querySelectorAll('.delete-post-btn').forEach(btn => {
                btn.addEventListener('click', () => this.deleteSnapshotElement(btn.dataset.id));
            });

        } catch (err) {
            tray.innerHTML = `<p class="tray-placeholder-notice" style="color: var(--accent);">Failed loading snapshots panel layout.</p>`;
        }
    }

    bindStudioEvents() {
        const snapBtn = document.getElementById('btn-snap');
        const fileUpload = document.getElementById('file-upload');
        const overlayPreview = document.getElementById('active-overlay-preview');
        const stickers = document.querySelectorAll('.selectable-sticker');

        // Handle sticker overlay choice selection matrix
        stickers.forEach(sticker => {
            sticker.addEventListener('click', () => {
                // 1. Reset border highlights on all stickers
                stickers.forEach(s => s.style.borderColor = 'transparent');

                // 2. Extract selected overlay source path parameters
                this.selectedOverlay = sticker.getAttribute('data-src');

                // 3. Highlight the chosen item visually
                sticker.style.borderColor = 'var(--accent, #6200ee)';

                // 4. Update the live preview layer image inside the viewfinder
                if (overlayPreview) {
                    overlayPreview.src = this.selectedOverlay;
                    overlayPreview.classList.remove('hidden-preview');
                }

                // 5. 🔓 Activate the picture-taking mechanism state configurations
                if (snapBtn) {
                    snapBtn.removeAttribute('disabled');
                    snapBtn.style.opacity = '1';
                    snapBtn.style.cursor = 'pointer';
                }
            });
        });

        // Capture trigger handling logic
        if (snapBtn) {
            snapBtn.addEventListener('click', () => {
                if (!this.selectedOverlay) return; // Fail-safe protection layer block
                this.executeViewportCapture();
            });
        }

        if (fileUpload) {
            fileUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = async (event) => {
                    await this.submitCompositionPayload(event.target.result);
                };
                reader.readAsDataURL(file);
            });
        }
    }

    async executeViewportCapture() {
        const video = document.getElementById('webcam-preview');
        const canvas = document.getElementById('fallback-canvas');
        if (!video || !canvas) return;

        const ctx = canvas.getContext('2d');
        canvas.width = 640;
        canvas.height = 480;
        
        ctx.drawImage(video, 0, 0, 640, 480);
        const base64Data = canvas.toDataURL('image/png');

        await this.submitCompositionPayload(base64Data);
    }

    async submitCompositionPayload(base64Image) {
        if (!this.selectedOverlay) {
            alert('Please select a visual sticker layer frame first.');
            return;
        }

        try {
            await api.post('/posts', {
                image: base64Image,
                overlay: this.selectedOverlay
            });

            alert('Composition successfully saved!');
            await this.loadUserSnapshots();
        } catch (err) {
            alert(`Error transmitting picture payload: ${err.message}`);
        }
    }

    async deleteSnapshotElement(postId) {
        if (!confirm('Are you certain you want to delete this specific creation?')) return;

        try {
            await api.delete(`/posts/${postId}`);
            await this.loadUserSnapshots();
        } catch (err) {
            alert(`Failed to execute asset removal: ${err.message}`);
        }
    }
}
