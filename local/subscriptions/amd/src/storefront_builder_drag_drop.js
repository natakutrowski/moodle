/* eslint-disable no-undef */
define([], function() {
    const SELECTORS = {
        list: '[data-region="storefront-section-list"]',
        card: '[data-region="storefront-section-card"]',
        handle: '[data-drag-handle]',
        order: '[data-section-order]',
        command: '[data-builder-command]',
        live: '[data-region="storefront-builder-live"]',
        form: '[data-region="storefront-builder-form"]',
        composerControl: '[data-composer-control]',
        previewCanvas: '[data-region="storefront-preview-canvas"]',
        previewButton: '[data-preview-device]',
        globalZones: '[data-region="storefront-global-zones"]',
        globalZonesValue: '[data-region="storefront-global-zones-value"]',
        saveSection: '[data-save-section]',
        saveStatus: '[data-section-save-status]',
    };

    let draggedCard = null;
    let dragEnabledCard = null;


    const synchroniseComposerCard = function(card) {
        const columns = card.querySelector('[name^="section_columns_"]');
        const column = card.querySelector('[name^="section_column_"]');
        const ratio = card.querySelector('[name^="section_layout_ratio_"]');
        if (!columns || !column || !ratio) {
            return;
        }

        const count = Math.max(1, Math.min(3, Number(columns.value) || 1));
        const allowedRatios = count === 1
            ? ['100']
            : count === 2
                ? ['50_50', '40_60', '60_40', '33_67', '67_33']
                : ['33_33_33'];
        const defaultRatio = count === 1 ? '100' : count === 2 ? '50_50' : '33_33_33';

        if (!allowedRatios.includes(ratio.value)) {
            ratio.value = defaultRatio;
        }
        column.value = String(Math.max(1, Math.min(count, Number(column.value) || 1)));
        Array.from(column.options).forEach(function(option) {
            option.disabled = Number(option.value) > count;
        });
        Array.from(ratio.options).forEach(function(option) {
            option.disabled = !allowedRatios.includes(option.value);
        });
        card.dataset.composerColumns = String(count);
    };

    const moveIntoTargetLayout = function(source, target) {
        const targetColumns = target.querySelector('[name^="section_columns_"]');
        const targetRow = target.querySelector('[name^="section_row_id_"]');
        const sourceColumns = source.querySelector('[name^="section_columns_"]');
        const sourceColumn = source.querySelector('[name^="section_column_"]');
        const sourceRatio = source.querySelector('[name^="section_layout_ratio_"]');
        const sourceRow = source.querySelector('[name^="section_row_id_"]');
        const targetColumn = target.querySelector('[name^="section_column_"]');
        const targetRatio = target.querySelector('[name^="section_layout_ratio_"]');

        if (!targetColumns || Number(targetColumns.value) < 2 || !sourceRow || !targetRow) {
            return;
        }

        sourceColumns.value = targetColumns.value;
        sourceRatio.value = targetRatio ? targetRatio.value : (Number(targetColumns.value) === 3 ? '33_33_33' : '50_50');
        sourceRow.value = targetRow.value;
        sourceColumn.value = String(Math.min(Number(targetColumns.value), (Number(targetColumn?.value) || 1) + 1));
        synchroniseComposerCard(source);
    };

    const cards = function(list) {
        return Array.from(list.querySelectorAll(':scope > ' + SELECTORS.card));
    };

    const syncOrder = function(list) {
        cards(list).forEach(function(card, position) {
            const order = card.querySelector(SELECTORS.order);
            if (order) {
                order.value = String(position * 10);
            }

            card.dataset.sectionPosition = String(position);

            card.querySelectorAll(SELECTORS.command).forEach(function(button) {
                const command = button.dataset.builderCommand;
                if (command) {
                    button.value = command + ':' + position;
                }
            });
        });
    };

    const announce = function(message) {
        const live = document.querySelector(SELECTORS.live);
        if (!live) {
            return;
        }

        live.textContent = '';
        window.setTimeout(function() {
            live.textContent = message;
        }, 20);
    };

    const markUnsaved = function() {
        const form = document.querySelector(SELECTORS.form);
        if (!form) {
            return;
        }

        form.classList.add('commerce-storefront-builder--unsaved');
        form.dataset.reordered = '1';
    };

    const moveBeforeOrAfter = function(list, target, clientY) {
        if (!draggedCard || target === draggedCard) {
            return;
        }

        const bounds = target.getBoundingClientRect();
        const insertAfter = clientY > bounds.top + (bounds.height / 2);

        if (insertAfter) {
            target.insertAdjacentElement('afterend', draggedCard);
        } else {
            target.insertAdjacentElement('beforebegin', draggedCard);
        }

        syncOrder(list);
        markUnsaved();
    };

    const activateHandle = function(handle) {
        const card = handle.closest(SELECTORS.card);
        if (!card) {
            return;
        }

        dragEnabledCard = card;
        card.setAttribute('draggable', 'true');
    };

    const deactivateHandle = function() {
        if (dragEnabledCard) {
            dragEnabledCard.setAttribute('draggable', 'false');
        }
        dragEnabledCard = null;
    };


    const saveSection = async function(card, button) {
        const form = document.querySelector(SELECTORS.form);
        if (!form || !form.dataset.sectionSaveUrl) {
            return;
        }

        const index = card.dataset.sectionIndex || '0';
        const textarea = card.querySelector('[name="section_content_' + index + '"]');
        if (textarea && window.tinyMCE && window.tinyMCE.get(textarea.id)) {
            window.tinyMCE.get(textarea.id).save();
        }

        const data = new FormData();
        card.querySelectorAll('input, select, textarea').forEach(function(field) {
            if (!field.name || field.disabled || field.type === 'button' || field.type === 'submit') {
                return;
            }
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                return;
            }
            if (field.type === 'file') {
                Array.from(field.files || []).forEach(function(file) {
                    data.append(field.name, file, file.name);

                    // Stable aliases are sent in addition to the indexed field
                    // names. Section indices can change after drag and drop,
                    // while these aliases remain unambiguous for the endpoint.
                    if (field.name === 'section_video_file_' + index) {
                        data.append('storefront_media_video', file, file.name);
                    } else if (field.name === 'section_video_poster_' + index) {
                        data.append('storefront_media_poster', file, file.name);
                    } else if (field.name === 'section_image_file_' + index) {
                        data.append('storefront_media_image', file, file.name);
                    } else if (field.name === 'section_h5p_file_' + index) {
                        data.append('storefront_media_h5p', file, file.name);
                    }
                });
                return;
            }
            data.append(field.name, field.value);
        });

        const visible = card.querySelector('[name="section_visible_' + index + '"]');
        if (visible && !visible.checked) {
            data.append(visible.name, '0');
        }
        const sesskey = form.querySelector('[name="sesskey"]');
        const editlang = form.querySelector('[name="editlang"]');
        data.append('sesskey', sesskey ? sesskey.value : '');
        data.append('editlang', editlang ? editlang.value : 'fr');
        data.append('sku', form.dataset.productSku || '');
        data.append('section_index', index);

        const status = card.querySelector(SELECTORS.saveStatus);
        const originalTitle = button.getAttribute('title') || '';
        button.disabled = true;
        button.classList.add('is-saving');
        card.classList.add('commerce-storefront-section-card--saving');
        if (status) {
            status.textContent = button.dataset.savingLabel || '…';
        }

        try {
            const response = await fetch(form.dataset.sectionSaveUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || originalTitle);
            }

            const itemid = payload.section ? Number(payload.section.mediaitemid || 0) : 0;
            const sectionid = payload.section ? String(payload.section.id || '') : '';
            const itemfield = card.querySelector('[name="section_content_itemid_' + index + '"]');
            const idfield = card.querySelector('[name="section_id_' + index + '"]');
            if (itemfield && itemid > 0) {
                itemfield.value = String(itemid);
            }
            if (idfield && sectionid) {
                idfield.value = sectionid;
            }
            card.querySelectorAll('input[type="file"]').forEach(function(input) {
                input.value = '';
            });
            card.classList.remove('commerce-storefront-section-card--dirty');
            card.classList.add('commerce-storefront-section-card--saved');
            let savedMessage = payload.message || originalTitle;
            if (payload.diagnostics) {
                const diagnostic = payload.diagnostics.video
                    || payload.diagnostics.image
                    || payload.diagnostics.h5p
                    || payload.diagnostics.poster;
                if (diagnostic && diagnostic.filename) {
                    savedMessage += ' · ' + diagnostic.filename;
                }
            }
            if (status) {
                status.textContent = savedMessage;
            }
            const readiness = card.querySelector('[data-section-readiness]');
            if (readiness) {
                const ready = Boolean(payload.ready);
                readiness.textContent = ready
                    ? (payload.readylabel || 'Ready')
                    : (payload.incompletelabel || 'Incomplete');
                readiness.classList.toggle('text-bg-success', ready);
                readiness.classList.toggle('text-bg-warning', !ready);
            }
            const preview = card.querySelector('[data-section-preview]');
            if (preview && payload.diagnostics) {
                const image = payload.diagnostics.image;
                const video = payload.diagnostics.video;
                const h5p = payload.diagnostics.h5p;
                if (image && image.url) {
                    preview.innerHTML = '<img src="' + image.url + '" alt="" loading="lazy">';
                } else if (video && video.url) {
                    preview.innerHTML = '<video src="' + video.url + '" controls preload="metadata"></video>';
                } else if (h5p && h5p.filename) {
                    preview.textContent = h5p.filename;
                }
            }
            announce(savedMessage);
        } catch (error) {
            card.classList.add('commerce-storefront-section-card--save-error');
            if (status) {
                status.textContent = error.message || originalTitle;
            }
        } finally {
            button.disabled = false;
            button.classList.remove('is-saving');
            card.classList.remove('commerce-storefront-section-card--saving');
        }
    };

    const initialiseCard = function(list, card) {
        const handle = card.querySelector(SELECTORS.handle);
        if (!handle) {
            return;
        }

        handle.addEventListener('pointerdown', function(event) {
            event.stopPropagation();
            activateHandle(handle);
        });

        handle.addEventListener('pointerup', deactivateHandle);
        handle.addEventListener('pointercancel', deactivateHandle);

        card.querySelectorAll(SELECTORS.composerControl).forEach(function(control) {
            control.addEventListener('change', function() {
                synchroniseComposerCard(card);
                markUnsaved();
            });
        });
        synchroniseComposerCard(card);

        const saveButton = card.querySelector(SELECTORS.saveSection);
        if (saveButton) {
            saveButton.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                saveSection(card, saveButton);
            });
        }
        card.querySelectorAll('input, select, textarea').forEach(function(field) {
            field.addEventListener('change', function() {
                card.classList.add('commerce-storefront-section-card--dirty');
            });
        });

        handle.addEventListener('keydown', function(event) {
            if (!event.altKey || !['ArrowUp', 'ArrowDown'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const currentCards = cards(list);
            const position = currentCards.indexOf(card);
            const targetPosition = event.key === 'ArrowUp'
                ? position - 1
                : position + 1;

            if (targetPosition < 0 || targetPosition >= currentCards.length) {
                return;
            }

            if (event.key === 'ArrowUp') {
                currentCards[targetPosition].insertAdjacentElement(
                    'beforebegin',
                    card
                );
            } else {
                currentCards[targetPosition].insertAdjacentElement(
                    'afterend',
                    card
                );
            }

            syncOrder(list);
            markUnsaved();
            handle.focus();
            announce(card.dataset.sectionLabel || '');
        });

        card.addEventListener('dragstart', function(event) {
            if (dragEnabledCard !== card) {
                event.preventDefault();
                return;
            }

            draggedCard = card;
            card.classList.add('commerce-storefront-section-card--dragging');
            list.classList.add('commerce-storefront-section-list--dragging');

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData(
                    'text/plain',
                    card.dataset.sectionIndex || ''
                );
            }
        });

        card.addEventListener('dragover', function(event) {
            if (!draggedCard) {
                return;
            }

            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
            moveBeforeOrAfter(list, card, event.clientY);
        });

        card.addEventListener('drop', function(event) {
            if (!draggedCard) {
                return;
            }

            event.preventDefault();
            moveIntoTargetLayout(draggedCard, card);
            syncOrder(list);
            markUnsaved();
        });

        card.addEventListener('dragend', function() {
            const label = card.dataset.sectionLabel || '';
            card.classList.remove('commerce-storefront-section-card--dragging');
            list.classList.remove('commerce-storefront-section-list--dragging');
            draggedCard = null;
            deactivateHandle();
            syncOrder(list);
            announce(label);
        });
    };


    const setPreviewDevice = function(device) {
        if (!['desktop', 'tablet', 'mobile'].includes(device)) {
            return;
        }

        const canvas = document.querySelector(SELECTORS.previewCanvas);
        if (!canvas) {
            return;
        }

        canvas.classList.remove(
            'commerce-storefront-preview-canvas--desktop',
            'commerce-storefront-preview-canvas--tablet',
            'commerce-storefront-preview-canvas--mobile'
        );
        canvas.classList.add('commerce-storefront-preview-canvas--' + device);
        canvas.dataset.previewDevice = device;

        document.querySelectorAll(SELECTORS.previewButton).forEach(function(button) {
            const active = button.dataset.previewDevice === device;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        try {
            window.localStorage.setItem('local_subscriptions_storefront_preview', device);
        } catch (error) {
            // Private browsing or storage policy: the preview still works for this page.
        }
    };

    const initialisePreview = function() {
        let device = 'desktop';
        try {
            device = window.localStorage.getItem('local_subscriptions_storefront_preview') || device;
        } catch (error) {
            // Keep the safe desktop default.
        }

        setPreviewDevice(device);
        document.querySelectorAll(SELECTORS.previewButton).forEach(function(button) {
            button.addEventListener('click', function() {
                setPreviewDevice(button.dataset.previewDevice || 'desktop');
            });
            button.addEventListener('keydown', function(event) {
                if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) {
                    return;
                }
                event.preventDefault();
                const buttons = Array.from(document.querySelectorAll(SELECTORS.previewButton));
                const position = buttons.indexOf(button);
                const next = event.key === 'ArrowRight'
                    ? (position + 1) % buttons.length
                    : (position - 1 + buttons.length) % buttons.length;
                buttons[next].focus();
                setPreviewDevice(buttons[next].dataset.previewDevice || 'desktop');
            });
        });
    };


    const initialiseGlobalZones = function() {
        const list = document.querySelector(SELECTORS.globalZones);
        const value = document.querySelector(SELECTORS.globalZonesValue);
        if (!list || !value) {
            return;
        }
        let dragged = null;
        const sync = function() {
            value.value = Array.from(list.querySelectorAll('[data-zone]'))
                .map(function(item) { return item.dataset.zone; })
                .join(',');
            markUnsaved();
        };
        const move = function(item, direction) {
            const sibling = direction < 0 ? item.previousElementSibling : item.nextElementSibling;
            if (!sibling) {
                return;
            }
            if (direction < 0) {
                list.insertBefore(item, sibling);
            } else {
                list.insertBefore(sibling, item);
            }
            sync();
            item.focus();
        };
        list.querySelectorAll('[data-zone]').forEach(function(item) {
            item.addEventListener('dragstart', function() {
                dragged = item;
                item.classList.add('is-dragging');
            });
            item.addEventListener('dragover', function(event) {
                if (!dragged || dragged === item) {
                    return;
                }
                event.preventDefault();
                const rect = item.getBoundingClientRect();
                list.insertBefore(dragged, event.clientY < rect.top + rect.height / 2 ? item : item.nextSibling);
            });
            item.addEventListener('drop', function(event) {
                event.preventDefault();
                sync();
            });
            item.addEventListener('dragend', function() {
                item.classList.remove('is-dragging');
                dragged = null;
                sync();
            });
            item.addEventListener('keydown', function(event) {
                if (!event.altKey || !['ArrowUp', 'ArrowDown'].includes(event.key)) {
                    return;
                }
                event.preventDefault();
                move(item, event.key === 'ArrowUp' ? -1 : 1);
            });
        });
        sync();
    };

    const init = function() {
        const list = document.querySelector(SELECTORS.list);
        if (!list) {
            return;
        }

        initialisePreview();
        initialiseGlobalZones();
        syncOrder(list);
        cards(list).forEach(function(card) {
            initialiseCard(list, card);
        });
    };

    return {
        init: init,
    };
});
