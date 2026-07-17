const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? '/api' 
    : 'https://camagru-backend.onrender.com'; // 👈 It matches perfectly! // 👈 Replace with your actual live Render URL later

export const api = {

    getHeaders() {
        const headers = { 'Content-Type': 'application/json' };
        const token = localStorage.getItem('token');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        return headers;
    },

    async post(url, data) {
        // Combines the base path dynamically with your endpoint parameters
        const response = await fetch(`${API_BASE_URL}${url}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'Network execution transaction layer failure.');
        }

        return response.json();
    },

    async get(url) {
        // Combines the base path dynamically with your endpoint parameters
        const response = await fetch(`${API_BASE_URL}${url}`, {
            method: 'GET',
            headers: this.getHeaders()
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'Network query retrieval layer failure.');
        }

        return response.json();
    }
};
