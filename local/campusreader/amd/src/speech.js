/** CampusFR D2.14.2 — French neural voices via authenticated Poodll Reader endpoint only. */
define([], function() {
    'use strict';
    const FALLBACK = {"js_listen_again":"🔊 Réécouter","js_retry":"Réessayer","js_retry_aria":"Réessayer la prononciation IA","js_voice_ai":"Voix IA : ","js_voice_audio_error":"Audio IA momentanément indisponible.","js_voice_blocked":"Appuie sur « Réécouter » pour lancer le son.","js_voice_error":"Voix IA momentanément indisponible.","js_voice_preparing":"Préparation de la voix de ","js_voice_unconfigured":"Voix neuronales non configurées.","js_voice_waiting":"La voix termine de se préparer… ({n}/2)","vocab_voice":"Voix française neuronale"};
    var PREF = 'campusfr.reader.neural.voice.v1';
    var VOICES = [
        {id: 'Lea', label: 'Léa · voix féminine'},
        {id: 'Remi', label: 'Rémi · voix masculine'}
    ];
    function chosen() {
        try {
            var saved = window.localStorage.getItem(PREF);
            if (VOICES.some(function(v) { return v.id === saved; })) return saved;
        } catch (error) { /* Private window. */ }
        return 'Lea';
    }
    function create(root, bubble) {
        let UI = {};
        try { UI = JSON.parse(root.dataset.cfrUi || '{}'); } catch (ignored) { UI = {}; }
        const t = (key) => UI[key] || FALLBACK[key] || key;
        const fmt = (key, values) => t(key).replace(/\{(\w+)\}/g, (_, name) => String(values[name] ?? ''));
        var neuralEnabled = root.dataset.cfrTtsEnabled === '1';
        var pending = null, player = null, playerUrl = null, playerKey = null, version = 0, playAttempt = 0;
        var narration = root.querySelector('[data-cfr-audio-player] audio');
        var canResumeNarration = false, liveState = null, retryControl = null, mode = chosen();
        function setRetryVisible(visible) {
            if (retryControl) retryControl.hidden = !visible;
        }
        function status(message) { if (liveState) liveState.textContent = message; }
        function setLoading(loading) {
            if (liveState) liveState.classList.toggle('cfr-tts-busy', loading);
        }
        function releasePlayer() {
            if (player) {
                player.onended = null; player.onerror = null;
                player.pause(); player.removeAttribute('src'); player.load(); player = null;
            }
            playerKey = null;
            if (playerUrl) { URL.revokeObjectURL(playerUrl); playerUrl = null; }
        }
        function resumeNarration() {
            var resume = canResumeNarration;
            canResumeNarration = false;
            if (resume && narration && narration.paused && !narration.ended) {
                var p = narration.play();
                if (p && typeof p.catch === 'function') p.catch(function() {});
            }
        }
        function stop(resume) {
            version += 1;
            if (pending) { pending.abort(); pending = null; }
            releasePlayer();
            setLoading(false);
            setRetryVisible(false);
            if (resume) resumeNarration();
            else canResumeNarration = false;
        }
        function beforePlay() {
            if (narration && !narration.paused && !narration.ended) {
                narration.pause(); canResumeNarration = true;
            }
        }
        var audioRoot = root.querySelector('[data-cfr-audio-player]');
        if (audioRoot) audioRoot.addEventListener('pointerdown', function() {
            canResumeNarration = false;
        }, true);
        if (narration) narration.addEventListener('play', function() {
            // A late play event after we paused narration must not cancel a new word.
            if (narration.paused) return;
            canResumeNarration = false;
            if (pending || (player && !player.paused && !player.ended)) stop(false);
        });
        function keyFor(value, pageIndex) { return JSON.stringify([value, pageIndex, mode]); }
        function playReady(currentVersion) {
            if (!player || version !== currentVersion) return;
            var currentPlayer = player;
            var attempt = ++playAttempt;
            beforePlay();
            setRetryVisible(false);
            status(t('js_voice_ai') + (mode === 'Remi' ? 'Rémi' : 'Léa'));
            try {
                currentPlayer.currentTime = 0;
                var promise = currentPlayer.play();
                if (promise && typeof promise.catch === 'function') promise.catch(blocked);
            } catch (error) { blocked(); }
            function blocked() {
                if (currentVersion !== version || player !== currentPlayer || attempt !== playAttempt) return;
                resumeNarration();
                status(t('js_voice_blocked'));
            }
        }
        function neuralSpeak(value, pageIndex, currentVersion) {
            var controller = new AbortController();
            pending = controller;
            setRetryVisible(false);
            setLoading(true);
            status(t('js_voice_preparing') + (mode === 'Remi' ? 'Rémi' : 'Léa') + '…');
            var body = new URLSearchParams();
            if (root.dataset.cfrTtsScope === 'publication') {
                body.set('slug', root.dataset.cfrPubSlug || '');
                body.set('chapter', root.dataset.cfrChapter || '');
            } else {
                body.set('book', root.dataset.cfrTtsBook || '');
            }
            body.set('page', String(pageIndex));
            body.set('text', value);
            body.set('voice', mode);
            body.set('sesskey', root.dataset.cfrTtsSesskey || '');
            var endpoint = root.dataset.cfrTtsScope === 'publication'
                ? '/local/campusreader/publication_pronounce.php'
                : '/local/campusreader/pronounce.php';
            // Cloud Poodll can return an intermediate URL before the final MP3 is cached.
            // Retry ONLY a 503 carrying our server's explicit Retry-After hint.
            // Maximum 2 retries, no new voice, word or user-provided URL involved.
            function fetchAudio(attempt) {
                if (controller.signal.aborted || version !== currentVersion) {
                    return Promise.reject(new Error('cancelled'));
                }
                return fetch(window.M.cfg.wwwroot + endpoint, {
                    method: 'POST', credentials: 'same-origin', cache: 'no-store',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                    body: body.toString(), signal: controller.signal
                }).then(function(response) {
                    if (controller.signal.aborted || version !== currentVersion) throw new Error('cancelled');
                    if (response.status === 503 && response.headers.has('Retry-After') && attempt < 2) {
                        var seconds = Number(response.headers.get('Retry-After'));
                        if (!Number.isFinite(seconds) || seconds < 1 || seconds > 5) {
                            throw new Error('invalid-retry-hint');
                        }
                        status(fmt('js_voice_waiting', {n: attempt + 1}));
                        return new Promise(function(resolve) { setTimeout(resolve, seconds * 1000); })
                            .then(function() { return fetchAudio(attempt + 1); });
                    }
                    if (!response.ok || !(response.headers.get('Content-Type') || '').includes('audio/mpeg')) {
                        throw new Error('tts-unavailable');
                    }
                    return response.blob();
                });
            }
            fetchAudio(0).then(function(blob) {
                if (version !== currentVersion) return;
                pending = null;
                setLoading(false);
                playerUrl = URL.createObjectURL(blob);
                player = new Audio(playerUrl);
                playerKey = keyFor(value, pageIndex);
                player.onended = function() {
                    if (currentVersion === version) resumeNarration();
                };
                player.onerror = function() {
                    if (currentVersion === version) {
                        stop(true);
                        status(t('js_voice_audio_error'));
                        setRetryVisible(true);
                    }
                };
                playReady(currentVersion);
            }).catch(function(error) {
                if (version !== currentVersion || error.name === 'AbortError') return;
                pending = null;
                releasePlayer();
                setLoading(false);
                resumeNarration();
                status(t('js_voice_error'));
                setRetryVisible(true);
            });
        }
        function speak(value, pageIndex) {
            if (!neuralEnabled || !Number.isInteger(pageIndex) || pageIndex < 0) {
                stop(true);
                status('Prononciation neuronale indisponible pour cette page.');
                return;
            }
            var carryNarration = canResumeNarration;
            stop(false);
            var currentVersion = version;
            beforePlay();
            if (carryNarration) canResumeNarration = true;
            neuralSpeak(value, pageIndex, currentVersion);
        }
        function mountControls(value, pageIndex) {
            var panel = document.createElement('div');
            panel.className = 'cfr-tooltip__voice-panel';
            var replay = document.createElement('button');
            replay.type = 'button'; replay.className = 'cfr-tooltip__replay';
            replay.textContent = t('js_listen_again');
            replay.disabled = !neuralEnabled;
            replay.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                // Loaded bytes allow a synchronous user gesture even after autoplay was blocked.
                if (player && playerKey === keyFor(value, pageIndex)) playReady(version);
                else if (!pending) speak(value, pageIndex);
            });
            var selector = document.createElement('select');
            selector.className = 'cfr-tooltip__voice-select';
            selector.setAttribute('aria-label', t('vocab_voice'));
            selector.disabled = !neuralEnabled;
            VOICES.forEach(function(voice) {
                var option = document.createElement('option');
                option.value = voice.id; option.textContent = t(voice.id === 'Lea' ? 'vocab_voice_female' : 'vocab_voice_male');
                selector.appendChild(option);
            });
            selector.value = mode;
            selector.addEventListener('change', function(e) {
                mode = e.target.value;
                try { window.localStorage.setItem(PREF, mode); } catch (error) { /* Private window. */ }
                speak(value, pageIndex);
            });
            liveState = document.createElement('span');
            liveState.className = 'cfr-tooltip__voice-status';
            liveState.setAttribute('role', 'status');
            liveState.setAttribute('aria-live', 'polite');
            retryControl = document.createElement('button');
            retryControl.type = 'button';
            retryControl.className = 'cfr-tts-retry';
            retryControl.textContent = t('js_retry');
            retryControl.setAttribute('aria-label', t('js_retry_aria'));
            retryControl.hidden = true;
            retryControl.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (pending || !neuralEnabled) return;
                speak(value, pageIndex);
            });
            panel.append(replay, selector, liveState, retryControl);
            bubble.appendChild(panel);
            if (!neuralEnabled) status(t('js_voice_unconfigured'));
            return panel;
        }
        return {speak: speak, stop: stop, mountControls: mountControls};
    }
    return {create: create};
});
