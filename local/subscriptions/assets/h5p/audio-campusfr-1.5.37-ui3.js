var H5P = H5P || {};

/**
 * H5P audio module
 *
 * @external {jQuery} $ H5P.jQuery
 */
H5P.Audio = (function ($) {
  /**
  * @param {Object} params Options for this library.
  * @param {Number} id Content identifier.
  * @param {Object} extras Extras.
  * @returns {undefined}
  */
  function C(params, id, extras) {
    H5P.EventDispatcher.call(this);

    this.contentId = id;
    this.params = params;
    this.extras = extras;
    this.toggleButtonEnabled = true;

    // Retrieve previous state
    if (extras && extras.previousState !== undefined) {
      this.oldTime = extras.previousState.currentTime;
    }

    this.params = $.extend({}, {
      playerMode: 'minimalistic',
      fitToWrapper: false,
      controls: true,
      autoplay: false,
      audioNotSupported: "Your browser does not support this audio",
      playAudio: "Play audio",
      pauseAudio: "Pause audio",
      propagateButtonClickEvents: true
    }, params);

    // Required if e.g. used in CoursePresentation as area to click on
    if (this.params.playerMode === 'transparent') {
      this.params.fitToWrapper = true;
    }

    this.on('resize', this.resize, this);
  }

  C.prototype = Object.create(H5P.EventDispatcher.prototype);
  C.prototype.constructor = C;

  /**
   * Adds a minimalistic audio player with only "play" and "pause" functionality.
   *
   * @param {jQuery} $container Container for the player.
   * @param {boolean} transparentMode true: the player is only visible when hovering over it; false: player's UI always visible
   */
  C.prototype.addMinimalAudioPlayer = function ($container, transparentMode) {
    var INNER_CONTAINER = 'h5p-audio-inner';
    var AUDIO_BUTTON = 'h5p-audio-minimal-button';
    var PLAY_BUTTON = 'h5p-audio-minimal-play';
    var PLAY_BUTTON_PAUSED = 'h5p-audio-minimal-play-paused';
    var PAUSE_BUTTON = 'h5p-audio-minimal-pause';

    var self = this;
    this.$container = $container;

    self.$inner = $('<div/>', {
      'class': INNER_CONTAINER + (transparentMode ? ' h5p-audio-transparent' : '')
    }).appendTo($container);

    var audioButton = $('<button/>', {
      'class': AUDIO_BUTTON + " " + PLAY_BUTTON,
      'aria-label': this.params.playAudio
    }).appendTo(self.$inner)
      .click( function (event) {
        if (!self.params.propagateButtonClickEvents){
          event.stopPropagation();
        }

        if (!self.isEnabledToggleButton()) {
          return;
        }

        // Prevent ARIA from playing over audio on click
        this.setAttribute('aria-hidden', 'true');

        if (self.audio.paused) {
          self.play();
        }
        else {
          self.pause();
        }
      })
      .on('focusout', function () {
        // Restore ARIA, required when playing longer audio and tabbing out and back in
        this.setAttribute('aria-hidden', 'false');
      });

    // Fit to wrapper
    if (this.params.fitToWrapper) {
      audioButton.css({
        'width': '100%',
        'height': '100%'
      });
      audioButton.addClass('h5p-audio-fit-wrapper');
    }

    //Event listeners that change the look of the player depending on events.
    self.audio.addEventListener('ended', function () {
      audioButton
        .attr('aria-hidden', false)
        .attr('aria-label', self.params.playAudio)
        .removeClass(PAUSE_BUTTON)
        .removeClass(PLAY_BUTTON_PAUSED)
        .addClass(PLAY_BUTTON);
    });

    self.audio.addEventListener('play', function () {
      audioButton
        .attr('aria-label', self.params.pauseAudio)
        .removeClass(PLAY_BUTTON)
        .removeClass(PLAY_BUTTON_PAUSED)
        .addClass(PAUSE_BUTTON);
    });

    self.audio.addEventListener('pause', function () {
      // Don't override if initial look is set
      if (!audioButton.hasClass(PLAY_BUTTON)) {
        audioButton
          .attr('aria-hidden', false)
          .attr('aria-label', self.params.playAudio)
          .removeClass(PAUSE_BUTTON)
          .addClass(PLAY_BUTTON_PAUSED);
      }
    });

    H5P.Audio.MINIMAL_BUTTON = AUDIO_BUTTON + " " + PLAY_BUTTON;
    H5P.Audio.MINIMAL_BUTTON_PAUSED = AUDIO_BUTTON + " " + PLAY_BUTTON_PAUSED;

    this.$audioButton = audioButton;
    // Scale icon to container
    self.resize();
  };

  /**
   * Resizes the audio player icon when the wrapper is resized.
   */
  C.prototype.resize = function () {
    // Find the smallest value of height and width, and use it to choose the font size.
    if (this.params.fitToWrapper && this.$container && this.$container.width()) {
      var w = this.$container.width();
      var h = this.$container.height();
      if (w < h) {
        this.$audioButton.css({'font-size': (w / 2 - 4) + 'px'});
      }
      else {
        this.$audioButton.css({'font-size': (h / 2 - 4) + 'px'});
      }
    }
  };

  return C;
})(H5P.jQuery);

