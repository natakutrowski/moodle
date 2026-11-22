/* CampusFR D2.8 personal vocabulary — bounded, grant-checked audio playback. */
define([], function() {
    'use strict';
    const FALLBACK = {"js_retry":"Réessayer","js_retry_aria":"Réessayer la prononciation IA","js_vocab_all_letters":"Tous","js_vocab_audio_error":"Prononciation IA momentanément indisponible. ","js_vocab_confirm_remove":"Retirer ce mot de ton vocabulaire ?","js_vocab_count":"{count} mots affichés sur {total}","js_vocab_kept":"Ta saisie personnelle a été conservée.","js_vocab_letter_aria":"Mots commençant par {letter}","js_vocab_letters_aria":"Toutes les lettres","js_vocab_preparing":"Préparation de la prononciation…","js_vocab_suggested":"Proposition IA affichée. Clique sur Enregistrer pour la conserver.","js_vocab_suggesting":"Proposition de traduction en cours…","js_voice_ai":"Voix IA : ","js_voice_waiting":"La voix termine de se préparer… ({n}/2)"};
    const init = () => {
        let UI = {};
        try { UI = JSON.parse(document.querySelector('.cfr-vocab-page')?.dataset.cfrUi || '{}'); } catch (ignored) { UI = {}; }
        const t = (key) => UI[key] || FALLBACK[key] || key;
        const fmt = (key, values) => t(key).replace(/\{(\w+)\}/g, (_, name) => String(values[name] ?? ''));
        let player = null;
        let url = null;
        let currentRequest = null;
        let currentButton = null;
        let currentLoadingStatus = null;
        let currentAudioStatus = null;
        let requestSerial = 0;
        const PREF = 'campusfr.reader.neural.voice.v1';
        const voiceSelect = document.querySelector('[data-cfr-neural-voice]');
        if (voiceSelect) {
            try {
                const saved = window.localStorage.getItem(PREF);
                if (saved === 'Lea' || saved === 'Remi') voiceSelect.value = saved;
            } catch (error) { /* Browser preferences unavailable. */ }
            voiceSelect.addEventListener('change', () => {
                stop();
                try {window.localStorage.setItem(PREF, voiceSelect.value);} catch (error) { /* No storage. */ }
            });
        }
        const setLoading = (status, loading) => {
            if (currentLoadingStatus && currentLoadingStatus !== status) {
                currentLoadingStatus.classList.remove('cfr-tts-busy');
                currentLoadingStatus = null;
            }
            if (status) status.classList.toggle('cfr-tts-busy', loading);
            currentLoadingStatus = loading ? status : null;
        };
        // This is a button, not a new endpoint: clicking it repeats the existing,
        // authenticated audio request for the same saved vocabulary entry.
        const showAudioRetry = (status) => {
            status.textContent = t('js_vocab_audio_error');
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'cfr-tts-retry cfr-tts-retry--inline';
            retry.dataset.cfrAudioRetry = '1';
            retry.textContent = t('js_retry');
            retry.setAttribute('aria-label', t('js_retry_aria'));
            status.appendChild(retry);
        };
        const stop = () => {
            requestSerial += 1;
            setLoading(currentLoadingStatus, false);
            if (currentAudioStatus) { currentAudioStatus.textContent = ''; currentAudioStatus = null; }
            if (currentRequest) { currentRequest.abort(); currentRequest = null; }
            if (currentButton) { currentButton.disabled = false; currentButton = null; }
            if (player) { player.pause(); player.removeAttribute('src'); player = null; }
            if (url) { URL.revokeObjectURL(url); url = null; }
        };

        // Client-side sorting/filtering: no extra queries, no cross-user data.
        const initVocabularyIndex = () => {
            const list = document.querySelector('[data-cfr-vocab-list]');
            const search = document.querySelector('[data-cfr-vocab-search]');
            const sort = document.querySelector('[data-cfr-vocab-sort]');
            const collection = document.querySelector('[data-cfr-vocab-collection]');
            const story = document.querySelector('[data-cfr-vocab-story]');
            const reset = document.querySelector('[data-cfr-vocab-reset]');
            const alphabet = document.querySelector('[data-cfr-vocab-alphabet]');
            const counter = document.querySelector('[data-cfr-vocab-count]');
            const empty = document.querySelector('[data-cfr-vocab-no-match]');
            if (!list || !search || !sort || !alphabet || !counter || !collection || !story || !reset) return;
            const collator = new Intl.Collator('fr', {sensitivity: 'base', numeric: true});
            const normal = (value) => (value || '').normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr');
            const entries = Array.from(list.querySelectorAll('[data-cfr-vocab-entry]'))
                .map((node, index) => {
                    const word = node.dataset.word || '';
                    const first = normal(word).charAt(0).toUpperCase();
                    let sources = [];
                    try { sources = JSON.parse(node.dataset.sources || '[]'); } catch (error) { /* No sources. */ }
                    return {node, word, sources, first: /^[A-Z]$/.test(first) ? first : '#',
                        created: Number(node.dataset.created) || 0, index};
                });
            const letters = Array.from(new Set(entries.map((entry) => entry.first))).sort(collator.compare);
            let selected = '';
            const all = [{letter: '', label: t('js_vocab_all_letters')}, ...letters.map((letter) => ({letter, label: letter}))];
            const buttons = all.map(({letter, label}) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'cfr-vocab-letter';
                button.textContent = label;
                button.dataset.letter = letter;
                button.setAttribute('aria-label', letter ? fmt('js_vocab_letter_aria', {letter}) : t('js_vocab_letters_aria'));
                button.addEventListener('click', () => {selected = letter; render();});
                alphabet.appendChild(button);
                return button;
            });
            const syncStories = () => {
                Array.from(story.options).forEach((option) => {
                    option.disabled = Boolean(option.value && collection.value && option.dataset.collection !== collection.value);
                    option.hidden = option.disabled;
                });
                if (story.selectedOptions[0]?.disabled) story.value = '';
                story.disabled = collection.value === '!unavailable';
            };
            const render = () => {
                syncStories();
                const query = normal(search.value.trim());
                const visible = entries.filter(({node, word, first, sources}) =>
                    (!selected || selected === first) &&
                    (!collection.value || (collection.value === '!unavailable' ? sources.length === 0 :
                        sources.some((key) => key.startsWith(collection.value + ':')))) &&
                    (!story.value || sources.includes(story.value)) &&
                    (!query || normal(word).includes(query) ||
                        normal(node.querySelector('input[name="translation"]')?.value).includes(query)));
                const mode = sort.value;
                visible.sort((a, b) => mode === 'recent'
                    ? (b.created - a.created || b.index - a.index)
                    : (mode === 'za' ? -1 : 1) * (collator.compare(a.word, b.word) || a.index - b.index));
                const fragment = document.createDocumentFragment();
                let last = null;
                visible.forEach((item) => {
                    if (mode !== 'recent' && item.first !== last) {
                        const heading = document.createElement('h2');
                        heading.className = 'cfr-vocab-letter-heading';
                        heading.textContent = item.first;
                        fragment.appendChild(heading);
                        last = item.first;
                    }
                    fragment.appendChild(item.node);
                });
                if (currentButton && !visible.some((item) => item.node.contains(currentButton))) stop();
                list.replaceChildren(fragment);
                buttons.forEach((button) => {
                    const active = button.dataset.letter === selected;
                    button.setAttribute('aria-pressed', String(active));
                });
                const n = visible.length;
                counter.textContent = fmt('js_vocab_count', {count: n, total: entries.length});
                if (empty) empty.hidden = n !== 0;
            };
            search.addEventListener('input', render);
            sort.addEventListener('change', render);
            collection.addEventListener('change', render);
            story.addEventListener('change', render);
            reset.addEventListener('click', () => {
                search.value = ''; sort.value = 'az'; collection.value = ''; story.value = ''; selected = ''; render();
            });
            render();
        };
        initVocabularyIndex();
        document.addEventListener('click', (event) => {
            const suggestButton = event.target.closest('[data-cfr-suggest]');
            if (suggestButton) {
                const card = suggestButton.closest('.cfr-vocab-card');
                const status = card?.querySelector('[data-cfr-translation-status]');
                const input = card?.querySelector('input[name="translation"]');
                if (!status || !input) return;
                const original = input.value;
                suggestButton.disabled = true;
                status.textContent = t('js_vocab_suggesting');
                const body = new URLSearchParams({slug:suggestButton.dataset.slug || '',
                    chapter:suggestButton.dataset.chapter || '',page:suggestButton.dataset.page || '',
                    text:suggestButton.dataset.word || '',target:suggestButton.dataset.target || 'en',
                    sesskey:suggestButton.dataset.sesskey || ''});
                fetch(window.M.cfg.wwwroot + '/local/campusreader/publication_translate.php', {
                    method:'POST',credentials:'same-origin',cache:'no-store',
                    headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
                    body:body.toString(),
                }).then((response) => {
                    if (!response.ok) throw new Error('failed');
                    return response.json();
                }).then((result) => {
                    if (!result.success || typeof result.translation !== 'string') throw new Error('failed');
                    if (input.value === original) {
                        input.value = result.translation;
                        status.textContent = t('js_vocab_suggested');
                    } else { status.textContent = t('js_vocab_kept'); }
                }).catch(() => {status.textContent = 'Proposition IA indisponible actuellement.';})
                    .finally(() => { suggestButton.disabled = false; });
                return;
            }
            const retry = event.target.closest('[data-cfr-audio-retry]');
            const button = retry
                ? retry.closest('.cfr-vocab-card')?.querySelector('[data-cfr-replay]')
                : event.target.closest('[data-cfr-replay]');
            if (!button || button.disabled) return;
            const card = button.closest('.cfr-vocab-card');
            const status = card?.querySelector('[data-cfr-audio-status]');
            if (!status) return;
            stop();
            const serial = requestSerial;
            const controller = new AbortController();
            currentRequest = controller;
            currentButton = button;
            currentAudioStatus = status;
            const requestedVoice = voiceSelect?.value === 'Remi' ? 'Remi' : 'Lea';
            button.disabled = true;
            setLoading(status, true);
            status.textContent = t('js_vocab_preparing');
            const body = new URLSearchParams({slug: button.dataset.slug || '',
                chapter: button.dataset.chapter || '', page: button.dataset.page || '',
                text: button.dataset.word || '', voice: requestedVoice,
                sesskey: button.dataset.sesskey || ''});
            // Retry ONLY when the authenticated server explicitly marks a transient failure.
            // Poodll may take a short time to provide the final cached MP3.
            const fetchAudio = (attempt) => {
                if (controller.signal.aborted || requestSerial !== serial) {
                    return Promise.reject(new Error('cancelled'));
                }
                return fetch(window.M.cfg.wwwroot + '/local/campusreader/publication_pronounce.php', {
                    method: 'POST', credentials: 'same-origin', cache: 'no-store',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                    body: body.toString(), signal: controller.signal,
                }).then((response) => {
                    if (response.status === 503 && response.headers.has('Retry-After') && attempt < 2) {
                        const seconds = Number(response.headers.get('Retry-After'));
                        if (!Number.isFinite(seconds) || seconds < 1 || seconds > 5) {
                            throw new Error('invalid-retry-hint');
                        }
                        status.textContent = fmt('js_voice_waiting', {n: attempt + 1});
                        return new Promise((resolve) => setTimeout(resolve, seconds * 1000))
                            .then(() => fetchAudio(attempt + 1));
                    }
                    if (!response.ok || !(response.headers.get('Content-Type') || '').includes('audio/mpeg')) {
                        throw new Error('tts-unavailable');
                    }
                    return response.blob();
                });
            };
            fetchAudio(0).then((audio) => {
                if (controller.signal.aborted || serial !== requestSerial) return;
                currentRequest = null;
                setLoading(status, false);
                url = URL.createObjectURL(audio);
                player = new Audio(url);
                player.onended = () => { if (serial === requestSerial) stop(); };
                player.onerror = () => {
                    if (serial !== requestSerial) return;
                    stop();
                    showAudioRetry(status);
                };
                status.textContent = t('js_voice_ai') + (requestedVoice === 'Remi' ? 'Rémi' : 'Léa');
                const promise = player.play();
                if (promise) return promise;
            }).catch((error) => {
                if (controller.signal.aborted || serial !== requestSerial || error.name === 'AbortError') return;
                setLoading(status, false);
                stop();
                showAudioRetry(status);
            }).finally(() => {
                if (serial === requestSerial) { button.disabled = false; currentRequest = null; }
            });
        });
        document.addEventListener('submit', (event) => {
            if (event.target.matches('.cfr-vocab-remove-form') && !window.confirm(t('js_vocab_confirm_remove'))) {
                event.preventDefault();
            }
        });
        window.addEventListener('pagehide', stop);
    };
    return {init};
});
