import { store, navigate } from './app.js';

/**
 * --- CAMAGRU LOCAL WORKSPACE EMULATOR & MOCK API CLIENT ---
 * Safely mimics a live backend server to fully render and test frontend routing layouts.
 */
class ApiClient {
    constructor() {
        this.baseUrl = '/api'; 
    }

    async request(endpoint, options = {}) {
        console.log(`[Mock Network Intercept] Outgoing Target Vector: ${options.method || 'GET'} -> ${endpoint}`);

        // Introduce a slight delay to simulate normal network lag (proves spinner animations work)
        await new Promise(resolve => setTimeout(resolve, 400));

        // 🔐 ROUTE: Login Matrix Simulation
        if (endpoint === '/users' && options.method === 'POST') {
            const body = JSON.parse(options.body);
            if (body.username && body.password) {
                return { token: 'mock_jwt_token_camagru_auth', username: body.username };
            }
            throw new Error('Invalid user structural parameters.');
        }

        // 📸 ROUTE: Studio Snapshots Feed Data Generation
        if (endpoint.startsWith('/posts?filter=mine') && options.method === 'GET') {
            // Checks storage or defaults to pre-filled dummy items
            const localizedData = localStorage.getItem('mock_user_posts');
            return localizedData ? JSON.parse(localizedData) : [
                { id: 101, image_path: "https://picsum.photos/640/480?random=1" },
                { id: 102, image_path: "https://picsum.photos/640/480?random=2" }
            ];
        }

        // 📤 ROUTE: Uploading/Saving Compositions
        if (endpoint === '/posts' && options.method === 'POST') {
            const body = JSON.parse(options.body);
            const localizedData = localStorage.getItem('mock_user_posts');
            const posts = localizedData ? JSON.parse(localizedData) : [];
            
            // Add new element to local mock state memory
            posts.unshift({
                id: Date.now(),
                // If camera isn't transmitting live frames, use a visual fallback asset
                image_path: body.image.startsWith('data:') ? body.image : "https://picsum.photos/640/480?random=3"
            });
            
            localStorage.setItem('mock_user_posts', JSON.stringify(posts));
            return { status: "Success", message: "Composite layer rendered." };
        }

        // 🗑️ ROUTE: Removing Images from Sidebar
        if (endpoint.startsWith('/posts/') && options.method === 'DELETE') {
            const idToDelete = parseInt(endpoint.split('/').pop());
            const localizedData = localStorage.getItem('mock_user_posts');
            if (localizedData) {
                let posts = JSON.parse(localizedData);
                posts = posts.filter(p => p.id !== idToDelete);
                localStorage.setItem('mock_user_posts', JSON.stringify(posts));
            }
            return { status: "Success" };
        }

        // Fallback for gallery landing pages
        return [];
    }

    async get(endpoint) { return this.request(endpoint, { method: 'GET' }); }
    async post(endpoint, body) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(body) }); }
    async delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }
}

export const api = new ApiClient();
