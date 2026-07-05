import { store, navigate } from '../app.js';
import { api } from '../api.js';

export default class StudioView {
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
                    <div class="video-container">
                        <video id="webcam-preview" autoplay playsinline></video>
                        <img id="active-overlay-preview" class="hidden-preview" src="" alt="Overlay Layer" />
                        <canvas id="fallback-canvas" style="display:none;"></canvas>
                    </div>
                    
                    <div class="controls-row">
                        <button id="btn-snap" class="action-btn-primary" disabled>Take Snapshot</button>
                        <input type="file" id="file-upload" accept="image/*" class="file-input-hidden" />
                        <label for="file-upload" class="action-btn-secondary">Upload Image File</label>
                    </div>

                    <div class="overlay-selection-grid">
                        <h3>Choose Your Overlay Asset</h3>
                        <div class="sticker-options">
                            <img src="/assets/stickers/frames.png" class="sticker-item" data-sticker="frames.png" alt="Frame" />
                            <img src="/assets/stickers/star.png" class="sticker-item" data-sticker="star.png" alt="Star" />
                            <img src="/assets/stickers/cat.png" class="sticker-item" data-sticker="cat.png" alt="Cat Ears" />
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
        this.bindUserInteractions();
        await this.loadUserSnapshots();
    }

    async initializeHardwareMedia() {
        const video = document.getElementById('webcam-preview');
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 }, audio: false });
            video.srcObject = this.stream;
        } catch (err) {
            console.warn("Camera streaming unavailable: Fallback file upload mode activated.");
            video.insertAdjacentHTML('afterend', `<div class="camera-error-banner">Hardware camera capture unlinked. Please upload a file below.</div>`);
        }
    }

    bindUserInteractions() {
        const snapBtn = document.getElementById('btn-snap');
        const overlayPreview = document.getElementById('active-overlay-preview');
        const fileUpload = document.getElementById('file-upload');
        
        // Handle sticker overlay asset selections
        this.container.querySelectorAll('.sticker-item').forEach(img => {
            img.addEventListener('click', (e) => {
                this.container.querySelectorAll('.sticker-item').forEach(i => i.classList.remove('selected'));
                e.target.classList.add('selected');
                this.selectedOverlay = e.target.getAttribute('data-sticker');
                
                overlayPreview.src = e.target.src;
                overlayPreview.classList.remove('hidden-preview');
                snapBtn.removeAttribute('disabled');
            });
        });

        // Trigger snapshot capture execution
        snapBtn.addEventListener('click', () => this.captureImageComposition());

        // Local File Upload Fallback Logic
        fileUpload.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                this.submitCompositionPayload(event.target.result);
            };
            reader.readAsDataURL(file);
        });
    }

    async loadUserSnapshots() {
        const feedContainer = document.getElementById('user-snapshots-feed');
        try {
            // Uses API utility shortcut directly
            const posts = await api.get('/posts?filter=mine');
            
            if (posts.length === 0) {
                feedContainer.innerHTML = `<p class="muted">No posts compiled yet.</p>`;
                return;
            }

            feedContainer.innerHTML = posts.map(post => `
                <div class="mini-post-card" data-id="${post.id}">
                    <img src="${post.image_path}" alt="User post thumbnail" />
                    <button class="delete-post-btn" data-id="${post.id}">&times;</button>
                </div>
            `).join('');

            // Bind deletion handlers dynamically
            feedContainer.querySelectorAll('.delete-post-btn').forEach(btn => {
                btn.addEventListener('click', (e) => this.deleteSnapshotElement(e.target.getAttribute('data-id')));
            });

        } catch (err) {
            feedContainer.innerHTML = `<p class="error-text">Failed to fetch local thumbnails.</p>`;
        }
    }

    async captureImageComposition() {
        const video = document.getElementById('webcam-preview');
        const canvas = document.getElementById('fallback-canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = 640;
        canvas.height = 480;
        
        // Paint active frame locally from streaming tracks buffer
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
            // High level payload delivery using api.js
            await api.post('/posts', {
                image: base64Image,
                overlay: this.selectedOverlay
            });

            alert('Composition successfully saved!');
            await this.loadUserSnapshots(); // Live list update
        } catch (err) {
            alert(`Error transmitting picture payload: ${err.message}`);
        }
    }

    async deleteSnapshotElement(postId) {
        if (!confirm('Are you certain you want to delete this specific creation?')) return;

        try {
            // Central delete router pipeline integration
            await api.delete(`/posts/${postId}`);
            await this.loadUserSnapshots(); // Live visual list refresh
        } catch (err) {
            alert(`Failed to execute asset removal: ${err.message}`);
        }
    }
}
