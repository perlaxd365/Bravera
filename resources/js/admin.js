import '../css/admin.css';

import './bootstrap';

import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// Sidebar
document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
    document.querySelector('#adminSidebar')?.classList.toggle('is-open');
});