/**
 * Wipe out the content of the wrapper and put our HTML in it.
 *
 * @param {jQuery} $wrapper Our poor container.
 */
/**
 * CAMPUSFR_SHARED_AUDIO
 * Defers creation until interaction, prepares metadata
 * on touchstart, and start playback directly from touchend/click. Keep at most
 * one native audio element active per H5P document.
 * Minimalistic, transparent and autoplay modes keep the upstream behaviour.
 */
/**
 * CampusFR: iPadOS one-tap audio initialization.
 * Defer creating <audio> until pointer/touch interaction; start loading on
 * touchstart and call play() synchronously from touchend/click user activation.
 * The placeholder covers a normally-sized native control, rather than
 * hiding it as a 1px element. There is never more than one active audio.
 * Labels use translations distributed with this H5P.Audio library.
 */
H5P.Audio.CAMPUSFR_LABELS = {"en":["Play audio","Pause audio"],"ar":["تشغيل الصوت","إيقاف الصوت مؤقتًا"],"af":["Speel klank","Pouseer klank"],"bg":["Възпроизвеждане на звук","Пауза"],"bs":["Pusti audio","Pauziraj audio"],"cs":["Přehrát zvuk","Pozastavit zvuk"],"de":["Audio abspielen","Audio pausieren"],"el":["Αναπαραγωγή ήχου","Παύση ήχου"],"es-mx":["Reproducir audio","Pausar audio"],"es":["Reproducir audio","Pausar audio"],"et":["Esita heli","Peata heli"],"eu":["Erreproduzitu audioa","Pausatu audioa"],"ca":["Reprodueix l’àudio","Posa en pausa l’àudio"],"da":["Afspil lyd","Sæt lyden på pause"],"fa":["پخش صدا","مکث صدا"],"fi":["Toista audio","Keskeytä audio"],"fr":["Écouter l’audio","Mettre en pause"],"gl":["Reproducir audio","Pausar audio"],"he":["נגן שמע","השהיית שמע"],"hu":["Hang lejátszása","Szünet"],"ja":["音声を再生","一時停止"],"it":["Avvia l'audio","Metti in pausa l'audio"],"ka":["აუდიოს ჩართვა","პაუზა"],"km":["លេងសម្លេង","ផ្អាកសម្លេង"],"ko":["오디오를 재생하세요.","오디오 일시 멈춤"],"lt":["Leisti garsą","Pristabdyti garsą"],"mn":["Аудиог тоглуулах","Аудиог зогсоох"],"nl":["Afspelen audio","Pauzeren audio"],"nn":["Spel av","Set på pause"],"lv":["Atskaņot audio","Pauzēt audio"],"nb":["Spill av","Sett på pause"],"pl":["Odtwórz dźwięk","Wstrzymaj odtwarzanie"],"pt-br":["Reproduzir áudio","Pausar o áudio"],"pt":["Reproduzir áudio","Fazer pausa"],"ro":["Redă audio","Pauză audio"],"ru":["Воспроизвести аудио","Пауза"],"sl":["Predvajaj zvok","Zaustavi predvajanje"],"sma":["Play audio","Pause audio"],"sme":["Play audio","Pause audio"],"sr":["Репродукујте","Паузирај"],"sv":["Spela ljud","Pausa ljud"],"sw":["Cheza sauti","Sitisha sauti"],"th":["เล่นเสียง","หยุดเล่น"],"uk":["Відтворити аудіо","Пауза"],"vi":["Phát âm thanh","Dừng âm thanh"],"zh-hans":["播放音频","暂停音频"],"zh-hant":["播放音訊","暫停音訊"],"zh":["播放音频","暂停音频"],"smj":["Play audio","Pause audio"],"tr":["Ses dosyasını başlat","Ses dosyasını duraklat"]};
H5P.Audio._campusfrGetLabels = function (params) {
  var candidates = [
    document.documentElement.getAttribute('lang'),
    window.H5PIntegration && window.H5PIntegration.language,
    navigator.language
  ];
  var chosen = 'en';
  var all = H5P.Audio.CAMPUSFR_LABELS;
  for (var i = 0; i < candidates.length; i++) {
    var value = String(candidates[i] || '').trim().toLowerCase().replace(/_/g, '-');
    if (all[value]) { chosen = value; break; }
    if (all[value.split('-')[0]]) { chosen = value.split('-')[0]; break; }
    // French Canadian etc. that have no matching regional translation.
  }
  // Respect deliberate author overrides, translate only generic English defaults.
  var play = params.playAudio;
  var pause = params.pauseAudio;
  if (!play || play === 'Play audio') { play = all[chosen][0]; }
  if (!pause || pause === 'Pause audio') { pause = all[chosen][1]; }
  H5P.Audio._campusfrLastLanguage = chosen;
  return {play: play, pause: pause, language: chosen};
};

