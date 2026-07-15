/* eslint-env amd */

define([
    'core/notification'
], function(Notification) {
    'use strict';

    var SELECTORS = {
        confirmForm: '[data-inbox-confirm]',
        busyForm: '[data-inbox-busy-form]',
        submitButton: 'button[type="submit"], input[type="submit"]',
        liveRegion: '[data-inbox-live-region]',
        firstInvalid: ':invalid'
    };

    var initialized = false;

    /**
     * Return the live region belonging to a form or page.
     *
     * @param {HTMLElement} element
     * @returns {HTMLElement|null}
     */
    var findLiveRegion = function(element) {
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
            var containerRegion = container.querySelector(
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

            window.addEventListener(
                'pageshow',
                handlePageShow
            );
        } catch (error) {
            reportError(error);
        }
    };

    return {
        init: init
    };
});