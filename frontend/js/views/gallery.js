import { api } from '../api.js';
import { store } from '../app.js';

export default class Gallery {
    constructor(container) { 
        this.container = container;
    }

    async render() {
        this.container.innerHTML = `
            <div class="discovery-header">
                <h2>Community Exploration Gallery</h2>
                <p>Browse global creations captured inside Camagru studio matrix pipelines.</p>
                
                <!-- 🔍 BONUS FEATURE 1: Live Frontend Filter Search Input -->
                <div class="search-container" style="margin-top: 15px;">
                    <input type="text" id="gallery-search" placeholder="🔍 Filter by creator username..." 
                        style="padding: 8px 12px; width: 100%; max-width: 300px; border-radius: 4px; border: 1px solid var(--border, #ccc); background: transparent; color: inherit;" />
                </div>
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
            const posts = await api.get('/posts');

            if (!posts || posts.length === 0) {
                gridContainer.innerHTML = `
                    <div class="empty-feed-card">
                        <p>The exploration matrix database is currently unpopulated.</p>
                    </div>
                `;
                return;
            }

            const isAuthenticated = !!store.token;
            const searchInput = document.getElementById('gallery-search');

            // Dynamically filters and renders posts based on user input
            const renderFilteredGrid = (searchTerm = '') => {
                const filteredPosts = posts.filter(post => 
                    (post.username || 'Anonymous').toLowerCase().includes(searchTerm.toLowerCase())
                );

                if (filteredPosts.length === 0) {
                    gridContainer.innerHTML = `
                        <div class="empty-feed-card">
                            <p>No creators match your active exploration filters.</p>
                        </div>
                    `;
                    return;
                }

                gridContainer.innerHTML = filteredPosts.map(post => {
                    const commentsHtml = (post.comments || []).map(c => `
                        <div class="comment-item"><strong>${c.username}:</strong> ${c.text}</div>
                    `).join('');

                    return `
                        <div class="gallery-post-card" data-id="${post.id}">
                            <img src="${post.image_path}" alt="Community creation" />
                            <div class="post-meta">
                                <span>By ${post.username || 'Anonymous'}</span>
                                <div class="post-actions-group" style="display: flex; gap: 8px; align-items: center;">
                                    <button class="like-btn ${post.user_liked ? 'active-like' : ''}" ${!isAuthenticated ? 'disabled' : ''}>
                                        ❤️ <span class="count">${post.likes_count || 0}</span>
                                    </button>

                                    <!-- 💾 BONUS FEATURE 2: Client-Side Image Download -->
                                    <a href="${post.image_path}" download="camagru_snapshot_${post.id}.png" class="download-btn" style="text-decoration: none; border: 1px solid var(--border, #ccc); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; color: inherit; display: inline-flex; align-items: center; gap: 4px;">
                                        💾 Save Image
                                    </a>
                                </div>
                            </div>
                            <div class="comments-section">
                                <div class="comments-list">${commentsHtml}</div>
                                ${isAuthenticated ? `
                                    <div class="comment-input-row">
                                        <input type="text" class="comment-box" placeholder="Add a comment..." />
                                        <button class="send-comment-btn">Post</button>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('');

                this.bindSocialInteractions();
            };

            // Run initial population
            renderFilteredGrid();

            // Intercept search typing input events
            if (searchInput) {
                searchInput.oninput = (e) => renderFilteredGrid(e.target.value.trim());
            }

        } catch (err) {
            gridContainer.innerHTML = `
                <div class="error-banner">
                    <p>Failed to sync global image matrix stream: ${err.message}</p>
                </div>
            `;
        }
    }

    bindSocialInteractions() {
        const isAuthenticated = !!store.token;
        const cards = this.container.querySelectorAll('.gallery-post-card');
        
        cards.forEach(card => {
            const postId = card.getAttribute('data-id');
            const likeBtn = card.querySelector('.like-btn');
            const sendBtn = card.querySelector('.send-comment-btn');
            const commentBox = card.querySelector('.comment-box');

            if (isAuthenticated) {
                likeBtn.onclick = async () => {
                    try {
                        const res = await api.post('/posts/like', { post_id: postId });
                        const countSpan = likeBtn.querySelector('.count');
                        let currentCount = parseInt(countSpan.textContent);
                        
                        if (res.liked) {
                            likeBtn.classList.add('active-like');
                            countSpan.textContent = currentCount + 1;
                        } else {
                            likeBtn.classList.remove('active-like');
                            countSpan.textContent = currentCount - 1;
                        }
                    } catch (err) {
                        console.error('Like error:', err);
                    }
                };

                if (sendBtn && commentBox) {
                    sendBtn.onclick = async () => {
                        const text = commentBox.value.trim();
                        if (!text) return;

                        try {
                            await api.post('/posts/comment', { post_id: postId, text: text });
                            commentBox.value = '';
                            await this.loadGalleryPosts(); 
                        } catch (err) {
                            alert('Could not submit comment.');
                        }
                    };
                }
            }
        });
    }
}