/**
 * CampusFR: shared native player, isolated to full-mode H5P.Audio.
 * Instrumentation disabled in this consolidated version.
 */
H5P.Audio._campusfrSharedLog = function () {};

/**
 * CampusFR UI3: custom visual transport controls for the one shared audio.
 * The native <audio> remains in the DOM at normal size, with its own UI
 * visually hidden, to preserve WebKit's existing one-tap playback path.
 */
H5P.Audio._campusfrEnsurePlayerStyles = function () {
  if (document.getElementById('campusfr-h5p-player-ui3-style')) { return; }
  var style = document.createElement('style');
  style.id = 'campusfr-h5p-player-ui3-style';
  style.textContent = [
    '.campusfr-h5p-player-ui3{position:relative;display:none;align-items:center;gap:10px;width:100%;max-width:436px;min-height:58px;padding:8px 12px;box-sizing:border-box;border-radius:999px;background:#281b49;color:#fff;font:inherit;overflow:hidden;}',
    '.campusfr-h5p-player-ui3 button{font:inherit;cursor:pointer;-webkit-appearance:none;appearance:none;touch-action:manipulation;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-main{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;flex:0 0 40px;width:40px;height:40px;padding:0;border:0;border-radius:50%;background:#f50065;color:#fff;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-time{position:relative;z-index:1;flex:0 0 auto;white-space:nowrap;font-size:12px;font-weight:600;font-variant-numeric:tabular-nums;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-seek{position:relative;z-index:1;flex:1 1 auto;min-width:28px;width:100%;height:26px;margin:0;padding:0;cursor:pointer;accent-color:#f50065;-webkit-appearance:none;appearance:none;background:transparent;touch-action:pan-y;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-seek::-webkit-slider-runnable-track{height:5px;border-radius:99px;background:linear-gradient(to right,#f50065 var(--campusfr-played,0%),rgba(255,255,255,.6) var(--campusfr-played,0%));}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-seek::-moz-range-track{height:5px;border-radius:99px;background:rgba(255,255,255,.6);}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-seek::-moz-range-progress{height:5px;border-radius:99px;background:#f50065;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-seek::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:12px;height:12px;margin-top:-3.5px;background:#fff;border:2px solid #f50065;border-radius:50%;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-seek::-moz-range-thumb{width:10px;height:10px;background:#fff;border:2px solid #f50065;border-radius:50%;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-volume{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;flex:0 0 28px;width:28px;height:36px;padding:0;border:0;background:transparent;color:#fff;}',
    '.campusfr-h5p-player-ui3 button:focus-visible,.campusfr-h5p-player-ui3 input:focus-visible{outline:2px solid #fff;outline-offset:2px;}',
    '.campusfr-h5p-player-ui3 .campusfr-h5p-player-seek:disabled{opacity:.5;cursor:default;}',
    '@media(max-width:420px){.campusfr-h5p-player-ui3{gap:7px;padding:8px 10px;}.campusfr-h5p-player-ui3 .campusfr-h5p-player-time{font-size:11px;}}'
  ].join('\n');
  (document.head || document.documentElement).appendChild(style);
};

H5P.Audio._campusfrPlayerExtraLabels = function (language) {
  var all = {
    fr: ['Progression audio', 'Couper le son', 'Activer le son'],
    ru: ['Перемотка аудио', 'Выключить звук', 'Включить звук'],
    en: ['Audio position', 'Mute audio', 'Unmute audio'],
    es: ['Posición del audio', 'Silenciar audio', 'Activar sonido'],
    de: ['Audioposition', 'Ton ausschalten', 'Ton einschalten'],
    it: ['Posizione audio', 'Disattiva audio', 'Attiva audio'],
    pt: ['Posição do áudio', 'Silenciar áudio', 'Ativar som'],
    uk: ['Позиція аудіо', 'Вимкнути звук', 'Увімкнути звук']
  };
  return all[language] || all[(language || '').split('-')[0]] || all.en;
};

