// frontend/js/app.js
import { Register } from './views/register.js';
import Login from './views/login.js';

// FIX: Export the state store object so login.js can access it
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
    '/login': Login, // Links to your custom Login class layout
    '/register': Register
};

// FIX: Ensure navigate is exported cleanly for the other components
export function navigate(path) {
    window.history.pushState({}, "", path);
    router();
}

function router() {
    if (navLinksContainer) {
        navLinksContainer.classList.remove('open');
    }

    const path = window.location.pathname;
    const view = routes[path] || routes['/'];

    document.querySelectorAll('.nav-item').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === path) {
            link.classList.add('active');
        }
    });

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
