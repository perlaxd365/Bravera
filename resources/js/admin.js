import '../css/admin.css';

document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
    document.querySelector('#adminSidebar')?.classList.toggle('is-open');
});
