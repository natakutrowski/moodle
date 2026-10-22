// This file is part of Moodle - http://moodle.org/
/** @module local_subscriptions/journey_map */
/* eslint-disable no-undef */
define([], function() {
    /** Keep markers anchored at the TRUE projected lat/lon, displacing only labels when close. */
    const separateNearbyLabels = (map) => {
        const canvas = map.querySelector('.commerce-geo-map__canvas');
        if (!canvas) return;
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        const controls = Array.from(map.querySelectorAll('[data-geo-point]'));
        const points = controls.map((button) => {
            const x = Number.parseFloat(button.style.getPropertyValue('--geo-x')) * width / 100;
            const y = Number.parseFloat(button.style.getPropertyValue('--geo-y')) * height / 100;
            return {button, x, y, dx: 0, dy: 0};
        });
        const minSpace = 38;
        for (let turn = 0; turn < 90; turn++) {
            let moved = false;
            for (let i = 0; i < points.length; i++) {
                for (let j = i + 1; j < points.length; j++) {
                    const a = points[i];
                    const b = points[j];
                    let vx = a.x + a.dx - b.x - b.dx;
                    let vy = a.y + a.dy - b.y - b.dy;
                    let distance = Math.hypot(vx, vy);
                    if (distance >= minSpace) continue;
                    if (distance < 0.01) {
                        vx = 1;
                        vy = ((i + j) % 2 ? 1 : -1);
                        distance = Math.hypot(vx, vy);
                    }
                    const push = Math.min((minSpace - distance) * 0.54, 12);
                    const ux = vx / distance * push;
                    const uy = vy / distance * push;
                    a.dx += ux;
                    a.dy += uy;
                    b.dx -= ux;
                    b.dy -= uy;
                    moved = true;
                }
            }
            for (const point of points) {
                point.dx *= .994;
                point.dy *= .994;
                const x = point.x + point.dx;
                const y = point.y + point.dy;
                if (x < 23) point.dx += 23 - x;
                if (x > width - 23) point.dx -= x - width + 23;
                if (y < 23) point.dy += 23 - y;
                if (y > height - 23) point.dy -= y - height + 23;
            }
            if (!moved) break;
        }
        points.forEach(({button, dx, dy}) => {
            button.style.setProperty('--geo-dx', `${dx.toFixed(1)}px`);
            button.style.setProperty('--geo-dy', `${dy.toFixed(1)}px`);
            button.style.setProperty('--geo-line', `${Math.hypot(dx, dy).toFixed(1)}px`);
            button.style.setProperty('--geo-angle', `${Math.atan2(dy, dx)}rad`);
        });
    };

    const init = () => {
        document.querySelectorAll('[data-showroom-geo-map]').forEach((map) => {
            if (map.dataset.geoBound === '1') return;
            map.dataset.geoBound = '1';
            const detail = map.querySelector('[data-geo-detail]');
            const photo = map.querySelector('[data-geo-photo]');
            const close = map.querySelector('[data-geo-close]');
            const buttons = Array.from(map.querySelectorAll('[data-geo-point]'));
            if (!detail || !photo || !close || !buttons.length) return;
            let selected = null;
            const dismiss = (restoreFocus) => {
                detail.hidden = true;
                if (selected) {
                    selected.setAttribute('aria-pressed', 'false');
                    if (restoreFocus) selected.focus();
                }
                selected = null;
            };
            buttons.forEach((button) => button.addEventListener('click', () => {
                if (selected === button) {
                    dismiss(false);
                    return;
                }
                buttons.forEach((item) => item.setAttribute('aria-pressed', 'false'));
                button.setAttribute('aria-pressed', 'true');
                selected = button;
                map.querySelector('[data-geo-number]').textContent = button.dataset.number || '';
                map.querySelector('[data-geo-place]').textContent = button.dataset.place || '';
                map.querySelector('[data-geo-caption]').textContent = button.dataset.caption || '';
                photo.src = button.dataset.photo || '';
                photo.alt = button.dataset.place || '';
                detail.hidden = false;
            }));
            close.addEventListener('click', () => dismiss(true));
            map.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !detail.hidden) {
                    event.preventDefault();
                    dismiss(true);
                }
            });
            let resizeQueued = false;
            const update = () => {
                if (resizeQueued) return;
                resizeQueued = true;
                window.requestAnimationFrame(() => {
                    resizeQueued = false;
                    separateNearbyLabels(map);
                });
            };
            update();
            window.addEventListener('resize', update);
        });
    };
    return {init};
});
