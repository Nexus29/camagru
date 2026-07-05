import { Register } from './views/register.js';

const viewContainer = document.getElementById('app');
const navLinksContainer = document.getElementById('nav-links');

/**
 * 📱 Navigation Burger Toggle Action
 */
const navToggle = document.getElementById('nav-toggle');
if (navToggle) {
    navToggle.addEventListener('click', () => {
        navLinksContainer.classList.toggle('open');
    });
}

/**
 * Clean Single-Page App Routes Mapping
 */
const routes = {
    '/': { 
        render: () => `
            <h2>Gallery Stream</h2>
            <div class="gallery-layout-grid">
                <p style="color: #888;">Local frontend sandbox environment active. No backend images hosted yet.</p>
            </div>` 
    },
    '/login': { 
        render: () => `
            <div class="auth-form-card">
                <h2 class="auth-form-heading">Sign In</h2>
                <form id="login-form">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" placeholder="Enter profile name" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="action-btn-primary">Login</button>
                </form>
                <a href="/register" class="toggle-link" data-link>Don't have an account? Register</a>
            </div>` 
    },
    '/register': Register
};

export function navigate(path) {
    window.history.pushState({}, "", path);
    router();
}

function router() {
    // Drop mobile visibility classes instantly on page travel actions
    if (navLinksContainer) {
        navLinksContainer.classList.remove('open');
    }

    const path = window.location.pathname;
    const view = routes[path] || routes['/'];

    // Manage link highlighting styles cleanly
    document.querySelectorAll('.nav-item').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === path) {
            link.classList.add('active');
        }
    });

    // Directly replace inner HTML contents (Clears the startup spin cycle loader)
    if (viewContainer) {
        viewContainer.innerHTML = view.render();
    }

    // Initialize UI interactivity mechanics
    if (view.attachListeners) {
        view.attachListeners(navigate);
    }
}

// Global browser button state navigation integration
window.addEventListener("popstate", router);

document.body.addEventListener("click", e => {
    const targetLink = e.target.closest("[data-link]");
    if (targetLink) {
        e.preventDefault();
        navigate(targetLink.getAttribute("href"));
    }
});

// Fire layout construction immediately on runtime load
document.addEventListener('DOMContentLoaded', router);
