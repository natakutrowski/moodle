/* eslint-env amd */
define([
    'core/config',
    'core/notification'
], function(
    Config,
    Notification
) {
    'use strict';

    var SELECTORS = {
        confirmForm: '[data-inbox-confirm]',
        busyForm: '[data-inbox-busy-form]',
        submitButton: 'button[type="submit"], input[type="submit"]',
        submitterValue: '[data-inbox-submitter-value]',
        liveRegion: '[data-inbox-live-region]',
        firstInvalid: ':invalid',
        threadCard:
            '[data-inbox-thread-card]',

        threadPreviewLink:
            '[data-inbox-thread-preview]',

        readingPanel:
            '[data-region="inbox-reading-panel"]',

        contextPanel:
            '[data-region="inbox-context-panel"]',

        previewPlaceholder:
            '[data-region="inbox-preview-placeholder"]',

        previewHeading:
            '.crm-inbox-preview-title',

        previewLiveRegion:
            '[data-inbox-preview-live-region]'
    };

    var initialized = false;

    /**
     * Return the live region belonging to a form or page.
     *
     * @param {HTMLElement} element
     * @returns {HTMLElement|null}
     */
    var findLiveRegion = function(element) {
        if (
            !element ||
            typeof element.querySelector !== 'function'
        ) {
            return document.querySelector(
                SELECTORS.liveRegion
            );
        }

        var formRegion = element.querySelector(
            SELECTORS.liveRegion
        );

        if (formRegion) {
            return formRegion;
        }

        var container = element.closest(
            'section, article, .card, main'
        );

        if (container) {
            var containerRegion =
                container.querySelector(
                    SELECTORS.liveRegion
                );

            if (containerRegion) {
                return containerRegion;
            }
        }

        return document.querySelector(
            SELECTORS.liveRegion
        );
    };

    /**
     * Announce a message to assistive technologies.
     *
     * @param {HTMLElement} element
     * @param {String} message
     */
    var announce = function(element, message) {
        var region = findLiveRegion(element);

        if (!region || !message) {
            return;
        }

        region.textContent = '';

        window.setTimeout(function() {
            region.textContent = message;
        }, 20);
    };

    /**
     * Announces an Inbox preview update without replacing its content.
     *
     * @param {String} message
     */
    var announcePreview = function(message) {
        var region = document.querySelector(
            SELECTORS.previewLiveRegion
        );

        if (!region || !message) {
            return;
        }

        region.textContent = '';

        window.setTimeout(function() {
            region.textContent = message;
        }, 20);
    };

    /**
     * Preserve the clicked submit button name and value.
     *
     * Disabled submit buttons are not included in the submitted form data.
     * The busy-state logic disables all submit buttons, so the clicked
     * button value must be copied before that happens.
     *
     * @param {HTMLFormElement} form
     * @param {HTMLElement|null} submitter
     */
    var preserveSubmitter = function(form, submitter) {
        var previous =
            form.querySelector(
                SELECTORS.submitterValue
            );

        if (previous) {
            previous.remove();
        }

        if (
            !submitter ||
            !submitter.name ||
            submitter.disabled
        ) {
            return;
        }

        var input = document.createElement('input');

        input.type = 'hidden';
        input.name = submitter.name;
        input.value = submitter.value || '';
        input.dataset.inboxSubmitterValue = '1';

        form.appendChild(input);
    };

    /**
     * Disable submit controls and mark a form as busy.
     *
     * @param {HTMLFormElement} form
     */
    var markBusy = function(form) {
        if (form.dataset.inboxSubmitting === '1') {
            return;
        }

        form.dataset.inboxSubmitting = '1';
        form.setAttribute('aria-busy', 'true');

        var buttons = form.querySelectorAll(
            SELECTORS.submitButton
        );

        buttons.forEach(function(button) {
            var loadingLabel =
                button.dataset.loadingLabel || '';

            button.disabled = true;

            if (
                button.tagName === 'BUTTON' &&
                loadingLabel
            ) {
                if (!button.dataset.originalLabel) {
                    button.dataset.originalLabel =
                        button.textContent;
                }

                button.textContent = loadingLabel;
            }
        });

        announce(
            form,
            form.dataset.busyAnnouncement || ''
        );
    };

    /**
     * Restore a form when browser validation prevents submission.
     *
     * @param {HTMLFormElement} form
     */
    var restoreForm = function(form) {
        form.dataset.inboxSubmitting = '0';
        form.removeAttribute('aria-busy');

        var submitterValue =
            form.querySelector(
                SELECTORS.submitterValue
            );

        if (submitterValue) {
            submitterValue.remove();
        }        

        var buttons = form.querySelectorAll(
            SELECTORS.submitButton
        );

        buttons.forEach(function(button) {
            button.disabled = false;

            if (
                button.tagName === 'BUTTON' &&
                button.dataset.originalLabel
            ) {
                button.textContent =
                    button.dataset.originalLabel;
            }
        });
    };

    /**
     * Ask for confirmation when required.
     *
     * @param {HTMLFormElement} form
     * @returns {Boolean}
     */
    var confirmForm = function(form) {
        var message =
            form.dataset.inboxConfirm || '';

        if (!message) {
            return true;
        }

        return window.confirm(message);
    };

    /**
     * Handle all Inbox form submissions.
     *
     * @param {Event} event
     */
    var handleSubmit = function(event) {
        var form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (
            !form.matches(SELECTORS.busyForm) &&
            !form.matches(SELECTORS.confirmForm)
        ) {
            return;
        }

        if (form.dataset.inboxSubmitting === '1') {
            event.preventDefault();

            return;
        }

        if (!form.checkValidity()) {
            restoreForm(form);

            var invalidField =
                form.querySelector(
                    SELECTORS.firstInvalid
                );

            if (invalidField) {
                invalidField.focus();
            }

            return;
        }

        if (!confirmForm(form)) {
            event.preventDefault();
            restoreForm(form);

            return;
        }

        preserveSubmitter(
            form,
            event.submitter || null
        );

        markBusy(form);
    };

    /**
     * Restore forms when the page is returned from the browser cache.
     */
    var handlePageShow = function() {
        document
            .querySelectorAll(
                SELECTORS.busyForm +
                ', ' +
                SELECTORS.confirmForm
            )
            .forEach(function(form) {
                restoreForm(form);
            });
    };

    /**
     * Add a visible error message if a JavaScript error occurs.
     *
     * @param {Error} error
     */
    var reportError = function(error) {
        Notification.exception(error);
    };

    /**
     * Returns the Inbox Workspace root.
     *
     * @returns {HTMLElement|null}
     */
    var getInboxWorkspace = function() {
        return document.querySelector(
            '.crm-workspace-inbox'
        );
    };

    /**
     * Returns both permanent preview regions.
     *
     * @returns {Object|null}
     */
    var getPreviewRegions = function() {
        var reading =
            document.querySelector(
                SELECTORS.readingPanel
            );

        var context =
            document.querySelector(
                SELECTORS.contextPanel
            );

        if (!reading || !context) {
            return null;
        }

        return {
            reading: reading,
            context: context
        };
    };

    /**
     * Updates the busy state of both preview regions.
     *
     * @param {Object} regions
     * @param {Boolean} busy
     */
    var setPreviewBusy = function(
        regions,
        busy
    ) {
        var value = busy
            ? 'true'
            : 'false';

        regions.reading.setAttribute(
            'aria-busy',
            value
        );

        regions.context.setAttribute(
            'aria-busy',
            value
        );

        regions.reading.classList.toggle(
            'is-loading',
            busy
        );

        regions.context.classList.toggle(
            'is-loading',
            busy
        );
    };

    /**
     * Renders the loading state.
     *
     * @param {Object} regions
     */
    var renderPreviewLoading = function(
        regions
    ) {
        var workspace = getInboxWorkspace();

        var message = workspace
            ? workspace.dataset.previewLoading || ''
            : '';

        var loadingHtml =
            '<div class="crm-inbox-preview-loading" role="status">' +
            '<span class="spinner-border spinner-border-sm" ' +
            'aria-hidden="true"></span>' +
            '<span>' +
            escapeHtml(message) +
            '</span>' +
            '</div>';

        regions.reading.innerHTML =
            loadingHtml;

        regions.context.innerHTML =
            loadingHtml;
    };

    /**
     * Escapes plain text before inserting it into HTML.
     *
     * @param {String} value
     * @returns {String}
     */
    var escapeHtml = function(value) {
        var element =
            document.createElement('div');

        element.textContent =
            value || '';

        return element.innerHTML;
    };

    /**
     * Marks one thread card as selected.
     *
     * @param {Number} threadId
     */
    var selectThreadCard = function(threadId) {
        document
            .querySelectorAll(
                SELECTORS.threadCard
            )
            .forEach(function(card) {
                var selected =
                    Number(card.dataset.threadId) ===
                    Number(threadId);

                card.classList.toggle(
                    'is-selected',
                    selected
                );

                if (selected) {
                    card.setAttribute(
                        'aria-current',
                        'true'
                    );
                } else {
                    card.removeAttribute(
                        'aria-current'
                    );
                }
            });
    };

    /**
     * Updates the current URL without reloading the Inbox.
     *
     * @param {Number} threadId
     * @param {Boolean} replace
     */
    var updatePreviewUrl = function(
        threadId,
        replace
    ) {
        var url = new URL(
            window.location.href
        );

        url.searchParams.set(
            'threadid',
            String(threadId)
        );

        var state = {
            inboxThreadId: Number(threadId)
        };

        if (replace) {
            window.history.replaceState(
                state,
                '',
                url.toString()
            );
        } else {
            window.history.pushState(
                state,
                '',
                url.toString()
            );
        }
    };

    /**
     * Displays an AJAX preview error.
     *
     * @param {Object} regions
     * @param {String} message
     */
    var renderPreviewError = function(
        regions,
        message
    ) {
        var html =
            '<div class="alert alert-danger" role="alert">' +
            escapeHtml(message) +
            '</div>';

        regions.reading.innerHTML = html;

        regions.context.innerHTML = '';
    };

    /**
     * Loads one Inbox thread preview.
     *
     * @param {HTMLElement} link
     * @param {Boolean} updateHistory
     * @param {Boolean} moveFocus
     * @returns {Promise<void>}
     */
    var loadThreadPreview = function(
        link,
        updateHistory,
        moveFocus
    ) {
        var regions = getPreviewRegions();

        if (!regions) {
            window.location.href = link.href;

            return Promise.resolve();
        }

        var card = link.closest(
            SELECTORS.threadCard
        );

        var endpoint = card
            ? card.dataset.previewUrl || ''
            : '';

        var threadId =
            Number(link.dataset.threadId || 0);

        if (!endpoint || threadId <= 0) {
            window.location.href = link.href;

            return Promise.resolve();
        }

        setPreviewBusy(
            regions,
            true
        );

        renderPreviewLoading(
            regions
        );

        var requestUrl = new URL(
            endpoint,
            window.location.origin
        );

        requestUrl.searchParams.set(
            'threadid',
            String(threadId)
        );

        requestUrl.searchParams.set(
            'sesskey',
            Config.sesskey
        );

        return window.fetch(
            requestUrl.toString(),
            {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            }
        )
            .then(function(response) {
                if (!response.ok) {
                    throw new Error(
                        'Inbox preview request failed'
                    );
                }

                return response.json();
            })
            .then(function(payload) {
                if (
                    !payload ||
                    payload.success !== true
                ) {
                    throw new Error(
                        payload && payload.message
                            ? payload.message
                            : 'Inbox preview unavailable'
                    );
                }

                regions.reading.innerHTML =
                    payload.readinghtml || '';

                regions.context.innerHTML =
                    payload.contexthtml || '';

                selectThreadCard(
                    threadId
                );

                if (updateHistory) {
                    updatePreviewUrl(
                        threadId,
                        false
                    );
                }

                announcePreview(
                    payload.announcement || ''
                );

                if (moveFocus) {
                    var heading =
                        regions.reading.querySelector(
                            SELECTORS.previewHeading
                        );

                    if (heading) {
                        heading.setAttribute(
                            'tabindex',
                            '-1'
                        );

                        heading.focus();
                    } else {
                        regions.reading.focus();
                    }
                }
            })
            .catch(function(error) {
                var workspace =
                    getInboxWorkspace();

                var fallbackMessage =
                    workspace
                        ? workspace.dataset.previewError || ''
                        : '';

                renderPreviewError(
                    regions,
                    error.message ||
                        fallbackMessage
                );

                Notification.exception(
                    error
                );
            })
            .finally(function() {
                setPreviewBusy(
                    regions,
                    false
                );
            });
    };

    /**
     * Handles clicks on Inbox thread preview links.
     *
     * @param {MouseEvent} event
     */
    var handleThreadPreviewClick = function(
        event
    ) {
        var link = event.target.closest(
            SELECTORS.threadPreviewLink
        );

        if (!link) {
            return;
        }

        /*
        * Preserve native browser behaviours:
        * Ctrl/Cmd click, Shift click, middle click, new tab.
        */
        if (
            event.button !== 0 ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        event.preventDefault();

        loadThreadPreview(
            link,
            true,
            true
        );
    };

    /**
     * Opens the thread preview when the non-link area of a card is clicked.
     *
     * @param {MouseEvent} event
     */
    var handleThreadCardClick = function(
        event
    ) {
        if (
            event.target.closest(
                'a, button, input, select, textarea, label'
            )
        ) {
            return;
        }

        var card = event.target.closest(
            SELECTORS.threadCard
        );

        if (!card) {
            return;
        }

        var link = card.querySelector(
            SELECTORS.threadPreviewLink
        );

        if (!link) {
            return;
        }

        event.preventDefault();

        loadThreadPreview(
            link,
            true,
            true
        );
    };

    /**
     * Loads the thread selected in the current URL.
     *
     * @param {Boolean} replaceHistory
     */
    var loadThreadFromUrl = function(
        replaceHistory
    ) {
        var url = new URL(
            window.location.href
        );

        var threadId =
            Number(
                url.searchParams.get(
                    'threadid'
                ) || 0
            );

        if (threadId <= 0) {
            return;
        }

        var link = document.querySelector(
            SELECTORS.threadPreviewLink +
            '[data-thread-id="' +
            threadId +
            '"]'
        );

        if (!link) {
            return;
        }

        loadThreadPreview(
            link,
            false,
            false
        ).then(function() {
            if (replaceHistory) {
                updatePreviewUrl(
                    threadId,
                    true
                );
            }
        });
    };

    /**
     * Restores the preview selected by browser history.
     */
    var handlePopState = function() {
        loadThreadFromUrl(
            false
        );
    };

    /**
     * Initialise the Inbox interface.
     */
    var init = function() {
        if (initialized) {
            return;
        }

        initialized = true;

        try {
            document.addEventListener(
                'submit',
                handleSubmit
            );

            document.addEventListener(
                'click',
                handleThreadPreviewClick
            );

            document.addEventListener(
                'click',
                handleThreadCardClick
            );

            window.addEventListener(
                'pageshow',
                handlePageShow
            );

            window.addEventListener(
                'popstate',
                handlePopState
            );

            loadThreadFromUrl(
                true
            );
        } catch (error) {
            reportError(error);
        }
    };

    return {
        init: init
    };
});