(function() {
    'use strict';

    const ROOT_SELECTOR = '#commerce-showroom-builder';

    const parseJson = (value, fallback = {}) => {
        const parse = candidate => {
            const parsed = JSON.parse(candidate || '{}');
            return parsed && typeof parsed === 'object' ? parsed : fallback;
        };

        try {
            return parse(value);
        } catch (error) {
            // Compatibility with older builder HTML that escaped JSON before
            // html_writer escaped the data attribute a second time.
            const textarea = document.createElement('textarea');
            textarea.innerHTML = value || '';
            try {
                return parse(textarea.value);
            } catch (legacyError) {
                return fallback;
            }
        }
    };

    const readConfig = root => {
        const id = root.dataset.configId || '';
        const node = id ? document.getElementById(id) : null;
        return node ? parseJson(node.textContent, null) : null;
    };

    const setStatus = (root, message, state = 'success') => {
        const node = root.querySelector('[data-role="status"]');
        if (!node) {
            return;
        }
        node.textContent = message;
        node.dataset.state = state;
    };

    const request = async(config, action, values = {}) => {
        const body = new URLSearchParams(Object.assign({
            action,
            showroomid: String(config.showroomid),
            sesskey: config.sesskey,
        }, values));
        const response = await fetch(config.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body,
        });
        const text = await response.text();
        let payload;
        try {
            payload = JSON.parse(text);
        } catch (error) {
            throw new Error(config.strings.error + ' (HTTP ' + response.status + ')');
        }
        if (!response.ok || !payload.ok) {
            throw new Error(payload.error || config.strings.error);
        }
        return payload;
    };

    const uploadMedia = async(config, blockid, field, file) => {
        const body = new FormData();
        body.append('action', 'uploadmedia');
        body.append('showroomid', String(config.showroomid));
        body.append('blockid', String(blockid));
        body.append('field', field);
        body.append('sesskey', config.sesskey);
        body.append('media', file);

        const response = await fetch(config.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            body,
        });
        const text = await response.text();
        let payload;
        try {
            payload = JSON.parse(text);
        } catch (error) {
            throw new Error(config.strings.error + ' (HTTP ' + response.status + ')');
        }
        if (!response.ok || !payload.ok) {
            throw new Error(payload.error || config.strings.error);
        }
        return payload;
    };


    const uploadExercisePreview = async(config, blockid, exercisekey, language, file) => {
        const body = new FormData();
        body.append('action', 'uploadexercisepreview');
        body.append('showroomid', String(config.showroomid));
        body.append('blockid', String(blockid));
        body.append('exercisekey', exercisekey);
        body.append('language', language);
        body.append('sesskey', config.sesskey);
        body.append('media', file);

        const response = await fetch(config.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            body,
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
            throw new Error(payload.error || config.strings.error);
        }
        return payload;
    };

    const importExerciseZip = async(config, blockid, language, file) => {
        const body = new FormData();
        body.append('action', 'importexercisezip');
        body.append('showroomid', String(config.showroomid));
        body.append('blockid', String(blockid));
        body.append('language', language);
        body.append('sesskey', config.sesskey);
        body.append('archive', file);

        const response = await fetch(config.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            body,
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
            throw new Error(payload.error || config.strings.error);
        }
        return payload;
    };

    const blockIds = root => Array.from(root.querySelectorAll('[data-block-id]'))
        .map(block => Number(block.dataset.blockId));

    const updateMoveButtons = root => {
        const blocks = Array.from(root.querySelectorAll('[data-block-id]'));
        blocks.forEach((block, index) => {
            const up = block.querySelector('[data-action="move-block-up"]');
            const down = block.querySelector('[data-action="move-block-down"]');
            if (up) {
                up.disabled = index === 0;
            }
            if (down) {
                down.disabled = index === blocks.length - 1;
            }
        });
    };

    const persistBlockOrder = async(root, config) => {
        await request(config, 'reorder', {blockids: JSON.stringify(blockIds(root))});
        updateMoveButtons(root);
        setStatus(root, config.strings.saved);
    };


    const dialogFor = root => root.parentElement.querySelector('[data-role="block-dialog"]');

    const createBusinessInput = (field, value, id) => {
        let input;
        if (field.type === 'textarea') {
            input = document.createElement('textarea');
            input.rows = field.editor ? 12 : 5;
            input.className = 'form-control';
        } else if (field.type === 'select') {
            input = document.createElement('select');
            input.className = 'form-select';
            Object.entries(field.options || {}).forEach(([optionValue, label]) => {
                const option = document.createElement('option');
                option.value = optionValue;
                option.textContent = label;
                input.append(option);
            });
        } else if (field.type === 'range') {
            input = document.createElement('input');
            input.type = 'range';
            input.className = 'form-range';
            input.min = String(field.min ?? 0);
            input.max = String(field.max ?? 100);
            input.step = String(field.step ?? 1);
        } else if (field.type === 'color') {
            input = document.createElement('input');
            input.type = 'color';
            input.className = 'form-control form-control-color';
        } else {
            input = document.createElement('input');
            input.type = field.type === 'url' ? 'url' : 'text';
            input.className = 'form-control';
        }

        input.id = id;
        input.value = value == null || value === ''
            ? String(field.default ?? '')
            : value;
        // Empty editorial fields intentionally activate the public fallback.
        input.required = false;
        input.dataset.businessField = field.name;
        input.dataset.fieldType = field.type;
        return input;
    };

    const previewUrl = (url) => {
        try {
            const resolved = new URL(url, window.location.href);
            resolved.searchParams.set('_showroompreview', String(Date.now()));
            return resolved.toString();
        } catch (error) {
            const separator = String(url).includes('?') ? '&' : '?';
            return String(url) + separator + '_showroompreview=' + Date.now();
        }
    };

    const refreshMediaPreview = (wrapper, url, config) => {
        const preview = wrapper.querySelector('[data-role="media-preview"]');
        const visual = wrapper.querySelector('[data-role="media-visual"]');
        const empty = wrapper.querySelector('[data-role="media-empty"]');
        const remove = wrapper.querySelector('[data-action="remove-media"]');
        const isVideo = visual.tagName === 'VIDEO';

        const hasUrl = Boolean((url || '').trim());
        preview.classList.toggle('has-media', hasUrl);
        visual.hidden = !hasUrl;
        empty.hidden = hasUrl;
        remove.disabled = !hasUrl;

        if (isVideo) {
            visual.pause();
            visual.removeAttribute('src');
            visual.load();
        }

        if (hasUrl) {
            // Moodle replaces media at a stable pluginfile URL. Cache-bust the editor preview
            // so a newly uploaded video never keeps the previous frame or file in memory.
            visual.src = previewUrl(url);
            if (isVideo) {
                visual.currentTime = 0;
                visual.load();
            }
        } else {
            visual.removeAttribute('src');
            if (isVideo) {
                visual.load();
            }
            empty.textContent = isVideo
                ? config.strings.mediaemptyvideo
                : config.strings.mediaempty;
        }
    };

    const syncMediaJson = (dialog, field, value) => {
        const jsonField = dialog.querySelector('[data-field="configjson"]');
        if (!jsonField) {
            return;
        }

        const parsed = parseJson(jsonField.value, collectFields(dialog));
        parsed[field] = value;
        jsonField.value = JSON.stringify(parsed, null, 2);
        jsonField.dataset.initialValue = jsonField.value;
        jsonField.dataset.jsonDirty = '0';
    };

    const renderMediaField = (container, field, values, dialog, config) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'commerce-showroom-media-field mb-3';
        wrapper.dataset.mediaField = field.name;

        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = field.label;

        if (field.help) {
            const help = document.createElement('div');
            help.className = 'form-text commerce-showroom-media-help';
            help.textContent = field.help;
            wrapper.append(label, help);
        } else {
            wrapper.append(label);
        }

        const urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.className = 'form-control';
        urlInput.value = values[field.name] || '';
        urlInput.dataset.businessField = field.name;
        urlInput.dataset.fieldType = 'media';

        const preview = document.createElement('div');
        preview.className = 'commerce-showroom-media-preview';
        preview.dataset.role = 'media-preview';

        const isVideo = field.kind === 'video';
        const visual = document.createElement(isVideo ? 'video' : 'img');
        visual.dataset.role = 'media-visual';
        if (isVideo) {
            visual.controls = true;
            visual.preload = 'metadata';
        } else {
            visual.alt = '';
            visual.loading = 'lazy';
        }

        const empty = document.createElement('div');
        empty.className = 'commerce-showroom-media-preview__empty';
        empty.dataset.role = 'media-empty';

        preview.append(visual, empty);

        const controls = document.createElement('div');
        controls.className = 'commerce-showroom-media-controls';

        const choose = document.createElement('label');
        choose.className = 'btn btn-sm btn-outline-primary mb-0';
        choose.innerHTML = '<i class="fa-solid '
            + (isVideo ? 'fa-video' : 'fa-image')
            + '" aria-hidden="true"></i> '
            + (isVideo ? config.strings.mediachoosevideo : config.strings.mediachoose);

        const file = document.createElement('input');
        file.type = 'file';
        file.accept = (field.acceptedtypes || []).join(',');
        file.hidden = true;
        choose.append(file);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.dataset.action = 'remove-media';
        remove.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i> '
            + (isVideo ? config.strings.mediaremovevideo : config.strings.mediaremove);

        const status = document.createElement('span');
        status.className = 'commerce-showroom-media-status';
        status.setAttribute('aria-live', 'polite');

        controls.append(choose, remove, status);
        wrapper.append(preview, controls, urlInput);
        container.append(wrapper);

        refreshMediaPreview(wrapper, urlInput.value, config);

        urlInput.addEventListener('input', () => {
            refreshMediaPreview(wrapper, urlInput.value, config);
        });

        file.addEventListener('change', async() => {
            const selected = file.files && file.files[0];
            const blockid = dialog.querySelector('[data-field="blockid"]').value;
            if (!selected || !blockid) {
                return;
            }
            if (field.maxbytes && selected.size > Number(field.maxbytes)) {
                status.textContent = field.help || config.strings.error;
                file.value = '';
                return;
            }

            status.textContent = config.strings.mediauploading;
            choose.classList.add('disabled');
            remove.disabled = true;
            try {
                const payload = await uploadMedia(
                    config,
                    blockid,
                    field.name,
                    selected
                );
                urlInput.value = payload.url || '';
                refreshMediaPreview(wrapper, urlInput.value, config);
                syncMediaJson(dialog, field.name, urlInput.value);
                status.textContent = isVideo
                    ? config.strings.mediauploadedvideo
                    : config.strings.mediauploaded;
            } catch (error) {
                status.textContent = error.message;
            } finally {
                choose.classList.remove('disabled');
                file.value = '';
            }
        });

        remove.addEventListener('click', async() => {
            const blockid = dialog.querySelector('[data-field="blockid"]').value;
            if (!blockid) {
                return;
            }

            status.textContent = config.strings.mediauploading;
            try {
                await request(config, 'deletemedia', {
                    blockid,
                    field: field.name,
                });
                urlInput.value = '';
                refreshMediaPreview(wrapper, '', config);
                syncMediaJson(dialog, field.name, '');
                status.textContent = '';
            } catch (error) {
                status.textContent = error.message;
            }
        });
    };

    const renderRegularField = (container, field, values, dialog, config) => {
        if (field.type === 'media') {
            renderMediaField(container, field, values, dialog, config);
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = field.type === 'checkbox'
            ? 'form-check form-switch mb-3'
            : 'mb-3';
        const id = 'showroom-field-' + field.name;
        let input;

        if (field.type === 'checkbox') {
            input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'form-check-input';
            input.checked = Boolean(values[field.name]);
            input.id = id;
            input.dataset.businessField = field.name;
            input.dataset.fieldType = field.type;

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = id;
            label.textContent = field.label;
            wrapper.append(input, label);
        } else {
            const label = document.createElement('label');
            label.className = 'form-label';
            label.htmlFor = id;
            label.textContent = field.label;
            input = createBusinessInput(field, values[field.name], id);
            wrapper.append(label, input);

            if (field.type === 'range') {
                const valueLabel = document.createElement('div');
                valueLabel.className = 'form-text text-end';
                const refreshRangeLabel = () => {
                    valueLabel.textContent = input.value + '%';
                };
                input.addEventListener('input', refreshRangeLabel);
                refreshRangeLabel();
                wrapper.append(valueLabel);
            }
        }

        container.append(wrapper);
    };

    const exerciseMediaState = (config, blockid) => {
        const all = config.exerciseMedia || {};
        all[String(blockid)] = all[String(blockid)] || {};
        return all[String(blockid)];
    };

    const exerciseFallback = (field, language) => {
        return field.fallbacks && field.fallbacks[language] != null
            ? field.fallbacks[language]
            : '';
    };

    const exerciseValue = (values, field, language, config) => {
        const translations = values.translations || {};
        if (translations[language] && translations[language][field.name] != null
            && String(translations[language][field.name]).trim() !== '') {
            return translations[language][field.name];
        }
        if (language === (config.defaultlanguage || 'fr')
            && values[field.name] != null
            && String(values[field.name]).trim() !== '') {
            return values[field.name];
        }
        return exerciseFallback(field, language);
    };

    const refreshExerciseMediaSlot = (slot, url, config) => {
        const image = slot.querySelector('[data-role="exercise-media-image"]');
        const empty = slot.querySelector('[data-role="exercise-media-empty"]');
        const remove = slot.querySelector('[data-action="remove-exercise-media"]');
        const hasUrl = Boolean((url || '').trim());
        const isDefault = slot.dataset.exerciseMediaLanguage === 'default';

        image.hidden = !hasUrl;
        empty.hidden = hasUrl;
        remove.disabled = !hasUrl;
        if (hasUrl) {
            image.src = previewUrl(url);
        } else {
            image.removeAttribute('src');
            empty.replaceChildren();

            const icon = document.createElement('i');
            icon.className = isDefault
                ? 'fa-regular fa-image'
                : 'fa-solid fa-arrow-turn-up';
            icon.setAttribute('aria-hidden', 'true');

            const title = document.createElement('strong');
            title.textContent = isDefault
                ? config.strings.exerciseimageempty
                : config.strings.exerciseimagelocalizedempty;

            empty.append(icon, title);

            if (!isDefault) {
                const help = document.createElement('span');
                help.textContent = config.strings.exerciseimagelocalizedfallback;
                empty.append(help);
            }
        }
    };

    const createExerciseMediaSlot = (exercise, language, blockid, mediaState, config) => {
        const slot = document.createElement('div');
        slot.className = 'commerce-showroom-exercise-media-slot';
        slot.dataset.exerciseMediaKey = exercise.key;
        slot.dataset.exerciseMediaLanguage = language.code;

        const heading = document.createElement('div');
        heading.className = 'commerce-showroom-exercise-media-slot__heading';
        heading.textContent = language.label;

        const preview = document.createElement('div');
        preview.className = 'commerce-showroom-exercise-media-slot__preview';

        const image = document.createElement('img');
        image.dataset.role = 'exercise-media-image';
        image.alt = '';
        image.loading = 'lazy';

        const empty = document.createElement('div');
        empty.dataset.role = 'exercise-media-empty';
        empty.className = 'commerce-showroom-exercise-media-slot__empty';

        preview.append(image, empty);

        const controls = document.createElement('div');
        controls.className = 'commerce-showroom-exercise-media-slot__controls';

        const choose = document.createElement('label');
        choose.className = 'btn btn-sm btn-outline-primary mb-0';
        choose.innerHTML = '<i class="fa-solid fa-image" aria-hidden="true"></i> '
            + config.strings.exercisechooseimage;

        const file = document.createElement('input');
        file.type = 'file';
        file.accept = '.png,.jpg,.jpeg,.webp';
        file.hidden = true;
        choose.append(file);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.dataset.action = 'remove-exercise-media';
        remove.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i> '
            + config.strings.exerciseremoveimage;

        const status = document.createElement('span');
        status.className = 'commerce-showroom-media-status';
        status.setAttribute('aria-live', 'polite');

        controls.append(choose, remove);
        slot.append(heading, preview, controls, status);

        const current = mediaState[exercise.key] && mediaState[exercise.key][language.code]
            ? mediaState[exercise.key][language.code]
            : '';
        refreshExerciseMediaSlot(slot, current, config);

        file.addEventListener('change', async() => {
            const selected = file.files && file.files[0];
            if (!selected) {
                return;
            }
            if (selected.size > 20 * 1024 * 1024) {
                status.textContent = config.strings.error;
                file.value = '';
                return;
            }

            status.textContent = config.strings.mediauploading;
            choose.classList.add('disabled');
            remove.disabled = true;
            try {
                const payload = await uploadExercisePreview(
                    config,
                    blockid,
                    exercise.key,
                    language.code,
                    selected
                );
                mediaState[exercise.key] = mediaState[exercise.key] || {};
                mediaState[exercise.key][language.code] = payload.url || '';
                refreshExerciseMediaSlot(slot, payload.url || '', config);
                status.textContent = config.strings.mediauploaded;
            } catch (error) {
                status.textContent = error.message;
            } finally {
                choose.classList.remove('disabled');
                file.value = '';
            }
        });

        remove.addEventListener('click', async() => {
            status.textContent = config.strings.mediauploading;
            try {
                await request(config, 'deleteexercisepreview', {
                    blockid,
                    exercisekey: exercise.key,
                    language: language.code,
                });
                mediaState[exercise.key] = mediaState[exercise.key] || {};
                mediaState[exercise.key][language.code] = '';
                refreshExerciseMediaSlot(slot, '', config);
                status.textContent = '';
            } catch (error) {
                status.textContent = error.message;
            }
        });

        return slot;
    };

    const renderExerciseImport = (container, blockid, mediaState, config) => {
        const box = document.createElement('div');
        box.className = 'commerce-showroom-exercise-import';

        const title = document.createElement('strong');
        title.textContent = config.strings.exerciseimport;

        const help = document.createElement('p');
        help.className = 'form-text mb-2';
        help.textContent = config.strings.exerciseimporthelp;

        const row = document.createElement('div');
        row.className = 'commerce-showroom-exercise-import__controls';

        const language = document.createElement('select');
        language.className = 'form-select form-select-sm';
        [
            {code: 'default', label: config.strings.exercisedefault},
            ...(config.languages || []),
        ].forEach(item => {
            const option = document.createElement('option');
            option.value = item.code;
            option.textContent = item.label;
            language.append(option);
        });

        const choose = document.createElement('label');
        choose.className = 'btn btn-sm btn-outline-primary mb-0';
        choose.innerHTML = '<i class="fa-solid fa-file-zipper" aria-hidden="true"></i> '
            + config.strings.exerciseimportbutton;

        const file = document.createElement('input');
        file.type = 'file';
        file.accept = '.zip,application/zip';
        file.hidden = true;
        choose.append(file);

        const status = document.createElement('span');
        status.className = 'commerce-showroom-media-status';
        status.setAttribute('aria-live', 'polite');

        row.append(language, choose, status);
        box.append(title, help, row);
        container.append(box);

        file.addEventListener('change', async() => {
            const selected = file.files && file.files[0];
            if (!selected) {
                return;
            }

            status.textContent = config.strings.mediauploading;
            choose.classList.add('disabled');
            try {
                const payload = await importExerciseZip(
                    config,
                    blockid,
                    language.value,
                    selected
                );
                Object.entries(payload.media || {}).forEach(([key, url]) => {
                    mediaState[key] = mediaState[key] || {};
                    mediaState[key][language.value] = url || '';
                });
                box.dispatchEvent(new CustomEvent('exercise-media-batch-updated', {
                    bubbles: true,
                    detail: {language: language.value},
                }));
                const report = payload.report || {};
                const matched = Object.keys(report.matched || {}).length;
                const stored = Number(report.stored || 0);

                status.replaceChildren();
                const badge = document.createElement('span');
                badge.className = 'commerce-showroom-exercise-import__result'
                    + (matched >= 12 ? ' is-complete' : '');
                badge.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> '
                    + config.strings.exerciseimportdone
                        .replace('{stored}', String(stored))
                        .replace('{matched}', String(matched));
                status.append(badge);
            } catch (error) {
                status.textContent = error.message;
            } finally {
                choose.classList.remove('disabled');
                file.value = '';
            }
        });
    };

    const renderExerciseEditor = (container, exerciseFields, values, dialog, config) => {
        if (!exerciseFields.length) {
            return;
        }

        const blockid = dialog.querySelector('[data-field="blockid"]').value;
        const mediaState = exerciseMediaState(config, blockid);
        const grouped = new Map();

        exerciseFields.forEach(field => {
            const key = field.exercisekey;
            if (!grouped.has(key)) {
                grouped.set(key, {
                    key,
                    position: Number(field.exerciseposition || 0),
                    label: field.exerciselabel || key,
                    icon: field.exerciseicon || 'fa-solid fa-puzzle-piece',
                    fields: [],
                });
            }
            grouped.get(key).fields.push(field);
        });
        const exercises = Array.from(grouped.values()).sort((a, b) => a.position - b.position);

        const wrapper = document.createElement('section');
        wrapper.className = 'commerce-showroom-exercise-editor';

        const heading = document.createElement('div');
        heading.className = 'commerce-showroom-exercise-editor__heading';
        const title = document.createElement('h4');
        title.textContent = config.strings.exerciseeditor;
        heading.append(title);
        wrapper.append(heading);

        renderExerciseImport(wrapper, blockid, mediaState, config);

        const accordions = document.createElement('div');
        accordions.className = 'commerce-showroom-exercise-editor__accordions';

        exercises.forEach((exercise, exerciseIndex) => {
            const details = document.createElement('details');
            details.className = 'commerce-showroom-exercise-editor__item';
            if (exerciseIndex === 0) {
                details.open = true;
                details.classList.add('is-active');
            }
            details.addEventListener('toggle', () => {
                details.classList.toggle('is-active', details.open);
            });

            const summary = document.createElement('summary');
            summary.innerHTML = '<span class="commerce-showroom-exercise-editor__number">'
                + String(exercise.position).padStart(2, '0')
                + '</span><i class="' + exercise.icon + '" aria-hidden="true"></i><strong></strong>'
                + '<i class="fa-solid fa-chevron-down commerce-showroom-exercise-editor__chevron" aria-hidden="true"></i>';
            summary.querySelector('strong').textContent = exercise.label;

            const body = document.createElement('div');
            body.className = 'commerce-showroom-exercise-editor__body';

            const contentTitle = document.createElement('h5');
            contentTitle.textContent = config.strings.exercisecontent;
            body.append(contentTitle);

            const textGrid = document.createElement('div');
            textGrid.className = 'commerce-showroom-exercise-editor__languages';

            (config.languages || []).forEach(language => {
                const languageBox = document.createElement('div');
                languageBox.className = 'commerce-showroom-exercise-editor__language';
                const languageTitle = document.createElement('strong');
                languageTitle.textContent = language.label;
                languageBox.append(languageTitle);

                exercise.fields.forEach(field => {
                    const fieldWrap = document.createElement('div');
                    fieldWrap.className = 'mb-2';
                    const id = 'showroom-exercise-' + exercise.key + '-' + language.code + '-' + field.name;
                    const label = document.createElement('label');
                    label.className = 'form-label form-label-sm';
                    label.htmlFor = id;
                    label.textContent = field.label;
                    const input = createBusinessInput(
                        field,
                        exerciseValue(values, field, language.code, config),
                        id
                    );
                    input.dataset.businessLanguage = language.code;
                    fieldWrap.append(label, input);
                    languageBox.append(fieldWrap);
                });

                textGrid.append(languageBox);
            });
            body.append(textGrid);

            const mediaTitle = document.createElement('h5');
            mediaTitle.className = 'mt-3';
            mediaTitle.textContent = config.strings.exercisemedia;
            body.append(mediaTitle);

            const mediaGrid = document.createElement('div');
            mediaGrid.className = 'commerce-showroom-exercise-editor__media';
            const mediaLanguages = [
                {code: 'default', label: config.strings.exercisedefault},
                ...(config.languages || []),
            ];
            mediaLanguages.forEach(language => {
                mediaGrid.append(
                    createExerciseMediaSlot(exercise, language, blockid, mediaState, config)
                );
            });
            body.append(mediaGrid);

            details.append(summary, body);
            accordions.append(details);
        });

        wrapper.append(accordions);
        wrapper.addEventListener('exercise-media-batch-updated', event => {
            wrapper.querySelectorAll('[data-exercise-media-key]').forEach(slot => {
                if (slot.dataset.exerciseMediaLanguage !== event.detail.language) {
                    return;
                }
                const key = slot.dataset.exerciseMediaKey;
                const url = mediaState[key] && mediaState[key][event.detail.language]
                    ? mediaState[key][event.detail.language]
                    : '';
                refreshExerciseMediaSlot(slot, url, config);
            });
        });
        container.append(wrapper);
    };

    const renderFields = (dialog, schema, values, config) => {
        const container = dialog.querySelector('[data-role="business-fields"]');
        if (!container) {
            return;
        }

        container.replaceChildren();
        const fields = schema && schema.fields ? schema.fields : [];
        const exerciseFields = fields.filter(field => field.exercise);
        const regularFields = fields.filter(field => !field.exercise);

        const commonFieldNames = new Set([
            'sectionwidth',
            'sectionbackground',
            'sectionbackgroundcolor',
            'sectionbackgroundimageurl',
            'sectionbackgroundopacity',
            'sectionbackgroundblur',
            'sectionspacing',
            'sectionanimation',
        ]);
        const nonTranslatedFields = regularFields.filter(
            field => !field.translatable
        );
        const commonFields = nonTranslatedFields.filter(
            field => commonFieldNames.has(field.name)
        );
        const specificFields = nonTranslatedFields.filter(
            field => !commonFieldNames.has(field.name)
        );

        specificFields.forEach(
            field => renderRegularField(
                container,
                field,
                values,
                dialog,
                config
            )
        );

        if (commonFields.length) {
            const presentation = document.createElement('details');
            presentation.className =
                'commerce-showroom-dialog__presentation';

            const presentationSummary =
                document.createElement('summary');
            presentationSummary.innerHTML =
                '<i class="fa-solid fa-palette" aria-hidden="true"></i> '
                + config.strings.commonpresentation;

            const help = document.createElement('p');
            help.className =
                'commerce-showroom-dialog__presentation-help';
            help.textContent =
                config.strings.commonpresentationhelp;

            const body = document.createElement('div');
            body.className =
                'commerce-showroom-dialog__presentation-body';

            const backgroundNames = new Set([
                'sectionbackground',
                'sectionbackgroundcolor',
                'sectionbackgroundimageurl',
                'sectionbackgroundopacity',
                'sectionbackgroundblur',
            ]);
            const general = document.createElement('div');
            general.className =
                'commerce-showroom-dialog__presentation-grid';

            const background = document.createElement('div');
            background.className =
                'commerce-showroom-dialog__presentation-background';

            const backgroundTitle = document.createElement('h5');
            backgroundTitle.textContent =
                config.strings.commonbackground;
            background.append(backgroundTitle);

            commonFields.forEach(field => {
                const target = backgroundNames.has(field.name)
                    ? background
                    : general;
                renderRegularField(
                    target,
                    field,
                    values,
                    dialog,
                    config
                );
            });

            body.append(help, general);
            if (
                background.querySelector('[data-business-field]')
            ) {
                body.append(background);
            }
            presentation.append(
                presentationSummary,
                body
            );
            container.append(presentation);
        }

        const translatedFields = regularFields.filter(field => field.translatable);
        if (translatedFields.length) {
            const tabs = document.createElement('div');
            tabs.className = 'commerce-showroom-language-tabs';
            tabs.setAttribute('role', 'tablist');

            const panels = document.createElement('div');
            panels.className = 'commerce-showroom-language-panels';
            const translations = values.translations || {};

            (config.languages || []).forEach((language, index) => {
                const tab = document.createElement('button');
                tab.type = 'button';
                tab.className = 'commerce-showroom-language-tab'
                    + (index === 0 ? ' is-active' : '');
                tab.textContent = language.label;
                tab.dataset.languageTab = language.code;
                tab.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
                tabs.append(tab);

                const panel = document.createElement('div');
                panel.className = 'commerce-showroom-language-panel'
                    + (index === 0 ? ' is-active' : '');
                panel.dataset.languagePanel = language.code;

                translatedFields.forEach(field => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-3';
                    const id = 'showroom-field-' + language.code + '-' + field.name;
                    const label = document.createElement('label');
                    label.className = 'form-label';
                    label.htmlFor = id;
                    label.textContent = field.label;
                    const legacy = language.code === (config.defaultlanguage || 'fr')
                        ? values[field.name]
                        : '';
                    const value = translations[language.code]
                        && translations[language.code][field.name] != null
                        ? translations[language.code][field.name]
                        : legacy;
                    const input = createBusinessInput(field, value, id);
                    input.dataset.businessLanguage = language.code;
                    wrapper.append(label, input);
                    panel.append(wrapper);
                });

                panels.append(panel);
            });

            tabs.addEventListener('click', event => {
                const active = event.target.closest('[data-language-tab]');
                if (!active) {
                    return;
                }
                tabs.querySelectorAll('[data-language-tab]').forEach(tab => {
                    const selected = tab === active;
                    tab.classList.toggle('is-active', selected);
                    tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                });
                panels.querySelectorAll('[data-language-panel]').forEach(panel => {
                    panel.classList.toggle(
                        'is-active',
                        panel.dataset.languagePanel === active.dataset.languageTab
                    );
                });
            });

            container.append(tabs, panels);
        }

        renderExerciseEditor(container, exerciseFields, values, dialog, config);
    };

    const showJsonError = (dialog, message = '') => {
        const field = dialog.querySelector('[data-field="configjson"]');
        const error = dialog.querySelector('[data-role="json-error"]');
        if (field) {
            field.setCustomValidity(message);
        }
        if (error) {
            error.textContent = message;
            error.classList.toggle('is-visible', Boolean(message));
        }
    };

    const parseAdvancedJson = (dialog, config) => {
        const field = dialog.querySelector('[data-field="configjson"]');
        if (!field) {
            return {};
        }
        try {
            const parsed = JSON.parse(field.value || '{}');
            if (!parsed || Array.isArray(parsed) || typeof parsed !== 'object') {
                throw new Error(config.strings.jsonobjectrequired);
            }
            showJsonError(dialog, '');
            return parsed;
        } catch (error) {
            const message = error.message || config.strings.invalidjson;
            showJsonError(dialog, message);
            field.reportValidity();
            throw error;
        }
    };

    const applyJsonToFields = (dialog, config) => {
        const type = dialog.dataset.blockType || 'html';
        const values = parseAdvancedJson(dialog, config);
        renderFields(
            dialog,
            config.schemas ? config.schemas[type] : null,
            values,
            config
        );
        return values;
    };

    const syncFieldsToJson = dialog => {
        const field = dialog.querySelector('[data-field="configjson"]');
        if (!field) {
            return;
        }
        field.value = JSON.stringify(collectFields(dialog), null, 2);
        field.dataset.jsonDirty = '0';
        showJsonError(dialog, '');
    };

    const openEditor = (root, block, config) => {
        const dialog = dialogFor(root);
        if (!dialog) {
            return;
        }
        const type = block.dataset.blockType || 'html';
        const values = parseJson(block.dataset.config, {});
        dialog.dataset.blockType = type;
        dialog.classList.toggle('is-exercise-editor', type === 'exercise_explorer');
        dialog.querySelector('[data-field="blockid"]').value = block.dataset.blockId || '';
        dialog.querySelector('[data-field="blockkey"]').value = block.dataset.blockKey || '';
        dialog.querySelector('[data-field="enabled"]').checked = block.dataset.enabled === '1';
        const jsonField = dialog.querySelector('[data-field="configjson"]');
        jsonField.value = JSON.stringify(values, null, 2);
        jsonField.dataset.initialValue = jsonField.value;
        jsonField.dataset.jsonDirty = '0';
        jsonField.setCustomValidity('');
        const jsonError = dialog.querySelector('[data-role="json-error"]');
        if (jsonError) {
            jsonError.textContent = '';
            jsonError.classList.remove('is-visible');
        }
        renderFields(dialog, config.schemas ? config.schemas[type] : null, values, config);
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', 'open');
        }
    };

    const closeEditor = root => {
        const dialog = dialogFor(root);
        if (!dialog) {
            return;
        }
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
    };

    const collectFields = dialog => {
        const values = {translations: {}};

        dialog.querySelectorAll('[data-business-field]').forEach(input => {
            const value = input.dataset.fieldType === 'checkbox'
                ? input.checked
                : input.value;
            const language = input.dataset.businessLanguage || '';

            if (language) {
                values.translations[language] = values.translations[language] || {};
                values.translations[language][input.dataset.businessField] = value;
                if (language === 'fr') {
                    values[input.dataset.businessField] = value;
                }
                return;
            }

            values[input.dataset.businessField] = value;
        });

        return values;
    };

    const bindDrag = (root, config) => {
        const list = root.querySelector('[data-role="block-list"]');
        if (!list) {
            return;
        }
        let armed = null;
        let dragged = null;
        list.querySelectorAll('[data-block-id]').forEach(block => block.draggable = false);
        list.addEventListener('pointerdown', event => {
            const handle = event.target.closest('[data-role="drag-handle"]');
            const block = handle ? handle.closest('[data-block-id]') : null;
            if (block) {
                armed = block;
                block.draggable = true;
            }
        });
        document.addEventListener('pointerup', () => {
            if (armed && armed !== dragged) {
                armed.draggable = false;
                armed = null;
            }
        });
        list.addEventListener('dragstart', event => {
            const block = event.target.closest('[data-block-id]');
            if (!armed || block !== armed) {
                event.preventDefault();
                return;
            }
            dragged = block;
            dragged.classList.add('is-dragging');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', dragged.dataset.blockId || '');
            }
        });
        list.addEventListener('dragover', event => {
            if (!dragged) {
                return;
            }
            event.preventDefault();
            const target = event.target.closest('[data-block-id]');
            if (!target || target === dragged) {
                return;
            }
            const rect = target.getBoundingClientRect();
            list.insertBefore(dragged, event.clientY > rect.top + rect.height / 2 ? target.nextSibling : target);
        });
        list.addEventListener('dragend', async() => {
            if (!dragged) {
                return;
            }
            const current = dragged;
            current.classList.remove('is-dragging');
            current.draggable = false;
            dragged = null;
            armed = null;
            try {
                await persistBlockOrder(root, config);
            } catch (error) {
                setStatus(root, error.message, 'error');
            }
        });
    };

    const init = () => {
        const root = document.querySelector(ROOT_SELECTOR);
        if (!root || root.dataset.builderInitialised === '1') {
            return;
        }
        const config = readConfig(root);
        if (!config) {
            return;
        }
        root.dataset.builderInitialised = '1';
        const list = root.querySelector('[data-role="block-list"]');
        const dialog = dialogFor(root);
        bindDrag(root, config);
        updateMoveButtons(root);

        root.addEventListener('click', async event => {
            const button = event.target.closest('[data-action]');
            if (!button) {
                return;
            }
            event.preventDefault();
            const action = button.dataset.action;
            const block = button.closest('[data-block-id]');
            try {
                if (action === 'initialise-defaults') {
                    if (!window.confirm(config.strings.confirmdefaults)) {
                        return;
                    }
                    const payload = await request(config, 'initialisedefaults');
                    setStatus(
                        root,
                        config.strings.defaultsinitialised.replace('{count}', String(payload.updated || 0))
                    );
                    window.location.replace(
                        window.location.pathname
                        + window.location.search
                        + '#commerce-showroom-builder'
                    );
                } else if (action === 'apply-template') {
                    const select = root.querySelector('[data-role="page-template"]');
                    if (!select || !select.value) {
                        return;
                    }
                    if (window.confirm(config.strings.confirmdelete)) {
                        await request(config, 'applytemplate', {templatekey: select.value});
                        window.location.reload();
                    }
                } else if (action === 'add-block') {
                    const select = root.querySelector('[data-role="block-type"]');
                    if (select && select.value) {
                        await request(config, 'add', {blocktype: select.value});
                        window.location.reload();
                    }

                } else if (block && (action === 'move-block-up' || action === 'move-block-down')) {
                    const sibling = action === 'move-block-up'
                        ? block.previousElementSibling
                        : block.nextElementSibling;
                    if (!sibling || !sibling.matches('[data-block-id]')) {
                        updateMoveButtons(root);
                        return;
                    }

                    if (action === 'move-block-up') {
                        list.insertBefore(block, sibling);
                    } else {
                        list.insertBefore(sibling, block);
                    }

                    await persistBlockOrder(root, config);
                } else if (action === 'collapse-all' || action === 'expand-all') {
                    const collapsed = action === 'collapse-all';
                    root.querySelectorAll('[data-block-id]').forEach(item => {
                        item.classList.toggle('is-collapsed', collapsed);
                        const toggle = item.querySelector(
                            '[data-action="collapse-block"]'
                        );
                        if (toggle) {
                            toggle.setAttribute(
                                'aria-expanded',
                                collapsed ? 'false' : 'true'
                            );
                        }
                    });
                } else if (block && action === 'collapse-block') {
                    const collapsed = block.classList.toggle('is-collapsed');
                    button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                } else if (block && action === 'edit-block') {
                    openEditor(root, block, config);
                } else if (block && action === 'toggle-block') {
                    const enabled = block.dataset.enabled !== '1';
                    await request(config, 'toggle', {blockid: block.dataset.blockId, enabled: enabled ? '1' : '0'});
                    block.dataset.enabled = enabled ? '1' : '0';
                    block.classList.toggle('is-disabled', !enabled);
                    setStatus(root, config.strings.saved);
                } else if (block && action === 'duplicate-block') {
                    await request(config, 'duplicate', {blockid: block.dataset.blockId});
                    window.location.reload();
                } else if (block && action === 'delete-block' && window.confirm(config.strings.confirmdelete)) {
                    await request(config, 'delete', {blockid: block.dataset.blockId});
                    block.remove();
                    await persistBlockOrder(root, config);
                }
            } catch (error) {
                setStatus(root, error.message, 'error');
            }
        });

        if (dialog) {
            dialog.addEventListener('click', event => {
                if (event.target.closest('[data-action="close-dialog"]')) {
                    event.preventDefault();
                    closeEditor(root);
                    return;
                }
                if (event.target.closest('[data-action="apply-json"]')) {
                    event.preventDefault();
                    try {
                        applyJsonToFields(dialog, config);
                    } catch (error) {
                        // The inline validation message already explains the error.
                    }
                    return;
                }
                if (event.target.closest('[data-action="sync-json"]')) {
                    event.preventDefault();
                    syncFieldsToJson(dialog);
                }
            });
            const advancedJson = dialog.querySelector('[data-field="configjson"]');
            if (advancedJson) {
                advancedJson.addEventListener('input', () => {
                    advancedJson.dataset.jsonDirty = '1';
                    showJsonError(dialog, '');
                });
            }
            const form = dialog.querySelector('[data-role="block-form"]');
            if (form) {
                form.addEventListener('submit', async event => {
                    event.preventDefault();
                    const blockid = dialog.querySelector('[data-field="blockid"]').value;
                    const jsonField = dialog.querySelector('[data-field="configjson"]');
                    let values;
                    let advancedjson = '0';
                    try {
                        if (jsonField && jsonField.dataset.jsonDirty === '1') {
                            values = parseAdvancedJson(dialog, config);
                            advancedjson = '1';
                        } else {
                            values = collectFields(dialog);
                        }
                        await request(config, 'update', {
                            blockid,
                            blockkey: dialog.querySelector('[data-field="blockkey"]').value,
                            enabled: dialog.querySelector('[data-field="enabled"]').checked ? '1' : '0',
                            configjson: JSON.stringify(values),
                            advancedjson,
                        });
                        closeEditor(root);
                        window.location.reload();
                    } catch (error) {
                        setStatus(root, error.message, 'error');
                    }
                });
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, {once: true});
    } else {
        init();
    }
})();
