import { API } from '../api.js';
import { navigateTo } from '../app.js';

export function renderLoginView() {
    setTimeout(() => {
        document.getElementById('login-form').onsubmit = async (e) => {
            e.preventDefault();
            try {
                await API.post('/auth/login', { username: e.target.username.value, password: e.target.password.value });
                navigateTo('/studio');
            } catch (err) { alert(err.message); }
        };
    }, 0);
    return `<form id="login-form" class="auth-form"><h1>Login</h1><input type="text" name="username" placeholder="Username" required><input type="password" name="password" placeholder="Password" required><button class="btn">Login</button></form>`;
}

export function renderRegisterView() {
    setTimeout(() => {
        document.getElementById('reg-form').onsubmit = async (e) => {
            e.preventDefault();
            try {
                await API.post('/auth/register', { username: e.target.username.value, email: e.target.email.value, password: e.target.password.value });
                alert('Verification email sent!'); navigateTo('/login');
            } catch (err) { alert(err.message); }
        };
    }, 0);
    return `<form id="reg-form" class="auth-form"><h1>Register</h1><input type="text" name="username" placeholder="Username" required><input type="email" name="email" placeholder="Email" required><input type="password" name="password" placeholder="Password" required><button class="btn">Register</button></form>`;
}
