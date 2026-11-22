/* CampusFR D2.9b — editor-side waveform, non-AI suggestions and visual cue editing.
 * No audio is sent to any speech provider; review/approval remain server-side.
 */
(function () {
    'use strict';
    const audio = document.getElementById('cfr-d29-audio');
    const form = document.getElementById('cfr-d29-form');
    if (!audio || !form) { return; }
    const jsonNode = document.getElementById('cfr-d29-i18n');
    let labels = {};
    try { labels = JSON.parse(jsonNode?.textContent || '{}'); } catch (ignored) { /* Safe fallback. */ }
    const tr = key => Object.prototype.hasOwnProperty.call(labels, key) ? labels[key] : key;
    const canvas = document.getElementById('cfr-d29-wave');
    const ctx = canvas && canvas.getContext('2d');
    const box = canvas && canvas.parentElement;
    const readout = document.getElementById('cfr-d29-current');
    const payload = document.getElementById('cfr-d29-payload');
    const summary = document.getElementById('cfr-d29-summary');
    const tools = document.getElementById('cfr-d29-tools-status');
    const total = document.getElementById('cfr-d29-endlabel');
    const guess = document.getElementById('cfr-d29-suggest');
    const audition = document.getElementById('cfr-d29-audition');
    const speed = document.getElementById('cfr-d29-speed');
    const rows = Array.from(form.querySelectorAll('[data-cue-id]'));
    let chosen = rows.findIndex(r => r.querySelector('[data-time=start]').value !== '');
    if (chosen < 0) { chosen = 0; }
    let envelope = null;
    let drag = null;
    let auditionTo = null;
    let clockRaf = 0;
    const MIN_MS = 80;
    const clamp = (n, lo, hi) => Math.max(lo, Math.min(hi, n));
    const fmt = sec => {
        sec = Math.max(0, Number.isFinite(sec) ? sec : 0);
        return Math.floor(sec / 60) + ':' + String(Math.floor(sec % 60)).padStart(2, '0') +
            '.' + String(Math.floor((sec % 1) * 100)).padStart(2, '0');
    };
    const durationMs = () => Number.isFinite(audio.duration) && audio.duration > 0 ?
        Math.round(audio.duration * 1000) : 0;
    const startInput = row => row.querySelector('[data-time=start]');
    const endInput = row => row.querySelector('[data-time=end]');
    const skipInput = row => row.querySelector('[data-skip]');
    const num = input => input.value.trim() === '' ? null : Math.round(Number(input.value) * 1000);
    const setTime = (input, ms) => {
        if (input && !input.disabled) { input.value = (ms / 1000).toFixed(2); }
    };
    function setStatus(message, error) {
        if (!tools) { return; }
        tools.textContent = message;
        tools.setAttribute('role', error ? 'alert' : 'status');
    }
    function choose(i, seek) {
        if (i < 0 || i >= rows.length) { return; }
        chosen = i;
        rows.forEach((row, index) => row.classList.toggle('is-selected', index === i));
        if (seek) {
            const value = num(startInput(rows[i]));
            if (value !== null && durationMs() > 0) { audio.currentTime = value / 1000; }
        }
        draw();
    }
    function colors() {
        const dark = document.documentElement.classList.contains('campus-dark') ||
            document.body.classList.contains('campus-dark');
        return {base: dark ? '#a9a1c2' : '#978ca8', track: dark ? '#262234' : '#f6f0f6',
            line: dark ? '#c7b9d3' : '#c6b9cb', active: '#e0397c', surface: dark ? '#392538' : '#ffe4ee'};
    }
    function draw() {
        if (!canvas || !ctx) { return; }
        const rect = canvas.getBoundingClientRect();
        const w = Math.max(1, rect.width), h = Math.max(1, rect.height);
        const scale = Math.min(window.devicePixelRatio || 1, 2);
        if (canvas.width !== Math.round(w * scale) || canvas.height !== Math.round(h * scale)) {
            canvas.width = Math.round(w * scale); canvas.height = Math.round(h * scale);
        }
        ctx.setTransform(scale, 0, 0, scale, 0, 0);
        const c = colors();
        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle = c.track; ctx.fillRect(0, 0, w, h);
        const dur = durationMs();
        if (!dur) { return; }
        if (envelope && envelope.length > 0) {
            const bw = Math.max(1, w / envelope.length);
            envelope.forEach((level, i) => {
                const amp = Math.max(2, Math.pow(clamp(level, 0, 1), .65) * (h - 22));
                ctx.fillStyle = c.base;
                ctx.fillRect(i * bw, (h - amp) / 2, Math.max(1, bw - .3), amp);
            });
        } else {
            ctx.font = '12px system-ui, sans-serif'; ctx.textAlign = 'center';
            ctx.fillStyle = c.line;
            ctx.fillText(tr('cfr_i18n_audio_js_01'), w / 2, h / 2);
        }
        // Show all intervals without exposing any original content beyond this editor.
        rows.forEach((row, i) => {
            if (skipInput(row).checked) { return; }
            const a = num(startInput(row)), b = num(endInput(row));
            if (a === null || b === null || b <= a) { return; }
            const x0 = clamp(a / dur * w, 0, w), x1 = clamp(b / dur * w, 0, w);
            ctx.fillStyle = i === chosen ? 'rgba(224,57,124,.28)' : 'rgba(70,63,100,.12)';
            ctx.fillRect(x0, 0, x1 - x0, h);
        });
        const row = rows[chosen];
        if (row && !skipInput(row).checked) {
            [['start', startInput(row)], ['end', endInput(row)]].forEach(pair => {
                const val = num(pair[1]); if (val === null) { return; }
                const x = clamp(val / dur * w, 0, w);
                ctx.strokeStyle = pair[0] === 'start' ? c.active : '#287e80';
                ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, h); ctx.stroke();
                ctx.fillStyle = ctx.strokeStyle; ctx.fillRect(x - 5, 2, 10, 11);
            });
        }
        const pos = clamp((audio.currentTime * 1000) / dur * w, 0, w);
        ctx.strokeStyle = c.active; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(pos, 0); ctx.lineTo(pos, h); ctx.stroke();
    }
    function update() {
        if (readout) { readout.textContent = fmt(audio.currentTime); }
        if (total) { total.textContent = fmt(audio.duration); }
        const ms = Math.round(audio.currentTime * 1000);
        rows.forEach(row => {
            const a = num(startInput(row)), b = num(endInput(row));
            row.classList.toggle('is-playing', !skipInput(row).checked && a !== null && b !== null &&
                ms >= a && ms < b);
        });
        if (auditionTo !== null && ms >= auditionTo) {
            audio.pause(); auditionTo = null;
        }
        draw();
    }
    function scheduleUpdate() {
        if (clockRaf) { return; }
        clockRaf = requestAnimationFrame(() => { clockRaf = 0; update(); });
    }
    async function loadEnvelope() {
        if (!canvas || !ctx || typeof window.AudioContext !== 'function' &&
                typeof window.webkitAudioContext !== 'function') {
            setStatus(tr('cfr_i18n_audio_js_02'), false);
            return;
        }
        let sound = null;
        try {
            // Reading the audio is authorized by the existing authenticated CRM preview endpoint.
            // Limit memory pressure from long studio recordings; never send media to an external API.
            const response = await fetch(audio.currentSrc || audio.src, {credentials:'same-origin', cache:'no-store'});
            if (!response.ok || Number(response.headers.get('content-length') || 0) > 28 * 1024 * 1024) {
                throw new Error(tr('cfr_i18n_audio_js_03'));
            }
            const bytes = await response.arrayBuffer();
            if (bytes.byteLength > 28 * 1024 * 1024) { throw new Error(tr('cfr_i18n_audio_js_04')); }
            const Ctor = window.AudioContext || window.webkitAudioContext;
            sound = new Ctor();
            const pcm = await sound.decodeAudioData(bytes);
            const bins = 1300; const channels = pcm.numberOfChannels;
            envelope = new Array(bins);
            let highest = 1e-9;
            for (let i = 0; i < bins; i++) {
                const a = Math.floor(i / bins * pcm.length);
                const b = Math.min(pcm.length, Math.floor((i + 1) / bins * pcm.length));
                const stride = Math.max(1, Math.floor((b - a) / 85));
                let sum = 0, samples = 0;
                for (let p = a; p < b; p += stride) {
                    const channel = pcm.getChannelData(samples % channels);
                    sum += Math.abs(channel[p]); samples++;
                }
                const v = samples ? sum / samples : 0;
                highest = Math.max(highest, v);
                envelope[i] = v;
            }
            envelope = envelope.map(v => v / highest);
            setStatus(tr('cfr_i18n_audio_js_05'), false);
            draw();
        } catch (e) {
            envelope = null;
            setStatus(tr('cfr_i18n_audio_js_06'), false);
            draw();
        } finally {
            if (sound) { try { await sound.close(); } catch (ignored) {} }
        }
    }
    function candidateBounds(row) {
        const dur = durationMs();
        const a = num(startInput(row)), b = num(endInput(row));
        return {a, b, dur};
    }
    function mark(row, side, ms) {
        const input = side === 'start' ? startInput(row) : endInput(row);
        if (input.disabled || !Number.isFinite(ms)) { return; }
        ms = clamp(Math.round(ms / 10) * 10, 0, durationMs());
        const other = side === 'start' ? num(endInput(row)) : num(startInput(row));
        if (other !== null && ((side === 'start' && ms >= other) ||
                (side === 'end' && ms <= other))) {
            setStatus(tr('cfr_i18n_audio_js_07'), true);
            return;
        }
        skipInput(row).checked = false;
        setTime(input, ms);
        scheduleUpdate();
    }
    function xToTime(evt) {
        const rect = canvas.getBoundingClientRect();
        return clamp((evt.clientX - rect.left) / Math.max(1, rect.width), 0, 1) * durationMs();
    }
    if (canvas) {
        canvas.addEventListener('pointerdown', evt => {
            if (!durationMs()) { return; }
            const row = rows[chosen];
            const rect = canvas.getBoundingClientRect();
            const x = evt.clientX - rect.left;
            drag = null;
            if (row && !skipInput(row).checked) {
                const handles = ['start', 'end'].map(side => ({side,
                    ms: num(side === 'start' ? startInput(row) : endInput(row))})).filter(h => h.ms !== null);
                handles.sort((a,b) => Math.abs(a.ms / durationMs() * rect.width - x) -
                    Math.abs(b.ms / durationMs() * rect.width - x));
                if (handles.length && Math.abs(handles[0].ms / durationMs() * rect.width - x) <= 14 &&
                        !(handles[0].side === 'start' ? startInput(row).disabled : endInput(row).disabled)) {
                    drag = {side: handles[0].side, row};
                    canvas.setPointerCapture(evt.pointerId);
                }
            }
            if (!drag) { audio.currentTime = xToTime(evt) / 1000; scheduleUpdate(); }
        });
        canvas.addEventListener('pointermove', evt => {
            if (!drag) { return; }
            mark(drag.row, drag.side, xToTime(evt));
        });
        canvas.addEventListener('pointerup', () => { drag = null; });
        canvas.addEventListener('pointercancel', () => { drag = null; });
    }
    // Assisted pre-alignment: weighted allocation and rough pauses, NOT speech recognition.
    function propose() {
        const dur = durationMs();
        const active = rows.filter(r => !skipInput(r).checked && !startInput(r).disabled);
        const allWords = active.reduce((sum, r) => sum + (r.querySelector('.cfr-d29-cue-text')?.textContent || '')
            .trim().split(/\s+/u).length, 0);
        if (!dur || !active.length) { setStatus(tr('cfr_i18n_audio_js_08'), true); return; }
        if (dur < Math.max(active.length * 210, allWords * 105)) {
            setStatus(tr('cfr_i18n_audio_js_09'), true);
            return;
        }
        if (!window.confirm(tr('cfr_i18n_audio_js_10') +
                tr('cfr_i18n_audio_js_11'))) { return; }
        const weights = active.map(r => Math.max(2, (r.querySelector('.cfr-d29-cue-text')?.textContent || '')
            .trim().split(/\s+/u).length));
        const sum = weights.reduce((a,b) => a + b, 0);
        const edges = [0];
        let progressed = 0;
        for (let i = 0; i < active.length - 1; i++) {
            progressed += weights[i];
            const ideal = progressed / sum * dur;
            const margin = Math.min(2600, dur / active.length * .42);
            let best = ideal, score = Number.POSITIVE_INFINITY;
            if (envelope && envelope.length > 0) {
                for (let j = 0; j < envelope.length; j++) {
                    const pos = j / envelope.length * dur;
                    if (Math.abs(pos - ideal) > margin) { continue; }
                    const metric = envelope[j] + .42 * Math.abs(pos - ideal) / Math.max(margin, 1);
                    if (metric < score) { score = metric; best = pos; }
                }
            }
            edges.push(clamp(best, edges[edges.length - 1] + MIN_MS, dur - (active.length - i - 1) * MIN_MS));
        }
        edges.push(dur);
        let count = 0;
        active.forEach((row, i) => {
            if (startInput(row).value.trim() !== '' || endInput(row).value.trim() !== '') { return; }
            const a = edges[i], b = edges[i+1];
            if (b - a >= MIN_MS) {
                setTime(startInput(row), a); setTime(endInput(row), b); count++;
            }
        });
        setStatus(count + tr('cfr_i18n_audio_js_12'), false);
        choose(chosen, false); update();
    }
    rows.forEach((row, i) => {
        row.addEventListener('focusin', () => choose(i, false));
        row.querySelector('.cfr-d29-cue-play')?.addEventListener('click', () => {
            choose(i, true);
            const a = num(startInput(row)), b = num(endInput(row));
            if (a !== null && b !== null && b > a) {
                audio.currentTime = a / 1000; auditionTo = b; void audio.play().catch(() => {});
            }
        });
        row.querySelectorAll('[data-mark]').forEach(button => button.addEventListener('click', () => {
            choose(i, false); mark(row, button.dataset.mark, audio.currentTime * 1000);
        }));
        row.querySelectorAll('[data-time]').forEach(input => input.addEventListener('input', scheduleUpdate));
        skipInput(row).addEventListener('change', () => {
            if (skipInput(row).checked) { startInput(row).value = ''; endInput(row).value = ''; }
            choose(i, false); scheduleUpdate();
        });
    });
    guess?.addEventListener('click', propose);
    audition?.addEventListener('click', () => {
        const row = rows[chosen]; if (!row) { return; }
        const a = num(startInput(row)), b = num(endInput(row));
        if (a === null || b === null || b <= a) { setStatus(tr('cfr_i18n_audio_js_13'), true); return; }
        audio.currentTime = a / 1000; auditionTo = b;
        void audio.play().catch(() => setStatus(tr('cfr_i18n_audio_js_14'), true));
    });
    speed?.addEventListener('change', () => { audio.playbackRate = Number(speed.value) || 1; });
    audio.addEventListener('loadedmetadata', () => { update(); void loadEnvelope(); });
    audio.addEventListener('timeupdate', scheduleUpdate);
    audio.addEventListener('seeked', scheduleUpdate);
    audio.addEventListener('ended', () => { auditionTo = null; scheduleUpdate(); });
    window.addEventListener('resize', scheduleUpdate);
    form.addEventListener('submit', event => {
        const cues = []; let previous = 0, timed = 0, invalid = '';
        const max = durationMs();
        rows.forEach(row => {
            const skip = skipInput(row).checked;
            const a = skip ? null : num(startInput(row));
            const b = skip ? null : num(endInput(row));
            if (!skip && (a !== null || b !== null)) {
                if (!Number.isInteger(a) || !Number.isInteger(b) || a < previous || a < 0 || b <= a ||
                    b > 7200000 || (max && b > max + 500)) {
                    invalid = tr('cfr_i18n_audio_js_15');
                } else { previous = b; timed++; }
            }
            cues.push({id: row.dataset.cueId, skip, startms:a, endms:b});
        });
        if (invalid) {
            event.preventDefault();
            if (summary) { summary.textContent = invalid; summary.setAttribute('role','alert'); }
            return;
        }
        payload.value = JSON.stringify(cues);
        if (summary) { summary.textContent = timed + tr('cfr_i18n_audio_js_16'); }
    });
    choose(chosen, false); update();
    if (audio.readyState >= 1) { void loadEnvelope(); }
}());
