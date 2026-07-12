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
                
                <!-- 🔔 NOTIFICATION PREFERENCE CHECKBOX -->
                <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin: 1.5rem 0;">
                    <input type="checkbox" id="settings-notify" style="width: auto; margin: 0; cursor: pointer;">
                    <label for="settings-notify" style="margin: 0; cursor: pointer; font-size: 0.9rem; color: #e4e4e7;">
                        Email me when someone comments on my snapshots
                    </label>
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
        const notifyCheckbox = document.getElementById('settings-notify');

        // Track initial checkbox state to verify if a setting mutation took place
        let initialNotifyState = true;

        // 🔄 Fetch current info upon navigation presentation trigger
        try {
            const currentProfile = await api.get('/get-profile');
            usernameDisplay.textContent = currentProfile.username;
            emailDisplay.textContent = currentProfile.email;
            
            // Map state explicitly (defaulting to true if undefined or null)
            initialNotifyState = currentProfile.notify_on_comment !== false;
            notifyCheckbox.checked = initialNotifyState;
            
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
            const notifyOnComment = notifyCheckbox.checked;

            const payload = {};
            if (username) payload.username = username;
            if (email) payload.email = email;
            if (password) payload.password = password;
            
            // Include notification toggle preference only if it differs from the initial layout value
            if (notifyOnComment !== initialNotifyState) {
                payload.notify_on_comment = notifyOnComment;
            }

            if (Object.keys(payload).length === 0) {
                errorBanner.textContent = "Please fill out at least one field or change preferences to submit updates.";
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
                    
                    // Update initial flag states locally upon successful response serialization
                    initialNotifyState = notifyOnComment;
                    notifyCheckbox.checked = initialNotifyState;
                    
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