H5P.Audio._campusfrTime = function (value) {
  if (!Number.isFinite(value) || value < 0) { return '0:00'; }
  var seconds = Math.floor(value);
  var minutes = Math.floor(seconds / 60);
  var hours = Math.floor(minutes / 60);
  if (hours) {
    return hours + ':' + String(minutes % 60).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
  }
  return minutes + ':' + String(seconds % 60).padStart(2, '0');
};

H5P.Audio._campusfrSvgIcon = function (pathData, size) {
  var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('width', String(size));
  svg.setAttribute('height', String(size));
  svg.setAttribute('focusable', 'false');
  svg.setAttribute('aria-hidden', 'true');
  var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', pathData);
  path.setAttribute('fill', 'currentColor');
  svg.appendChild(path);
  return {svg: svg, path: path};
};


H5P.Audio._campusfrGetShared = function () {
  if (H5P.Audio._campusfrShared) { return H5P.Audio._campusfrShared; }
  var audio = document.createElement('audio');
  audio.className = 'h5p-audio';
  audio.controls = true;
  audio.preload = 'none';
  audio.setAttribute('controlsList', 'nodownload noplaybackrate');
  // Keep a real, full-size browser media element for iPadOS, but hide the grey UI.
  audio.tabIndex = -1;
  audio.setAttribute('aria-hidden', 'true');
  audio.style.width = '100%';
  audio.style.height = '100%';
  audio.style.position = 'absolute';
  audio.style.top = '0';
  audio.style.left = '0';
  audio.style.opacity = '0';
  audio.style.pointerEvents = 'none';
  audio.style.display = 'block';
  var s = {audio: audio, owner: null, generation: 0, nextId: 0};
  H5P.Audio._campusfrShared = s;

  // One listener per event for the lifetime of the shared element.
  ['loadstart', 'loadedmetadata', 'durationchange', 'canplay', 'play', 'pause', 'playing', 'waiting', 'stalled', 'seeked', 'volumechange', 'error', 'ended', 'emptied', 'abort'].forEach(function (type) {
    audio.addEventListener(type, function () {
      var owner = s.owner;
      H5P.Audio._campusfrSharedLog(type, owner,
        type === 'error' && audio.error ? 'errorCode=' + audio.error.code : '');
      if (!owner) { return; }
      if (type === 'loadedmetadata' && owner._campusfrSavedTime > 0) {
        try {
          audio.currentTime = Math.min(owner._campusfrSavedTime,
            Number.isFinite(audio.duration) ? audio.duration : owner._campusfrSavedTime);
          owner._campusfrSavedTime = 0;
        } catch (e) { H5P.Audio._campusfrSharedLog('seek-error', owner, e.name); }
      }
      if (type === 'playing') {
        owner._campusfrSharedButton.style.display = 'none';
        owner._campusfrPlayerUi.style.display = 'flex';
      }
      if (type === 'error') {
        owner._campusfrSharedButton.style.display = 'flex';
        owner._campusfrPlayerUi.style.display = 'none';
        audio.style.display = 'none';
      }
      if (owner._campusfrUpdateUi) { owner._campusfrUpdateUi(); }
      if (type === 'ended') {
        owner._campusfrSavedTime = 0;
        if (typeof owner.endedCallback === 'function') { owner.endedCallback(); }
      }
    });
  });
  var progressLogged = false;
  audio.addEventListener('timeupdate', function () {
    if (!s.owner || audio.currentTime <= 0.1 || audio.paused) { return; }
    if (!progressLogged) {
      H5P.Audio._campusfrSharedLog('progress', s.owner, Math.round(audio.currentTime * 10) / 10);
      progressLogged = true;
    }
    if (s.owner._campusfrUpdateUi) { s.owner._campusfrUpdateUi(); }
  });
  audio.addEventListener('emptied', function () { progressLogged = false; });

  // No on-screen diagnostics in consolidated CampusFR build.
  return s;
};

