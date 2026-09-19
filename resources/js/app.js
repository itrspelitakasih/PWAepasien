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

/**
 * Polls for tickets called today and, the first time a given ticket shows
 * up as called, vibrates the phone and shows a banner. Seen ticket ids are
 * kept in localStorage so a reload doesn't re-alert. Browsers only allow
 * vibration while the page is open and after the user has tapped it once;
 * iOS Safari doesn't support it at all (the WhatsApp message covers that).
 */
window.patientWatchQueueCalls = function (endpoint, banner) {
    const STORAGE_KEY = 'queue_call_alerted';
    const PATTERN = [600, 250, 600, 250, 600, 250, 1200];

    const readSeen = () => {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');
        } catch (e) {
            return [];
        }
    };

    const writeSeen = (ids) => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(ids.slice(-50)));
        } catch (e) {}
    };

    const alertFor = (ticket) => {
        banner.querySelector('[data-call-text]').textContent =
            `Nomor antrean ${ticket.queue_number} dipanggil di ${ticket.poli}. Silakan menuju ruang periksa.`;
        banner.classList.remove('hidden');

        if (navigator.vibrate) {
            navigator.vibrate(PATTERN);
        }
    };

    const check = async () => {
        if (document.hidden) return;

        try {
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;

            const { called } = await response.json();
            const seen = readSeen();
            const fresh = called.filter((ticket) => !seen.includes(ticket.id));

            if (fresh.length === 0) return;

            alertFor(fresh[fresh.length - 1]);
            writeSeen([...seen, ...fresh.map((ticket) => ticket.id)]);
        } catch (e) {}
    };

    banner.querySelector('[data-call-dismiss]')?.addEventListener('click', () => {
        banner.classList.add('hidden');
        navigator.vibrate?.(0);
    });

    document.addEventListener('visibilitychange', check);
    setInterval(check, 8000);
    check();
};
