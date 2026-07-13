import { Register } from './views/register.js';
import { ForgotPassword } from './views/forgot.js';
import { ResetPassword } from './views/reset.js';
import { Settings } from './views/settings.js';
import Login from './views/login.js';
import Gallery from './views/gallery.js';
import Studio from './views/studio.js';

export const store = {
    token: localStorage.getItem('token') || null,
    user: null
};

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

const routes = {
    '/': Gallery,
    '/login': Login,
    '/register': Register,
	'/forgot-password': ForgotPassword,
    '/reset-password': ResetPassword,
    '/settings': Settings,
    '/studio': Studio,
};

export function navigate(path) {
    window.history.pushState({}, "", path);
    router();
}

function updateNavbar(currentPath) {
    if (!navLinksContainer) return;

    if (store.token) {
        navLinksContainer.innerHTML = `
            <a href="/" class="nav-item ${currentPath === '/' ? 'active' : ''}" data-link>Gallery</a>
            <a href="/studio" class="nav-item ${currentPath === '/studio' ? 'active' : ''}" data-link>Studio</a>
            <a href="/settings" class="nav-item ${currentPath === '/settings' ? 'active' : ''}" data-link>Settings</a>
            <button id="logout-btn" class="nav-item logout-action-btn" style="background: none; border: none; color: inherit; font: inherit; cursor: pointer; padding: 0; text-align: center;">Logout</button>
        `;

        document.getElementById('logout-btn')?.addEventListener('click', () => {
            localStorage.removeItem('token');
            store.token = null;
            store.user = null;
            navigate('/login');
        });
    } else {
        navLinksContainer.innerHTML = `
            <a href="/" class="nav-item ${currentPath === '/' ? 'active' : ''}" data-link>Gallery</a>
            <a href="/login" class="nav-item ${currentPath === '/login' ? 'active' : ''}" id="login-nav-link" data-link>Sign In</a>
            <a href="/register" class="nav-item ${currentPath === '/register' ? 'active' : ''}" id="register-nav-link" data-link>Register</a>
        `;
    }
}

function router() {
    if (navLinksContainer) {
        navLinksContainer.classList.remove('open');
    }

    const path = window.location.pathname;
    
    updateNavbar(path);

    if ((path === '/studio' || path === '/settings') && !store.token) {
		navigate('/login');
		return;
	}

    const view = routes[path] || routes['/'];

    if (viewContainer) {
        if (view.prototype && view.prototype.constructor) {
            const viewInstance = new view(viewContainer);
            viewInstance.render();
        } else {
            viewContainer.innerHTML = view.render();
            if (view.attachListeners) {
                view.attachListeners(navigate);
            }
        }
    }
}

window.addEventListener("popstate", router);

document.body.addEventListener("click", e => {
    const targetLink = e.target.closest("[data-link]");
    if (targetLink) {
        e.preventDefault();
        navigate(targetLink.getAttribute("href"));
    }
});

document.addEventListener('DOMContentLoaded', router);
