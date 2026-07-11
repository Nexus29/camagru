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
                    <div class="video-container" style="position: relative; max-width: 640px; aspect-ratio: 4/3; margin: 0 auto; background: #000;">
                        <video id="webcam-preview" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <img id="active-overlay-preview" class="hidden-preview" src="" alt="Overlay Layer" style="position: absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; object-fit:contain;" />
                        <canvas id="fallback-canvas" style="display:none;"></canvas>
                    </div>
                    
                    <div class="controls-row" style="text-align: center; margin-top: 15px;">
                        <button id="btn-snap" class="action-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed; padding: 10px 20px; border-radius: 4px; border: none; font-weight: bold;">Take Snapshot</button>
                        <input type="file" id="file-upload" accept="image/*" class="file-input-hidden" style="display: none;" />
                        <label for="file-upload" class="action-btn-secondary" style="margin-left: 10px; cursor: pointer;">Upload Image File</label>
                    </div>

                    <div class="overlay-selection-grid" style="margin-top: 25px;">
                        <h3>Choose Your RetroPie Border Filter</h3>
                        <div id="dynamic-sticker-options" class="sticker-options" style="display: flex; gap: 12px; margin-top: 10px; flex-wrap: wrap; justify-content: center;">
                            </div>
                    </div>
                </div>

                <div class="sidebar-history">
                    <h3>Your Snapshots</h3>
                    <div id="user-snapshots-feed" class="mini-gallery">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        `;

        await this.initializeHardwareMedia();
        this.loadFrontendOverlays(); 
        await this.loadUserSnapshots();
    }

    async initializeHardwareMedia() {
        const video = document.getElementById('webcam-preview');
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 }, audio: false });
            video.srcObject = this.stream;
        } catch (err) {
            console.warn("Camera streaming unavailable.");
            video.insertAdjacentHTML('afterend', `<div class="camera-error-banner" style="color: #ff5555; padding: 10px; text-align:center;">Camera offline. File upload active.</div>`);
        }
    }

    loadFrontendOverlays() {
        const stickerTray = document.getElementById('dynamic-sticker-options');
        if (!stickerTray) return;

        const overlays = [
            { name: 'Retro CRT Border', url: '/uploads/overlays/crt-border.png' },
            { name: 'Retro NES Overlay', url: '/uploads/overlays/nes-overlay.png' },
            { name: 'Vintage DOS Border', url: '/uploads/overlays/dos-border.png' }
        ];
		
        stickerTray.innerHTML = overlays.map(file => `
            <img src="${file.url}" 
                 class="sticker-item real-overlay-asset" 
                 data-sticker-url="${file.url}" 
                 alt="${file.name}" 
                 title="${file.name}"
                 style="width: 85px; height: 65px; object-fit: contain; cursor: pointer; border: 2px solid transparent; border-radius: 4px; background: #1a1a1a; padding: 2px;" />
        `).join('');

        this.bindUserInteractions();
    }

    bindUserInteractions() {
        const snapBtn = document.getElementById('btn-snap');
        const overlayPreview = document.getElementById('active-overlay-preview');
        const fileUpload = document.getElementById('file-upload');
        const stickerItems = this.container.querySelectorAll('.real-overlay-asset');
        
        stickerItems.forEach(img => {
            img.addEventListener('click', (e) => {
                stickerItems.forEach(i => i.style.borderColor = 'transparent');
                e.target.style.borderColor = '#6200ee';
                
                this.selectedOverlay = e.target.getAttribute('data-sticker-url');
                
                overlayPreview.src = e.target.src;
                overlayPreview.classList.remove('hidden-preview');
                
                snapBtn.removeAttribute('disabled');
                snapBtn.style.opacity = '1';
                snapBtn.style.cursor = 'pointer';
            });
        });

        snapBtn.onclick = () => this.captureAndSubmitComposition();

        fileUpload.onchange = (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                this.submitCompositionPayload(event.target.result);
            };
            reader.readAsDataURL(file);
        };
    }

    async loadUserSnapshots() {
        const feedContainer = document.getElementById('user-snapshots-feed');
        try {
            const posts = await api.get('/posts?filter=mine');
            if (posts.length === 0) {
                feedContainer.innerHTML = `<p class="muted">No posts compiled yet.</p>`;
                return;
            }
            feedContainer.innerHTML = posts.map(post => `
                <div class="mini-post-card" data-id="${post.id}">
                    <img src="${post.image_path}" alt="Thumbnail" />
                    <button class="delete-post-btn" data-id="${post.id}">&times;</button>
                </div>
            `).join('');

            feedContainer.querySelectorAll('.delete-post-btn').forEach(btn => {
                btn.onclick = (e) => this.deleteSnapshotElement(e.target.getAttribute('data-id'));
            });
        } catch (err) {
            feedContainer.innerHTML = `<p class="error-text">Failed to fetch thumbnails.</p>`;
        }
    }

    async captureAndSubmitComposition() {
        const video = document.getElementById('webcam-preview');
        const canvas = document.getElementById('fallback-canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = 640;
        canvas.height = 480;
        ctx.drawImage(video, 0, 0, 640, 480);
        
        await this.submitCompositionPayload(canvas.toDataURL('image/png'));
    }

    async submitCompositionPayload(base64Image) {
        if (!this.selectedOverlay) return;

        try {
            await api.post('/posts', {
                image: base64Image,
                overlay: this.selectedOverlay
            });
            alert('Composition successfully saved to database!');
            await this.loadUserSnapshots(); 
        } catch (err) {
            alert(`Error transmitting snapshot payload: ${err.message}`);
        }
    }

    async deleteSnapshotElement(postId) {
        if (!confirm('Are you certain you want to delete this specific creation?')) return;
        try {
            await api.post(`/posts/delete`, { id: postId });
            await this.loadUserSnapshots();
        } catch (err) {
            alert(`Failed to execute asset removal: ${err.message}`);
        }
    }
}
