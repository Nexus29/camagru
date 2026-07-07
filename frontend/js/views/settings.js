import { api } from '../api.js';

export const Settings = {
    render: () => `
        <div class="auth-form-card">
            <h2 class="auth-form-heading">Account Settings</h2>
            <p class="auth-form-subtitle">Update your credentials. Leave fields blank to keep current settings.</p>
            <div id="settings-error" class="error-banner" style="display: none;"></div>
            <div id="settings-success" class="error-banner" style="display: none; background-color: #22c55e22; border-color: #22c55eaa; color: #4ade80;"></div>
            <form id="settings-form">
                <div class="form-group">
                    <label>Change Username</label>
                    <input type="text" id="settings-username" placeholder="New Username" />
                </div>
                <div class="form-group">
                    <label>Change Email Address</label>
                    <input type="email" id="settings-email" placeholder="New Email Address" />
                </div>
                <div class="form-group">
                    <label>Change Password</label>
                    <input type="password" id="settings-password" placeholder="New Password (8+ chars)" minlength="8" />
                </div>
                <button type="submit" class="action-btn-primary">Save Modifications</button>
            </form>
        </div>
    `,
    attachListeners: (navigate) => {
        document.getElementById('settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorBanner = document.getElementById('settings-error');
            const successBanner = document.getElementById('settings-success');
            errorBanner.style.display = 'none';
            successBanner.style.display = 'none';

            const payload = {};
            const user = document.getElementById('settings-username').value.trim();
            const mail = document.getElementById('settings-email').value.trim();
            const pass = document.getElementById('settings-password').value;

            if (user) payload.username = user;
            if (mail) payload.email = mail;
            if (pass) payload.password = pass;

            try {
                const res = await api.post('/update-profile', payload);
                successBanner.textContent = res.message;
                successBanner.style.display = 'block';
                
                if (res.email_changed) {
                    localStorage.clear();
                    setTimeout(() => window.location.href = '/login', 4000);
                }
            } catch (err) {
                errorBanner.textContent = err.message;
                errorBanner.style.display = 'block';
            }
        });
    }
};
