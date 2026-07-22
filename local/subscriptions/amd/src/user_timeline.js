/* eslint-env amd */
define([], function() {

    var SELECTORS = {
        root: '[data-user-timeline]',
        item: '[data-timeline-item]',
        group: '[data-timeline-group]',
        category: '[data-timeline-category-filter]',
        search: '[data-timeline-search]',
        period: '[data-timeline-period]',
        important: '[data-timeline-important]',
        reset: '[data-timeline-reset]',
        toggle: '[data-timeline-toggle]',
        body: '[data-timeline-body]',
        expandAll: '[data-timeline-expand-all]',
        collapseAll: '[data-timeline-collapse-all]',
        results: '[data-timeline-results]',
        empty: '[data-timeline-empty]',
        loadMore: '[data-timeline-load-more]',
        groupBody: '[data-timeline-group-body]',
        groups: '[data-timeline-groups]'
    };

    /**
     * Normalise searchable strings.
     *
     * @param {String} value
     * @return {String}
     */
    var normalise = function(value) {
        return String(value || '')
            .toLocaleLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    };

    /**
     * Read current filtering state.
     *
     * @param {HTMLElement} root
     * @return {Object}
     */
    var readState = function(root) {
        var active =
            root.querySelector(
                SELECTORS.category +
                '[aria-pressed="true"]'
            );

        var search =
            root.querySelector(
                SELECTORS.search
            );

        var period =
            root.querySelector(
                SELECTORS.period
            );

        var important =
            root.querySelector(
                SELECTORS.important
            );

        return {
            category:
                active
                    ? active.dataset.timelineCategoryFilter
                    : 'all',

            search:
                normalise(
                    search ? search.value : ''
                ),

            days:
                period && period.value !== 'all'
                    ? parseInt(period.value, 10)
                    : 0,

            important:
                Boolean(
                    important &&
                    important.checked
                )
        };
    };

    /**
     * Determine whether an item matches filters.
     *
     * @param {HTMLElement} item
     * @param {Object} state
     * @return {Boolean}
     */
    var matches = function(item, state) {
        if (
            state.category !== 'all' &&
            item.dataset.category !== state.category
        ) {
            return false;
        }

        if (
            state.important &&
            item.dataset.importance === 'normal'
        ) {
            return false;
        }

        if (state.search !== '') {
            var searchable =
                normalise(
                    item.dataset.searchText
                );

            if (
                searchable.indexOf(
                    state.search
                ) === -1
            ) {
                return false;
            }
        }

        if (state.days > 0) {
            var timestamp =
                parseInt(
                    item.dataset.timecreated || '0',
                    10
                );

            var minimum =
                Math.floor(Date.now() / 1000) -
                state.days * 86400;

            if (
                timestamp <= 0 ||
                timestamp < minimum
            ) {
                return false;
            }
        }

        return true;
    };

    /**
     * Update the translated result counter.
     *
     * The original server-rendered string is used as a template.
     *
     * @param {HTMLElement} root
     * @param {Number} count
     */
    var updateCounter = function(root, count) {
        var target =
            root.querySelector(
                SELECTORS.results
            );

        if (!target) {
            return;
        }

        var template =
            target.dataset.countTemplate ||
            '__COUNT__';

        target.textContent =
            template.replace(
                '__COUNT__',
                count.toString()
            );
    };

    /**
     * Apply all Timeline filters.
     *
     * @param {HTMLElement} root
     */
    var applyFilters = function(root) {
        var state =
            readState(root);

        var visible =
            0;

        root.querySelectorAll(
            SELECTORS.item
        ).forEach(function(item) {
            var show =
                matches(item, state);

            item.classList.toggle(
                'd-none',
                !show
            );

            if (show) {
                visible++;
            }
        });

        root.querySelectorAll(
            SELECTORS.group
        ).forEach(function(group) {
            var hasVisible =
                Array.from(
                    group.querySelectorAll(
                        SELECTORS.item
                    )
                ).some(function(item) {
                    return !item.classList.contains(
                        'd-none'
                    );
                });

            group.classList.toggle(
                'd-none',
                !hasVisible
            );
        });

        var empty =
            root.querySelector(
                SELECTORS.empty
            );

        if (empty) {
            empty.classList.toggle(
                'd-none',
                visible > 0
            );
        }

        updateCounter(
            root,
            visible
        );
    };

    /**
     * Expand or collapse one event.
     *
     * @param {HTMLElement} button
     * @param {Boolean|null} expanded
     */
    var toggleItem = function(
        button,
        expanded
    ) {
        var control =
            button.getAttribute(
                'aria-controls'
            );

        if (!control) {
            return;
        }

        var body =
            document.getElementById(
                control
            );

        if (!body) {
            return;
        }

        var next =
            expanded === null
                ? button.getAttribute(
                    'aria-expanded'
                ) !== 'true'
                : expanded;

        button.setAttribute(
            'aria-expanded',
            next ? 'true' : 'false'
        );

        body.classList.toggle(
            'd-none',
            !next
        );
    };

    /**
     * Reset filtering state.
     *
     * @param {HTMLElement} root
     */
    var reset = function(root) {
        root.querySelectorAll(
            SELECTORS.category
        ).forEach(function(button) {
            var active =
                button.dataset.timelineCategoryFilter ===
                'all';

            button.setAttribute(
                'aria-pressed',
                active ? 'true' : 'false'
            );

            button.classList.toggle(
                'btn-primary',
                active
            );

            button.classList.toggle(
                'btn-outline-secondary',
                !active
            );
        });

        var search =
            root.querySelector(
                SELECTORS.search
            );

        if (search) {
            search.value = '';
        }

        var period =
            root.querySelector(
                SELECTORS.period
            );

        if (period) {
            period.value = 'all';
        }

        var important =
            root.querySelector(
                SELECTORS.important
            );

        if (important) {
            important.checked = false;
        }

        applyFilters(root);
    };

    /**
     * Initialise one Timeline.
     *
     * @param {HTMLElement} root
     */
    var initRoot = function(root) {
        if (
            root.dataset.timelineInitialised ===
            '1'
        ) {
            return;
        }

        root.dataset.timelineInitialised =
            '1';

        root.addEventListener(
            'click',
            function(event) {
                var category =
                    event.target.closest(
                        SELECTORS.category
                    );

                if (category) {
                    root.querySelectorAll(
                        SELECTORS.category
                    ).forEach(function(button) {
                        var active =
                            button === category;

                        button.setAttribute(
                            'aria-pressed',
                            active
                                ? 'true'
                                : 'false'
                        );

                        button.classList.toggle(
                            'btn-primary',
                            active
                        );

                        button.classList.toggle(
                            'btn-outline-secondary',
                            !active
                        );
                    });

                    applyFilters(root);
                    return;
                }

                var toggle =
                    event.target.closest(
                        SELECTORS.toggle
                    );

                if (toggle) {
                    toggleItem(
                        toggle,
                        null
                    );
                    return;
                }

                if (
                    event.target.closest(
                        SELECTORS.reset
                    )
                ) {
                    reset(root);
                    return;
                }

                var loadmore =
                    event.target.closest(
                        SELECTORS.loadMore
                    );

                if (loadmore) {
                    loadMore(
                        root,
                        loadmore
                    );

                    return;
                }

                if (
                    event.target.closest(
                        SELECTORS.expandAll
                    )
                ) {
                    root.querySelectorAll(
                        SELECTORS.toggle
                    ).forEach(function(button) {
                        toggleItem(
                            button,
                            true
                        );
                    });
                    return;
                }

                if (
                    event.target.closest(
                        SELECTORS.collapseAll
                    )
                ) {
                    root.querySelectorAll(
                        SELECTORS.toggle
                    ).forEach(function(button) {
                        toggleItem(
                            button,
                            false
                        );
                    });
                }
            }
        );

        root.addEventListener(
            'input',
            function(event) {
                if (
                    event.target.matches(
                        SELECTORS.search
                    )
                ) {
                    applyFilters(root);
                }
            }
        );

        root.addEventListener(
            'change',
            function(event) {
                if (
                    event.target.matches(
                        SELECTORS.period
                    ) ||
                    event.target.matches(
                        SELECTORS.important
                    )
                ) {
                    applyFilters(root);
                }
            }
        );
    };

    /**
     * Append AJAX groups while merging identical calendar days.
     *
     * @param {HTMLElement} root
     * @param {Array} groups
     */
    var appendGroups = function(root, groups) {
        var container =
            root.querySelector(
                SELECTORS.groups
            );

        if (!container) {
            return;
        }

        groups.forEach(function(group) {
            var selector =
                SELECTORS.group +
                '[data-timeline-group="' +
                CSS.escape(group.key) +
                '"]';

            var existing =
                container.querySelector(
                    selector
                );

            if (existing) {
                var existingBody =
                    existing.querySelector(
                        SELECTORS.groupBody
                    );

                if (existingBody) {
                    existingBody.insertAdjacentHTML(
                        'beforeend',
                        group.html
                    );
                }

                return;
            }

            var section =
                document.createElement(
                    'section'
                );

            section.className =
                'crm-timeline-group';

            section.dataset.timelineGroup =
                group.key;

            var heading =
                document.createElement(
                    'h3'
                );

            heading.className =
                'crm-timeline-date-heading';

            heading.textContent =
                group.label;

            var newGroupBody =
                document.createElement(
                    'div'
                );

            newGroupBody.className =
                'crm-timeline-group-body';

            newGroupBody.dataset.timelineGroupBody =
                '1';

            newGroupBody.innerHTML =
                group.html;

            section.appendChild(
                heading
            );

            section.appendChild(
                newGroupBody
            );

            container.appendChild(
                section
            );
        });
    };

    /**
     * Loads the next Timeline page.
     *
     * @param {HTMLElement} root
     * @param {HTMLButtonElement} button
     * @return {Promise<void>}
     */
    var loadMore = async function(
        root,
        button
    ) {
        if (
            button.dataset.loading === '1'
        ) {
            return;
        }

        var originalLabel =
            button.textContent;

        button.dataset.loading = '1';
        button.disabled = true;
        button.textContent =
            button.dataset.loadingLabel ||
            originalLabel;

        var formData =
            new URLSearchParams();

        formData.set(
            'sesskey',
            button.dataset.sesskey || ''
        );

        formData.set(
            'userid',
            button.dataset.userid || '0'
        );

        formData.set(
            'offset',
            button.dataset.offset || '0'
        );

        formData.set(
            'limit',
            button.dataset.limit || '20'
        );

        try {
            var response =
                await fetch(
                    button.dataset.url,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: formData.toString()
                    }
                );

            if (!response.ok) {
                throw new Error(
                    'Timeline request failed'
                );
            }

            var data =
                await response.json();

            if (!data.success) {
                throw new Error(
                    'Timeline response failed'
                );
            }

            appendGroups(
                root,
                data.groups || []
            );

            button.dataset.offset =
                String(
                    data.nextOffset || 0
                );

            if (!data.hasMore) {
                var wrapper =
                    button.closest(
                        '.crm-timeline-load-more'
                    );

                if (wrapper) {
                    wrapper.remove();
                }
            } else {
                button.disabled = false;
                button.textContent =
                    originalLabel;
                button.dataset.loading = '0';
            }

            applyFilters(root);
        } catch (error) {
            button.disabled = false;
            button.dataset.loading = '0';
            button.textContent =
                button.dataset.errorLabel ||
                originalLabel;
        }
    };

    /**
     * Public AMD entry point.
     */
    var init = function() {
        document.querySelectorAll(
            SELECTORS.root
        ).forEach(initRoot);
    };

    return {
        init: init
    };
});