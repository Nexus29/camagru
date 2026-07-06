import { api } from '../api.js';

export default class Gallery {
    constructor(container) { 
        this.container = container; 
    }

    async render() {
        this.container.innerHTML = `
            <div class="discovery-header">
                <h2>Community Exploration Gallery</h2>
                <p>Browse global creations captured inside Camagru studio matrix pipelines.</p>
            </div>
            <div id="global-posts-grid" class="gallery-layout-grid">
                <div class="spinner-loader">Loading public feed...</div>
            </div>
        `;

        await this.loadGalleryPosts();
    }

    async loadGalleryPosts() {
        const gridContainer = document.getElementById('global-posts-grid');
        try {
            // Uses the centralized api.js handler for GET queries
            const posts = await api.get('/posts');

            if (!posts || posts.length === 0) {
                gridContainer.innerHTML = `
                    <div class="empty-feed-card">
                        <p>The exploration matrix database is currently unpopulated.</p>
                    </div>
                `;
                return;
            }

            gridContainer.innerHTML = posts.map(post => `
                <div class="gallery-post-card">
                    <img src="${post.image_path}" alt="Community creation" />
                    <div class="post-meta">
                        <span>By ${post.username || 'Anonymous'}</span>
                    </div>
                </div>
            `).join('');

        } catch (err) {
            gridContainer.innerHTML = `
                <div class="error-banner">
                    <p>Failed to sync global image matrix stream: ${err.message}</p>
                </div>
            `;
        }
    }
}
