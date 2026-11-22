/* CampusFR reader — phase B2, Moodle AMD module. GPL-3.0-or-later. */
define(['require', 'local_campusreader/speech'], function(require, speechModule) {
    'use strict';
    const FALLBACK = {"js_add_vocab":"＋ Ajouter à mon vocabulaire","js_boot_failed":"Impossible de démarrer le livre 3D : ","js_boot_loading":"Chargement du livre 3D…","js_page_counter":"Page {page} / {total}","js_save_failed":"Enregistrement impossible. Réessaie.","js_saved":"✓ Enregistré","js_saved_vocab":"✓ Enregistré dans Mon vocabulaire","js_saving":"Enregistrement…","js_translate_ai_note":"Proposition IA à vérifier avant enregistrement.","js_translate_demo":"Traduction de démonstration","js_translate_demo_missing":"Traduction non disponible dans la démo","js_translate_failed":"Traduction IA momentanément indisponible.","js_translate_future":"Traduction complète prévue en phase D","js_translate_language":"Langue de traduction","js_translate_off":"Traduction IA non configurée. Saisis ta traduction personnelle.","js_translate_one":"Sélectionne un seul mot pour obtenir sa traduction.","js_translate_optional":"Traduction (facultative)","js_translate_placeholder":"Saisis la traduction…","js_translate_search":"Recherche de la traduction…","js_translate_to":"Traduire en : ","js_turn_back":"Revenir","js_turn_forward":"Tourner","js_turn_next":"Tourner vers la page suivante","js_turn_previous":"Tourner vers la page précédente","js_word_action":"Prononcer et traduire {word}"};

    let UI = {};
    const t = (key) => UI[key] || FALLBACK[key] || key;
    const fmt = (key, values) => t(key).replace(/\{(\w+)\}/g, (_, name) => String(values[name] ?? ''));

    // Only a small illustrative lexicon exists in B1. The contextual service belongs to phase D.
    const demoRu = {
        'étoiles': 'звёзды', 'étoile': 'звезда', 'petite': 'маленькая',
        'fille': 'девочка', 'fenêtre': 'окно', 'noël': 'Рождество',
        'lumière': 'свет', 'dorée': 'золотистый', 'neige': 'снег',
        'renard': 'лиса', 'merveilleux': 'чудесный', 'regardait': 'смотрела',
        'aperçut': 'заметила', 'retint': 'затаила',
        'retint son souffle': 'затаила дыхание',
        'la petite fille': 'маленькая девочка',
        'les étoiles': 'звёзды',
    };
    const letters = /\p{L}[\p{L}\p{M}]*(?:['’\-]\p{L}[\p{L}\p{M}]*)*/gu;
    const norm = (s) => s.trim().toLocaleLowerCase('fr-FR').replace(/\s+/g, ' ');
    const isExpression = (s) => /\s/.test(s.trim());

    // Preserve text nodes rather than injecting HTML constructed from book content.
    const tokenizeText = (paragraph) => {
        const text = paragraph.textContent;
        const frag = document.createDocumentFragment();
        let cursor = 0;
        for (const match of text.matchAll(letters)) {
            const at = match.index;
            if (at > cursor) frag.appendChild(document.createTextNode(text.slice(cursor, at)));
            const token = document.createElement('span');
            token.className = 'cfr-token';
            token.setAttribute('tabindex', '0');
            token.setAttribute('role', 'button');
            token.setAttribute('aria-label', fmt('js_word_action', {word: match[0]}));
            token.dataset.cfrWord = match[0];
            token.textContent = match[0];
            frag.appendChild(token);
            cursor = at + match[0].length;
        }
        if (cursor < text.length) frag.appendChild(document.createTextNode(text.slice(cursor)));
        paragraph.replaceChildren(frag);
    };
    const tokenize = (paragraph) => {
        // D2.9b: keep the frozen sentence boundaries for narration highlighting.
        // Word-level pronunciation and translation remain available inside them.
        const phrases = paragraph.querySelectorAll('.cfr-sync-phrase');
        if (phrases.length) { phrases.forEach(tokenizeText); return; }
        tokenizeText(paragraph);
    };

    const positionBubble = (bubble, rect) => {
        const margin = 10;
        const maxWidth = Math.min(300, window.innerWidth - 2 * margin);
        bubble.style.maxWidth = `${maxWidth}px`;
        // The tooltip lives in document.body to escape transformed/overflow-hidden pages.
        const w = bubble.offsetWidth;
        const h = bubble.offsetHeight;
        let left = rect.left + rect.width / 2 - w / 2;
        left = Math.min(window.innerWidth - w - margin, Math.max(margin, left));
        const roomAbove = rect.top >= h + margin * 2;
        const top = roomAbove ? rect.top - h - 9 : Math.min(rect.bottom + 9, window.innerHeight - h - margin);
        bubble.style.left = `${left}px`;
        bubble.style.top = `${Math.max(margin, top)}px`;
    };

    const mount = (PageFlipConstructor) => {
        const root = document.getElementById('cfr-reader');
        if (!root) return;
        const bookElement = root.querySelector('#cfr-book');
        const pages = [...root.querySelectorAll('.cfr-book-page')];
        const count = pages.length;
        const bookId = root.dataset.cfrBookId || '';
        // B2: browser-local demo only. Server-side account progression belongs to phase C.
        const progressKey = 'campusfr.reader.demo.page.v1:' + bookId;
        const readProgress = () => {
            try {
                const value = Number.parseInt(window.localStorage.getItem(progressKey), 10);
                return Number.isInteger(value) && value >= 0 && value < count ? value : 0;
            } catch (e) {
                return 0; // Storage may be disabled by the browser.
            }
        };
        const saveProgress = (index) => {
            try {
                if (Number.isInteger(index) && index >= 0 && index < count) {
                    window.localStorage.setItem(progressKey, String(index));
                }
            } catch (e) {
                // Keep reading functional when local storage is unavailable.
            }
        };
        const resumeIndex = readProgress();
        const reduceCheckbox = root.querySelector('[data-cfr-reduced]');
        const reducedPreference = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        reduceCheckbox.checked = reducedPreference;

        const chooser = root.querySelector('[data-cfr-book-select]');
        if (chooser) chooser.addEventListener('change', () => {
            // Only URL values generated by the server; no free-form navigation.
            const selected = chooser.selectedOptions[0]?.value;
            if (selected && selected.startsWith(window.location.origin + '/local/campusreader/index.php?')) {
                window.location.assign(selected);
            }
        });

        // Tokenization precedes StPageFlip: portrait mode may clone the page markup.
        root.querySelectorAll('.cfr-text').forEach(tokenize);
        let flip = null;
        const countEl = root.querySelector('[data-cfr-counter]');
        const previous = root.querySelector('[data-cfr-prev]');
        const next = root.querySelector('[data-cfr-next]');
        const stage = root.querySelector('.cfr-reader__stage');
        let turnHints = null;
        const updateCount = (index) => {
            countEl.textContent = fmt('js_page_counter', {page: index + 1, total: count});
            saveProgress(index);
            previous.disabled = index <= 0;
            next.disabled = index >= count - 1;
            // StPageFlip can advance in double-page spreads; keep hints in sync
            // with the same conditions already used for the main controls.
            if (turnHints) {
                turnHints.previous.hidden = previous.disabled;
                turnHints.next.hidden = next.disabled;
            }
        };

        const bubble = document.createElement('aside');
        bubble.className = 'cfr-tooltip';
        bubble.setAttribute('role', 'status');
        bubble.setAttribute('aria-live', 'polite');
        bubble.hidden = true;
        document.body.appendChild(bubble);
        // CAMPUSFR_D1_TTS: prefer authenticated neural French audio when configured;
        // otherwise use the best available French browser voice with a visible selector.
        let active = false;
        let translationRequest = 0;
        let translationAbort = null;
        const stopTranslation = () => {
            translationRequest += 1;
            if (translationAbort) translationAbort.abort();
            translationAbort = null;
        };
        const speechController = speechModule.create(root, bubble);
        const cancelSpeech = () => speechController.stop(true);
        const closeBubble = () => {
            bubble.hidden = true;
            active = false;
            stopTranslation();
            cancelSpeech();
        };
        const openBubble = (word, rect, pageIndex, paragraphIndex = -1) => {
            if (!word || word.length > 160) return;
            stopTranslation();
            cancelSpeech();
            const key = norm(word);
            bubble.replaceChildren();
            const heading = document.createElement('strong');
            heading.textContent = word;
            const translation = document.createElement('span');
            translation.className = 'cfr-tooltip__translation';
            const vocabularyMode = root.dataset.cfrVocabEnabled === '1';
            const validPage = Number.isInteger(pageIndex) && pageIndex >= 0;
            const selectedLang = root.dataset.cfrTargetLang || 'en';
            const langs = {ru:'Русский',en:'English',uk:'Українська',de:'Deutsch',
                es:'Español',it:'Italiano',pt:'Português',pl:'Polski'};
            const sub = document.createElement('small');
            let translationInput = null;
            let chooser = null;
            let suggestedValue = null;
            const isWord = /^\p{L}[\p{L}\p{M}]*(?:['’\-]\p{L}[\p{L}\p{M}]*)*$/u.test(word) && Array.from(word).length <= 80;
            translation.setAttribute('aria-live', 'polite');
            translation.textContent = vocabularyMode ? t('js_translate_search') :
                (demoRu[key] || t('js_translate_demo_missing'));
            sub.textContent = vocabularyMode ? t('js_translate_ai_note') :
                (demoRu[key] ? t('js_translate_demo') : t('js_translate_future'));
            bubble.appendChild(heading);
            if (vocabularyMode) {
                const langlabel = document.createElement('label');
                langlabel.className = 'cfr-tooltip__lang';
                langlabel.textContent = t('js_translate_to');
                chooser = document.createElement('select');
                chooser.setAttribute('aria-label', t('js_translate_language'));
                for (const [code, name] of Object.entries(langs)) {
                    const option = document.createElement('option');
                    option.value = code; option.textContent = name; chooser.appendChild(option);
                }
                chooser.value = langs[selectedLang] ? selectedLang : 'en';
                langlabel.appendChild(chooser);
                bubble.appendChild(langlabel);
            }
            bubble.append(translation, sub);
            if (!vocabularyMode || isWord) speechController.mountControls(word, pageIndex);
            const suggest = () => {
                if (!vocabularyMode || !validPage || !chooser) return;
                if (!isWord) {
                    translation.textContent = t('js_translate_one');
                    return;
                }
                if (root.dataset.cfrTranslationEnabled !== '1') {
                    translation.textContent = t('js_translate_off');
                    return;
                }
                stopTranslation();
                const serial = translationRequest;
                translationAbort = new AbortController();
                const requestedLang = chooser.value;
                root.dataset.cfrTargetLang = requestedLang;
                if (translationInput && translationInput.value === suggestedValue) translationInput.value = '';
                suggestedValue = null;
                translation.textContent = t('js_translate_search');
                const body = new URLSearchParams({slug:root.dataset.cfrPubSlug || '',
                    chapter:root.dataset.cfrChapter || '',page:String(pageIndex),text:word,
                    target:requestedLang,paragraph:String(paragraphIndex),sesskey:root.dataset.cfrTtsSesskey || ''});
                fetch(window.M.cfg.wwwroot + '/local/campusreader/publication_translate.php', {
                    method:'POST',credentials:'same-origin',cache:'no-store',
                    headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
                    body:body.toString(),signal:translationAbort.signal,
                }).then((response) => {
                    if (!response.ok) throw new Error('translation-unavailable');
                    return response.json();
                }).then((result) => {
                    if (serial !== translationRequest) return;
                    if (!result.success || typeof result.translation !== 'string') throw new Error('translation-unavailable');
                    translation.textContent = result.translation;
                    if (translationInput && !translationInput.value) {
                        suggestedValue = result.translation;
                        translationInput.value = suggestedValue;
                    }
                    positionBubble(bubble, rect);
                }).catch(() => {
                    if (serial === translationRequest) {
                        translation.textContent = t('js_translate_failed');
                        positionBubble(bubble, rect);
                    }
                });
            };
            if (chooser) chooser.addEventListener('change', (e) => { e.stopPropagation(); suggest(); });
            if (vocabularyMode && isWord &&
                    Number.isInteger(pageIndex) && pageIndex >= 0) {
                const form = document.createElement('form');
                form.className = 'cfr-vocab-save';
                const label = document.createElement('label');
                label.textContent = t('js_translate_optional');
                const input = document.createElement('input');
                input.type = 'text'; input.maxLength = 255;
                input.placeholder = t('js_translate_placeholder');
                label.appendChild(input);
                translationInput = input;
                const save = document.createElement('button');
                save.type = 'submit'; save.textContent = t('js_add_vocab');
                const status = document.createElement('span');
                status.className = 'cfr-vocab-save__status';
                status.setAttribute('role', 'status');
                form.append(label, save, status);
                form.addEventListener('submit', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    save.disabled = true;
                    status.textContent = t('js_saving');
                    const body = new URLSearchParams({
                        action: 'add', slug: root.dataset.cfrPubSlug || '',
                        chapter: root.dataset.cfrChapter || '',
                        page: String(pageIndex), word: word,
                        translation: input.value,
                        sesskey: root.dataset.cfrTtsSesskey || '',
                    });
                    fetch(window.M.cfg.wwwroot + '/local/campusreader/vocabulary_action.php', {
                        method: 'POST', credentials: 'same-origin', cache: 'no-store',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                        body: body.toString(),
                    }).then((response) => {
                        if (!response.ok) throw new Error('save-failed');
                        return response.json();
                    }).then(() => {
                        status.textContent = t('js_saved_vocab');
                        save.textContent = t('js_saved');
                    }).catch(() => {
                        status.textContent = t('js_save_failed');
                        save.disabled = false;
                    });
                });
                bubble.appendChild(form);
            }
            bubble.hidden = false;
            active = true;
            positionBubble(bubble, rect);
            suggest();
            if (!vocabularyMode || isWord) speechController.speak(word, pageIndex); // Exact authorized word.
        };

        if (typeof PageFlipConstructor !== 'function') {
            throw new Error('Constructeur StPageFlip invalide.');
        }
        flip = new PageFlipConstructor(bookElement, {
            width: 390, height: 550, size: 'stretch',
            minWidth: 225, maxWidth: 390, minHeight: 330, maxHeight: 550,
            usePortrait: true, showCover: true, drawShadow: true,
            maxShadowOpacity: 0.65, flippingTime: reducedPreference ? 1 : 900,
            // Allow realistic physical corner drag. A regular click on text never flips.
            disableFlipByClick: true, useMouseEvents: true,
            showPageCorners: true, clickEventForward: true,
            mobileScrollSupport: true, autoSize: true,
        });
        flip.on('flip', (e) => {
            updateCount(e.data);
            closeBubble();
            cancelSpeech();
        });
        flip.on('changeOrientation', () => {
            closeBubble();
            // Reposition after StPageFlip recalculates the portrait/spread size.
            window.requestAnimationFrame(() => window.requestAnimationFrame(placeTurnHints));
        });
        flip.on('init', (e) => updateCount(e.data.page));
        flip.loadFromHTML(pages);
        // Resume without animation; each book has an independent progress key.
        if (resumeIndex > 0) {
            try { flip.turnToPage(resumeIndex); } catch (e) {
                console.warn('CampusFR Reader B2 : reprise ignorée', e);
            }
        }
        bookElement.classList.add('cfr-book--ready');
        const boot = root.querySelector('[data-cfr-boot]');
        if (boot) boot.hidden = true;
        updateCount(flip.getCurrentPageIndex());
        const jump = (dir) => {
            if (!flip) return;
            closeBubble(); cancelSpeech();
            const here = flip.getCurrentPageIndex();
            if (dir < 0 && here <= 0) return;
            if (dir > 0 && here >= count - 1) return;
            if (reduceCheckbox.checked) {
                if (dir > 0) flip.turnToNextPage(); else flip.turnToPrevPage();
            } else {
                if (dir > 0) flip.flipNext('bottom'); else flip.flipPrev('bottom');
            }
        };
        previous.addEventListener('click', () => jump(-1));
        next.addEventListener('click', () => jump(1));

        // B1.3 — Small, explicit page-turn affordances OUTSIDE the StPageFlip DOM.
        // They do not capture drags on the physical page corners or selected words.
        const makeTurnHint = (direction, label, arrow) => {
            const control = document.createElement('button');
            control.type = 'button';
            control.className = `cfr-turn-hint cfr-turn-hint--${direction}`;
            control.setAttribute('aria-label', label);
            control.title = label;
            const symbol = document.createElement('span');
            symbol.className = 'cfr-turn-hint__symbol';
            symbol.setAttribute('aria-hidden', 'true');
            symbol.textContent = arrow;
            const caption = document.createElement('span');
            caption.className = 'cfr-turn-hint__caption';
            caption.setAttribute('aria-hidden', 'true');
            caption.textContent = direction === 'next' ? t('js_turn_forward') : t('js_turn_back');
            control.append(symbol, caption);
            stage.appendChild(control);
            return control;
        };
        turnHints = {
            previous: makeTurnHint('previous', t('js_turn_previous'), '↶'),
            next: makeTurnHint('next', t('js_turn_next'), '↷'),
        };
        turnHints.previous.addEventListener('click', () => jump(-1));
        turnHints.next.addEventListener('click', () => jump(1));
        // D2.12.3.2: let the stage hints extend into the ambient reader margins.
        // The pages themselves still clip their content and retain touch gestures.
        root.classList.add('cfr-reader--turn-hints-external');

        function placeTurnHints() {
            if (!turnHints || !stage || !bookElement.isConnected) return;
            const bounds = bookElement.getBoundingClientRect();
            const container = stage.getBoundingClientRect();
            if (bounds.width < 80 || bounds.height < 80 || container.width < 80) return;
            const compact = window.matchMedia('(max-width: 700px)').matches;
            const gap = compact ? 8 : 14;
            const viewportMargin = 8;
            const viewportWidth = document.documentElement.clientWidth || window.innerWidth;
            for (const [direction, control] of Object.entries(turnHints)) {
                const w = control.offsetWidth || (compact ? 44 : 74);
                const h = control.offsetHeight || (compact ? 44 : 60);
                // Absolute viewport positions keep the buttons off the printed/text pages,
                // even when the STAGE itself has no room beside a double-page spread.
                const outsideLeft = direction === 'previous'
                    ? bounds.left - w - gap
                    : bounds.right + gap;
                const fitsViewport = outsideLeft >= viewportMargin &&
                    outsideLeft + w <= viewportWidth - viewportMargin;
                // Do not place a floating button on top of reading text. If a phone
                // is too narrow, the persistent Précédent / Suivant bar remains usable.
                control.style.visibility = fitsViewport ? 'visible' : 'hidden';
                if (!fitsViewport) continue;
                const top = compact ? bounds.bottom - container.top - h - 22
                    : bounds.top - container.top + (bounds.height - h) * 0.58;
                const maxTop = Math.max(4, container.height - h - 4);
                control.style.left = `${Math.round(outsideLeft - container.left)}px`;
                control.style.top = `${Math.max(4, Math.min(maxTop, top))}px`;
            }
        }
        updateCount(flip.getCurrentPageIndex());
        window.requestAnimationFrame(() => window.requestAnimationFrame(placeTurnHints));
        window.addEventListener('resize', () => window.requestAnimationFrame(placeTurnHints), {passive: true});
        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(() => window.requestAnimationFrame(placeTurnHints));
            observer.observe(bookElement);
            observer.observe(stage);
            window.addEventListener('pagehide', () => observer.disconnect(), {once: true});
        }

        // Do not feed page-corner gestures to StPageFlip when a reader selects text.
        // Capture phase protects text in the portrait clones as well.
        ['mousedown', 'touchstart'].forEach((eventName) => {
            root.addEventListener(eventName, (e) => {
                if (e.target.closest('.cfr-text, .cfr-token')) e.stopPropagation();
            }, {capture: true, passive: true});
        });
        // Delegation to the stable shell, not to original page nodes (cloned in portrait mode).
        root.addEventListener('click', (e) => {
            const target = e.target.closest('.cfr-token');
            if (!target || !root.contains(target)) return;
            e.stopPropagation();
            e.preventDefault();
            const selection = window.getSelection();
            if (selection && isExpression(selection.toString())) return;
            openBubble(target.dataset.cfrWord || target.textContent, target.getBoundingClientRect(),
                Number(target.closest('[data-page-index]')?.dataset.pageIndex ?? -1),
                Number(target.closest('[data-cfr-paragraph]')?.dataset.cfrParagraph ?? -1));
        });
        root.addEventListener('keydown', (e) => {
            const target = e.target.closest('.cfr-token');
            if (target && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                openBubble(target.dataset.cfrWord || target.textContent, target.getBoundingClientRect(),
                Number(target.closest('[data-page-index]')?.dataset.pageIndex ?? -1),
                Number(target.closest('[data-cfr-paragraph]')?.dataset.cfrParagraph ?? -1));
            }
        });
        const checkSelection = () => {
            const sel = window.getSelection();
            if (!sel || sel.isCollapsed || !isExpression(sel.toString())) return;
            const anchor = sel.anchorNode?.parentElement;
            const focus = sel.focusNode?.parentElement;
            if (!anchor?.closest('.cfr-text') || !focus?.closest('.cfr-text')) return;
            if (!root.contains(anchor) || !root.contains(focus)) return;
            const expression = sel.toString().trim().replace(/\s+/g, ' ');
            if (!expression || expression.length > 160) return;
            const range = sel.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            if (rect.width < 1 && rect.height < 1) return;
            const aPage = anchor.closest('[data-page-index]');
            const fPage = focus.closest('[data-page-index]');
            if (!aPage || aPage !== fPage) return; // No cross-page speech/requests.
            openBubble(expression, rect, Number(aPage.dataset.pageIndex));
        };
        root.addEventListener('mouseup', () => window.requestAnimationFrame(checkSelection));
        root.addEventListener('touchend', () => window.setTimeout(checkSelection, 80), {passive: true});
        document.addEventListener('pointerdown', (e) => {
            if (!active) return;
            if (!bubble.contains(e.target) && !e.target.closest('.cfr-token')) closeBubble();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { closeBubble(); cancelSpeech(); }
            // Avoid changing pages when typing in controls outside the reader.
            if (!root.contains(document.activeElement)) return;
            if (e.target.closest('input,textarea,select')) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); jump(-1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); jump(1); }
        });
        // StPageFlip already handles native touch dragging. A second manual swipe
        // handler caused occasional double page turns; do not duplicate it here.
        window.addEventListener('pagehide', () => { stopTranslation(); cancelSpeech(); });
    };
    // page-flip.browser.js is a UMD bundle: on Moodle, RequireJS is present and
    // the vendor exports an AMD module rather than writing window.St.PageFlip.
    // Load it THROUGH RequireJS, never by injecting a plain <script> tag.
    // This also prevents anonymous AMD define() mismatches in the page console.
    const getPageFlipConstructor = (loaded) => {
        const candidates = [
            loaded?.PageFlip,
            loaded?.St?.PageFlip,
            loaded?.default?.PageFlip,
            loaded?.default,
            loaded,
            window.St?.PageFlip, // Fallback if the vendor ever switches to a global-only build.
        ];
        return candidates.find((candidate) => typeof candidate === 'function') || null;
    };

    const loadVendor = (url) => new Promise((resolve, reject) => {
        if (!url || !url.startsWith(window.location.origin + '/')) {
            reject(new Error('URL locale de StPageFlip invalide.'));
            return;
        }
        // The AMD-local require, injected by define(['require']), associates
        // the anonymous vendor module with the requested absolute URL.
        try {
            require([url], (loaded) => {
                const PageFlipConstructor = getPageFlipConstructor(loaded);
                if (PageFlipConstructor) {
                    resolve(PageFlipConstructor);
                } else {
                    reject(new Error('Le module AMD StPageFlip est chargé, mais PageFlip est absent.'));
                }
            }, (error) => {
                console.error('CampusFR reader B1.2: erreur RequireJS StPageFlip', error);
                reject(new Error('Impossible de charger le module AMD StPageFlip local.'));
            });
        } catch (error) {
            console.error('CampusFR reader B1.2: exception RequireJS StPageFlip', error);
            reject(new Error('Initialisation RequireJS de StPageFlip impossible.'));
        }
    });

    const init = (vendorUrl) => {
        const root = document.getElementById('cfr-reader');
        if (!root || root.dataset.cfrBooting) return;
        root.dataset.cfrBooting = '1';
        try { UI = JSON.parse(root.dataset.cfrUi || '{}'); } catch (ignored) { UI = {}; }
        const status = root.querySelector('[data-cfr-boot]');
        if (status) {
            status.hidden = false;
            status.textContent = t('js_boot_loading');
        }
        loadVendor(vendorUrl).then((PageFlipConstructor) => mount(PageFlipConstructor)).catch((err) => {
            if (status) {
                status.hidden = false;
                status.classList.add('cfr-reader__boot--error');
                status.textContent = t('js_boot_failed') + err.message;
            }
            // Console detail, with no secrets or user data.
            console.error('CampusFR reader B1.2:', err);
        });
    };
    return {init};
});
