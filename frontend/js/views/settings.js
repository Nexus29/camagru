// frontend/js/views/settings.js
import { api } from '../api.js';

export const Settings = {
    render: () => `
        <div class="auth-form-card">
            <h2 class="auth-form-heading">Account Settings</h2>
            
            <div class="current-profile-badge" style="background-color: var(--bg-surface); border: 1px solid var(--border); padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
                <p style="margin: 0 0 6px 0; color: #888;">Current Account Credentials:</p>
                <div><strong>Username:</strong> <span id="current-username-display">Loading...</span></div>
                <div style="margin-top: 4px;"><strong>Email:</strong> <span id="current-email-display">Loading...</span></div>
            </div>
            
            <p class="auth-form-subtitle">Modify parameters. Fields left blank will preserve current values.</p>
            
            <div id="settings-error" class="error-banner" style="display: none; background-color: #ef444422; border-color: #ef4444aa; color: #f87171; padding: 10px; border-radius: 4px; margin-bottom: 15px;"></div>
            <div id="settings-success" class="error-banner" style="display: none; background-color: #22c55e22; border-color: #22c55eaa; color: #4ade80; padding: 10px; border-radius: 4px; margin-bottom: 15px;"></div>
            
            <form id="settings-form">
                <div class="form-group">
                    <label for="settings-username">New Username</label>
                    <input type="text" id="settings-username" placeholder="Enter new username">
                </div>
                <div class="form-group">
                    <label for="settings-email">New Email Address</label>
                    <input type="email" id="settings-email" placeholder="new-email@domain.com">
                </div>
                <div class="form-group">
                    <label for="settings-password">New Password</label>
                    <input type="password" id="settings-password" placeholder="Minimum 8 characters" minlength="8">
                </div>
                <button type="submit" class="action-btn-primary">Save Profile Alterations</button>
            </form>
        </div>
    `,
    attachListeners: async (navigate) => {
        const form = document.getElementById('settings-form');
        const errorBanner = document.getElementById('settings-error');
        const successBanner = document.getElementById('settings-success');
        
        const usernameDisplay = document.getElementById('current-username-display');
        const emailDisplay = document.getElementById('current-email-display');

        // 🔄 Fetch current info upon navigation presentation trigger
        try {
            const currentProfile = await api.get('/get-profile');
            usernameDisplay.textContent = currentProfile.username;
            emailDisplay.textContent = currentProfile.email;
            
            // Optional: Preset inputs as placeholders
            document.getElementById('settings-username').placeholder = `Keep (${currentProfile.username})`;
            document.getElementById('settings-email').placeholder = `Keep (${currentProfile.email})`;
        } catch (err) {
            usernameDisplay.textContent = "Error loading";
            emailDisplay.textContent = "Error loading";
            errorBanner.textContent = "Could not pull profile settings metadata. Please refresh.";
            errorBanner.style.display = 'block';
        }

        // 💾 Submit modification data tracking sequence
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBanner.style.display = 'none';
            successBanner.style.display = 'none';

            const username = document.getElementById('settings-username').value.trim();
            const email = document.getElementById('settings-email').value.trim();
            const password = document.getElementById('settings-password').value;

            const payload = {};
            if (username) payload.username = username;
            if (email) payload.email = email;
            if (password) payload.password = password;

            if (Object.keys(payload).length === 0) {
                errorBanner.textContent = "Please fill out at least one field to submit updates.";
                errorBanner.style.display = 'block';
                return;
            }

            try {
                const res = await api.post('/update-profile', payload);
                successBanner.textContent = res.message;
                successBanner.style.display = 'block';

                if (res.email_changed) {
                    localStorage.removeItem('token');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 4000);
                } else {
                    form.reset();
                    if (username) usernameDisplay.textContent = username;
                    if (email) emailDisplay.textContent = email;
                    
                    document.getElementById('settings-username').placeholder = `Keep (${usernameDisplay.textContent})`;
                    document.getElementById('settings-email').placeholder = `Keep (${emailDisplay.textContent})`;
                }
            } catch (err) {
                errorBanner.textContent = err.message;
                errorBanner.style.display = 'block';
            }
        });
    }
};
