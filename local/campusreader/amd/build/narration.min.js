/**
 * CampusFR C3.4 — progressive enhancement of a protected HTML5 narration.
 * The native audio remains available if JS cannot initialize.
 * GPL-3.0-or-later.
 */
define([], function() {
    'use strict';

    function formatTime(seconds) {
        if (!Number.isFinite(seconds) || seconds < 0) {
            return '0:00';
        }
        var n = Math.floor(seconds);
        return Math.floor(n / 60) + ':' + String(n % 60).padStart(2, '0');
    }

    function init() {
        document.querySelectorAll('[data-cfr-audio-player]').forEach(function(root) {
            if (root.dataset.cfrAudioReady === '1') {
                return;
            }
            var audio = root.querySelector('audio');
            var ui = root.querySelector('[data-cfr-audio-ui]');
            var toggle = root.querySelector('[data-cfr-audio-toggle]');
            var label = root.querySelector('[data-cfr-audio-label]');
            var details = root.querySelector('[data-cfr-audio-details]');
            var seek = root.querySelector('[data-cfr-audio-seek]');
            var current = root.querySelector('[data-cfr-audio-current]');
            var total = root.querySelector('[data-cfr-audio-total]');
            var mute = root.querySelector('[data-cfr-audio-mute]');
            var volume = root.querySelector('[data-cfr-audio-volume]');
            if (!audio || !ui || !toggle || !label || !details || !seek || !current ||
                    !total || !mute || !volume) {
                return;
            }
            root.dataset.cfrAudioReady = '1';

            var revealed = false;
            var lastVolume = 1;
            function updateTimes() {
                var duration = Number.isFinite(audio.duration) ? audio.duration : 0;
                var position = Number.isFinite(audio.currentTime) ? audio.currentTime : 0;
                current.textContent = formatTime(position);
                total.textContent = duration > 0 ? formatTime(duration) : '--:--';
                var value = duration > 0 ? Math.round(1000 * position / duration) : 0;
                seek.value = String(Math.max(0, Math.min(1000, value)));
                seek.style.setProperty('--cfr-audio-progress', (value / 10) + '%');
                seek.disabled = duration <= 0;
            }
            function updatePlayState() {
                var playing = !audio.paused && !audio.ended;
                ui.classList.toggle('cfr-reader-audio__custom--playing', playing);
                toggle.setAttribute('aria-label', playing ? 'Mettre en pause la narration' : 'Écouter la narration');
                toggle.setAttribute('aria-pressed', playing ? 'true' : 'false');
                if (revealed) {
                    label.hidden = true;
                    details.hidden = false;
                }
                updateTimes();
            }
            function updateVolume() {
                var quiet = audio.muted || audio.volume === 0;
                mute.classList.toggle('cfr-reader-audio__mute--off', quiet);
                mute.setAttribute('aria-label', quiet ? 'Réactiver le son' : 'Couper le son');
                mute.setAttribute('aria-pressed', quiet ? 'true' : 'false');
                volume.value = quiet ? '0' : String(Math.round(audio.volume * 100));
            }
            function fallback() {
                // E.g. MIME not supported: put accessible browser controls back.
                audio.controls = true;
                ui.hidden = true;
                root.classList.remove('cfr-reader-audio--enhanced');
            }

            toggle.addEventListener('click', function() {
                revealed = true;
                label.hidden = true;
                details.hidden = false;
                if (audio.paused || audio.ended) {
                    if (audio.ended) {
                        audio.currentTime = 0;
                    }
                    var request = audio.play();
                    if (request && typeof request.catch === 'function') {
                        request.catch(function() {
                            // Keep the controls available if playback is rejected.
                            updatePlayState();
                        });
                    }
                } else {
                    audio.pause();
                }
                updatePlayState();
            });
            seek.addEventListener('input', function() {
                if (Number.isFinite(audio.duration) && audio.duration > 0) {
                    audio.currentTime = audio.duration * Number(seek.value) / 1000;
                    updateTimes();
                }
            });
            volume.addEventListener('input', function() {
                var next = Number(volume.value) / 100;
                audio.volume = Math.max(0, Math.min(1, next));
                audio.muted = false;
                if (audio.volume > 0) {
                    lastVolume = audio.volume;
                }
                updateVolume();
            });
            mute.addEventListener('click', function() {
                if (audio.muted || audio.volume === 0) {
                    audio.muted = false;
                    audio.volume = lastVolume > 0 ? lastVolume : 1;
                } else {
                    lastVolume = audio.volume;
                    audio.muted = true;
                }
                updateVolume();
            });
            ['loadedmetadata', 'durationchange', 'timeupdate', 'seeking', 'seeked', 'ended',
                'pause', 'play', 'playing'].forEach(function(eventName) {
                audio.addEventListener(eventName, updatePlayState);
            });
            audio.addEventListener('volumechange', updateVolume);
            audio.addEventListener('error', fallback);

            // Only hide native controls when every custom control is wired.
            audio.controls = false;
            ui.hidden = false;
            root.classList.add('cfr-reader-audio--enhanced');
            updateTimes();
            updateVolume();
            updatePlayState();
        });
    }

    return {init: init};
});
