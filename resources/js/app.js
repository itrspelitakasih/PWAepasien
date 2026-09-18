import './bootstrap';

window.patientSetTheme = function (theme, endpoint) {
    document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));

    try {
        localStorage.setItem('theme', theme === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : theme);
    } catch (e) {}

    if (!endpoint) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(endpoint, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token ?? '',
        },
        body: JSON.stringify({ theme }),
    }).catch(() => {});
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-drag-scroll]').forEach((scroller) => {
        let isDragging = false;
        let startX = 0;
        let startScrollLeft = 0;
        let moved = false;

        const start = (pageX) => {
            isDragging = true;
            moved = false;
            startX = pageX;
            startScrollLeft = scroller.scrollLeft;
        };

        const move = (pageX) => {
            if (!isDragging) return;
            const delta = pageX - startX;
            if (Math.abs(delta) > 3) moved = true;
            scroller.scrollLeft = startScrollLeft - delta;
        };

        const end = () => {
            isDragging = false;
        };

        scroller.addEventListener('mousedown', (e) => {
            start(e.pageX);
        });
        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            move(e.pageX);
        });
        window.addEventListener('mouseup', end);

        // Prevent a drag-release from also firing a click on child links/buttons.
        scroller.addEventListener('click', (e) => {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    });
});
