(function (MemoryGame, EventDispatcher, $) {
  /**
   * @private
   * @constant {number} WCAG_MIN_CONTRAST_AA_LARGE Minimum contrast ratio.
   * @see https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html
   */
  const WCAG_MIN_CONTRAST_AA_LARGE = 3;

  // CAMPUSFR_MEMORY_SHARED_AUDIO_BEGIN
  // One persistent HTMLAudioElement for Memory Game cards in
  // this H5P frame. No card media is loaded before first user interaction.
  let campusfrSharedCardAudio = null;
  const getCampusfrSharedCardAudio = () => {
    if (campusfrSharedCardAudio) {
      return campusfrSharedCardAudio;
    }
    const element = document.createElement('audio');
    element.preload = 'none';
    element.controls = false;
    element.setAttribute('playsinline', '');
    element.setAttribute('aria-hidden', 'true');
    element.tabIndex = -1;
    element.style.cssText = 'position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;';
    if (document.body) {
      document.body.appendChild(element);
    }
    const manager = {
      audio: element,
      owner: null,
      onPlaying: null,
      onStopped: null,
      playing: false,
      generation: 0,
    };
    // Diagnostics disabled in consolidated CampusFR build.
    manager.log = () => {};
    const signalStopped = () => {
      if (manager.playing) {
        manager.playing = false;
        if (manager.onStopped) { manager.onStopped(); }
      }
    };
    manager.signalStopped = signalStopped;
    element.addEventListener('playing', () => {
      manager.log('playing');
      if (manager.owner && !manager.playing) {
        manager.playing = true;
        if (manager.onPlaying) { manager.onPlaying(); }
      }
    });
    element.addEventListener('pause', () => {
      if (element.paused) { signalStopped(); }
    });
    element.addEventListener('ended', () => {
      manager.log('ended');
      signalStopped();
    });
    element.addEventListener('error', () => {
      manager.log('error');
      signalStopped();
    });
    campusfrSharedCardAudio = manager;
    return manager;
  };
  // CAMPUSFR_MEMORY_SHARED_AUDIO_END

  /**
   * Controls all the operations for each card.
   *
   * @class H5P.MemoryGame.Card
   * @extends H5P.EventDispatcher
   * @param {Object} image
   * @param {number} contentId
   * @param {number} cardsTotal Number of cards in total.
   * @param {string} alt
   * @param {Object} l10n Localization
   * @param {string} [description]
   * @param {Object} [styles]
   * @param {string} id Unique identifier for card including original+match info.
   */
  MemoryGame.Card = function (image, contentId, cardsTotal, alt, l10n, description, styles, audio, id) {
    /** @alias H5P.MemoryGame.Card# */
    const self = this;

    this.id = id;

    // Keep track of tabbable state
    self.isTabbable = false;

    // Initialize event inheritance
    EventDispatcher.call(self);

    let path;
    let $card;
    let $wrapper;
    let $image;
    let removedState;
    let flippedState;
    let audioPlayer;
    let supportedAudioFiles = [];

    const handlePlaying = () => {
      if ($card) {
        $card.addClass('h5p-memory-audio-playing');
        self.trigger('audioplay');
      }
    };
    const handleStopping = () => {
      if ($card) {
        $card.removeClass('h5p-memory-audio-playing');
        self.trigger('audiostop');
      }
    };

    const startCardAudio = () => {
      if (!audioPlayer) { return; }
      const shared = getCampusfrSharedCardAudio();
      const element = shared.audio;
      const switched = shared.owner !== self;
      if (switched) {
        // Stop the previous card without transferring its events to this one.
        element.pause();
        shared.signalStopped();
        shared.owner = self;
        shared.onPlaying = handlePlaying;
        shared.onStopped = handleStopping;
        shared.playing = false;
        shared.generation++;
        // Replace sources synchronously within the user's gesture.
        while (element.firstChild) { element.removeChild(element.firstChild); }
        element.removeAttribute('src');
        supportedAudioFiles.forEach((file) => {
          const source = document.createElement('source');
          source.src = H5P.getPath(file.path, contentId);
          source.type = file.mime;
          element.appendChild(source);
        });
        element.load();
        shared.log('switch');
      }
      else if (element.error) {
        // A retry is only attempted after a new, explicit user action.
        element.load();
        shared.log('retry');
      }
      const generation = ++shared.generation;
      try {
        const promise = element.play();
        shared.log('play-call');
        if (promise && typeof promise.catch === 'function') {
          promise.catch((error) => {
            if (shared.owner !== self || shared.generation !== generation) { return; }
            shared.log('play-rejected', error && error.name);
            shared.signalStopped();
          });
        }
      }
      catch (error) {
        shared.log('play-throw', error && error.name);
        shared.signalStopped();
      }
    };

    /**
     * Process HTML escaped string for use as attribute value,
     * e.g. for alt text or title attributes.
     *
     * @param {string} value
     * @return {string} WARNING! Do NOT use for innerHTML.
     */
    const massageAttributeOutput = (value = 'Missing description') => {
      const dparser = new DOMParser().parseFromString(value, 'text/html');
      const div = document.createElement('div');
      div.innerHTML = dparser.documentElement.textContent;

      return div.textContent || div.innerText;
    };

    self.buildDOM = () => {
      $wrapper = $('<li class="h5p-memory-wrap" tabindex="-1" role="button"><div class="h5p-memory-card">'
                  + `<div class="h5p-front"${styles && styles.front ? styles.front : ''}>${styles && styles.backImage ? '' : '<span></span>'}</div>`
                  + `<div class="h5p-back"${styles && styles.back ? styles.back : ''}>${
                    path ? `<img src="${path}" alt=""/>${audioPlayer ? '<div class="h5p-memory-audio-button"></div>' : ''}` : '<i class="h5p-memory-audio-instead-of-image">'
                  }</div>`
                + '</div></li>');

      $wrapper.on('keydown', (event) => {
        switch (event.code) {
          case 'Enter':
          case 'Space':
            self.flip();
            event.preventDefault();
            return;
          case 'ArrowRight':
            // Move focus forward
            self.trigger('next');
            event.preventDefault();
            return;
          case 'ArrowDown':
            // Move focus down
            self.trigger('down');
            event.preventDefault();
            return;
          case 'ArrowLeft':
            // Move focus back
            self.trigger('prev');
            event.preventDefault();
            return;
          case 'ArrowUp': // Up
            // Move focus up
            self.trigger('up');
            event.preventDefault();
            return;
          case 'End':
            // Move to last card
            self.trigger('last');
            event.preventDefault();
            return;
          case 'Home':
            // Move to first card
            self.trigger('first');
            event.preventDefault();
            break;
          default:
            break;
        }
      });

      $image = $wrapper.find('img');

      $card = $wrapper.children('.h5p-memory-card')
        .children('.h5p-front')
        .click((event) => {
          event.stopPropagation();
          self.flip();
        })
        .end();

      if (audioPlayer) {
        $card.children('.h5p-back')
          .click(() => {
            if ($card.hasClass('h5p-memory-audio-playing')) {
              self.stopAudio();
            }
            else {
              startCardAudio();
            }
          });
      }
    };

    // alt = alt || 'Missing description'; // Default for old games
    alt = massageAttributeOutput(alt);

    if (image && image.path) {
      path = H5P.getPath(image.path, contentId);
    }

    if (audio) {
      // Keep file lists per card, but reuse a single media element. Unlike the
      // upstream library this creates no sources and requests no audio here.
      const shared = getCampusfrSharedCardAudio();
      supportedAudioFiles = Array.isArray(audio) ? audio.filter((file) =>
        file && file.path && shared.audio.canPlayType(file.mime || '')
      ) : [];
      audioPlayer = supportedAudioFiles.length ? shared.audio : null;
    }

    this.buildDOM();

    /**
     * Get id of the card.
     * @returns {string} The id of the card. (originalIndex-sideNumber)
     */
    this.getId = () => self.id;

    /**
     * Update the cards label to make it accessible to users with a readspeaker
     *
     * @param {boolean} isMatched The card has been matched
     * @param {boolean} announce Announce the current state of the card
     * @param {boolean} reset Go back to the default label
     */
    self.updateLabel = function (isMatched, announce, reset) {
      // Determine new label from input params
      const imageAlt = alt ? ` ${alt}` : '';

      let label = reset
        ? l10n.cardUnturned
        : `${l10n.cardTurned}${imageAlt}`;

      if (isMatched) {
        label = `${l10n.cardMatched} ${label}`;
      }

      // Update the card's label
      $wrapper.attr('aria-label', `${l10n.cardPrefix
        .replace('%num', $wrapper.index() + 1)
        .replace('%total', cardsTotal)} ${label}`);

      // Update disabled property
      $wrapper.attr('aria-disabled', reset ? null : 'true');

      // Announce the label change
      if (announce) {
        $wrapper.blur().focus(); // Announce card label
      }
    };

    /**
     * Flip card.
     *
     * Win 11 screen reader announces image's alt tag even though it never gets
     * focus and button provides aria-label. Therefore alt tag is only set when
     * card is turned.
     * @param {object} [params] Parameters.
     * @param {boolean} [params.restoring] True if card is being restored from a saved state.
     */
    self.flip = function (params = {}) {
      if (flippedState) {
        $wrapper.blur().focus(); // Announce card label again
        return;
      }

      $card.addClass('h5p-flipped');
      $image.attr('alt', alt);
      flippedState = true;

      if (audioPlayer && !params.restoring) {
        startCardAudio();
      }

      this.trigger('flip', { restoring: params.restoring });
    };

    /**
     * Flip card back.
     */
    self.flipBack = function () {
      self.stopAudio();
      self.updateLabel(null, null, true); // Reset card label
      $card.removeClass('h5p-flipped');
      $image.attr('alt', '');
      flippedState = false;
    };

    /**
     * Remove.
     */
    self.remove = function () {
      $card.addClass('h5p-matched');
      removedState = true;
    };

    /**
     * Reset card to natural state
     */
    self.reset = function () {
      self.stopAudio();
      self.updateLabel(null, null, true); // Reset card label
      flippedState = false;
      removedState = false;
      $card[0].classList.remove('h5p-flipped', 'h5p-matched');
    };

    /**
     * Get card description.
     *
     * @returns {string}
     */
    self.getDescription = function () {
      return description;
    };

    /**
     * Get image clone.
     *
     * @returns {H5P.jQuery}
     */
    self.getImage = function () {
      return $card.find('img').clone();
    };

    /**
     * Append card to the given container.
     *
     * @param {H5P.jQuery} $container
     */
    self.appendTo = function ($container) {
      $wrapper.appendTo($container);

      $wrapper.attr(
        'aria-label',
        `${l10n.cardPrefix
          .replace('%num', $wrapper.index() + 1)
          .replace('%total', cardsTotal)} ${l10n.cardUnturned}`,
      );
    };

    /**
     * Re-append to parent container.
     */
    self.reAppend = function () {
      const parent = $wrapper[0].parentElement;
      parent.appendChild($wrapper[0]);
    };

    /**
     * Make the card accessible when tabbing
     */
    self.makeTabbable = function () {
      if ($wrapper) {
        $wrapper.attr('tabindex', '0');
        this.isTabbable = true;
      }
    };

    /**
     * Prevent tabbing to the card
     */
    self.makeUntabbable = function () {
      if ($wrapper) {
        $wrapper.attr('tabindex', '-1');
        this.isTabbable = false;
      }
    };

    /**
     * Make card tabbable and move focus to it
     */
    self.setFocus = function () {
      self.makeTabbable();
      if ($wrapper) {
        $wrapper.focus();
      }
    };

    /**
     * Check if the card has been removed from the game, i.e. if has
     * been matched.
     */
    this.isRemoved = () => removedState ?? false;

    /**
     * Determine whether card is flipped or not.
     * @returns {boolean} True if card is flipped, else false.
     */
    this.isFlipped = () => flippedState ?? false;

    /**
     * Stop any audio track that might be playing.
     */
    self.stopAudio = function () {
      if (!audioPlayer) { return; }
      const shared = getCampusfrSharedCardAudio();
      if (shared.owner !== self) { return; }
      shared.audio.pause();
      try { shared.audio.currentTime = 0; } catch (error) { /* No metadata yet. */ }
      shared.signalStopped();
      shared.owner = null;
      shared.onPlaying = null;
      shared.onStopped = null;
      shared.generation++;
      shared.log('stop');
    };
  };

  // Extends the event dispatcher
  MemoryGame.Card.prototype = Object.create(EventDispatcher.prototype);
  MemoryGame.Card.prototype.constructor = MemoryGame.Card;

  /**
   * Check to see if the given object corresponds with the semantics for
   * a memory game card.
   *
   * @param {object} params
   * @returns {boolean}
   */
  MemoryGame.Card.isValid = function (params) {
    return !!(params?.image?.path || params?.audio);
  };

  /**
   * Checks to see if the card parameters should create cards with different
   * images.
   *
   * @param {object} params
   * @returns {boolean}
   */
  MemoryGame.Card.hasTwoImages = function (params) {
    return !!(params?.match?.path || params?.matchAudio);
  };

  /**
   * Determines the theme for how the cards should look
   *
   * @param {string} backImage The url of the image to put on the back of the cards
   */
  MemoryGame.Card.determineStyles = function (backImage) {
    const styles = {
      front: '',
      back: '',
      backImage: !!backImage,
    };

    // Add back image for card
    if (backImage) {
      const backgroundImage = `background-image:url('${backImage}')`;

      styles.front = ` style="${backgroundImage}"`;
      styles.back = ` style="${backgroundImage}"`;
    }

    return styles;
  };

}(H5P.MemoryGame, H5P.EventDispatcher, H5P.jQuery));
