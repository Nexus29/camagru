import { store, navigate } from './app.js';

/**
 * --- CAMAGRU CENTRALIZED FETCH API UTILITY ---
 */
class ApiClient {
    constructor() {
        // Points natively to Nginx routing rules on the same origin host
        this.baseUrl = '/api'; 
    }

    /**
     * Core Request Wrapper Pipeline
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        // Setup standard production headers matrix
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        // If user is authenticated, automatically append token credentials
        if (store.token) {
            headers['Authorization'] = `Bearer ${store.token}`;
        }

        const config = {
            ...options,
            headers: {
                ...headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            // Intercept global authorization collapse states
            if (response.status === 401 || response.status === 403) {
                this.handleSessionExpiry();
                throw new Error(data.error || 'Session unauthorized.');
            }

            if (!response.ok) {
                throw new Error(data.error || `HTTP Network Error: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error(`[API Failure] Vector: ${endpoint} ->`, error.message);
            throw error;
        }
    }

    // HTTP Method Shortcuts for your views
    async get(endpoint) { return this.request(endpoint, { method: 'GET' }); }
    async post(endpoint, body) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(body) }); }
    async delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }

    handleSessionExpiry() {
        localStorage.removeItem('token');
        store.token = null;
        store.user = null;
        navigate('/login');
    }
}

export const api = new ApiClient();
