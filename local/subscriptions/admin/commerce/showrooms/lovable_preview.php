<?php
// Gustave Builder: authenticated, private, read-only visual preview. NEVER publishes a design.
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgePreviewService.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeCompileService as Builder;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);
$key = required_param('job', PARAM_ALPHANUM);
$sessionjob = $SESSION->campusfrlovablejob ?? null;
if (!is_array($sessionjob)) {
    throw new moodle_exception('nopermissions', 'error');
}
$job = Builder::verify_session($sessionjob, (int)$USER->id, $key);
$id = $job['id'];
$archive = Preview::approved_archive($job['dir'], $id);
$frame = optional_param('frame', 0, PARAM_INT) === 1;

// In addition to the iframe sandbox attribute, CSP sandbox isolates the iframe
// even when someone opens its URL directly in a new browser tab.
if ($frame) {
    $html = Preview::render_frame($archive, $id);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: private, no-store, max-age=0');
    header('Referrer-Policy: no-referrer');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header("Content-Security-Policy: sandbox allow-scripts; default-src 'none'; "
        . "script-src data:; style-src data: 'unsafe-inline' https://fonts.googleapis.com; img-src data:; "
        . "font-src data: https://fonts.gstatic.com; media-src data:; object-src 'none'; "
        . "connect-src 'none'; form-action 'none'; frame-src 'none'; "
        . "worker-src 'none'; base-uri 'none'; frame-ancestors 'self';");
    echo $html;
    exit;
}

$url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_preview.php', ['job' => $key]);
$frameurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_preview.php', [
    'job' => $key, 'frame' => 1,
]);
$buildurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_build.php', [
    'job' => $key,
]);
$title = 'Aperçu privé — ' . $id;
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-lovable-preview-page');
$PAGE->requires->js_init_code(<<<'JAVASCRIPT'
(function () {
    var frame = document.getElementById('campusfr-lovable-preview-frame');
    var controls = document.querySelectorAll('[data-campusfr-preview-width]');
    if (!frame) { return; }
    controls.forEach(function (button) {
        button.addEventListener('click', function () {
            var width = button.getAttribute('data-campusfr-preview-width');
            frame.style.width = width === 'mobile' ? '390px' : '100%';
            frame.style.maxWidth = '100%';
            frame.style.height = width === 'mobile' ? '844px' : '960px';
            controls.forEach(function (item) {
                item.classList.toggle('active', item === button);
                item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
            });
        });
    });
})();
JAVASCRIPT);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_showroom_cms_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php')],
    ['label' => 'Importer depuis Lovable',
        'url' => new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable.php')],
    ['label' => 'Générer et compiler', 'url' => $buildurl],
    ['label' => 'Aperçu privé', 'url' => null],
]);
echo CrmPageHeader::render($title,
    'Aperçu visuel temporaire et non publiable : vérifie le design avant toute intégration Commerce.',
    HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::SHOWROOMS, $context);
// Plain status indicator, not an action or extra navigation control.
echo html_writer::start_div('d-flex align-items-center flex-wrap gap-2 mb-3 small',
    ['aria-label' => 'Étapes de l’import']);
foreach ([1 => 'Importer', 2 => 'Compiler', 3 => 'Examiner'] as $number => $label) {
    $current = $number === 3;
    echo html_writer::tag('span', $number . ' · ' . $label, [
        'class' => $current ? 'badge rounded-pill text-bg-primary px-3 py-2' :
            'badge rounded-pill text-bg-light border text-dark px-3 py-2',
        'title' => $current ? 'Étape actuelle' : 'Étape du parcours',
    ]);
}
echo html_writer::end_div();

echo html_writer::start_div('card card-body mb-3');
echo html_writer::tag('p', '<strong>Mode lecture seule.</strong> Ce brouillon contient le design React et les '
    . 'textes russes extraits. Les traductions FR/EN, les 17 attributs à vérifier et les '
    . 'paiements Commerce ne sont pas connectés. Les boutons de paiement sont inactifs.',
    ['class' => 'mb-2']);
echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2');
echo html_writer::link($buildurl, 'Retour au journal de compilation',
    ['class' => 'btn btn-outline-secondary btn-sm w-auto']);
$campusurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/preview.php', [
    'lovablejob' => $key,
]);
echo html_writer::link($campusurl, '<i class="fa fa-desktop me-1" aria-hidden="true"></i> Voir sur CampusFR',
    ['class' => 'btn btn-primary btn-sm w-auto', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
echo html_writer::link($frameurl, 'Ouvrir le brouillon seul',
    ['class' => 'btn btn-outline-primary btn-sm w-auto', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
    'Showrooms existants', ['class' => 'btn btn-outline-secondary btn-sm w-auto',
        'target' => '_blank', 'rel' => 'noopener noreferrer']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('card card-body mb-4');
echo html_writer::start_div('d-flex align-items-center gap-2 flex-wrap mb-3');
echo html_writer::tag('strong', 'Affichage : ', ['class' => 'me-1']);
echo html_writer::tag('button', 'Ordinateur', [
    'class' => 'btn btn-outline-secondary btn-sm w-auto active', 'type' => 'button',
    'data-campusfr-preview-width' => 'desktop', 'aria-pressed' => 'true',
]);
echo html_writer::tag('button', 'Mobile (390 px)', [
    'class' => 'btn btn-outline-secondary btn-sm w-auto', 'type' => 'button',
    'data-campusfr-preview-width' => 'mobile', 'aria-pressed' => 'false',
]);
echo html_writer::end_div();
echo html_writer::start_div('text-center', ['style' => 'overflow-x:auto;']);
echo html_writer::tag('iframe', '', [
    'id' => 'campusfr-lovable-preview-frame', 'src' => $frameurl->out(false),
    'title' => 'Aperçu privé du projet Lovable ' . $id,
    'sandbox' => 'allow-scripts', 'referrerpolicy' => 'no-referrer',
    'loading' => 'eager', 'style' => 'display:block;width:100%;max-width:100%;'
        . 'height:960px;margin:auto;border:1px solid #d1d5db;border-radius:12px;background:#101020;',
]);
echo html_writer::end_div();
echo html_writer::tag('p', 'La page est isolée des styles Moodle (iframe + Shadow DOM). Les polices externes '
    . 'Nunito / Caveat sont chargées depuis Google Fonts dans l’iframe isolée.',
    ['class' => 'text-muted small mt-2 mb-0']);
echo html_writer::tag('p', 'L’aperçu est réservé à ta session administrateur et expire avec le projet temporaire. '
    . 'Aucun fichier n’a été copié dans un dossier web public.', ['class' => 'text-muted small mt-3 mb-0']);
echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
