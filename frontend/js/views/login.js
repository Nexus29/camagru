import { store, navigate } from '../app.js';
import { api } from '../api.js';

export default class Login {
    constructor(container) {
        this.container = container;
    }

    async render() {
        this.container.innerHTML = `
            <div class="auth-form-card">
                <h2>Welcome Back</h2>
                <div id="auth-error-banner" class="error-banner hidden-preview"></div>
                <form id="form-login">
                    <input type="text" id="auth-username" placeholder="Username" required />
                    <input type="password" id="auth-password" placeholder="Password" required />
                    <button type="submit" class="action-btn-primary">Authenticate Account</button>
                </form>
            </div>
        `;

        this.bindEvents();
    }

    bindEvents() {
        const form = document.getElementById('form-login');
        const errorBanner = document.getElementById('auth-error-banner');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBanner.classList.add('hidden-preview');

            const username = document.getElementById('auth-username').value.trim();
            const password = document.getElementById('auth-password').value;

            try {
                // Central API call replacing raw fetch blocks
                const response = await api.post('/users', { username, password });
                
                // Store authentication state on success
                localStorage.setItem('token', response.token);
                store.token = response.token;
                store.user = username;

                // Route inside the app space smoothly
                navigate('/studio');
            } catch (err) {
                errorBanner.textContent = err.message || 'Authentication sequence failed.';
                errorBanner.classList.remove('hidden-preview');
            }
        });
    }
}