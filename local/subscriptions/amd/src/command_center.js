/* eslint-env amd */
define([], function() {
    'use strict';

    function init() {
        window.setTimeout(function() {
            var root = document.querySelector('.campusfr-command-center');

            if (!root) {
                return;
            }

            boot(root);
        }, 100);
    }

    function boot(root) {

        if (!root) {
            return;
        }

        var searchUrl = root.getAttribute('data-search-url');
        var emptyLabel = root.getAttribute('data-empty-label') || 'No results';
        var errorLabel = root.getAttribute('data-error-label') || 'Search error';
        var loadingLabel = root.getAttribute('data-loading-label') || 'Loading…';

        var trigger = root.querySelector('.campusfr-command-trigger');
        var modal = root.querySelector('.campusfr-command-modal');
        var input = root.querySelector('.campusfr-command-input');
        var results = root.querySelector('.campusfr-command-results');
        var backdrop = root.querySelector('.campusfr-command-backdrop');
        var closeButton = root.querySelector('.campusfr-command-close');

        var controller = null;
        var activeIndex = -1;
        var currentItems = [];
        var debounceTimer = null;

        function open() {
            modal.classList.remove('d-none');
            modal.setAttribute('aria-hidden', 'false');
            input.value = '';
            results.innerHTML = '';
            currentItems = [];
            activeIndex = -1;

            window.setTimeout(function() {
                input.focus();
                input.select();
            }, 50);
        }

        function close() {
            modal.classList.add('d-none');
            modal.setAttribute('aria-hidden', 'true');
            input.value = '';
            results.innerHTML = '';
            currentItems = [];
            activeIndex = -1;
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setActive(index) {
            var rows = results.querySelectorAll('.campusfr-command-result');

            rows.forEach(function(row) {
                row.classList.remove('is-active');
            });

            if (!rows.length) {
                activeIndex = -1;
                return;
            }

            if (index < 0) {
                index = rows.length - 1;
            }

            if (index >= rows.length) {
                index = 0;
            }

            activeIndex = index;
            rows[activeIndex].classList.add('is-active');
            rows[activeIndex].scrollIntoView({block: 'nearest'});
        }

        function openActive() {
            if (activeIndex < 0 || !currentItems[activeIndex]) {
                return;
            }

            window.location.href = currentItems[activeIndex].url;
        }

        function render(items) {
            currentItems = items || [];
            activeIndex = -1;

            if (!currentItems.length) {
                results.innerHTML = '<div class="campusfr-command-empty">' + escapeHtml(emptyLabel) + '</div>';
                return;
            }

            results.innerHTML = currentItems.map(function(item, index) {
                return '' +
                    '<a class="campusfr-command-result" href="' + escapeHtml(item.url) + '" data-index="' + index + '">' +
                        '<span class="campusfr-command-result-icon">' + escapeHtml(item.icon) + '</span>' +
                        '<span class="campusfr-command-result-body">' +
                            '<span class="campusfr-command-result-title">' + escapeHtml(item.title) + '</span>' +
                            '<span class="campusfr-command-result-subtitle">' + escapeHtml(item.subtitle) + '</span>' +
                        '</span>' +
                        '<span class="campusfr-command-result-type">' + escapeHtml(item.type) + '</span>' +
                    '</a>';
            }).join('');

            setActive(0);
        }

        function search(query) {
            query = query.trim();

            if (query.length < 2) {
                results.innerHTML = '';
                currentItems = [];
                activeIndex = -1;
                return;
            }

            results.innerHTML = '<div class="campusfr-command-empty">' + escapeHtml(loadingLabel) + '</div>';

            if (controller) {
                controller.abort();
            }

            controller = new AbortController();

            fetch(searchUrl + '?q=' + encodeURIComponent(query), {
                method: 'GET',
                credentials: 'same-origin',
                signal: controller.signal
            })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (!data || data.success === false) {
                        results.innerHTML = '<div class="campusfr-command-empty">' + escapeHtml(errorLabel) + '</div>';
                        return;
                    }

                    render(data.results || []);
                })
                .catch(function(error) {
                    if (error.name !== 'AbortError') {
                        results.innerHTML = '<div class="campusfr-command-empty">' + escapeHtml(errorLabel) + '</div>';
                    }
                });
        }

        function debounceSearch() {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(function() {
                search(input.value);
            }, 180);
        }

        trigger.addEventListener('click', open);

        trigger.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                open();
            }
        });

        backdrop.addEventListener('click', close);
        closeButton.addEventListener('click', close);

        input.addEventListener('input', debounceSearch);

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(activeIndex + 1);
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(activeIndex - 1);
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                openActive();
            }
        });

        document.addEventListener('keydown', function(e) {
            var key = String(e.key || '').toLowerCase();
            var isShortcut = ((e.metaKey || e.ctrlKey) && key === 'k') || (e.ctrlKey && e.altKey && key === 'k');

            if (isShortcut) {
                e.preventDefault();

                if (modal.classList.contains('d-none')) {
                    open();
                } else {
                    close();
                }
            }
        });
    }

    return {
        init: init
    };
});