H5P.Audio.prototype.attachSharedNativeAudio = function ($wrapper) {
  var self = this;
  var s = H5P.Audio._campusfrGetShared();
  var labels = H5P.Audio._campusfrGetLabels(this.params);
  var extraLabels = H5P.Audio._campusfrPlayerExtraLabels(labels.language);
  H5P.Audio._campusfrEnsurePlayerStyles();
  $wrapper.addClass('h5p-audio-wrapper h5p-theme h5p-audio-controls');

  var files = (Array.isArray(this.params.files) ? this.params.files : []).filter(function (f) {
    return f && s.audio.canPlayType(f.mime || '');
  });
  if (!files.length) { this.attachNotSupportedMessage($wrapper); return; }

  this._campusfrLazyNative = true;
  this._campusfrSharedId = ++s.nextId;
  this._campusfrSavedTime = Number(this.oldTime) || 0;
  this._campusfrSharedFiles = files;
  this._campusfrSharedWrapper = $wrapper;
  this.audio = undefined;

  // CampusFR UI2: visual-only button makeover. Keep audio activation unchanged.
  // The markup uses DOM APIs, not HTML interpolation, to preserve safe i18n.
  var button = document.createElement('button');
  button.type = 'button';
  button.className = 'campusfr-h5p-lazy-audio';
  button.style.cssText = [
    'display:flex', 'align-items:center', 'gap:14px',
    'width:100%', 'box-sizing:border-box', 'min-height:58px',
    'padding:8px 18px 8px 12px', 'text-align:left',
    'border:0', 'border-radius:999px',
    'background:#281b49', 'color:#ffffff',
    'font-family:inherit', 'font-size:18px', 'font-weight:600',
    'line-height:1.25', 'cursor:pointer',
    '-webkit-appearance:none', 'appearance:none',
    'touch-action:manipulation', 'box-shadow:none'
  ].join(';') + ';';
  if (!this.params.fitToWrapper) { button.style.maxWidth = '436px'; }

  var icon = document.createElement('span');
  icon.setAttribute('aria-hidden', 'true');
  icon.style.cssText = [
    'display:inline-flex', 'align-items:center', 'justify-content:center',
    'flex:0 0 40px', 'width:40px', 'height:40px',
    'box-sizing:border-box', 'border-radius:50%',
    'background:#f50065'
  ].join(';') + ';';
  var triangle = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  triangle.setAttribute('viewBox', '0 0 24 24');
  triangle.setAttribute('width', '21');
  triangle.setAttribute('height', '21');
  triangle.setAttribute('focusable', 'false');
  triangle.setAttribute('aria-hidden', 'true');
  var trianglePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  trianglePath.setAttribute('d', 'M8 5.5v13l11-6.5z');
  trianglePath.setAttribute('fill', '#ffffff');
  triangle.appendChild(trianglePath);
  icon.appendChild(triangle);

  var label = document.createElement('span');
  label.style.cssText = 'flex:1 1 auto;min-width:0;overflow-wrap:break-word;';
  label.textContent = labels.play;
  button.appendChild(icon);
  button.appendChild(label);
  button.setAttribute('aria-label', labels.play);
  this._campusfrSharedButton = button;

  // Visual-only CampusFR transport controls. The audio's engine, sources and
  // synchronous play() path remain those of the validated shared-player build.
  var player = document.createElement('div');
  player.className = 'campusfr-h5p-player-ui3';
  if (this.params.fitToWrapper) { player.style.maxWidth = 'none'; }

  var toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.className = 'campusfr-h5p-player-main';
  toggle.setAttribute('aria-label', labels.pause);
  var controlIcon = H5P.Audio._campusfrSvgIcon('M7 5h4v14H7zM13 5h4v14h-4z', 21);
  toggle.appendChild(controlIcon.svg);
  player.appendChild(toggle);

  var time = document.createElement('span');
  time.className = 'campusfr-h5p-player-time';
  time.textContent = '0:00 / --:--';
  player.appendChild(time);

  var seek = document.createElement('input');
  seek.type = 'range';
  seek.className = 'campusfr-h5p-player-seek';
  seek.min = '0';
  seek.max = '100';
  seek.step = '0.1';
  seek.value = '0';
  seek.disabled = true;
  seek.setAttribute('aria-label', extraLabels[0]);
  seek.style.setProperty('--campusfr-played', '0%');
  player.appendChild(seek);

  var volume = document.createElement('button');
  volume.type = 'button';
  volume.className = 'campusfr-h5p-player-volume';
  volume.setAttribute('aria-label', extraLabels[1]);
  var soundIcon = H5P.Audio._campusfrSvgIcon('M3 9v6h4l5 4V5L7 9H3zm11.5-1.5a6 6 0 0 1 0 9v-2a4 4 0 0 0 0-5zM17 4a11 11 0 0 1 0 16v-2a9 9 0 0 0 0-12z', 20);
  volume.appendChild(soundIcon.svg);
  player.appendChild(volume);
  this._campusfrPlayerUi = player;

  function syncUi() {
    if (s.owner !== self) { return; }
    var a = s.audio;
    var duration = a.duration;
    var ready = Number.isFinite(duration) && duration > 0;
    var position = Number.isFinite(a.currentTime) && a.currentTime > 0 ? a.currentTime : 0;
    var running = !a.paused && !a.ended;
    controlIcon.path.setAttribute('d', running ?
      'M7 5h4v14H7zM13 5h4v14h-4z' : 'M8 5.5v13l11-6.5z');
    toggle.setAttribute('aria-label', running ? labels.pause : labels.play);
    time.textContent = H5P.Audio._campusfrTime(position) + ' / ' +
      (ready ? H5P.Audio._campusfrTime(duration) : '--:--');
    seek.disabled = !ready;
    if (ready) {
      seek.max = String(duration);
      seek.value = String(Math.min(position, duration));
      seek.style.setProperty('--campusfr-played',
        (Math.max(0, Math.min(100, position / duration * 100))) + '%');
      seek.setAttribute('aria-valuetext', time.textContent);
    }
    volume.setAttribute('aria-label', a.muted ? extraLabels[2] : extraLabels[1]);
    soundIcon.path.setAttribute('d', a.muted ?
      'M3 9v6h4l5 4V5L7 9H3zm12.4 1.2-1.4 1.4 1.4 1.4-1.4 1.4 1.4 1.4 1.4-1.4 1.4 1.4 1.4-1.4-1.4-1.4 1.4-1.4-1.4-1.4-1.4 1.4z' :
      'M3 9v6h4l5 4V5L7 9H3zm11.5-1.5a6 6 0 0 1 0 9v-2a4 4 0 0 0 0-5zM17 4a11 11 0 0 1 0 16v-2a9 9 0 0 0 0-12z');
  }
  this._campusfrUpdateUi = syncUi;

  toggle.addEventListener('click', function (event) {
    event.preventDefault();
    if (!self.toggleButtonEnabled || s.owner !== self) { return; }
    if (!s.audio.paused && !s.audio.ended) {
      s.audio.pause();
    } else {
      // Resume synchronously from the user's gesture, including on iPadOS.
      var result = s.audio.play();
      if (result && typeof result.catch === 'function') {
        result.catch(function () { syncUi(); });
      }
    }
    syncUi();
  });

  seek.addEventListener('input', function () {
    if (s.owner !== self || seek.disabled) { return; }
    var requested = Number(seek.value);
    if (!Number.isFinite(requested)) { return; }
    try { s.audio.currentTime = requested; } catch (e) {}
    syncUi();
  });

  volume.addEventListener('click', function (event) {
    event.preventDefault();
    if (s.owner !== self) { return; }
    s.audio.muted = !s.audio.muted;
    syncUi();
  });

  function detachOwner() {
    var old = s.owner;
    if (!old) { return; }
    var a = s.audio;
    try {
      if (Number.isFinite(a.currentTime) && !a.ended) {
        old._campusfrSavedTime = a.currentTime;
      } else { old._campusfrSavedTime = 0; }
      a.pause();
    } catch (e) {}
    old.audio = undefined;
    old._campusfrSharedButton.style.display = 'flex';
    old._campusfrPlayerUi.style.display = 'none';
    if (a.parentNode) { a.parentNode.removeChild(a); }
    s.owner = null;
    H5P.Audio._campusfrSharedLog('detach', old);
  }

  function startFromGesture() {
    if (!self.toggleButtonEnabled) { return; }
    var a = s.audio;
    var switched = s.owner !== self;
    if (switched) {
      detachOwner();
      s.owner = self;
      self.audio = a;
      a.style.maxWidth = self.params.fitToWrapper ? 'none' : '300px';
      a.style.display = 'block';
      // Reuse the SAME element, but assign this lesson's sources.
      while (a.firstChild) { a.removeChild(a.firstChild); }
      a.removeAttribute('src');
      files.forEach(function (f) {
        var source = document.createElement('source');
        source.src = H5P.getPath(f.path, self.contentId);
        source.type = f.mime;
        a.appendChild(source);
      });
      player.appendChild(a);
      H5P.Audio._campusfrSharedLog('switch', self, 'sourceCount=' + files.length);
    } else if (a.error) {
      H5P.Audio._campusfrSharedLog('retry-error', self, a.error.code);
    }

    // No asynchronous waits between this click and play(). Never create a
    // second HTMLMediaElement when changing exercise.
    var generation = ++s.generation;
    // Render the native element before play(); no asynchronous wait or repaint.
    button.style.display = 'none';
    player.style.display = 'flex';
    a.style.display = 'block';
    try {
      // New <source> children are not selected reliably without load().
      // Keep load() and play() synchronous in this same user gesture.
      if (switched || a.networkState === HTMLMediaElement.NETWORK_EMPTY || a.error) {
        a.load();
        H5P.Audio._campusfrSharedLog('load', self);
      }
      var result = a.play();
      H5P.Audio._campusfrSharedLog('play-call', self);
      // The native controls remain transparent below the CampusFR transport.
      syncUi();
      if (result && typeof result.then === 'function') {
        result.then(function () {
          if (s.owner === self && s.generation === generation) {
            H5P.Audio._campusfrSharedLog('play-ok', self);
          }
        }).catch(function (err) {
          if (s.owner !== self || s.generation !== generation) { return; }
          H5P.Audio._campusfrSharedLog('play-failed', self, err.name);
          button.style.display = 'flex';
          player.style.display = 'none';
          a.style.display = 'none';
        });
      }
    } catch (err) {
      H5P.Audio._campusfrSharedLog('play-throw', self, err.name);
      button.style.display = 'flex';
      player.style.display = 'none';
      a.style.display = 'none';
    }
  }
  // Retain the public H5P.Audio play() path used by some parent H5P content.
  this._campusfrActivate = startFromGesture;
  button.addEventListener('click', function (event) {
    event.preventDefault();
    startFromGesture();
  });
  $wrapper.empty().append(button).append(player);

  // Pause when the active exercise is no longer visible; keep the single
  // element reusable. Ignore stale observers from previous exercise wrappers.
  if (typeof IntersectionObserver !== 'undefined' && !window.H5PEditor) {
    this._campusfrLazyObserver = new IntersectionObserver(function (entries) {
      if (entries[0] && entries[0].intersectionRatio === 0 && s.owner === self) {
        detachOwner();
      }
    }, {root: document.documentElement, threshold: [0]});
    this._campusfrLazyObserver.observe($wrapper.get(0));
  }
};

