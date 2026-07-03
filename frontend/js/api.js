class ApiClient {
    constructor() { this.baseUrl = '/api'; this.csrfToken = null; }
    async request(endpoint, options = {}) {
        options.headers = { 'Accept': 'application/json', ...options.headers };
        if (this.csrfToken && ['POST', 'DELETE'].includes(options.method)) {
            options.headers['X-CSRF-Token'] = this.csrfToken;
        }
        options.credentials = 'include';
        const res = await fetch(`${this.baseUrl}${endpoint}`, options);
        const data = await res.json();
        if (data.csrfToken) this.csrfToken = data.csrfToken;
        if (!res.ok) throw new Error(data.message || 'API Error');
        return data;
    }
    get(end) { return this.request(end, { method: 'GET' }); }
    post(end, body) {
        const isFD = body instanceof FormData;
        return this.request(end, {
            method: 'POST',
            headers: isFD ? {} : { 'Content-Type': 'application/json' },
            body: isFD ? body : JSON.stringify(body)
        });
    }
}
export const API = new ApiClient();
