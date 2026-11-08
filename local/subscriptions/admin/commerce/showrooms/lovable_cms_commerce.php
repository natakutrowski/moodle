<?php
// Gustave Builder: map real Commerce products to imported design cards, admin review only.
declare(strict_types=1);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableCommerceLink as Link;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductLinkOptions;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);
$id = required_param('id', PARAM_INT);
[$record, $metadata] = Draft::require_draft($DB, $id);
$currency = optional_param('currency', 'EUR', PARAM_ALPHA);
$activecurrencies = CommerceShowroomCurrencyResolver::active_currencies($DB);
$currency = strtoupper($currency);
if (!in_array($currency, $activecurrencies, true)) {
    $currency = (string)($activecurrencies[0] ?? 'EUR');
}
$url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_commerce.php', [
    'id' => $id, 'currency' => $currency,
]);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();
    if (required_param('action', PARAM_ALPHA) !== 'save') {
        throw new invalid_parameter_exception('Action Commerce invalide.');
    }
    $submitted = optional_param_array('sku', [], PARAM_RAW_TRIMMED);
    if (!is_array($submitted)) {
        throw new invalid_parameter_exception('SKU Commerce invalides.');
    }
    Link::save($DB, $id, $submitted, (int)$USER->id);
    redirect($url, 'Associations Commerce enregistrées. Aucune vente activée.', null,
        \core\output\notification::NOTIFY_SUCCESS);
}
CrmPageConfigurator::configure($PAGE, $context, $url,
    'Brouillon Lovable · Commerce', 'local-subscriptions-lovable-cms-commerce');
$selected = Link::selected($record);
$error = '';
$review = ['offers' => [], 'selected' => $selected, 'currency' => $currency];
if (array_filter($selected, static fn(string $sku): bool => $sku !== '')) {
    try {
        $review = Link::read_only_review($DB, $id, $currency);
    } catch (\Throwable $exception) {
        $error = $exception->getMessage();
    }
}
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo html_writer::tag('h1', 'Brouillon CMS Lovable — Commerce', ['class' => 'h3']);
echo $OUTPUT->notification('Raccordement de données uniquement : catalogue, disponibilité et prix sont '
    . 'gérés par Commerce. Les montants actuellement inscrits dans le JSX Lovable sont encore fictifs ; '
    . 'le checkout, Stripe, Alfa et la publication restent INACTIFS.', 'warning');
echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2 mb-3');
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_edit.php',
    ['id' => $id]), 'Textes et traductions', ['class' => 'btn btn-sm btn-outline-secondary w-auto']);
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/showrooms/preview.php',
    ['id' => $id]), 'Voir le design Lovable', [
        'class' => 'btn btn-sm btn-outline-primary w-auto', 'target' => '_blank', 'rel' => 'noopener noreferrer',
    ]);
echo html_writer::end_div();
echo html_writer::start_div('card card-body mb-3', ['style' => 'max-width:1000px']);
echo html_writer::tag('h2', 'Associer les trois cartes à des produits existants', ['class' => 'h5']);
echo html_writer::tag('p', 'Chaque référence est contrôlée par le même catalogue que l’éditeur Commerce '
    . 'natif. Laisser vide si une offre n’est pas encore configurée.', ['class' => 'text-muted small']);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
foreach (Link::CARD_ROLES as $role => $label) {
    $roleoptions = (new CommerceShowroomProductLinkOptions($DB))->options_for_role($role, $selected[$role]);
    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', s($label) . ' · ' . s($role), [
        'for' => 'sku_' . $role, 'class' => 'form-label fw-semibold',
    ]);
    echo html_writer::select($roleoptions, 'sku[' . $role . ']', $selected[$role],
        ['' => 'Aucun produit associé'], [
            'id' => 'sku_' . $role, 'class' => 'form-select', 'style' => 'max-width:680px',
        ]);
    echo html_writer::end_div();
}
echo html_writer::tag('button', 'Enregistrer les associations', [
    'type' => 'submit', 'class' => 'btn btn-primary btn-sm w-auto',
]);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::start_div('card card-body', ['style' => 'max-width:1000px']);
echo html_writer::tag('h2', 'Tarifs réels — lecture seule', ['class' => 'h5']);
$changeurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_commerce.php',
    ['id' => $id]);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $changeurl->out(false),
    'class' => 'd-flex gap-2 align-items-end flex-wrap mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::start_div();
echo html_writer::tag('label', 'Devise active', ['class' => 'form-label small', 'for' => 'commerce_currency']);
echo html_writer::select(array_combine($activecurrencies, $activecurrencies), 'currency', $currency, false,
    ['id' => 'commerce_currency', 'class' => 'form-select form-select-sm']);
echo html_writer::end_div();
echo html_writer::tag('button', 'Afficher les tarifs', [
    'type' => 'submit', 'class' => 'btn btn-outline-secondary btn-sm w-auto',
]);
echo html_writer::end_tag('form');
if ($error !== '') {
    echo $OUTPUT->notification(s('Résolution Commerce : ' . $error), 'warning');
}
foreach (Link::CARD_ROLES as $role => $label) {
    $sku = (string)($selected[$role] ?? '');
    $offer = $review['offers'][$role] ?? null;
    $state = $sku === '' ? 'Non associé' : ($offer === null ? 'Référence à vérifier' :
        (!empty($offer['hasprice']) ? (string)$offer['priceformatted'] : 'Prix indisponible'));
    echo html_writer::start_div('d-flex align-items-center justify-content-between gap-3 border-bottom py-3');
    echo html_writer::tag('span', s($label) . '<small class="d-block text-muted">' .
        s($sku === '' ? 'Aucun SKU' : $sku) . '</small>');
    echo html_writer::tag('strong', s($state));
    echo html_writer::end_div();
}
echo html_writer::tag('p', 'Important : ces prix proviennent exclusivement du résolveur de Commerce. '
    . 'Les montants affichés dans le design React ne sont pas encore synchronisés et aucun bouton '
    . 'ne peut lancer un achat.', ['class' => 'small text-muted mt-3']);
echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
