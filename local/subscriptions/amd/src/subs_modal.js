/* eslint-env amd */
define(['core/config'], function(config) {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function init() {
        onReady(function() {
            var modalEl  = document.getElementById('subsModal');
            var frameEl  = document.getElementById('subsModalFrame');
            var loaderEl = document.getElementById('subsModalLoader');

            if (!modalEl || !frameEl || !loaderEl) {
                return;
            }

            var loadTimeout = null;

            function showLoader() {
                if (loadTimeout) {
                    clearTimeout(loadTimeout);
                    loadTimeout = null;
                }
                loaderEl.classList.add('is-visible');

                // Sécurité : si jamais rien ne répond, on cache le loader après N secondes.
                loadTimeout = setTimeout(function() {
                    loaderEl.classList.remove('is-visible');
                    loadTimeout = null;
                }, 8000);
            }

            function hideLoader() {
                if (loadTimeout) {
                    clearTimeout(loadTimeout);
                    loadTimeout = null;
                }
                loaderEl.classList.remove('is-visible');
            }

            var bsModal = null;
            try {
                if (window.bootstrap && window.bootstrap.Modal) {
                    bsModal = new window.bootstrap.Modal(modalEl);
                }
            } catch (e) {
                bsModal = null;
            }

            function showModal() {
                if (bsModal) {
                    bsModal.show();
                } else {
                    modalEl.classList.add('show');
                    modalEl.style.display = 'block';
                    modalEl.removeAttribute('aria-hidden');
                    modalEl.setAttribute('aria-modal', 'true');
                    document.body.classList.add('modal-open');
                    if (!document.querySelector('.modal-backdrop')) {
                        var bd = document.createElement('div');
                        bd.className = 'modal-backdrop fade show';
                        document.body.appendChild(bd);
                    }
                }
            }

            function hideModal() {
                if (document.activeElement && modalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                if (bsModal) {
                    bsModal.hide();
                } else {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    modalEl.setAttribute('aria-hidden','true');
                    modalEl.removeAttribute('aria-modal');
                    document.body.classList.remove('modal-open');
                    var bd = document.querySelector('.modal-backdrop');
                    if (bd) {
                        bd.remove();
                    }
                }
                hideLoader();
                frameEl.src = '';
            }

            // Fermeture via bouton X
            document.addEventListener('click', function(e) {
                var closer = e.target.closest('[data-bs-dismiss="modal"]');
                if (closer && modalEl.contains(closer)) {
                    e.preventDefault();
                    hideModal();
                }
            });

            function buildEmbeddedUrl(href) {
                try {
                    var url = new URL(href, config.wwwroot);
                    url.searchParams.set('embedded', '1');
                    return url.toString();
                } catch (e) {
                    if (href.indexOf('?') === -1) {
                        return href + '?embedded=1';
                    }
                    if (!/[?&]embedded=1/.test(href)) {
                        return href + '&embedded=1';
                    }
                    return href;
                }
            }

            function openSubsModal(href) {
                if (!href) {
                    return;
                }
                showLoader();
                var src = buildEmbeddedUrl(href);
                frameEl.src = src;
                showModal();
            }

            // À chaque fois que l'iframe a fini de charger une page (subscribe / checkout)
            frameEl.addEventListener('load', function() {
                hideLoader();
                hookIframeClicks();
            });

            // Ajoute un listener dans le document de l'iframe pour réafficher le loader
            // à chaque navigation interne (clic sur un lien réel).
            function hookIframeClicks() {
                try {
                    var win = frameEl.contentWindow;
                    if (!win || win.__subsClicksHooked) {
                        return;
                    }
                    var doc = win.document;
                    if (!doc) {
                        return;
                    }
                    win.__subsClicksHooked = true;

                    doc.addEventListener('click', function(e) {
                        var a = e.target.closest('a');
                        if (!a) {
                            return;
                        }
                        var href = a.getAttribute('href') || '';
                        if (!href) {
                            return;
                        }
                        // On ignore les ancres (#) pour éviter de lancer le loader sur les toggles
                        if (href.charAt(0) === '#') {
                            return;
                        }
                        showLoader();
                    }, true); // capture: on passe avant la navigation
                } catch (e) {
                    // Silencieux si jamais problème d’accès (ne devrait pas arriver ici)
                }
            }

            // On garde aussi un postMessage "ready" comme filet de sécurité éventuel
            window.addEventListener('message', function(e) {
                var data = e.data;
                if (!data || typeof data !== 'object') {
                    return;
                }
                if (data.type === 'subs_popup_ready') {
                    hideLoader();
                }
            });

            // Clic sur n’importe quel lien avec data-subs-modal (bouton "S’abonner" externe)
            document.addEventListener('click', function(e) {
                var a = e.target.closest('a[data-subs-modal]');
                if (!a) {
                    return;
                }
                e.preventDefault();
                openSubsModal(a.getAttribute('href'));
            });
        });
    }

    return {
        init: init
    };
});
