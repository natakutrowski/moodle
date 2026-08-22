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
            '[data-inbox-preview-live-region]',

        loadRemoteImages:
            '[data-inbox-load-images]',

        attachmentInput:
            '[data-inbox-attachment-input]',

        attachmentList:
            '[data-inbox-attachment-list]',

        attachmentBudget:
            '[data-inbox-attachment-budget]',

        richComposer:
            '[data-inbox-rich-composer]',

        richEditor:
            '[data-inbox-rich-editor]',

        bodyText:
            '[data-inbox-body-text]',

        bodyHtml:
            '[data-inbox-body-html]',

        inlineImageTrigger:
            '[data-inbox-inline-image-trigger]',

        inlineImageInput:
            '[data-inbox-inline-image-input]',

        inlineCidContainer:
            '[data-inbox-inline-cid-container]',

        recipientToggle:
            '[data-inbox-toggle-recipient]',

        recipientPicker:
            '[data-inbox-recipient-picker]',

        recipientSearch:
            '[data-inbox-recipient-search]',

        recipientValue:
            '[data-inbox-recipient-value]',

        recipientPills:
            '[data-inbox-recipient-pills]',

        recipientResults:
            '[data-inbox-recipient-results]',

        recipientSuggestion:
            '[data-inbox-recipient-suggestion]',

        recipientRemove:
            '[data-inbox-recipient-remove]',

        subjectToggle:
            '[data-inbox-subject-toggle]',

        subjectInput:
            '[data-inbox-subject-input]',

        autosaveForm:
            '[data-inbox-autosave-form]',

        autosaveStatus:
            '[data-inbox-autosave-status]',

        draftThreadId:
            '[data-inbox-draft-threadid]',

        customPeriodField:
            '[data-inbox-custom-period-field]',

        quickReplySelect:
            '[data-inbox-quick-reply-select]',

        templateType:
            '[data-inbox-template-type]',

        templateSubjectField:
            '[data-inbox-template-subject-field]',

        bulkSelectAll:
            '[data-inbox-select-all]',

        bulkThreadSelect:
            '[data-inbox-thread-select]',

        bulkCount:
            '[data-inbox-bulk-count]',

        bulkActionSelect:
            '[data-inbox-bulk-action-select]',

        bulkApply:
            '[data-inbox-bulk-apply]'
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
        form.dataset.autosaveSubmitting = '0';
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

        validateRecipientPickers(form);

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

        stopAutosaveForSubmit(form);

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
        moveFocus,
        loadImages
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

        if (loadImages) {
            requestUrl.searchParams.set(
                'loadimages',
                '1'
            );
        }

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
     * Reloads the current preview with remote images explicitly enabled.
     * This is deliberately a per-message user action because remote images
     * can contain tracking pixels.
     *
     * @param {MouseEvent} event
     */
    var handleLoadRemoteImages = function(event) {
        var button = event.target.closest(
            SELECTORS.loadRemoteImages
        );

        if (!button) {
            return;
        }

        event.preventDefault();

        var reading = document.querySelector(
            SELECTORS.readingPanel
        );
        var previewContent = reading
            ? reading.querySelector(
                '.crm-inbox-preview-reading-content'
            )
            : null;

        /*
         * Full thread.php has no AJAX preview regions. In that context,
         * reload the current thread with an explicit opt-in query parameter.
         * The server then passes loadimages through the Workspace factory to
         * InboxHtmlSanitizer.
         */
        if (!previewContent) {
            var currentUrl = new URL(
                window.location.href
            );

            currentUrl.searchParams.set(
                'loadimages',
                '1'
            );

            window.location.href =
                currentUrl.toString();

            return;
        }

        var threadId = Number(
            previewContent.dataset.threadId || 0
        );

        if (threadId <= 0) {
            return;
        }

        var link = document.querySelector(
            SELECTORS.threadPreviewLink +
            '[data-thread-id="' + threadId + '"]'
        );

        if (!link) {
            return;
        }

        loadThreadPreview(
            link,
            false,
            false,
            true
        );
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

    var formatBytes = function(bytes) {
        var value = Math.max(0, Number(bytes) || 0);

        if (value < 1024) {
            return value + ' B';
        }

        var units = ['KB', 'MB', 'GB'];
        var unit = -1;

        do {
            value /= 1024;
            unit++;
        } while (value >= 1024 && unit < units.length - 1);

        return value.toFixed(value >= 10 ? 1 : 2) + ' ' + units[unit];
    };

    var numericData = function(input, name, fallback) {
        var value = Number(input.dataset[name]);

        return Number.isFinite(value)
            ? value
            : fallback;
    };

    var attachmentLimits = function(input) {
        return {
            maxFiles:
                numericData(input, 'maxFiles', 10),
            maxFileSize:
                numericData(input, 'maxFileSize', 10485760),
            maxTotalSize:
                numericData(input, 'maxTotalSize', 26214400),
            existingTotalSize:
                numericData(input, 'existingTotalSize', 0)
        };
    };

    var attachmentTotal = function(input, files) {
        var limits = attachmentLimits(input);

        return limits.existingTotalSize + files.reduce(
            function(total, file) {
                return total + (Number(file.size) || 0);
            },
            0
        );
    };

    var renderAttachmentBudget = function(input, files) {
        var form = input.closest('form');
        var budget = form
            ? form.querySelector(SELECTORS.attachmentBudget)
            : null;

        if (!budget) {
            return;
        }

        var limits = attachmentLimits(input);
        var total = attachmentTotal(input, files);
        var remaining = Math.max(
            0,
            limits.maxTotalSize - total
        );

        budget.textContent =
            formatBytes(total) +
            ' / ' +
            formatBytes(limits.maxTotalSize) +
            ' · ' +
            formatBytes(remaining) +
            ' remaining';

        budget.classList.toggle(
            'is-near-limit',
            remaining <= limits.maxTotalSize * 0.2
        );
    };

    var announceAttachmentError = function(input, message) {
        var form = input.closest('form');
        var budget = form
            ? form.querySelector(SELECTORS.attachmentBudget)
            : null;

        if (!budget) {
            return;
        }

        budget.textContent = message;
        budget.classList.add('is-error');
    };

    var attachmentQueues = new WeakMap();

    /**
     * Stable browser-side identity for one selected file.
     *
     * @param {File} file
     * @returns {String}
     */
    var attachmentKey = function(file) {
        return [
            file.name || '',
            String(file.size || 0),
            String(file.lastModified || 0)
        ].join('|');
    };

    /**
     * Push the queued files back into the native input FileList.
     *
     * DataTransfer is supported by the browsers targeted by Moodle 5 and
     * lets successive file-picker selections behave additively.
     *
     * @param {HTMLInputElement} input
     * @param {File[]} files
     */
    var syncAttachmentInput = function(input, files) {
        if (typeof window.DataTransfer !== 'function') {
            return;
        }

        var transfer = new window.DataTransfer();

        files.forEach(function(file) {
            transfer.items.add(file);
        });

        input.files = transfer.files;
    };

    /**
     * Render files queued for the next save/send.
     *
     * @param {HTMLInputElement} input
     * @param {File[]} files
     */
    var renderAttachmentQueue = function(input, files) {
        var form = input.closest('form');
        var list = form
            ? form.querySelector(SELECTORS.attachmentList)
            : null;

        if (!list) {
            return;
        }

        list.textContent = '';

        files.forEach(function(file, index) {
            var row = document.createElement('div');
            row.className = 'crm-inbox-reply-new-file';

            var details = document.createElement('span');
            details.className = 'crm-inbox-reply-new-file-details';

            var name = document.createElement('span');
            name.className = 'crm-inbox-reply-new-file-name';
            name.textContent = file.name;

            var size = document.createElement('span');
            size.className = 'crm-inbox-reply-new-file-size';
            size.textContent = formatBytes(file.size);

            details.appendChild(name);
            details.appendChild(size);

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className =
                'btn btn-sm btn-link crm-inbox-reply-new-file-remove';
            remove.textContent = '×';
            remove.setAttribute(
                'aria-label',
                'Remove ' + file.name
            );

            remove.addEventListener('click', function() {
                var current =
                    attachmentQueues.get(input) || [];

                current = current.filter(function(queued, queuedIndex) {
                    return queuedIndex !== index;
                });

                attachmentQueues.set(input, current);
                syncAttachmentInput(input, current);
                renderAttachmentQueue(input, current);
            });

            row.appendChild(details);
            row.appendChild(remove);
            list.appendChild(row);
        });

        renderAttachmentBudget(input, files);
    };

    /**
     * Make multiple picker openings additive instead of replacing the
     * previous selection. This allows files to be selected from different
     * directories before one send.
     *
     * @param {Event} event
     */
    var handleAttachmentSelection = function(event) {
        var input = event.target.closest
            ? event.target.closest(SELECTORS.attachmentInput)
            : null;

        if (!input) {
            return;
        }

        var previous =
            attachmentQueues.get(input) || [];

        var merged = previous.slice();
        var known = new Set(
            merged.map(attachmentKey)
        );

        var limits = attachmentLimits(input);
        var rejected = [];

        Array.from(input.files || []).forEach(function(file) {
            var key = attachmentKey(file);

            if (known.has(key)) {
                return;
            }

            if (file.size > limits.maxFileSize) {
                rejected.push(
                    file.name +
                    ' (' +
                    formatBytes(file.size) +
                    ' > ' +
                    formatBytes(limits.maxFileSize) +
                    ')'
                );
                return;
            }

            if (merged.length >= limits.maxFiles) {
                rejected.push(
                    file.name +
                    ' (maximum ' +
                    limits.maxFiles +
                    ' files)'
                );
                return;
            }

            var candidate = merged.concat([file]);
            var total = attachmentTotal(input, candidate);

            if (total > limits.maxTotalSize) {
                rejected.push(
                    file.name +
                    ' (total would exceed ' +
                    formatBytes(limits.maxTotalSize) +
                    ')'
                );
                return;
            }

            merged.push(file);
            known.add(key);
        });

        attachmentQueues.set(input, merged);
        syncAttachmentInput(input, merged);
        renderAttachmentQueue(input, merged);

        if (rejected.length > 0) {
            announceAttachmentError(
                input,
                'Rejected: ' + rejected.join(' · ')
            );
        }
    };


    var recipientSearchTimers = new WeakMap();

    var recipientItems = function(picker) {
        var value = picker.querySelector(
            SELECTORS.recipientValue
        );
        var labels = {};

        try {
            labels = JSON.parse(
                picker.dataset.recipientLabels || '{}'
            ) || {};
        } catch (error) {
            labels = {};
        }

        if (!value || !value.value.trim()) {
            return [];
        }

        return value.value
            .split(/[,;]+/)
            .map(function(email) {
                return email.trim();
            })
            .filter(Boolean)
            .map(function(email) {
                return {
                    email: email,
                    name: String(
                        labels[email.toLowerCase()] || ''
                    ).trim()
                };
            });
    };

    var isEmail = function(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
            String(value || '').trim()
        );
    };

    var syncRecipientValue = function(picker, items) {
        var hidden = picker.querySelector(
            SELECTORS.recipientValue
        );

        if (hidden) {
            hidden.value = items
                .map(function(item) {
                    return item.email;
                })
                .join(', ');
        }

        picker._inboxRecipientItems = items;
    };

    var closeRecipientResults = function(picker) {
        var results = picker.querySelector(
            SELECTORS.recipientResults
        );
        var search = picker.querySelector(
            SELECTORS.recipientSearch
        );

        if (results) {
            results.textContent = '';
            results.classList.add('d-none');
        }

        if (search) {
            search.setAttribute('aria-expanded', 'false');
        }

        picker._inboxRecipientActiveIndex = -1;
    };

    var renderRecipientPills = function(picker) {
        var holder = picker.querySelector(
            SELECTORS.recipientPills
        );
        var items = picker._inboxRecipientItems || [];

        if (!holder) {
            return;
        }

        holder.textContent = '';

        items.forEach(function(item, index) {
            var pill = document.createElement('span');
            pill.className = 'crm-inbox-recipient-pill';

            var text = document.createElement('span');
            text.className = 'crm-inbox-recipient-pill-text';
            text.textContent = item.name
                ? item.name + ' · ' + item.email
                : item.email;

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'crm-inbox-recipient-pill-remove';
            remove.dataset.inboxRecipientRemove = String(index);
            remove.setAttribute('aria-label', 'Remove ' + item.email);
            remove.textContent = '×';

            pill.appendChild(text);
            pill.appendChild(remove);
            holder.appendChild(pill);
        });
    };

    var addRecipient = function(picker, item) {
        var email = String(item.email || '')
            .trim()
            .toLowerCase();

        if (!isEmail(email)) {
            return false;
        }

        var items = picker._inboxRecipientItems || [];
        var exists = items.some(function(existing) {
            return existing.email.toLowerCase() === email;
        });

        if (!exists) {
            items.push({
                email: email,
                name: String(item.name || '').trim()
            });
        }

        syncRecipientValue(picker, items);
        renderRecipientPills(picker);

        var search = picker.querySelector(
            SELECTORS.recipientSearch
        );

        if (search) {
            search.value = '';
            search.setCustomValidity('');
        }

        closeRecipientResults(picker);
        return true;
    };

    var renderRecipientResults = function(picker, results) {
        var holder = picker.querySelector(
            SELECTORS.recipientResults
        );
        var search = picker.querySelector(
            SELECTORS.recipientSearch
        );

        if (!holder || !search) {
            return;
        }

        holder.textContent = '';

        results.forEach(function(result) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'crm-inbox-recipient-result';
            button.dataset.inboxRecipientSuggestion = '1';
            button.dataset.email = result.email || '';
            button.dataset.name = result.name || '';
            button.setAttribute('aria-selected', 'false');

            var avatar = document.createElement('span');
            avatar.className = 'crm-inbox-recipient-result-avatar';
            avatar.textContent = '👤';

            var copy = document.createElement('span');
            copy.className = 'crm-inbox-recipient-result-copy';

            var name = document.createElement('strong');
            name.textContent = result.name || result.email || '';

            var email = document.createElement('span');
            email.textContent = result.email || '';

            var badge = document.createElement('span');
            badge.className = 'crm-inbox-recipient-result-user360';
            badge.textContent = 'User360';

            copy.appendChild(name);
            copy.appendChild(email);
            button.appendChild(avatar);
            button.appendChild(copy);
            button.appendChild(badge);
            holder.appendChild(button);
        });

        if (results.length > 0) {
            holder.classList.remove('d-none');
            search.setAttribute('aria-expanded', 'true');
            picker._inboxRecipientActiveIndex = -1;
        } else {
            closeRecipientResults(picker);
        }
    };

    var searchRecipients = function(picker, query) {
        var url = picker.dataset.recipientSearchUrl || '';

        if (!url || query.trim().length < 2) {
            closeRecipientResults(picker);
            return;
        }

        var endpoint = new URL(url, window.location.origin);
        endpoint.searchParams.set('q', query.trim());
        endpoint.searchParams.set('sesskey', Config.sesskey);

        window.fetch(
            endpoint.toString(),
            {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'}
            }
        )
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Recipient search failed');
                }
                return response.json();
            })
            .then(function(payload) {
                renderRecipientResults(
                    picker,
                    Array.isArray(payload.results)
                        ? payload.results
                        : []
                );
            })
            .catch(function(error) {
                closeRecipientResults(picker);
                reportError(error);
            });
    };

    var handleRecipientInput = function(event) {
        var search = event.target.closest
            ? event.target.closest(
                SELECTORS.recipientSearch
            )
            : null;

        if (!search) {
            return;
        }

        var picker = search.closest(
            SELECTORS.recipientPicker
        );

        if (!picker) {
            return;
        }

        var previous = recipientSearchTimers.get(picker);
        if (previous) {
            window.clearTimeout(previous);
        }

        recipientSearchTimers.set(
            picker,
            window.setTimeout(function() {
                searchRecipients(picker, search.value);
            }, 220)
        );
    };

    var recipientSuggestions = function(picker) {
        return Array.from(
            picker.querySelectorAll(
                SELECTORS.recipientSuggestion
            )
        );
    };

    var setActiveRecipientSuggestion = function(picker, index) {
        var suggestions = recipientSuggestions(picker);

        if (suggestions.length === 0) {
            picker._inboxRecipientActiveIndex = -1;
            return null;
        }

        index = Math.max(0, Math.min(index, suggestions.length - 1));
        picker._inboxRecipientActiveIndex = index;

        suggestions.forEach(function(suggestion, suggestionIndex) {
            suggestion.classList.toggle(
                'is-active',
                suggestionIndex === index
            );
            suggestion.setAttribute(
                'aria-selected',
                suggestionIndex === index ? 'true' : 'false'
            );
        });

        suggestions[index].scrollIntoView({block: 'nearest'});
        return suggestions[index];
    };

    var handleRecipientKeydown = function(event) {
        var search = event.target.closest
            ? event.target.closest(
                SELECTORS.recipientSearch
            )
            : null;

        if (!search) {
            return;
        }

        var picker = search.closest(
            SELECTORS.recipientPicker
        );

        if (!picker) {
            return;
        }

        var suggestions = recipientSuggestions(picker);

        if (event.key === 'ArrowDown' && suggestions.length > 0) {
            event.preventDefault();
            setActiveRecipientSuggestion(
                picker,
                Math.min(
                    (picker._inboxRecipientActiveIndex ?? -1) + 1,
                    suggestions.length - 1
                )
            );
            return;
        }

        if (event.key === 'ArrowUp' && suggestions.length > 0) {
            event.preventDefault();
            setActiveRecipientSuggestion(
                picker,
                Math.max(
                    (picker._inboxRecipientActiveIndex ?? suggestions.length) - 1,
                    0
                )
            );
            return;
        }

        if (event.key === 'Escape') {
            closeRecipientResults(picker);
            return;
        }

        var value = search.value
            .replace(/[,;]+$/, '')
            .trim();

        if (event.key === 'Enter' && suggestions.length > 0) {
            event.preventDefault();
            var activeIndex = picker._inboxRecipientActiveIndex ?? 0;
            var suggestion = suggestions[Math.max(0, activeIndex)];

            if (suggestion) {
                addRecipient(
                    picker,
                    {
                        email: suggestion.dataset.email || '',
                        name: suggestion.dataset.name || ''
                    }
                );
            }
            return;
        }

        if (
            event.key !== 'Enter'
            && event.key !== ','
            && event.key !== ';'
        ) {
            return;
        }

        if (!value) {
            return;
        }

        if (isEmail(value)) {
            event.preventDefault();
            addRecipient(
                picker,
                {email: value, name: ''}
            );
        }
    };

    var handleRecipientPaste = function(event) {
        var search = event.target.closest
            ? event.target.closest(
                SELECTORS.recipientSearch
            )
            : null;

        if (!search || !event.clipboardData) {
            return;
        }

        var raw = event.clipboardData.getData('text');
        var emails = raw.split(/[;,\\s]+/).filter(isEmail);

        if (emails.length < 2) {
            return;
        }

        var picker = search.closest(
            SELECTORS.recipientPicker
        );

        if (!picker) {
            return;
        }

        event.preventDefault();
        emails.forEach(function(email) {
            addRecipient(picker, {email: email, name: ''});
        });
    };

    var handleRecipientClick = function(event) {
        var suggestion = event.target.closest
            ? event.target.closest(
                SELECTORS.recipientSuggestion
            )
            : null;

        if (suggestion) {
            var picker = suggestion.closest(
                SELECTORS.recipientPicker
            );

            if (picker) {
                addRecipient(
                    picker,
                    {
                        email: suggestion.dataset.email || '',
                        name: suggestion.dataset.name || ''
                    }
                );
            }
            return;
        }

        var remove = event.target.closest
            ? event.target.closest(
                SELECTORS.recipientRemove
            )
            : null;

        if (remove) {
            var parent = remove.closest(
                SELECTORS.recipientPicker
            );

            if (!parent) {
                return;
            }

            var index = parseInt(
                remove.dataset.inboxRecipientRemove,
                10
            );
            var items = parent._inboxRecipientItems || [];

            if (!Number.isNaN(index)) {
                items.splice(index, 1);
                syncRecipientValue(parent, items);
                renderRecipientPills(parent);
            }
        }
    };

    var handleRecipientOutsideClick = function(event) {
        var activePicker = event.target.closest
            ? event.target.closest(
                SELECTORS.recipientPicker
            )
            : null;

        document.querySelectorAll(
            SELECTORS.recipientPicker
        ).forEach(function(picker) {
            if (picker !== activePicker) {
                closeRecipientResults(picker);
            }
        });
    };

    var initRecipientPickers = function() {
        document.querySelectorAll(
            SELECTORS.recipientPicker
        ).forEach(function(picker) {
            picker._inboxRecipientItems = recipientItems(picker);
            renderRecipientPills(picker);
        });
    };

    var validateRecipientPickers = function(form) {
        var valid = true;

        form.querySelectorAll(
            SELECTORS.recipientPicker
        ).forEach(function(picker) {
            if (picker.dataset.recipientRequired !== '1') {
                return;
            }

            var search = picker.querySelector(
                SELECTORS.recipientSearch
            );
            var items = picker._inboxRecipientItems || [];

            if (!search) {
                return;
            }

            if (items.length === 0 && isEmail(search.value)) {
                addRecipient(
                    picker,
                    {email: search.value, name: ''}
                );
            }

            if ((picker._inboxRecipientItems || []).length === 0) {
                search.setCustomValidity(
                    picker.dataset.recipientRequiredMessage
                    || 'Add at least one recipient.'
                );
                valid = false;
            } else {
                search.setCustomValidity('');
            }
        });

        return valid;
    };

    var handleSubjectToggle = function(event) {
        var button = event.target.closest
            ? event.target.closest(
                SELECTORS.subjectToggle
            )
            : null;

        if (!button) {
            return;
        }

        var form = button.closest('form');
        var subject = form
            ? form.querySelector(
                SELECTORS.subjectInput
            )
            : null;

        if (!subject) {
            return;
        }

        subject.removeAttribute('readonly');
        subject.focus();
        subject.select();
        button.classList.add('d-none');
    };

    var handleRecipientToggle = function(event) {
        var button = event.target.closest(
            SELECTORS.recipientToggle
        );

        if (!button) {
            return;
        }

        event.preventDefault();

        var name =
            button.dataset.inboxToggleRecipient;

        var form = button.closest('form');

        var field = form
            ? form.querySelector(
                '[data-inbox-recipient-field="' +
                name +
                '"]'
            )
            : null;

        if (!field) {
            return;
        }

        field.classList.toggle(
            'd-none'
        );

        var input = field.querySelector(
            SELECTORS.recipientSearch
        );

        if (
            input
            && !field.classList.contains('d-none')
        ) {
            input.focus();
        }
    };

    var inlineImageQueues = new WeakMap();

    var makeInlineCid = function() {
        return (
            'crm-inline-' +
            Date.now() +
            '-' +
            Math.random().toString(36).slice(2) +
            '@campusfr'
        );
    };

    var syncInlineFiles = function(input, entries) {
        if (typeof window.DataTransfer !== 'function') {
            return;
        }

        var transfer = new window.DataTransfer();

        entries.forEach(function(entry) {
            transfer.items.add(entry.file);
        });

        input.files = transfer.files;
    };

    var syncInlineCidInputs = function(composer, entries) {
        var container = composer.querySelector(
            SELECTORS.inlineCidContainer
        );

        if (!container) {
            return;
        }

        container.textContent = '';

        entries.forEach(function(entry) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'inlinecids[]';
            hidden.value = entry.cid;
            container.appendChild(hidden);
        });
    };

    var tinyEditorFor = function(editor) {
        if (!editor || !editor.id || !window.tinymce) {
            return null;
        }

        return window.tinymce.get(editor.id) || null;
    };

    var currentEditorHtml = function(editor) {
        var tiny = tinyEditorFor(editor);

        if (tiny) {
            return tiny.getContent({format: 'html'});
        }

        return editor.value || editor.innerHTML || '';
    };

    var editorHtmlForSubmit = function(editor) {
        var holder = document.createElement('div');
        holder.innerHTML = currentEditorHtml(editor);

        holder.querySelectorAll(
            'img[data-inline-cid]'
        ).forEach(function(image) {
            var cid = image.dataset.inlineCid || '';

            if (cid) {
                image.setAttribute('src', 'cid:' + cid);
            }

            image.removeAttribute('data-inline-cid');
            image.removeAttribute('data-inline-object-url');
        });

        return holder.innerHTML;
    };

    var syncRichComposer = function(composer) {
        var editor = composer.querySelector(SELECTORS.richEditor);
        var bodyText = composer.querySelector(SELECTORS.bodyText);
        var bodyHtml = composer.querySelector(SELECTORS.bodyHtml);

        if (!editor || !bodyText || !bodyHtml) {
            return;
        }

        var html = editorHtmlForSubmit(editor).trim();
        var textHolder = document.createElement('div');
        textHolder.innerHTML = html;

        bodyText.value = (textHolder.innerText || textHolder.textContent || '').trim();
        bodyHtml.value = html;
    };

    var insertInlineImage = function(editor, image) {
        var tiny = tinyEditorFor(editor);

        if (tiny) {
            tiny.insertContent(image.outerHTML + '<p><br></p>');
            return;
        }

        var value = editor.value || '';
        editor.value = value + image.outerHTML + '<p><br></p>';
    };

    var addInlineImageFiles = function(
        composer,
        files
    ) {
        var input = composer.querySelector(
            SELECTORS.inlineImageInput
        );
        var editor = composer.querySelector(
            SELECTORS.richEditor
        );

        if (!input || !editor) {
            return;
        }

        var entries =
            inlineImageQueues.get(input) || [];

        Array.from(files || []).forEach(function(file) {
            if (!file.type || !file.type.startsWith('image/')) {
                return;
            }

            var maxFileSize =
                Number(input.dataset.maxFileSize) ||
                10485760;

            if (file.size > maxFileSize) {
                announceAttachmentError(
                    input,
                    file.name +
                    ' (' +
                    formatBytes(file.size) +
                    ' > ' +
                    formatBytes(maxFileSize) +
                    ')'
                );

                return;
            }

            var cid = makeInlineCid();
            var url = URL.createObjectURL(file);

            entries.push({
                file: file,
                cid: cid,
                url: url
            });

            var image = document.createElement('img');
            image.src = url;
            image.alt = file.name || '';
            image.dataset.inlineCid = cid;
            image.dataset.inlineObjectUrl = url;
            image.className =
                'crm-inbox-inline-compose-image';

            insertInlineImage(editor, image);
        });

        inlineImageQueues.set(
            input,
            entries
        );
        syncInlineFiles(
            input,
            entries
        );
        syncInlineCidInputs(
            composer,
            entries
        );
        syncRichComposer(
            composer
        );
    };

    var initExistingInlineImages = function(composer) {
        var root = document.querySelector(
            '[data-inbox-inline-existing]'
        );

        if (!root) {
            return;
        }

        var raw =
            root.dataset.inboxInlineExisting || '';

        if (!raw) {
            return;
        }

        var entries;

        try {
            entries = JSON.parse(raw);
        } catch (error) {
            return;
        }

        var editor = composer.querySelector(
            SELECTORS.richEditor
        );

        if (!editor) {
            return;
        }

        var applyExisting = function() {
            var holder = document.createElement('div');
            holder.innerHTML = currentEditorHtml(editor);
            var changed = false;

            entries.forEach(function(entry) {
                holder.querySelectorAll('img').forEach(function(image) {
                    if (image.getAttribute('src') === 'cid:' + entry.cid) {
                        image.setAttribute('src', entry.url);
                        image.dataset.inlineCid = entry.cid;
                        changed = true;
                    }
                });
            });

            if (!changed) {
                return;
            }

            var tiny = tinyEditorFor(editor);
            if (tiny) {
                tiny.setContent(holder.innerHTML);
            } else {
                editor.value = holder.innerHTML;
            }
        };

        applyExisting();
        window.setTimeout(applyExisting, 300);
        window.setTimeout(applyExisting, 900);
    };

    var handleInlineImageTrigger = function(event) {
        var button = event.target.closest(
            SELECTORS.inlineImageTrigger
        );

        if (!button) {
            return;
        }

        event.preventDefault();

        var composer = button.closest(
            SELECTORS.richComposer
        );

        var input = composer
            ? composer.querySelector(
                SELECTORS.inlineImageInput
            )
            : null;

        if (input) {
            input.click();
        }
    };

    var handleInlineImageSelection = function(event) {
        var input = event.target.closest
            ? event.target.closest(
                SELECTORS.inlineImageInput
            )
            : null;

        if (!input) {
            return;
        }

        var composer = input.closest(
            SELECTORS.richComposer
        );

        if (!composer) {
            return;
        }

        addInlineImageFiles(
            composer,
            input.files
        );
    };

    var handleInlinePaste = function(event) {
        var editor = event.target.closest
            ? event.target.closest(
                SELECTORS.richEditor
            )
            : null;

        if (!editor) {
            return;
        }

        var files = Array.from(
            event.clipboardData
                ? event.clipboardData.files || []
                : []
        ).filter(function(file) {
            return file.type
                && file.type.startsWith('image/');
        });

        if (files.length === 0) {
            return;
        }

        event.preventDefault();

        addInlineImageFiles(
            editor.closest(
                SELECTORS.richComposer
            ),
            files
        );
    };

    var handleInlineDrop = function(event) {
        var editor = event.target.closest
            ? event.target.closest(
                SELECTORS.richEditor
            )
            : null;

        if (!editor) {
            return;
        }

        var files = Array.from(
            event.dataTransfer
                ? event.dataTransfer.files || []
                : []
        ).filter(function(file) {
            return file.type
                && file.type.startsWith('image/');
        });

        if (files.length === 0) {
            return;
        }

        event.preventDefault();

        addInlineImageFiles(
            editor.closest(
                SELECTORS.richComposer
            ),
            files
        );
    };

    var initRichComposers = function() {
        document.querySelectorAll(
            SELECTORS.richComposer
        ).forEach(function(composer) {
            initExistingInlineImages(
                composer
            );

            var editor = composer.querySelector(
                SELECTORS.richEditor
            );

            if (!editor) {
                return;
            }

            var sync = function() {
                syncRichComposer(composer);
            };

            editor.addEventListener('input', sync);
            editor.addEventListener('change', sync);

            var bindTiny = function() {
                var tiny = tinyEditorFor(editor);
                if (!tiny || tiny.__crmInboxBound) {
                    return;
                }
                tiny.__crmInboxBound = true;
                tiny.on('input change keyup undo redo SetContent', sync);
                initExistingInlineImages(composer);
                sync();
            };

            bindTiny();
            window.setTimeout(bindTiny, 300);
            window.setTimeout(bindTiny, 900);
            sync();
        });
    };

    var autosaveStates = new WeakMap();

    var serializeAutosave = function(form) {
        var composer = form.querySelector(
            SELECTORS.richComposer
        );

        if (composer) {
            syncRichComposer(composer);
        }

        var params = new URLSearchParams();

        params.set(
            'sesskey',
            Config.sesskey
        );

        params.set(
            'mode',
            form.dataset.autosaveMode || 'reply'
        );

        [
            'accountid',
            'threadid',
            'subject',
            'body',
            'bodyhtml'
        ].forEach(function(name) {
            var field = form.elements[name];

            if (field) {
                params.set(
                    name,
                    field.value || ''
                );
            }
        });

        ['to', 'cc', 'bcc'].forEach(function(name) {
            var field = form.elements[name];

            if (!field || !field.value) {
                return;
            }

            field.value.split(/[;,]+/).forEach(function(value) {
                value = value.trim();

                if (value) {
                    params.append(
                        name + '[]',
                        value
                    );
                }
            });
        });

        return params;
    };

    var autosaveFingerprint = function(form) {
        return serializeAutosave(form).toString();
    };

    var setAutosaveStatus = function(
        form,
        state,
        message
    ) {
        var status = form.querySelector(
            SELECTORS.autosaveStatus
        );

        if (!status) {
            return;
        }

        status.dataset.state = state;
        status.textContent = message || '';
    };

    var hasPendingFiles = function(form) {
        return Array.from(
            form.querySelectorAll(
                'input[type="file"]'
            )
        ).some(function(input) {
            return input.files
                && input.files.length > 0;
        });
    };

    var runAutosave = function(form) {
        var state = autosaveStates.get(form);

        if (
            !state
            || state.busy
            || form.dataset.inboxSubmitting === '1'
            || form.dataset.autosaveSubmitting === '1'
        ) {
            return;
        }

        var fingerprint = autosaveFingerprint(
            form
        );

        if (
            fingerprint === state.lastSaved
            || fingerprint === state.lastAttempt
        ) {
            return;
        }

        state.busy = true;
        state.lastAttempt = fingerprint;

        setAutosaveStatus(
            form,
            'saving',
            form.dataset.autosaveSaving || 'Saving…'
        );

        state.controller = new AbortController();

        fetch(
            form.dataset.autosaveUrl,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: serializeAutosave(form).toString(),
                signal: state.controller.signal
            }
        )
            .then(function(response) {
                if (!response.ok) {
                    throw new Error(
                        'Autosave HTTP ' + response.status
                    );
                }

                return response.json();
            })
            .then(function(result) {
                if (!result.success) {
                    throw new Error(
                        result.message || 'Autosave failed'
                    );
                }

                state.lastSaved = autosaveFingerprint(
                    form
                );
                state.lastAttempt = state.lastSaved;

                var threadField =
                    form.querySelector(
                        SELECTORS.draftThreadId
                    );

                if (
                    threadField
                    && Number(result.threadid) > 0
                ) {
                    threadField.value =
                        String(result.threadid);

                    if (
                        form.dataset.autosaveMode === 'compose'
                        && window.history
                        && window.history.replaceState
                    ) {
                        var url = new URL(
                            window.location.href
                        );

                        url.searchParams.set(
                            'threadid',
                            String(result.threadid)
                        );

                        window.history.replaceState(
                            {},
                            '',
                            url.toString()
                        );
                    }
                }

                setAutosaveStatus(
                    form,
                    'saved',
                    form.dataset.autosaveSaved || 'Saved'
                );
            })
            .catch(function(error) {
                state.lastAttempt = '';

                if (error && error.name === 'AbortError') {
                    return;
                }

                setAutosaveStatus(
                    form,
                    'error',
                    form.dataset.autosaveError ||
                        'Autosave unavailable'
                );
            })
            .finally(function() {
                state.busy = false;
                state.controller = null;
            });
    };

    var stopAutosaveForSubmit = function(form) {
        if (!form || !form.matches(SELECTORS.autosaveForm)) {
            return;
        }

        form.dataset.autosaveSubmitting = '1';

        var state = autosaveStates.get(form);

        if (!state) {
            return;
        }

        if (state.controller) {
            state.controller.abort();
        }

        state.lastAttempt = '';
        state.lastSaved = autosaveFingerprint(form);
    };

    var handleAutosaveSubmitIntent = function(event) {
        var submitter = event.target.closest
            ? event.target.closest(SELECTORS.submitButton)
            : null;

        if (!submitter || !submitter.form) {
            return;
        }

        stopAutosaveForSubmit(submitter.form);
    };

    var initAutosave = function() {
        document.querySelectorAll(
            SELECTORS.autosaveForm
        ).forEach(function(form) {
            var interval = Number(
                form.dataset.autosaveInterval || 8000
            );

            autosaveStates.set(
                form,
                {
                    busy: false,
                    controller: null,
                    lastSaved:
                        autosaveFingerprint(form),
                    lastAttempt: ''
                }
            );

            window.setInterval(
                function() {
                    runAutosave(form);
                },
                Math.max(4000, interval)
            );

            form.addEventListener(
                'focusout',
                function() {
                    window.setTimeout(
                        function() {
                            runAutosave(form);
                        },
                        150
                    );
                }
            );
        });

        window.addEventListener(
            'beforeunload',
            function(event) {
                var shouldWarn = false;

                document.querySelectorAll(
                    SELECTORS.autosaveForm
                ).forEach(function(form) {
                    var state =
                        autosaveStates.get(form);

                    if (
                        !state
                        || form.dataset.inboxSubmitting === '1'
                        || form.dataset.autosaveSubmitting === '1'
                    ) {
                        return;
                    }

                    if (
                        autosaveFingerprint(form)
                            !== state.lastSaved
                        || hasPendingFiles(form)
                    ) {
                        shouldWarn = true;
                    }
                });

                if (!shouldWarn) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            }
        );
    };

    var insertQuickReply = function(
        select
    ) {
        var id = Number(select.value || 0);

        if (id <= 0) {
            return;
        }

        var url = new URL(
            select.dataset.templateUrl,
            window.location.origin
        );

        url.searchParams.set(
            'id',
            String(id)
        );

        fetch(
            url.toString(),
            {
                credentials: 'same-origin'
            }
        )
            .then(function(response) {
                if (!response.ok) {
                    throw new Error(
                        'Template HTTP ' + response.status
                    );
                }

                return response.json();
            })
            .then(function(result) {
                if (!result.success) {
                    return;
                }

                var form = select.closest('form');
                var textarea = form
                    ? form.querySelector(
                        'textarea[data-inbox-editor="1"]'
                    )
                    : null;

                var editor = textarea
                    && window.tinyMCE
                    ? window.tinyMCE.get(
                        textarea.id
                    )
                    : null;

                if (editor) {
                    editor.insertContent(
                        result.bodyhtml || ''
                    );
                } else if (textarea) {
                    textarea.value =
                        textarea.value
                        + (result.bodyhtml || '');
                }

                if (
                    form
                    && result.subject
                    && form.elements.subject
                    && !form.elements.subject.value
                ) {
                    form.elements.subject.value =
                        result.subject;
                }

                select.value = '';
            })
            .catch(function() {
                select.value = '';
            });
    };

    var handleQuickReplyChange = function(
        event
    ) {
        var select = event.target.closest
            ? event.target.closest(
                SELECTORS.quickReplySelect
            )
            : null;

        if (!select) {
            return;
        }

        insertQuickReply(select);
    };

    var syncTemplateTypeFields = function(
        select
    ) {
        var form = select.closest('form');

        if (!form) {
            return;
        }

        var subjectField = form.querySelector(
            SELECTORS.templateSubjectField
        );

        if (!subjectField) {
            return;
        }

        var isSignature =
            select.value === 'signature';

        subjectField.classList.toggle(
            'd-none',
            isSignature
        );

        var subject = subjectField.querySelector(
            'input[name="subject"]'
        );

        if (subject) {
            subject.disabled = isSignature;

            if (isSignature) {
                subject.value = '';
            }
        }
    };

    var initTemplateTypeFields = function() {
        document.querySelectorAll(
            SELECTORS.templateType
        ).forEach(function(select) {
            syncTemplateTypeFields(select);
        });
    };

    var handleTemplateTypeChange = function(
        event
    ) {
        var select = event.target.closest
            ? event.target.closest(
                SELECTORS.templateType
            )
            : null;

        if (!select) {
            return;
        }

        syncTemplateTypeFields(select);
    };

    var syncBulkToolbar = function() {
        var checkboxes = Array.from(
            document.querySelectorAll(
                SELECTORS.bulkThreadSelect
            )
        );

        if (checkboxes.length === 0) {
            return;
        }

        var selected = checkboxes.filter(
            function(checkbox) {
                return checkbox.checked;
            }
        );

        var selectAll = document.querySelector(
            SELECTORS.bulkSelectAll
        );

        if (selectAll) {
            selectAll.checked =
                selected.length === checkboxes.length;

            selectAll.indeterminate =
                selected.length > 0
                && selected.length < checkboxes.length;
        }

        var count = document.querySelector(
            SELECTORS.bulkCount
        );

        if (count) {
            count.textContent =
                String(selected.length);
        }

        var action = document.querySelector(
            SELECTORS.bulkActionSelect
        );

        var apply = document.querySelector(
            SELECTORS.bulkApply
        );

        if (apply) {
            apply.disabled =
                selected.length === 0
                || !action
                || !action.value;
        }
    };

    var handleBulkChange = function(event) {
        var selectAll = event.target.closest
            ? event.target.closest(
                SELECTORS.bulkSelectAll
            )
            : null;

        if (selectAll) {
            document.querySelectorAll(
                SELECTORS.bulkThreadSelect
            ).forEach(function(checkbox) {
                checkbox.checked =
                    selectAll.checked;
            });

            syncBulkToolbar();
            return;
        }

        if (
            event.target.matches(
                SELECTORS.bulkThreadSelect
            )
            || event.target.matches(
                SELECTORS.bulkActionSelect
            )
        ) {
            syncBulkToolbar();
        }
    };

    var handleBulkSubmit = function(event) {
        var form = event.target.closest
            ? event.target.closest(
                '.crm-inbox-bulk-form'
            )
            : null;

        if (!form) {
            return;
        }

        var action = form.querySelector(
            SELECTORS.bulkActionSelect
        );

        if (
            action
            && action.value === 'trash'
            && !window.confirm(
                form.dataset.bulkTrashConfirm
                    || 'Move selected conversations to Trash?'
            )
        ) {
            event.preventDefault();
        }
    };

    var syncCustomPeriodFields = function(select) {
        if (!select || select.name !== 'period') {
            return;
        }

        var form = select.form;

        if (!form) {
            return;
        }

        form.querySelectorAll(
            SELECTORS.customPeriodField
        ).forEach(function(field) {
            field.classList.toggle(
                'd-none',
                select.value !== 'custom'
            );
        });
    };

    var handlePeriodChange = function(event) {
        var select = event.target;

        if (
            !(select instanceof HTMLSelectElement)
            || select.name !== 'period'
        ) {
            return;
        }

        syncCustomPeriodFields(select);
    };

    var initCustomPeriods = function() {
        document.querySelectorAll(
            'select[name="period"]'
        ).forEach(syncCustomPeriodFields);
    };

    var initBulkToolbar = function() {
        syncBulkToolbar();

        var form = document.querySelector(
            '.crm-inbox-bulk-form'
        );

        if (form) {
            form.addEventListener(
                'submit',
                handleBulkSubmit
            );
        }
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

            document.addEventListener(
                'click',
                handleLoadRemoteImages
            );

            document.addEventListener(
                'change',
                handleAttachmentSelection
            );

            document.addEventListener(
                'change',
                handlePeriodChange
            );

            document.addEventListener(
                'click',
                handleAutosaveSubmitIntent,
                true
            );

            document.addEventListener(
                'click',
                handleRecipientToggle
            );

            document.addEventListener(
                'input',
                handleRecipientInput
            );

            document.addEventListener(
                'keydown',
                handleRecipientKeydown
            );

            document.addEventListener(
                'paste',
                handleRecipientPaste
            );

            document.addEventListener(
                'click',
                handleRecipientClick
            );

            document.addEventListener(
                'click',
                handleRecipientOutsideClick
            );

            document.addEventListener(
                'click',
                handleSubjectToggle
            );

            document.addEventListener(
                'click',
                handleInlineImageTrigger
            );

            document.addEventListener(
                'change',
                handleInlineImageSelection
            );

            document.addEventListener(
                'change',
                handleQuickReplyChange
            );

            document.addEventListener(
                'change',
                handleTemplateTypeChange
            );

            document.addEventListener(
                'change',
                handleBulkChange
            );

            document.addEventListener(
                'paste',
                handleInlinePaste
            );

            document.addEventListener(
                'drop',
                handleInlineDrop
            );

            initRecipientPickers();
            initCustomPeriods();
            initRichComposers();
            initAutosave();
            initTemplateTypeFields();
            initBulkToolbar();

            document.addEventListener(
                'submit',
                function(event) {
                    var composer = event.target.querySelector
                        ? event.target.querySelector(
                            SELECTORS.richComposer
                        )
                        : null;

                    if (composer) {
                        syncRichComposer(
                            composer
                        );
                    }
                },
                true
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