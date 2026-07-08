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
        const response = await fetch(`/api${url}`, {
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
        const response = await fetch(`/api${url}`, {
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