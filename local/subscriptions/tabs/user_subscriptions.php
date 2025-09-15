<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib/user_subs_lib.php');
require_once(__DIR__ . '/../renderer/user_subs_renderer.php');
require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;

global $DB, $OUTPUT;

$renderer = new local_subscriptions_user_subs_renderer($PAGE, $OUTPUT);

// Traitement des actions POST (modif/suppression)
[$updated, $deleted] = handle_post_actions();

// Affichage
$subscriptions = $DB->get_records('user_subscription', null, 'start_date DESC');

echo $renderer->render_user_subscriptions_page($subscriptions);

// JS inline pour gérer l'état
echo html_writer::script(<<<JS
    document.addEventListener('DOMContentLoaded', function () {
        const updateState = () => {
            const checkboxes = document.querySelectorAll('.subscription-checkbox');
            const saveBtn = document.getElementById('save-button');
            const deleteBtn = document.getElementById('delete-button');
            const badge = document.getElementById('checked-count');

            let count = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) count++;
            });

            const active = count > 0;

            if (saveBtn) {
                saveBtn.disabled = !active;
                saveBtn.classList.toggle('disabled-btn', !active);
            }

            if (deleteBtn) {
                deleteBtn.disabled = !active;
                deleteBtn.classList.toggle('disabled-btn', !active);
            }

            if (badge) {
                badge.textContent = count + ' sélectionné' + (count > 1 ? 's' : '');
                badge.classList.toggle('d-none', count === 0);
            }
        };

        document.getElementById('edit-button')?.addEventListener('click', () => {
            // Affiche les champs éditables
            document.querySelectorAll('.edit-checkbox').forEach(cb => cb.classList.remove('d-none'));
            document.querySelectorAll('.edit-input').forEach(el => el.classList.remove('d-none'));
            document.querySelectorAll('.edit-display').forEach(el => el.style.display = 'none');
            document.getElementById('edit-button').disabled = true;
            document.getElementById('edit-button').classList.add('disabled-btn');
            document.getElementById('save-button').classList.remove('d-none');
            document.getElementById('delete-button').classList.remove('d-none');
            document.getElementById('cancel-button').classList.remove('d-none');

            // Initialise Select2 uniquement maintenant
            const selects = document.querySelectorAll('select');
            selects.forEach(select => {
                if (typeof jQuery !== 'undefined' && jQuery().select2) {
                jQuery(select).select2({ width: '100%' });
                }
            });

            updateState();
        });

        document.querySelectorAll('.subscription-checkbox').forEach(cb => {
            cb.addEventListener('change', updateState);
        });

        updateState();
    });

JS);

echo html_writer::script(<<<JS
    document.addEventListener('DOMContentLoaded', function () {
        // Ferme tous les popovers visibles
        function closeAllPopovers(except = null) {
            document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
                const pop = bootstrap.Popover.getInstance(el);
                if (pop && el !== except) pop.hide();
            });
        }

        document.querySelectorAll('[data-bs-toggle="popover"][data-popover-id]').forEach(triggerEl => {
            const contentId = triggerEl.getAttribute('data-popover-id');
            const contentEl = document.getElementById(contentId);

            if (!contentEl) return;

            const popover = new bootstrap.Popover(triggerEl, {
                html: true,
                placement: 'right',
                trigger: 'click',
                container: 'body',
                sanitize: false,
                content: function () {
                    return contentEl.innerHTML;
                }
            });

            // Ferme les autres quand on clique sur un nouveau
            triggerEl.addEventListener('click', function (e) {
                e.stopPropagation();
                closeAllPopovers(triggerEl);
            });
        });

        // Clique ailleurs = fermeture
        document.addEventListener('click', function () {
            closeAllPopovers();
        });
    });
JS);