H5P.Audio.prototype.attach = function ($wrapper) {
  // CAMPUSFR_SHARED_AUDIO: native full-player path only.
  if (this.params.playerMode === 'full' && this.params.controls !== false && !this.params.autoplay) {
    this.attachSharedNativeAudio($wrapper);
    return;
  }
  const self = this;
  $wrapper.addClass('h5p-audio-wrapper h5p-theme');

  // Check if browser supports audio.
  var audio = document.createElement('audio');
  if (audio.canPlayType === undefined) {
    this.attachNotSupportedMessage($wrapper);
    return;
  }

  // Add supported source files.
  if (this.params.files !== undefined && this.params.files instanceof Object) {
    for (var i = 0; i < this.params.files.length; i++) {
      var file = this.params.files[i];

      if (audio.canPlayType(file.mime)) {
        var source = document.createElement('source');
        source.src = H5P.getPath(file.path, this.contentId);
        source.type = file.mime;
        audio.appendChild(source);
      }
    }
  }

  if (!audio.children.length) {
    this.attachNotSupportedMessage($wrapper);
    return;
  }

  if (this.endedCallback !== undefined) {
    audio.addEventListener('ended', this.endedCallback, false);
  }

  audio.className = 'h5p-audio';
  audio.controls = this.params.controls === undefined ? true : this.params.controls;

  // Menu removed, because it's cut off if audio is used as H5P.Question intro
  const controlsList = 'nodownload noplaybackrate';
  audio.setAttribute('controlsList', controlsList);

  audio.preload = 'none';
  audio.style.display = 'block';

  if (this.params.fitToWrapper === undefined || this.params.fitToWrapper) {
    audio.classList.add('h5p-audio--fit-to-wrapper');
    if (!this.isRoot()) {
      // Only set height if this isn't a root
      audio.style.height = '100%';
    }
  }

  this.audio = audio;

  if (this.params.playerMode === 'minimalistic') {
    audio.controls = false;
    this.addMinimalAudioPlayer($wrapper, false);
  }
  else if (this.params.playerMode === 'transparent') {
    audio.controls = false;
    this.addMinimalAudioPlayer($wrapper, true);
  }
  else {
    $wrapper.html(audio);
  }

  if (audio.controls) {
    $wrapper.addClass('h5p-audio-controls');
  }

  // Set time to saved time from previous run
  if (this.oldTime) {
    if (this.$audioButton) {
      this.$audioButton.attr('class', H5P.Audio.MINIMAL_BUTTON_PAUSED);
    }
    this.seekTo(this.oldTime);
  }

  // Avoid autoplaying in authoring tool
  if (window.H5PEditor === undefined) {
    // Keep record of autopauses.
    // I.e: we don't wanna autoplay if the user has excplicitly paused.
    self.autoPaused = true;

    // Set up intersection observer
    new IntersectionObserver(function (entries) {
      const entry = entries[0];

      if (entry.intersectionRatio == 0) {
        if (!self.audio.paused) {
          // Audio element is hidden, pause it
          self.autoPaused = true;
          self.audio.pause();
        }
      }
      else if (self.params.autoplay && self.autoPaused) {
        // Audio element is visible. Autoplay if autoplay is enabled and it was
        // not explicitly paused by a user
        self.autoPaused = false;
        self.play();
      }
    }, {
      root: document.documentElement,
      threshold: [0, 1] // Get events when it is shown and hidden
    }).observe($wrapper.get(0));
  }
};

