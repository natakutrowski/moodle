// This file is part of Moodle - http://moodle.org/

/**
 * Interactions shared by custom Commerce Showrooms.
 *
 * @module     local_subscriptions/showroom
 * @copyright  2026 CampusFR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable no-undef */
define([], function() {
    const SELECTOR_CURRENCY = '[data-showroom-currency-ajax]';
    const SELECTOR_FAQ = '[data-showroom-faq]';
    const SELECTOR_STICKY = '[data-showroom-sticky-cta]';
    const SELECTOR_COMPARISON = '#showroom-comparison-title';
    const SELECTOR_VIDEO_DIALOG = '[data-showroom-video-dialog]';
    const SELECTOR_VIDEO_OPEN = '[data-showroom-video-open]';
    const SELECTOR_VIDEO_CLOSE = '[data-showroom-video-close]';
    const SELECTOR_INLINE_VIDEO = '[data-showroom-inline-video]';
    const SELECTOR_ASCENT = '[data-showroom-ascent]';
    const SELECTOR_REVEAL = '[data-showroom-reveal]';
    const SELECTOR_EXERCISE = '[data-showroom-exercise]';
    const SELECTOR_COMPARISON_OFFER = '[data-showroom-comparison-offer]';
    const SELECTOR_DESKTOP_STICKY = '[data-showroom-desktop-sticky]';
    const SELECTOR_HERO = '.commerce-showroom-hero';
    const SELECTOR_FINAL_CTA = '[data-showroom-final-cta]';
    const SELECTOR_DESKTOP_EXPEDITION = '[data-showroom-desktop-expedition]';
    const SELECTOR_FINAL_LEGAL = '[data-showroom-final-legal]';
    const SELECTOR_LEGAL_FIELD = '[data-showroom-legal-field]';
    const SELECTOR_COUNTER = '[data-showroom-counter]';


    const emitTrackingEvent = (name, detail = {}) => {
        const showroom = document.querySelector('[data-showroom]');
        const payload = {
            event: 'campusfr_showroom',
            showroomEvent: name,
            showroom: showroom ? showroom.dataset.showroom || '' : '',
            ...detail,
        };

        document.dispatchEvent(new CustomEvent(
            'campusfr:showroom',
            {detail: payload}
        ));

        if (Array.isArray(window.dataLayer)) {
            window.dataLayer.push(payload);
        }
    };

    const bindTracking = () => {
        document.querySelectorAll('[data-showroom-track]').forEach((target) => {
            if (target.dataset.showroomTrackingBound) {
                return;
            }

            target.dataset.showroomTrackingBound = '1';
            target.addEventListener('click', () => {
                emitTrackingEvent(
                    target.dataset.showroomTrack || 'interaction',
                    {
                        role: target.dataset.showroomTrackRole || '',
                        href: target.getAttribute('href') || '',
                    }
                );
            });
        });
    };


    const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const bindHeroParallax = () => {
        const hero = document.querySelector(SELECTOR_HERO);
        if (!hero || prefersReducedMotion() || hero.dataset.showroomParallaxBound) {
            return;
        }
        hero.dataset.showroomParallaxBound = '1';
        let queued = false;
        const update = () => {
            queued = false;
            const rect = hero.getBoundingClientRect();
            if (rect.bottom <= 0 || rect.top >= window.innerHeight) {
                return;
            }
            const progress = Math.max(0, Math.min(1, -rect.top / Math.max(rect.height, 1)));
            hero.style.setProperty('--showroom-parallax', `${progress * 26}px`);
            hero.style.setProperty('--showroom-parallax-soft', `${progress * 12}px`);
        };
        const requestUpdate = () => {
            if (!queued) {
                queued = true;
                window.requestAnimationFrame(update);
            }
        };
        update();
        window.addEventListener('scroll', requestUpdate, {passive: true});
        window.addEventListener('resize', requestUpdate);
    };

    const animateCounter = (element) => {
        const raw = element.dataset.counterValue || element.textContent || '';
        const digits = raw.replace(/[^0-9]/g, '');
        if (!digits) {
            return;
        }
        const target = Number.parseInt(digits, 10);
        const suffix = raw.trim().endsWith('+') ? '+' : (raw.includes('%') ? '%' : '');
        const separator = raw.includes(' ') || target >= 1000;
        const duration = 2200;
        const started = performance.now();
        const draw = (now) => {
            const ratio = Math.min(1, (now - started) / duration);
            const eased = 1 - Math.pow(1 - ratio, 3);
            const value = Math.round(target * eased);
            element.textContent = (separator ? value.toLocaleString(document.documentElement.lang || 'fr-FR') : String(value)) + suffix;
            if (ratio < 1) {
                window.requestAnimationFrame(draw);
            }
        };
        window.requestAnimationFrame(draw);
    };

    const bindCounters = () => {
        const counters = Array.from(document.querySelectorAll(SELECTOR_COUNTER));
        if (!counters.length) {
            return;
        }
        if (prefersReducedMotion() || typeof IntersectionObserver === 'undefined') {
            counters.forEach((counter) => counter.textContent = counter.dataset.counterValue || counter.textContent);
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    if (entry.target.dataset.counterAnimated !== '1') {
                        entry.target.dataset.counterAnimated = '1';
                        animateCounter(entry.target);
                    }
                    return;
                }
                entry.target.dataset.counterAnimated = '0';
                entry.target.textContent = '0';
            });
        }, {threshold: 0.65});
        counters.forEach((counter) => observer.observe(counter));
    };

    const bindAscentScrollProgress = () => {
        const ascent = document.querySelector(SELECTOR_ASCENT);
        if (!ascent || ascent.dataset.showroomScrollBound) {
            return;
        }

        const profile = ascent.querySelector('.commerce-showroom-ascent__profile');
        const cards = Array.from(ascent.querySelectorAll('.commerce-showroom-ascent-card'));
        const markers = Array.from(ascent.querySelectorAll('.commerce-showroom-ascent__marker'));
        const cardsRoot = ascent.querySelector('.commerce-showroom-ascent__cards');

        ascent.dataset.showroomScrollBound = '1';

        let queued = false;
        const clamp = (value) => Math.max(0, Math.min(1, value));

        const parseHex = (value, fallback) => {
            const match = String(value || '').trim().match(/^#([0-9a-f]{6})$/i);
            const hex = match ? match[1] : fallback.replace('#', '');
            return [
                parseInt(hex.slice(0, 2), 16),
                parseInt(hex.slice(2, 4), 16),
                parseInt(hex.slice(4, 6), 16),
            ];
        };

        const mix = (start, end, ratio) => {
            const channel = (index) => Math.round(start[index] + (end[index] - start[index]) * ratio);
            return `rgb(${channel(0)}, ${channel(1)}, ${channel(2)})`;
        };

        const desktopRatio = (viewport) => {
            if (!profile) {
                return 0;
            }

            const rect = profile.getBoundingClientRect();

            // Route first node is y=152/190 and summit node is y=20/190.
            // 0% when the first route point enters viewport bottom.
            // Campus topbar occupies ~150px: reach 100% just before the summit
            // enters that protected topbar area.
            const firstPointOffset = rect.height * (152 / 190);
            const summitOffset = rect.height * (20 / 190);
            const campusTopbarHeight = 150;
            const startTop = viewport - firstPointOffset;
            const endTop = campusTopbarHeight - summitOffset;
            const travel = Math.max(startTop - endTop, 1);

            return clamp((startTop - rect.top) / travel);
        };

        const mobileRatio = (viewport) => {
            if (!cardsRoot) {
                return 0;
            }

            const rect = cardsRoot.getBoundingClientRect();

            // Begin when the first card/rail reaches 84% of the viewport.
            // Finish when the bottom of the full ascent reaches 22%.
            const startTop = viewport * 0.84;
            const endTop = viewport * 0.22 - rect.height;
            const travel = Math.max(startTop - endTop, 1);

            return clamp((startTop - rect.top) / travel);
        };

        const updateDynamicColors = (ratio) => {
            const styles = window.getComputedStyle(ascent);
            const start = parseHex(
                styles.getPropertyValue('--showroom-ascent-gradient-start'),
                '#ff8ac6'
            );
            const end = parseHex(
                styles.getPropertyValue('--showroom-ascent-gradient-end'),
                '#6226ad'
            );

            const count = Math.max(cards.length, 1);

            cards.forEach((card, index) => {
                // At 0% every icon is pink.
                // At 100% the five cards form a spatial pink -> violet gradient.
                const target = count > 1 ? index / (count - 1) : 0;
                const color = mix(start, end, target * ratio);
                card.style.setProperty('--showroom-ascent-icon-color', color);
            });

            markers.forEach((marker, index) => {
                const target = markers.length > 1 ? index / (markers.length - 1) : 0;
                marker.style.setProperty(
                    '--showroom-ascent-icon-color',
                    mix(start, end, target * ratio)
                );
            });
        };

        const update = () => {
            queued = false;

            const viewport = window.innerHeight;
            const mobile = window.matchMedia('(max-width: 1100px)').matches;
            const ratio = mobile ? mobileRatio(viewport) : desktopRatio(viewport);

            ascent.style.setProperty('--showroom-ascent-progress', `${(ratio * 100).toFixed(1)}%`);
            ascent.style.setProperty('--showroom-ascent-progress-ratio', ratio.toFixed(4));
            updateDynamicColors(ratio);
        };

        const requestUpdate = () => {
            if (!queued) {
                queued = true;
                window.requestAnimationFrame(update);
            }
        };

        update();
        window.addEventListener('scroll', requestUpdate, {passive: true});
        window.addEventListener('resize', requestUpdate);
    };

    const bindCurrency = () => {
        const selectors = Array.from(document.querySelectorAll(SELECTOR_CURRENCY));
        const toolbar = document.querySelector('[data-showroom-currency-toolbar]');
        const showroomRoot = document.querySelector('[data-showroom]');
        if (!selectors.length || !toolbar || !showroomRoot) {
            return;
        }

        const endpoint = toolbar.dataset.endpoint || '';
        const showroom = toolbar.dataset.showroomKey || showroomRoot.dataset.showroom || '';
        const status = toolbar.querySelector('[data-showroom-currency-status]');
        const errorMessage = toolbar.dataset.errorMessage || '';
        let activeRequest = 0;

        const setBusy = (busy, currency = '') => {
            selectors.forEach((selector) => {
                selector.disabled = busy;
                if (currency !== '') {
                    selector.value = currency;
                }
                selector.setAttribute('aria-busy', busy ? 'true' : 'false');
            });
            toolbar.classList.toggle('is-loading', busy);
            if (busy && status) {
                status.textContent = '…';
            }
        };

        const updateOffer = (role, data) => {
            const offer = document.querySelector(`[data-showroom-offer="${role}"]`);
            if (!offer) {
                return;
            }
            const price = offer.querySelector('[data-showroom-price]');
            const compare = offer.querySelector('[data-showroom-compare-price]');
            const discount = offer.querySelector('[data-showroom-discount]');
            const priceid = offer.querySelector('[data-showroom-priceid]');
            const currencyInput = offer.querySelector('[data-showroom-currency-input]');
            const buyButton = offer.querySelector('form button[type="submit"]');
            const bundleNote = offer.querySelector('.commerce-showroom-offer__bundle-note');

            if (price) {
                price.textContent = data.priceformatted || '';
            }

            const purchaseForm = offer.querySelector('[data-provider-experience]');
            if (purchaseForm) {
                purchaseForm.dataset.price = data.priceformatted || '';
                purchaseForm.dataset.currency = data.currency || '';
            }
            if (compare) {
                compare.textContent = data.compareformatted || '';
                compare.hidden = !data.hascompareprice;
            }
            if (discount) {
                discount.textContent = data.discountlabel || '';
                discount.hidden = !data.haspromotion;
            }
            if (priceid) {
                priceid.value = String(data.priceid || 0);
            }
            if (currencyInput) {
                currencyInput.value = data.currency || '';
            }
            if (buyButton) {
                const enabled = Boolean(data.canbuy) && Number(data.priceid || 0) > 0;
                buyButton.disabled = !enabled;
                buyButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
            }
            offer.classList.toggle('is-bundle-blocked', Boolean(data.bundleblocked));
            if (bundleNote) {
                bundleNote.textContent = data.bundleblockedmessage || '';
                bundleNote.hidden = !data.bundleblocked;
            }
            const comparisonPrice = document.querySelector(`[data-showroom-comparison-price="${role}"]`);
            if (comparisonPrice) {
                comparisonPrice.textContent = data.priceformatted || '';
            }
        };

        const parsePayload = async(response) => {
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Invalid showroom currency response.');
            }
            return response.json();
        };

        const changeCurrency = async(currency) => {
            if (!endpoint || !showroom || currency === '') {
                return;
            }

            const requestId = ++activeRequest;
            const previousCurrency = selectors[0].value;
            setBusy(true, currency);

            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('showroom', showroom);
                url.searchParams.set('currency', currency);
                url.searchParams.set('_', String(Date.now()));

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await parsePayload(response);
                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Currency update failed.');
                }
                if (requestId !== activeRequest) {
                    return;
                }

                Object.entries(payload.offers || {}).forEach(([role, data]) => updateOffer(role, data));
                const bundle = payload.offers ? payload.offers.bundle : null;
                document.querySelectorAll('[data-showroom-featured-price]').forEach((featured) => {
                    if (bundle) {
                        featured.textContent = bundle.priceformatted || '';
                    }
                });

                const resolvedCurrency = payload.currency || currency;
                updateLegalProfile(payload.legalprofile || {});
                selectors.forEach((selector) => selector.value = resolvedCurrency);
                document.querySelectorAll('[data-showroom-current-currency]').forEach((label) => {
                    label.textContent = resolvedCurrency;
                });
                const current = new URL(window.location.href);
                current.searchParams.set('currency', resolvedCurrency);
                window.history.replaceState(window.history.state, '', current.toString());
                if (status) {
                    status.textContent = resolvedCurrency;
                }
                emitTrackingEvent('currency_change', {currency: resolvedCurrency});
            } catch (error) {
                if (requestId !== activeRequest) {
                    return;
                }
                selectors.forEach((selector) => selector.value = previousCurrency);
                if (status) {
                    status.textContent = errorMessage;
                }
                window.console.error('[CampusFR Showroom] Currency update failed', error);
            } finally {
                if (requestId === activeRequest) {
                    setBusy(false);
                }
            }
        };

        selectors.forEach((selector) => {
            if (selector.dataset.showroomBound) {
                return;
            }
            selector.dataset.showroomBound = '1';
            selector.addEventListener('change', () => changeCurrency(selector.value));
        });
    };

    const bindSmartOfferAnchors = () => {
        const featuredOffer = () => document.querySelector(
            '[data-showroom-offer="bundle"], .commerce-showroom-offer.is-featured'
        );

        const scrollToOffers = (updateHistory = true) => {
            const featured = featuredOffer();
            if (!featured) {
                return;
            }

            const rect = featured.getBoundingClientRect();
            const bottomPadding = 24;
            const targetTop = window.scrollY + rect.bottom - window.innerHeight + bottomPadding;
            window.scrollTo({
                top: Math.max(0, targetTop),
                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
            });

            if (updateHistory) {
                const current = new URL(window.location.href);
                current.hash = 'showroom-offers';
                window.history.replaceState(window.history.state, '', current.toString());
            }
        };

        document.querySelectorAll('a[href="#showroom-offers"], [data-showroom-smart-anchor]').forEach((anchor) => {
            if (anchor.dataset.showroomAnchorBound) {
                return;
            }
            anchor.dataset.showroomAnchorBound = '1';
            anchor.addEventListener('click', (event) => {
                event.preventDefault();
                scrollToOffers(true);
            });
        });

        if (window.location.hash === '#showroom-offers') {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => scrollToOffers(false));
            });
        }

        window.addEventListener('hashchange', () => {
            if (window.location.hash === '#showroom-offers') {
                scrollToOffers(false);
            }
        });
    };

    const bindFaqs = () => {
        document.querySelectorAll(SELECTOR_FAQ).forEach((item) => {
            if (item.dataset.showroomBound) {
                return;
            }

            item.dataset.showroomBound = '1';
            item.addEventListener('toggle', () => {
                if (!item.open) {
                    return;
                }

                document.querySelectorAll(SELECTOR_FAQ).forEach((other) => {
                    if (other !== item) {
                        other.open = false;
                    }
                });
            });
        });
    };

    const observeOffers = () => {
        const sticky = document.querySelector(SELECTOR_STICKY);
        const comparisonTitle = document.querySelector(SELECTOR_COMPARISON);
        const comparison = comparisonTitle ? comparisonTitle.closest('section') : null;
        if (!sticky || !comparison) {
            return;
        }

        const update = () => {
            const comparisonPassed = comparison.getBoundingClientRect().bottom < 0;
            sticky.hidden = !comparisonPassed;
        };

        sticky.hidden = true;
        update();
        window.addEventListener('scroll', update, {passive: true});
        window.addEventListener('resize', update);
    };


    const updateLegalProfile = (profile = {}) => {
        document.querySelectorAll(SELECTOR_LEGAL_FIELD).forEach((node) => {
            const field = node.dataset.showroomLegalField || '';
            const value = String(profile[field] || '').trim();
            node.textContent = value;
            node.hidden = value === '';
        });
    };

    const clearStickyBottom = (node) => {
        if (node) {
            node.style.removeProperty('bottom');
        }
    };

    const computeLegalStickyBottom = (legal) => {
        if (!legal) {
            return null;
        }

        const legalTop = legal.getBoundingClientRect().top;
        const overlap = window.innerHeight - legalTop;

        if (overlap <= 0) {
            return null;
        }

        return Math.max(10, overlap + 10);
    };

    const setDesktopStickyLegalClearance = (active) => {
        const legal = document.querySelector(SELECTOR_FINAL_LEGAL);
        const sticky = document.querySelector(SELECTOR_DESKTOP_STICKY);

        if (!active || !sticky || !legal) {
            clearStickyBottom(sticky);
            return;
        }

        const bottom = computeLegalStickyBottom(legal);
        if (bottom === null) {
            clearStickyBottom(sticky);
            return;
        }

        sticky.style.setProperty('bottom', `${bottom}px`, 'important');
    };


    const observeFinalCtaState = () => {
        const finalCta = document.querySelector(SELECTOR_FINAL_CTA);

        if (!finalCta) {
            document.documentElement.classList.remove('commerce-showroom-final-cta-active');
            return;
        }

        const update = () => {
            const rect = finalCta.getBoundingClientRect();
            const active = rect.top < window.innerHeight && rect.bottom > 0;
            document.documentElement.classList.toggle(
                'commerce-showroom-final-cta-active',
                active
            );
        };

        update();
        window.addEventListener('scroll', update, {passive: true});
        window.addEventListener('resize', update);
    };

    const observeDesktopSticky = () => {
        const sticky = document.querySelector(SELECTOR_DESKTOP_STICKY);
        const comparisonTitle = document.querySelector(SELECTOR_COMPARISON);
        const comparison = comparisonTitle ? comparisonTitle.closest('section') : null;
        const finalCta = document.querySelector(SELECTOR_FINAL_CTA);
        const expedition = sticky ? sticky.querySelector(SELECTOR_DESKTOP_EXPEDITION) : null;
        const desktop = window.matchMedia('(min-width: 992px)');

        if (!sticky || !comparison) {
            return;
        }

        const setExpeditionVisible = (visible) => {
            sticky.classList.toggle('is-final-cta-active', visible);
            setDesktopStickyLegalClearance(visible);

            if (!expedition) {
                return;
            }

            expedition.hidden = !visible;
            expedition.setAttribute('aria-hidden', visible ? 'false' : 'true');
        };

        const update = () => {
            if (!desktop.matches) {
                sticky.hidden = true;
                setExpeditionVisible(false);
                return;
            }

            const comparisonPassed = comparison.getBoundingClientRect().bottom < 0;
            sticky.hidden = !comparisonPassed;

            if (!comparisonPassed || !finalCta) {
                setExpeditionVisible(false);
                return;
            }

            const rect = finalCta.getBoundingClientRect();
            const finalActive = rect.top < window.innerHeight && rect.bottom > 0;
            setExpeditionVisible(finalActive);
            setDesktopStickyLegalClearance(finalActive);
        };

        sticky.hidden = true;
        setExpeditionVisible(false);
        update();

        window.addEventListener('scroll', update, {passive: true});
        window.addEventListener('resize', update);
        desktop.addEventListener?.('change', update);
    };

    const bindInlineVideos = () => {
        document.querySelectorAll(SELECTOR_INLINE_VIDEO).forEach((container) => {
            if (container.dataset.showroomBound) {
                return;
            }

            const video = container.querySelector('[data-showroom-inline-video-element]');
            const control = container.querySelector('[data-showroom-inline-video-control]');
            const label = container.querySelector('[data-showroom-inline-video-control-label]');
            if (!video || !control) {
                return;
            }

            container.dataset.showroomBound = '1';

            const labels = {
                play: control.dataset.playLabel || 'Play',
                replay: control.dataset.replayLabel || 'Replay',
            };

            const setState = (state) => {
                control.dataset.state = state;
                control.setAttribute('aria-label', labels[state]);
                if (label) {
                    label.textContent = labels[state];
                }
                container.classList.toggle('is-playing', !video.paused && !video.ended);
                container.classList.toggle('is-ended', state === 'replay');
                container.classList.toggle('is-paused', state === 'play');
            };

            const syncState = () => {
                if (video.ended) {
                    setState('replay');
                } else if (video.paused) {
                    setState('play');
                } else {
                    // Native controls handle pausing while playback is active.
                    container.classList.add('is-playing');
                    container.classList.remove('is-ended', 'is-paused');
                    control.dataset.state = 'play';
                }
            };

            const togglePlayback = () => {
                if (video.ended) {
                    video.currentTime = 0;
                    video.play().catch(() => syncState());
                    return;
                }

                if (video.paused) {
                    video.play().catch(() => syncState());
                }
            };

            control.addEventListener('click', togglePlayback);
            video.addEventListener('play', syncState);
            video.addEventListener('pause', syncState);
            video.addEventListener('ended', syncState);
            video.addEventListener('emptied', syncState);

            if (video.dataset.firstFrameFallback === '1') {
                const revealFirstFrame = () => {
                    if (!Number.isFinite(video.duration) || video.duration <= 0) {
                        return;
                    }
                    const target = Math.min(0.08, Math.max(video.duration / 1000, 0.01));
                    if (video.currentTime === 0) {
                        try {
                            video.currentTime = target;
                        } catch (error) {
                            window.console.debug('[CampusFR Showroom] First video frame unavailable.', error);
                        }
                    }
                };

                if (video.readyState >= HTMLMediaElement.HAVE_METADATA) {
                    revealFirstFrame();
                } else {
                    video.addEventListener('loadedmetadata', revealFirstFrame, {once: true});
                }
                video.addEventListener('seeked', () => container.classList.add('has-first-frame'), {once: true});
                video.load();
            }

            document.addEventListener('visibilitychange', () => {
                if (document.hidden && !video.paused) {
                    video.pause();
                }
            });

            syncState();
        });
    };

    const bindVideoDialog = () => {
        const dialog = document.querySelector(SELECTOR_VIDEO_DIALOG);
        if (!dialog || dialog.dataset.showroomBound) {
            return;
        }

        dialog.dataset.showroomBound = '1';
        let returnFocus = null;

        document.querySelectorAll(SELECTOR_VIDEO_OPEN).forEach((trigger) => {
            trigger.addEventListener('click', () => {
                returnFocus = trigger;
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    dialog.setAttribute('open', 'open');
                }
                document.documentElement.classList.add('commerce-showroom-video-open');
            });
        });

        const closeDialog = () => {
            const player = dialog.querySelector('video');
            if (player && !player.paused) {
                player.pause();
            }
            if (typeof dialog.close === 'function' && dialog.open) {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
            }
            document.documentElement.classList.remove('commerce-showroom-video-open');
            if (returnFocus) {
                returnFocus.focus();
            }
        };

        const close = dialog.querySelector(SELECTOR_VIDEO_CLOSE);
        if (close) {
            close.addEventListener('click', closeDialog);
        }

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                closeDialog();
            }
        });
        dialog.addEventListener('close', () => {
            document.documentElement.classList.remove('commerce-showroom-video-open');
        });
    };


    const bindExerciseExplorer = () => {
        const explorer = document.querySelector('[data-showroom-exercise-explorer]');
        if (!explorer) { return; }
        const buttons = Array.from(explorer.querySelectorAll(SELECTOR_EXERCISE));
        const preview = explorer.querySelector('.commerce-showroom-exercise-preview');
        const previewBody = explorer.querySelector('[data-showroom-exercise-preview-body]');
        const image = explorer.querySelector('[data-showroom-exercise-preview-image]');
        const empty = explorer.querySelector('[data-showroom-exercise-preview-empty]');
        const loading = explorer.querySelector('[data-showroom-exercise-preview-loading]');
        const mobileTitle = explorer.querySelector('[data-showroom-exercise-mobile-title]');
        const mobileText = explorer.querySelector('[data-showroom-exercise-mobile-text]'); const mobileIcon = explorer.querySelector('[data-showroom-exercise-mobile-icon]');
        const previous = explorer.querySelector('[data-showroom-exercise-desktop-prev]');
        const next = explorer.querySelector('[data-showroom-exercise-desktop-next]');
        const current = explorer.querySelector('[data-showroom-exercise-desktop-current]');
        const dots = Array.from(explorer.querySelectorAll('[data-showroom-exercise-dot]'));
        if (!buttons.length || !image || !empty || !previewBody) { return; }
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const previewCache = new Map(); let activationToken=0; let swipeStartX=null; let swipeStartY=null;
        const buttonForKey=key=>buttons.find(button=>button.dataset.exerciseKey===key);
        const preload=url=>{ const source=(url||'').trim(); if(!source)return Promise.resolve(false); if(previewCache.has(source))return previewCache.get(source); const promise=new Promise(resolve=>{ const preloadImage=new Image(); preloadImage.decoding='async'; preloadImage.onload=()=>resolve(true); preloadImage.onerror=()=>resolve(false); preloadImage.src=source; if(preloadImage.complete)resolve(preloadImage.naturalWidth>0); }); previewCache.set(source,promise); return promise; };
        const preloadAll=()=>{ const urls=Array.from(new Set(buttons.filter(button=>button.dataset.hasPreview==='1').map(button=>(button.dataset.previewUrl||'').trim()).filter(Boolean))); if('requestIdleCallback' in window){window.requestIdleCallback(()=>urls.forEach(url=>preload(url)),{timeout:1800});}else{window.setTimeout(()=>urls.forEach(url=>preload(url)),180);} };
        const setLoading=active=>{ previewBody.classList.toggle('is-loading',active); if(loading)loading.hidden=!active; };
        const showUnavailable=()=>{ image.removeAttribute('src'); image.alt=''; image.hidden=true; empty.hidden=false; setLoading(false); };
        const renderPreview=async(button,token)=>{ const hasPreview=button.dataset.hasPreview==='1'&&Boolean((button.dataset.previewUrl||'').trim()); if(!hasPreview){showUnavailable();return;} const url=button.dataset.previewUrl.trim(); const absoluteUrl=new URL(url,window.location.href).href; if(image.src===absoluteUrl&&!image.hidden){setLoading(false);return;} setLoading(true); if(!reduceMotion)previewBody.classList.add('is-switching'); const loaded=await preload(url); if(token!==activationToken)return; if(!loaded){previewBody.classList.remove('is-switching');showUnavailable();return;} image.src=url; image.alt=button.dataset.previewAlt||''; image.hidden=false; empty.hidden=true; const reveal=()=>{if(token!==activationToken)return;setLoading(false);previewBody.classList.remove('is-switching');}; if(reduceMotion)reveal(); else window.requestAnimationFrame(()=>window.requestAnimationFrame(reveal)); };
        const activate=button=>{ if(!button)return; activationToken+=1; const token=activationToken; buttons.forEach(item=>{const active=item===button;item.classList.toggle('is-active',active);item.setAttribute('aria-selected',active?'true':'false');item.tabIndex=active?0:-1;}); dots.forEach(dot=>{const active=dot.dataset.exerciseKey===button.dataset.exerciseKey;dot.classList.toggle('is-active',active);dot.setAttribute('aria-pressed',active?'true':'false');}); const position=button.dataset.exercisePosition||String(buttons.indexOf(button)+1); if(current)current.textContent=position; if(mobileTitle)mobileTitle.textContent=button.dataset.exerciseTitle||''; if(mobileText)mobileText.textContent=button.dataset.exerciseText||''; if(mobileIcon)mobileIcon.className=button.dataset.exerciseIcon||''; renderPreview(button,token); };
        const move=offset=>{const index=buttons.findIndex(button=>button.classList.contains('is-active'));const currentIndex=index>=0?index:0;activate(buttons[(currentIndex+offset+buttons.length)%buttons.length]);};
        buttons.forEach(button=>{if(button.dataset.showroomBound)return;button.dataset.showroomBound='1';button.addEventListener('mouseenter',()=>{if(button.dataset.hasPreview==='1')preload(button.dataset.previewUrl);});button.addEventListener('focus',()=>{if(button.dataset.hasPreview==='1')preload(button.dataset.previewUrl);});button.addEventListener('click',()=>activate(button));});
        dots.forEach(dot=>{if(!dot.dataset.showroomBound){dot.dataset.showroomBound='1';dot.addEventListener('click',()=>activate(buttonForKey(dot.dataset.exerciseKey)));}});
        if(previous&&!previous.dataset.showroomBound){previous.dataset.showroomBound='1';previous.addEventListener('click',()=>move(-1));} if(next&&!next.dataset.showroomBound){next.dataset.showroomBound='1';next.addEventListener('click',()=>move(1));}
        if(preview&&!preview.dataset.showroomSwipeBound){preview.dataset.showroomSwipeBound='1';preview.addEventListener('pointerdown',event=>{if(!window.matchMedia('(max-width: 767.98px)').matches)return;swipeStartX=event.clientX;swipeStartY=event.clientY;});preview.addEventListener('pointerup',event=>{if(swipeStartX===null||swipeStartY===null||!window.matchMedia('(max-width: 767.98px)').matches){swipeStartX=null;swipeStartY=null;return;}const deltaX=event.clientX-swipeStartX;const deltaY=event.clientY-swipeStartY;swipeStartX=null;swipeStartY=null;if(Math.abs(deltaX)<48||Math.abs(deltaX)<=Math.abs(deltaY)*1.15)return;move(deltaX<0?1:-1);});preview.addEventListener('pointercancel',()=>{swipeStartX=null;swipeStartY=null;});}
        const initial=buttons.find(button=>button.classList.contains('is-active'))||buttons[0]; activate(initial); preloadAll();
    };

    const bindOfferComparison = () => {
        const controls = Array.from(document.querySelectorAll(SELECTOR_COMPARISON_OFFER));
        if (!controls.length) {
            return;
        }
        const activate = (role) => {
            controls.forEach((control) => {
                control.classList.toggle('is-highlighted', control.dataset.showroomComparisonOffer === role);
            });
            document.querySelectorAll('[data-comparison-role]').forEach((cell) => {
                cell.classList.toggle('is-highlighted', cell.dataset.comparisonRole === role);
            });
            document.querySelectorAll('[data-showroom-offer]').forEach((offer) => {
                offer.classList.toggle('is-comparison-highlighted', offer.dataset.showroomOffer === role);
            });
        };
        controls.forEach((control) => {
            if (control.dataset.showroomBound) {
                return;
            }
            control.dataset.showroomBound = '1';
            control.addEventListener('click', () => {
                const role = control.dataset.showroomComparisonOffer || '';
                activate(role);
                const offer = document.querySelector(`[data-showroom-offer="${role}"]`);
                if (offer) {
                    offer.scrollIntoView({behavior: 'smooth', block: 'center'});
                    offer.focus({preventScroll: true});
                }
            });
        });
    };

    const observeStorySections = () => {
        const targets = [
            ...document.querySelectorAll(SELECTOR_REVEAL),
            ...document.querySelectorAll(SELECTOR_ASCENT),
        ];
        if (!targets.length) {
            return;
        }
        if (typeof IntersectionObserver === 'undefined') {
            targets.forEach((target) => {
                target.classList.add(target.matches(SELECTOR_ASCENT) ? 'is-visible' : 'is-revealed');
            });
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                entry.target.classList.add(
                    entry.target.matches(SELECTOR_ASCENT) ? 'is-visible' : 'is-revealed'
                );
                observer.unobserve(entry.target);
            });
        }, {threshold: 0.16});
        targets.forEach((target) => observer.observe(target));
    };



    /**
     * Shrink editorial offer text only when necessary so titles remain complete.
     * One-line titles and two-line subtitles are never ellipsized.
     */
    const fitOfferEditorialText = () => {
        const elements = Array.from(document.querySelectorAll('[data-showroom-fit-lines]'));
        if (!elements.length) {
            return;
        }

        const fit = (element) => {
            const lines = Math.max(1, Number.parseInt(element.dataset.showroomFitLines || '1', 10));
            const minimum = Math.max(10, Number.parseFloat(element.dataset.showroomFitMin || '12'));

            element.style.fontSize = '';
            const computed = window.getComputedStyle(element);
            const initial = Number.parseFloat(computed.fontSize) || minimum;
            let size = initial;

            const fits = () => {
                const current = window.getComputedStyle(element);
                const lineHeight = Number.parseFloat(current.lineHeight) || (size * 1.35);
                if (lines === 1) {
                    return element.scrollWidth <= element.clientWidth + 1;
                }
                return element.scrollHeight <= (lineHeight * lines) + 1;
            };

            while (size > minimum && !fits()) {
                size = Math.max(minimum, size - .5);
                element.style.fontSize = `${size}px`;
            }
        };

        const fitAll = () => elements.forEach(fit);
        fitAll();

        if (typeof ResizeObserver !== 'undefined') {
            const observer = new ResizeObserver(() => window.requestAnimationFrame(fitAll));
            elements.forEach((element) => observer.observe(element));
        } else {
            window.addEventListener('resize', () => window.requestAnimationFrame(fitAll));
        }
    };

    const bindMobileComparison = () => {
        document.querySelectorAll('[data-showroom-comparison-mobile]').forEach((root) => {
            if (root.dataset.ready === '1') return;
            root.dataset.ready = '1';
            const rail = root.querySelector('[data-comparison-rail]');
            const slides = [...root.querySelectorAll('[data-comparison-slide]')];
            const tabs = [...root.querySelectorAll('[data-comparison-tab]')];
            const dots = [...root.querySelectorAll('[data-comparison-dot]')];
            const counter = root.querySelector('[data-comparison-counter]');
            const previous = root.querySelector('[data-comparison-prev]');
            const next = root.querySelector('[data-comparison-next]');
            const recommended = root.querySelector('[data-comparison-mobile-recommended]');
            const featuredIndex = tabs.findIndex((item) => item.classList.contains('is-featured'));
            if (!rail || !slides.length) return;
            let active = 0;
            const update = (index) => {
                active = Math.max(0, Math.min(slides.length - 1, index));
                tabs.forEach((item, position) => item.classList.toggle('is-active', position === active));
                dots.forEach((item, position) => item.classList.toggle('is-active', position === active));
                if (counter) counter.textContent = `${active + 1} / ${slides.length}`;
                if (previous) previous.disabled = active === 0;
                if (next) next.disabled = active === slides.length - 1;
                if (recommended) recommended.hidden = active !== featuredIndex;
            };
            const go = (index) => {
                if (!slides[index]) return;
                rail.scrollTo({left: index * rail.clientWidth, behavior: 'smooth'});
                update(index);
            };
            tabs.forEach((item, index) => item.addEventListener('click', () => go(index)));
            dots.forEach((item, index) => item.addEventListener('click', () => go(index)));
            previous?.addEventListener('click', () => go(active - 1));
            next?.addEventListener('click', () => go(active + 1));
            let frame = 0;
            rail.addEventListener('scroll', () => {
                cancelAnimationFrame(frame);
                frame = requestAnimationFrame(() => update(Math.round(rail.scrollLeft / (rail.clientWidth || 1))));
            }, {passive: true});
            update(0);
        });
    };

    const bindInteractiveProblemArrows = () => {
        document.querySelectorAll('[data-showroom-interactive-problem]').forEach((root) => {
            const stage = root.querySelector('.commerce-showroom-interactive-problem__stage');
            const svg = root.querySelector('[data-interactive-problem-arrows]');
            const target = root.querySelector('[data-interactive-problem-target]');
            const crosses = Array.from(root.querySelectorAll('[data-interactive-problem-cross]'));

            if (!stage || !svg || !target || crosses.length !== 4) {
                return;
            }

            const ns = 'http://www.w3.org/2000/svg';
            const endGap = 24;
            let frame = 0;

            const draw = () => {
                if (window.matchMedia('(max-width: 767.98px)').matches) {
                    svg.replaceChildren();
                    return;
                }

                const stageRect = stage.getBoundingClientRect();
                const targetRect = target.getBoundingClientRect();
                if (!stageRect.width || !stageRect.height || !targetRect.width || !targetRect.height) {
                    return;
                }

                svg.setAttribute('viewBox', `0 0 ${stageRect.width} ${stageRect.height}`);
                svg.replaceChildren();

                const startX = targetRect.left - stageRect.left + (targetRect.width / 2);
                const startY = targetRect.top - stageRect.top + (targetRect.height / 2);

                crosses.forEach((cross) => {
                    const crossRect = cross.getBoundingClientRect();
                    const endX = crossRect.left - stageRect.left - endGap;
                    const endY = crossRect.top - stageRect.top + (crossRect.height / 2);
                    const distance = Math.max(0, endX - startX);

                    if (distance < 20) {
                        return;
                    }

                    const control1X = startX + (distance * .38);
                    const control2X = startX + (distance * .76);

                    const path = document.createElementNS(ns, 'path');
                    path.setAttribute(
                        'd',
                        `M ${startX} ${startY} ` +
                        `C ${control1X} ${startY}, ${control2X} ${endY}, ${endX} ${endY}`
                    );
                    svg.append(path);

                    const head = document.createElementNS(ns, 'polyline');
                    head.setAttribute(
                        'points',
                        `${endX - 8},${endY - 5} ${endX},${endY} ${endX - 8},${endY + 5}`
                    );
                    head.classList.add('commerce-showroom-interactive-problem__arrow-head');
                    svg.append(head);
                });
            };

            const scheduleDraw = () => {
                window.cancelAnimationFrame(frame);
                frame = window.requestAnimationFrame(draw);
            };

            if (typeof ResizeObserver !== 'undefined') {
                const observer = new ResizeObserver(scheduleDraw);
                observer.observe(stage);
                observer.observe(target);
                crosses.forEach((cross) => observer.observe(cross));
            }

            window.addEventListener('resize', scheduleDraw, {passive: true});
            window.requestAnimationFrame(draw);
        });
    };

    const bindInteractiveProblem = () => {
        document.querySelectorAll('[data-showroom-interactive-problem]').forEach((root) => {
            if (root.dataset.interactiveProblemReady === '1') return;
            root.dataset.interactiveProblemReady = '1';
            const target = root.querySelector('[data-problem-target]');
            const targetIcon = root.querySelector('[data-problem-target-icon]');
            const selected = root.querySelector('[data-problem-selected]');
            const feedback = root.querySelector('[data-problem-feedback]');
            const success = root.querySelector('[data-problem-success]')?.textContent.trim() || '';
            const error = root.querySelector('[data-problem-error]')?.textContent.trim() || '';
            const correct = root.dataset.correctAnswer || '';
            if (!target || !targetIcon) return;

            let resetTimer = 0;
            const evaluate = (value) => {
                window.clearTimeout(resetTimer);
                const isCorrect = value === correct;
                target.classList.remove('is-dragover', 'is-correct', 'is-error');
                target.classList.add(isCorrect ? 'is-correct' : 'is-error');
                targetIcon.textContent = isCorrect ? '✓' : '×';
                if (selected) selected.textContent = value;
                if (feedback) feedback.textContent = isCorrect ? success : error;
                emitTrackingEvent('interactive_problem_answer', {answer: value, correct: isCorrect ? '1' : '0'});
                const resetDelay = isCorrect
                    ? (prefersReducedMotion() ? 3200 : 2600)
                    : (prefersReducedMotion() ? 2400 : 1500);

                resetTimer = window.setTimeout(() => {
                    target.classList.remove('is-correct', 'is-error');
                    targetIcon.textContent = '?';
                    if (selected) selected.textContent = '';
                    if (feedback) feedback.textContent = '';
                }, resetDelay);
            };

            root.querySelectorAll('[data-problem-choice]').forEach((choice) => {
                choice.addEventListener('click', () => evaluate(choice.dataset.value || ''));
                choice.addEventListener('dragstart', (event) => {
                    event.dataTransfer?.setData('text/plain', choice.dataset.value || '');
                    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'copy';
                });
            });
            target.addEventListener('dragover', (event) => {
                event.preventDefault();
                target.classList.add('is-dragover');
            });
            target.addEventListener('dragleave', () => target.classList.remove('is-dragover'));
            target.addEventListener('drop', (event) => {
                event.preventDefault();
                evaluate(event.dataTransfer?.getData('text/plain') || '');
            });
        });
    };

    const init = () => {
        bindTracking();
        fitOfferEditorialText();
        bindCurrency();
        bindSmartOfferAnchors();
        bindFaqs();
        observeOffers();
        observeFinalCtaState();
        observeDesktopSticky();
        bindVideoDialog();
        bindInlineVideos();
        bindExerciseExplorer();
        bindInteractiveProblem();
        bindInteractiveProblemArrows();
        bindOfferComparison();
        bindMobileComparison();
        observeStorySections();
        bindHeroParallax();
        bindCounters();
        bindAscentScrollProgress();
    };

    return {init};
});
