/**
 * CampusFR D2.9b — read-only narration cues carried by approved frozen snapshots.
 * Never calls CRM endpoints, never reads live editable alignments, and does not
 * interfere with the word click-to-translate/pronounce behavior.
 */
define([], function() {
    'use strict';
    const FALLBACK = {"js_sync_paused":"Lecture en pause.","js_sync_playing":"Lecture en cours…"};
    function init() {
        const json = document.getElementById('cfr-pub-sync-data');
        const audio = document.querySelector('[data-cfr-sync-player]');
        const root = document.getElementById('cfr-reader');
        let UI = {};
        try { UI = JSON.parse(root?.dataset.cfrUi || '{}'); } catch (ignored) { UI = {}; }
        const t = (key) => UI[key] || FALLBACK[key] || key;
        const caption = document.querySelector('[data-cfr-sync-current]');
        if (!json || !audio || !root) { return; }
        let cues = [];
        try {
            cues = JSON.parse(json.textContent);
            if (!Array.isArray(cues) || cues.length > 1200 || cues.some(c =>
                    typeof c !== 'object' || !/^p\d+s\d+$/.test(c.id) || typeof c.skip !== 'boolean' ||
                    (!c.skip && (!Number.isInteger(c.startms) || !Number.isInteger(c.endms) ||
                        c.startms < 0 || c.endms <= c.startms)))) { return; }
        } catch (ignored) { return; }
        const timed = cues.filter(c => !c.skip);
        if (!timed.length) { return; }
        const byid = new Map(timed.map(c => [c.id, c]));
        let current = '';
        let raf = 0;
        function apply() {
            raf = 0;
            const ms = Math.round(audio.currentTime * 1000);
            let active = '';
            // Approved cues are ordered, but gaps between spoken phrases are allowed.
            for (let i = 0; i < timed.length; i++) {
                const c = timed[i];
                if (ms >= c.startms && ms < c.endms) { active = c.id; break; }
            }
            if (current === active) { return; }
            current = active;
            root.querySelectorAll('.cfr-sync-phrase').forEach(el => {
                el.classList.toggle('is-speaking', active !== '' && el.dataset.cfrSyncId === active);
            });
            if (caption) {
                const match = byid.get(active);
                caption.textContent = match && typeof match.text === 'string' ? match.text :
                    (audio.paused ? t('js_sync_paused') : t('js_sync_playing'));
            }
        }
        function schedule() {
            if (raf) { return; }
            raf = requestAnimationFrame(apply);
        }
        ['timeupdate','seeked','play','pause','ended','loadedmetadata'].forEach(event =>
            audio.addEventListener(event, schedule));
        schedule();
    }
    return {init};
});