/**
 * Attaches not supported message.
 *
 * @param {jQuery} $wrapper Our dear container.
 */
H5P.Audio.prototype.attachNotSupportedMessage = function ($wrapper) {
  $wrapper.addClass('h5p-audio-not-supported');
  $wrapper.html(
    '<div class="h5p-audio-inner">' +
      '<div class="h5p-audio-not-supported-icon"><span/></div>' +
      '<span>' + this.params.audioNotSupported + '</span>' +
    '</div>'
  );

  if (this.endedCallback !== undefined) {
    this.endedCallback();
  }
}

/**
 * Stop & reset playback.
 *
 * @returns {undefined}
 */
H5P.Audio.prototype.resetTask = function () {
  this.stop();
  this.seekTo(0);
  if (this.$audioButton) {
    this.$audioButton.attr('class', H5P.Audio.MINIMAL_BUTTON);
  }
};

/**
 * Stop the audio. TODO: Rename to pause?
 *
 * @returns {undefined}
 */
H5P.Audio.prototype.stop = function () {
  if (this.audio !== undefined) {
    this.audio.pause();
  }
};

/**
 * Play
 */
H5P.Audio.prototype.play = function () {
  if (this._campusfrLazyNative && this.audio === undefined) {
    this._campusfrActivate(true);
    return;
  }
  if (this.audio !== undefined) {
    // play() returns a Promise that can fail, e.g. while autoplaying
    this.audio.play().catch((error) => {
      console.warn(error);
    });
  }
};

