/**
 * --- CAMAGRU PLATFORM ENGINE & SPA ROUTER ---
 */

// Global state container
export const store = {
    user: null,
    token: localStorage.getItem('token') || null,
    currentView: null
};

// Main routing registration matrix
const routes = {
    '/': async () => (await import('./views/gallery.js')).default,
    '/login': async () => (await import('./views/login.js')).default,
    '/studio': async () => (await import('./views/studio.js')).default
};

export async function navigate(path, appendHistory = true) {
    if (appendHistory) {
        window.history.pushState({}, "", path);
    }
    
    const container = document.getElementById('app');
    const routeLoader = routes[path] || routes['/'];
    
    // Render transient load spinner layout block
    container.innerHTML = `<div class="spinner-center"><div class="spinner"></div></div>`;
    
    try {
        const ViewClass = await routeLoader();
        const activeView = new ViewClass(container);
        store.currentView = activeView;
        await activeView.render();
        updateNavigationUI(path);
    } catch (err) {
        container.innerHTML = `<div class="error-panel">Failed to load view resource: ${err.message}</div>`;
    }
}

function updateNavigationUI(currentPath) {
    const nav = document.getElementById('main-navigation');
    const isLoggedIn = !!store.token;
    
    let html = `<a href="/" class="nav-item ${currentPath === '/' ? 'active' : ''}" data-link>Public Gallery</a>`;
    if (isLoggedIn) {
        html += `
            <a href="/studio" class="nav-item ${currentPath === '/studio' ? 'active' : ''}" data-link>Camera Studio</a>
            <button id="btn-logout" class="nav-btn-logout">Logout</button>
        `;
    } else {
        html += `<a href="/login" class="nav-item ${currentPath === '/login' ? 'active' : ''}" data-link>Sign In</a>`;
    }
    nav.innerHTML = html;
}

// Global Event Routing Pipeline Interception
document.addEventListener('click', e => {
    const link = e.target.closest('[data-link]');
    if (link) {
        e.preventDefault();
        navigate(link.getAttribute('href'));
    }
    
    if (e.target.id === 'btn-logout') {
        localStorage.removeItem('token');
        store.token = null;
        store.user = null;
        navigate('/login');
    }
});

window.addEventListener('popstate', () => {
    navigate(window.location.pathname, false);
});

// App Initialization entrypoint hook
document.addEventListener('DOMContentLoaded', () => {
    navigate(window.location.pathname, false);
});
