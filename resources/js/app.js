import './bootstrap';
import Swal from 'sweetalert2';
import './checkout';

document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('js');

    const els = document.querySelectorAll('.reveal');

    if (!('IntersectionObserver' in window) || !els.length) {
        els.forEach((el) => el.classList.add('is-visible'));

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );

    els.forEach((el) => observer.observe(el));
});