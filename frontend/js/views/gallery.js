import { API } from '../api.js';

export class GalleryFeed {
    constructor() { this.container = document.getElementById('gallery-feed'); }
    async loadFeed() {
        try {
            const data = await API.get('/gallery/stream?page=1');
            this.container.innerHTML = data.posts.map(p => `
                <div style="background:var(--bg-surface); padding:1rem; margin-bottom:1rem;">
                    <img src="${p.url}" style="width:100%; max-width:500px;">
                    <p>❤️ <span>${p.likes_count}</span></p>
                </div>
            `).join('');
        } catch { this.container.innerHTML = "<p>No posts found.</p>"; }
    }
}
