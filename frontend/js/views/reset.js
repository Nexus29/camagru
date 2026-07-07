import { api } from '../api.js';

export const ResetPassword = {
    render: () => `
        <div class="auth-form-card">
            <h2 class="auth-form-heading">New Password</h2>
            <p class="auth-form-subtitle">Enter your new secure password configuration below</p>
            <div id="reset-error" class="error-banner" style="display: none;"></div>
            <div id="reset-success" class="error-banner" style="display: none; background-color: #22c55e22; border-color: #22c55eaa; color: #4ade80;"></div>
            <form id="reset-form">
                <div class="form-group">
                    <input type="password" id="reset-pass" placeholder="At least 8 characters" minlength="8" required />
                </div>
                <button type="submit" class="action-btn-primary">Update Password</button>
            </form>
        </div>
    `,
    attachListeners: (navigate) => {
        document.getElementById('reset-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            const errorBanner = document.getElementById('reset-error');
            const successBanner = document.getElementById('reset-success');

            try {
                const res = await api.post('/reset-password', { token, password: document.getElementById('reset-pass').value });
                successBanner.textContent = res.message;
                successBanner.style.display = 'block';
                setTimeout(() => navigate('/login'), 3000);
            } catch (err) {
                errorBanner.textContent = err.message;
                errorBanner.style.display = 'block';
            }
        });
    }
};