/**
 * @public
 * Pauses the audio.
 */
H5P.Audio.prototype.pause = function () {
  if (this.audio !== undefined) {
    this.audio.pause();
  }
};

/**
 * @public
 * Seek to audio position.
 *
 * @param {number} seekTo Time to seek to in seconds.
 */
H5P.Audio.prototype.seekTo = function (seekTo) {
  if (this._campusfrLazyNative && this.audio === undefined) {
    this._campusfrSavedTime = seekTo;
  }
  else if (this.audio !== undefined) {
    this.audio.currentTime = seekTo;
  }
};

/**
 * @public
 * Get current state for resetting it later.
 *
 * @returns {object} Current state.
 */
H5P.Audio.prototype.getCurrentState = function () {
  var time = this.audio !== undefined ?
    (this.audio.ended ? 0 : this.audio.currentTime) :
    (this._campusfrLazyNative ? this._campusfrSavedTime : 0);
  if (time > 0) {
    return {currentTime: time};
  }
};

/**
 * @public
 * Disable button.
 * Not using disabled attribute to block button activation, because it will
 * implicitly set tabindex = -1 and confuse ChromeVox navigation. Clicks handled
 * using "pointer-events: none" in CSS.
 */
H5P.Audio.prototype.disableToggleButton = function () {
  this.toggleButtonEnabled = false;
  if (this.$audioButton) {
    this.$audioButton.addClass(H5P.Audio.BUTTON_DISABLED);
  }
};

/**
 * @public
 * Enable button.
 */
H5P.Audio.prototype.enableToggleButton = function () {
  this.toggleButtonEnabled = true;
  if (this.$audioButton) {
    this.$audioButton.removeClass(H5P.Audio.BUTTON_DISABLED);
  }
};

/**
 * @public
 * Check if button is enabled.
 * @return {boolean} True, if button is enabled. Else false.
 */
H5P.Audio.prototype.isEnabledToggleButton = function () {
  return this.toggleButtonEnabled;
};

/** @constant {string} */
H5P.Audio.BUTTON_DISABLED = 'h5p-audio-disabled';
