/* eslint-env amd */
/* global M */

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
            var modalEl   = document.getElementById('campusTrialModal');
            if (!modalEl) {
                return;
            }

            var formEl    = document.getElementById('campusTrialForm');
            var errBox    = document.getElementById('campusTrialError');
            var redirEl   = document.getElementById('campusTrialRedirectId');
            var expiredEl = document.getElementById('campusTrialExpired');
            var formWrap  = document.getElementById('campusTrialFormWrap');
            var btnSub    = document.getElementById('campusTrialSubscribe');
            var btnCont   = document.getElementById('campusTrialContinue');

            var fFirst = document.getElementById('trialFirst');
            var fLast  = document.getElementById('trialLast');
            var fEmail = document.getElementById('trialEmail');
            var fPass  = document.getElementById('trialPass');
            var fTos   = document.getElementById('trialAcceptTos');
            var fPhone = document.getElementById('trialPhone');

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
                    return;
                }
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

            function hideModal() {
                if (document.activeElement && modalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                if (bsModal) {
                    bsModal.hide();
                    return;
                }
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
                document.body.classList.remove('modal-open');
                var bd = document.querySelector('.modal-backdrop');
                if (bd) {
                    bd.remove();
                }
            }

            // Fermer via data-bs-dismiss="modal" (croix / Annuler)
            document.addEventListener('click', function(e) {
                var closer = e.target.closest('[data-bs-dismiss="modal"]');
                if (closer && modalEl.contains(closer)) {
                    e.preventDefault();
                    hideModal();
                }
            });

            // ---- Toggle œil mot de passe ----
            function togglePasswordVisibility(btn) {
                var selector = btn.getAttribute('data-target');
                if (!selector) {
                    return;
                }
                var input = document.querySelector(selector);
                if (!input) {
                    return;
                }

                // Bascule du type
                var nowVisible;
                if (input.type === 'password') {
                    input.type = 'text';
                    nowVisible = true;
                } else {
                    input.type = 'password';
                    nowVisible = false;
                }

                // Icône
                var icon = btn.querySelector('.password-toggle-icon');
                if (icon) {
                    // visible => 🙈, masqué => 👁
                    icon.textContent = nowVisible ? '🙈' : '👁';
                }

                // Tooltip + aria-label dynamiques
                var showLabel = btn.getAttribute('data-show-label') || '';
                var hideLabel = btn.getAttribute('data-hide-label') || '';
                var label;

                if (nowVisible) {
                    // On voit le mot de passe : l’action du bouton est de le masquer
                    label = hideLabel || showLabel;
                } else {
                    // Le mot de passe est masqué : l’action est de l’afficher
                    label = showLabel || hideLabel;
                }

                if (label) {
                    btn.setAttribute('aria-label', label);
                    btn.setAttribute('title', label);
                }
            }


            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.password-toggle');
                if (!btn) {
                    return;
                }
                e.preventDefault();
                togglePasswordVisibility(btn);
            });

            // ---- Validation dynamique ----
            function valid() {
                if (!formEl) {
                    return false;
                }
                var ok = true;

                if (!fFirst.value.trim()) {
                    ok = false;
                }
                if (!fLast.value.trim()) {
                    ok = false;
                }
                if (!fEmail.value.trim()) {
                    ok = false;
                }
                if (fEmail.validity && !fEmail.validity.valid) {
                    ok = false;
                }
                if (fPhone && !fPhone.value.trim()) {
                    ok = false;
                }

                var pass1 = fPass.value || '';

                if (!pass1 || pass1.length < 8) {
                    ok = false;
                }

                if (fTos && !fTos.checked) {
                    ok = false;
                }

                if (btnCont) {
                    btnCont.disabled = !ok;
                }
                return ok;
            }

            ['input', 'change', 'keyup'].forEach(function(ev) {
                [fFirst, fLast, fEmail, fPass, fTos, fPhone].forEach(function(el) {
                    if (el) {
                        el.addEventListener(ev, valid);
                    }
                });
            });

            // ---- API globale pour ouvrir la modale ----
            window.campusTrialOpen = function(redirectid) {
                if (redirEl) {
                    redirEl.value = redirectid || '';
                }
                if (expiredEl) {
                    expiredEl.classList.add('d-none');
                    expiredEl.innerHTML = '';
                }
                if (formWrap) {
                    formWrap.classList.remove('d-none');
                }
                if (btnSub) {
                    btnSub.classList.add('d-none');
                }
                if (errBox) {
                    errBox.classList.add('d-none');
                    errBox.textContent = '';
                    errBox.classList.remove('alert-warning', 'alert-danger');
                }
                if (btnCont) {
                    btnCont.classList.remove('d-none');
                    btnCont.disabled = true;
                }

                showModal();
            };

            // ---- Vérification status trial (cookie / backend) ----
            function check(redirectid) {
                var url = config.wwwroot + '/local/campus/trial_check.php?redirectid=' + encodeURIComponent(redirectid);
                fetch(url, {
                    credentials: 'same-origin',
                    headers: {'X-Requested-With': 'fetch'}
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(j) {
                    if (j.status === 'ok') {
                        var target = j.redirect || (config.wwwroot + '/local/campus/mycourses.php');
                        window.location.href = target;
                        return;
                    }

                    if (j.status === 'expired') {
                        var subHref = j.subscribe || (config.wwwroot + '/boutique');
                        if (redirEl) {
                            redirEl.value = redirectid;
                        }
                        if (expiredEl) {
                            expiredEl.classList.remove('d-none', 'alert-danger');
                            expiredEl.classList.add('alert-warning');
                            expiredEl.innerHTML = M.util.get_string('trial_expired_html', 'local_campus', {subscribe: subHref});
                        }
                        if (formWrap) {
                            formWrap.classList.add('d-none');
                        }
                        if (btnSub) {
                            btnSub.textContent = M.util.get_string('trial_btn_subscribe', 'local_campus');
                            btnSub.href = subHref;
                            btnSub.classList.remove('d-none');
                        }
                        if (btnCont) {
                            btnCont.disabled = true;
                            btnCont.classList.add('d-none');
                        }
                        document.cookie = 'campus_trial=; Max-Age=0; path=/';
                        showModal();
                        return;
                    }

                    if (j.status === 'already_subscribed') {
                        var safeHref = j.login || (config.wwwroot + '/login/index.php');

                        if (errBox) {
                            errBox.classList.remove('d-none', 'alert-danger');
                            errBox.classList.add('alert-warning');
                            errBox.innerHTML = M.util.get_string('trial_already_subscribed_html', 'local_campus', {login: safeHref});
                        }

                        if (btnCont) {
                            btnCont.classList.add('d-none');
                            btnCont.disabled = true;
                        }
                        if (btnSub) {
                            btnSub.textContent = M.util.get_string('login');
                            btnSub.href = safeHref;
                            btnSub.target = '_top';
                            btnSub.classList.remove('d-none');
                        }

                        return;
                    }

                    window.campusTrialOpen(redirectid);
                })
                .catch(function() {
                    window.campusTrialOpen(redirectid);
                });
            }

            window.campusTrialCheck = check;

            // Clic sur n'importe quel élément [data-campus-trial-redirect]
            document.addEventListener('click', function(e) {
                var a = e.target.closest('[data-campus-trial-redirect]');
                if (!a) {
                    return;
                }
                e.preventDefault();
                check(a.getAttribute('data-campus-trial-redirect'));
            });

            // Soumission du formulaire
            if (formEl) {
                formEl.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!valid()) {
                        return;
                    }
                    if (errBox) {
                        errBox.classList.add('d-none');
                        errBox.textContent = '';
                        errBox.classList.remove('alert-warning', 'alert-danger');
                    }

                    var fd = new FormData(formEl);
                    fetch(config.wwwroot + '/local/campus/trial_gate.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin',
                        headers: {'X-Requested-With': 'fetch'}
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(j) {
                        if (j.status === 'ok') {
                            var target = j.redirect || (config.wwwroot + '/local/campus/mycourses.php');
                            window.location.href = target;
                            return;
                        }

                        if (j.status === 'already_subscribed') {
                            var safeHref = j.login || (config.wwwroot + '/login/index.php');

                            if (errBox) {
                                errBox.classList.remove('d-none', 'alert-danger');
                                errBox.classList.add('alert-warning');
                                errBox.innerHTML = M.util.get_string('trial_already_subscribed_html', 'local_campus', {login: safeHref});
                            }

                            if (btnCont) {
                                btnCont.classList.add('d-none');
                                btnCont.disabled = true;
                            }
                            if (btnSub) {
                                btnSub.textContent = M.util.get_string('login', 'moodle');
                                btnSub.href = safeHref;
                                btnSub.classList.remove('d-none');
                            }

                            return;
                        }

                        if (j.status === 'expired') {
                            var subHref = j.subscribe || (config.wwwroot + '/boutique');

                            if (expiredEl) {
                                expiredEl.classList.remove('d-none', 'alert-danger');
                                expiredEl.classList.add('alert-warning');
                                expiredEl.innerHTML = M.util.get_string('trial_expired_html', 'local_campus', {subscribe: subHref});
                            }
                            if (formWrap) {
                                formWrap.classList.add('d-none');
                            }
                            if (btnSub) {
                                btnSub.textContent = M.util.get_string('trial_btn_subscribe', 'local_campus');
                                btnSub.href = subHref;
                                btnSub.classList.remove('d-none');
                            }
                            if (btnCont) {
                                btnCont.disabled = true;
                                btnCont.classList.add('d-none');
                            }
                            showModal();
                            return;
                        }

                        throw new Error(j && j.message ? j.message : 'Error');
                    })
                    .catch(function(err) {
                        if (errBox) {
                            errBox.textContent = (err && err.message) ? err.message : String(err);
                            errBox.classList.remove('d-none');
                            errBox.classList.remove('alert-danger');
                            errBox.classList.add('alert-warning');
                        }
                    });
                });
            }

            // Sécurité : fermeture sur clic sur la croix
            document.addEventListener('click', function(e) {
                if (e.target.matches('#campusTrialModal .btn-close')) {
                    hideModal();
                }
            });
        });
    }

    return {
        init: init
    };
});
