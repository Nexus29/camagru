import { RegisterView } from './views/RegisterView.js';

export const store = {
    token: localStorage.getItem('token') || null,
    user: null
};

const viewContainer = document.getElementById('app');
const navLinksContainer = document.getElementById('nav-links');

/**
 * 📱 Mobile View Navigation Toggle Handler
 */
document.getElementById('nav-toggle').addEventListener('click', () => {
    navLinksContainer.classList.toggle('open');
});

/**
 * Single-Page Client-Side Router Routing Map
 */
const routes = {
    '/': { render: () => `<h2>Gallery Stream</h2><div class="gallery-layout-grid"></div>` },
    '/login': { render: () => `<h2>Login Workspace</h2>` },
    '/register': RegisterView
};

export function navigate(path) {
    window.history.pushState({}, "", path);
    router();
}

function router() {
    // ⚡ Collapse mobile context dropdowns instantly on view shifts
    navLinksContainer.classList.remove('open');

    const path = window.location.pathname;
    const view = routes[path] || routes['/'];

    // Update active UI classes inside the menu
    document.querySelectorAll('.nav-item').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === path) link.classList.add('active');
    });

    // Mount structural layout to the core viewport
    viewContainer.innerHTML = view.render();

    // Bind event controllers dynamically if listeners exist
    if (view.attachListeners) {
        view.attachListeners(navigate);
    }
}

// Intercept SPA layout anchor element executions
window.addEventListener("popstate", router);

document.body.addEventListener("click", e => {
    if (e.target.matches("[data-link]")) {
        e.preventDefault();
        navigate(e.target.getAttribute("href"));
    }
});

router();
