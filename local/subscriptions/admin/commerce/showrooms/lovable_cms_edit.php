<?php
// Gustave Builder — edit persistent but deliberately unpublishable CMS Lovable text draft.
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);
$id = required_param('id', PARAM_INT);
$lang = optional_param('lang', 'ru', PARAM_ALPHA);
if (!in_array($lang, ['ru', 'fr', 'en'], true)) {
    throw new invalid_parameter_exception('Langue non prise en charge.');
}
[$record, $meta] = Draft::require_draft($DB, $id);
$url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_edit.php', ['id' => $id, 'lang' => $lang]);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();
    if (required_param('action', PARAM_ALPHA) !== 'save') {
        throw new invalid_parameter_exception('Action invalide.');
    }
    $values = optional_param_array('values', [], PARAM_RAW);
    if (!is_array($values)) {
        throw new invalid_parameter_exception('Formulaire invalide.');
    }
    Draft::save_texts($DB, $id, $lang, $values, (int)$USER->id);
    redirect($url, 'Textes enregistrés dans le brouillon CMS.', null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$title = 'Brouillon CMS Lovable — ' . $meta['designid'];
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-lovable-cms-page');
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo html_writer::tag('h1', s($title), ['class' => 'h3']);
echo $OUTPUT->notification('Brouillon persistant, NON PUBLIABLE. Le ZIP est sauvegardé dans Moodle File API. '
    . 'Les paiements et les 17 attributs JSX restent à contrôler. FR et EN ne sont pas traduits automatiquement.', 'warning');

echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2 mb-3');
foreach (['ru' => 'RU · Source', 'fr' => 'FR · Traductions', 'en' => 'EN · Translations'] as $code => $label) {
    echo html_writer::link(new moodle_url($url, ['lang' => $code]), s($label), [
        'class' => 'btn btn-sm w-auto ' . ($lang === $code ? 'btn-primary' : 'btn-outline-secondary'),
    ]);
}
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/showrooms/preview.php',
    ['id' => $id, 'lang' => $lang]), 'Prévisualiser dans CampusFR',
    ['class' => 'btn btn-sm btn-outline-primary w-auto', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_commerce.php',
    ['id' => $id]), 'Commerce · Produits et tarifs',
    ['class' => 'btn btn-sm btn-outline-primary w-auto']);
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
    'Liste des showrooms', ['class' => 'btn btn-sm btn-outline-secondary w-auto']);
echo html_writer::end_div();

$original = $meta['catalogues']['ru'];
$current = $meta['catalogues'][$lang] ?? [];
$done = count(array_filter($current, static fn($value): bool => is_string($value) && trim($value) !== ''));
echo html_writer::tag('p', s(count($original) . ' champs · ' . $done . ' renseignés en ' . strtoupper($lang)),
    ['class' => 'text-muted']);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
$groups = [];
foreach ($original as $key => $russian) {
    $section = explode('_', $key, 2)[0];
    $groups[$section][$key] = $russian;
}
$first = true;
foreach ($groups as $section => $fields) {
    echo html_writer::start_tag('details', ['class' => 'card card-body mb-2', 'open' => $first ? 'open' : null]);
    echo html_writer::tag('summary', s(ucfirst(str_replace('_', ' ', $section)) . ' · ' . count($fields) . ' textes'),
        ['class' => 'fw-semibold mb-3', 'style' => 'cursor:pointer']);
    foreach ($fields as $key => $russian) {
        echo html_writer::start_div('mb-3');
        echo html_writer::tag('label', s($key), ['class' => 'form-label small fw-semibold', 'for' => 'field-' . $key]);
        if ($lang !== 'ru') {
            echo html_writer::tag('p', s($russian), ['class' => 'text-muted small mb-1']);
        }
        echo html_writer::tag('textarea', s((string)($current[$key] ?? '')), [
            'name' => 'values[' . $key . ']', 'id' => 'field-' . $key,
            'rows' => 2, 'maxlength' => 3000, 'class' => 'form-control',
            'style' => 'max-width:860px',
            'placeholder' => $lang === 'ru' ? '' : 'Traduction à saisir',
        ]);
        echo html_writer::end_div();
    }
    echo html_writer::end_tag('details');
    $first = false;
}
echo html_writer::tag('button', 'Enregistrer les textes ' . strtoupper($lang), [
    'type' => 'submit', 'class' => 'btn btn-primary btn-sm w-auto mt-3',
]);
echo html_writer::end_tag('form');
echo html_writer::tag('p', 'Les traductions manquantes affichent temporairement le texte RU dans l’aperçu. '
    . 'Aucune traduction automatique ni intégration aux paiements n’est effectuée.', ['class' => 'small text-muted mt-3']);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
