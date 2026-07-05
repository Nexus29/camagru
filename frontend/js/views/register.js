import { api } from '../api.js';

export const RegisterView = {
    render: () => {
        return `
            <div class="auth-form-card">
                <h2 class="auth-form-heading">Create Account</h2>
                <p class="auth-form-subtitle">Join Camagru to build and share photo compositions</p>
                
                <div id="auth-error" class="error-banner" style="display: none;"></div>

                <form id="register-form">
                    <div class="form-group">
                        <label for="reg-email">Email Address</label>
                        <input type="email" id="reg-email" placeholder="name@domain.com" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-username">Username</label>
                        <input type="text" id="reg-username" placeholder="Choose a display name" minlength="3" maxlength="20" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-password">Password</label>
                        <input type="password" id="reg-password" placeholder="At least 8 characters" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-confirm">Confirm Password</label>
                        <input type="password" id="reg-confirm" placeholder="Verify password security" required>
                    </div>
                    <button type="submit" class="action-btn-primary">Create Secure Account</button>
                </form>
                
                <a href="/login" class="toggle-link" data-link>Already have an account? Sign In</a>
            </div>
        `;
    },

    attachListeners: (navigate) => {
        const form = document.getElementById('register-form');
        const errorBanner = document.getElementById('auth-error');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBanner.style.display = 'none';

            const email = document.getElementById('reg-email').value.trim();
            const username = document.getElementById('reg-username').value.trim();
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('reg-confirm').value;

            // Mobile view validation guard checks
            if (password !== confirmPassword) {
                errorBanner.textContent = "Passwords do not match.";
                errorBanner.style.display = 'block';
                return;
            }

            try {
                // Hits your secure ApiClient endpoint wrapper
                await api.register({ email, username, password });
                alert("Account created successfully! Please check your email to verify your status.");
                navigate('/login');
            } catch (err) {
                errorBanner.textContent = err.message || "Registration failed. Try again.";
                errorBanner.style.display = 'block';
            }
        });
    }
};
