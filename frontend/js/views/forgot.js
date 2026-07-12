import { api } from '../api.js';

export const ForgotPassword = {
    render: () => `
        <div class="auth-form-card">
            <h2 class="auth-form-heading">Forgot Password</h2>
            <p class="auth-form-subtitle">Enter your email and we'll send you a initialization link</p>
            <div id="forgot-error" class="error-banner" style="display: none;"></div>
            <div id="forgot-success" class="error-banner" style="display: none; background-color: #22c55e22; border-color: #22c55eaa; color: #4ade80;"></div>
            <form id="forgot-form">
                <div class="form-group">
                    <input type="email" id="forgot-email" placeholder="name@domain.com" required />
                </div>
                <button type="submit" class="action-btn-primary">Send Reset Link</button>
            </form>
            <a href="/login" class="toggle-link" data-link>Back to Sign In</a>
        </div>
    `,
    attachListeners: (navigate) => {
        document.getElementById('forgot-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorBanner = document.getElementById('forgot-error');
            const successBanner = document.getElementById('forgot-success');
            errorBanner.style.display = 'none';
            successBanner.style.display = 'none';

            try {
				const res = await api.post('/forgot-password', { email: document.getElementById('forgot-email').value });
				successBanner.textContent = res.message;
				successBanner.style.display = 'block';
				
				// 🧼 Clears input field cleanly on submission success
				e.target.reset();
			} catch (err) {
				errorBanner.textContent = err.message;
				errorBanner.style.display = 'block';
			}
        });
    }
};
