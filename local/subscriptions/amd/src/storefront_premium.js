// This file is part of Moodle - http://moodle.org/
/* eslint-disable no-undef */
define([], function() {
    const SELECTOR = '[data-premium-animation]:not([data-premium-ready])';

    const initReveal = () => {
        const nodes = Array.from(document.querySelectorAll(SELECTOR));
        if (!nodes.length) {
            return;
        }
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        nodes.forEach((node) => {
            node.dataset.premiumReady = '1';
            if (reduced || node.dataset.premiumAnimation === 'none') {
                node.classList.add('is-premium-visible');
            }
        });
        if (reduced || !('IntersectionObserver' in window)) {
            nodes.forEach((node) => node.classList.add('is-premium-visible'));
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-premium-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {threshold: 0.12, rootMargin: '0px 0px -6%'});
        nodes.forEach((node) => observer.observe(node));
    };

    return {init: initReveal};
});
