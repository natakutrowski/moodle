<?php
// X2.3A: authenticated CRM draft compilation. No publishing, no checkout changes.
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');
require_once(__DIR__ . '/../../../classes/task/lovable_build_task.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeCompileService as Builder;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
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
    throw new moodle_exception('nopermissions', 'error', '', 'Aucun projet préparé dans cette session.');
}
$job = Builder::verify_session($sessionjob, (int)$USER->id, $key);
$dir = $job['dir'];
$id = $job['id'];

$url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_build.php', ['job' => $key]);
$title = 'Générer et compiler un design Lovable';
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-lovable-build-page');

// Download is restricted to the current session and a completed, immutable job output.
$download = optional_param('download', 0, PARAM_INT);
if ($download === 1) {
    require_sesskey();
    $state = Builder::get_status($dir);
    $file = $dir . '/campusfr-lovable-' . $id . '-apercu-non-publiable.zip';
    if (!$state || $state['state'] !== 'ready' || !is_file($file) || is_link($file)) {
        throw new moodle_exception('filenotfound', 'error');
    }
    send_file($file, 'campusfr-lovable-' . $id . '-apercu-non-publiable.zip', 0, 0, false, true, 'application/zip');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();
    try {
        $action = optional_param('action', '', PARAM_ALPHA);
        if ($action === 'savecms') {
            $archive = Preview::approved_archive($dir, $id);
            $cmsid = Draft::create_from_ready_archive($DB, $USER, $archive, $id);
            redirect(new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_edit.php',
                ['id' => $cmsid]), 'Brouillon CMS créé et conservé dans Moodle.', null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
        if ($action !== 'compile') {
            throw new invalid_parameter_exception('Action inconnue.');
        }
        Builder::queue((int)$USER->id, $key, $id, $dir);
        redirect($url, 'Compilation ajoutée à la file des tâches Moodle.');
    } catch (\Throwable $exception) {
        $error = $exception->getMessage();
    }
}
$status = Builder::get_status($dir);
$availability = Builder::availability();

// Browser polling is presentation-only: cron does all compilation independently of the page.
if ($status && in_array($status['state'], ['queued', 'running'], true)) {
    $PAGE->requires->js_init_code('window.setTimeout(function(){window.location.reload();}, 7000);');
}

// Client-side clipboard action: copies only the log already displayed to this admin.
// Moodle initialises this code after the DOM is ready; nothing is sent to a server.
$PAGE->requires->js_init_code(<<<'JAVASCRIPT'
(function () {
    var button = document.getElementById('campusfr-copy-build-log');
    var source = document.getElementById('campusfr-build-log');
    var feedback = document.getElementById('campusfr-copy-build-feedback');
    if (!button || !source || !feedback) { return; }
    function report(ok) {
        feedback.textContent = ok ? 'Journal copié !' : 'Copie impossible : sélectionne le texte du journal.';
    }
    button.addEventListener('click', function () {
        var text = source.textContent || '';
        if (!text) { report(false); return; }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { report(true); }, function () { report(false); });
            return;
        }
        var area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', 'readonly');
        area.style.position = 'fixed';
        area.style.left = '-9999px';
        document.body.appendChild(area);
        area.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(area);
        report(ok);
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
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title,
    'Génère une version de travail du design, sans publier de showroom ni connecter de paiement.',
    HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::SHOWROOMS, $context);
// Plain status indicator, not an action or extra navigation control.
echo html_writer::start_div('d-flex align-items-center flex-wrap gap-2 mb-3 small',
    ['aria-label' => 'Étapes de l’import']);
foreach ([1 => 'Importer', 2 => 'Compiler', 3 => 'Examiner'] as $number => $label) {
    $current = $number === 2;
    echo html_writer::tag('span', $number . ' · ' . $label, [
        'class' => $current ? 'badge rounded-pill text-bg-primary px-3 py-2' :
            'badge rounded-pill text-bg-light border text-dark px-3 py-2',
        'title' => $current ? 'Étape actuelle' : 'Étape du parcours',
    ]);
}
echo html_writer::end_div();

if ($error !== '') {
    echo $OUTPUT->notification(s($error), 'error');
}
echo html_writer::start_div('card card-body mb-3 w-100', ['style' => 'max-width:1000px']);
echo html_writer::tag('h2', 'Projet : ' . s($id), ['class' => 'h4']);
echo html_writer::tag('p', 'Le projet préparé est conservé temporairement. La compilation crée uniquement un ZIP '
    . 'd’aperçu, à examiner avant une éventuelle intégration dans Commerce.', ['class' => 'text-muted']);
if (!$status) {
    if (!$availability['available']) {
        echo $OUTPUT->notification(s($availability['message']), 'warning');
    } else {
        echo html_writer::tag('p', s($availability['message']), ['class' => 'text-muted small']);
    }
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false),
        'class' => 'd-flex flex-wrap align-items-center gap-2']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'compile']);
    $buttonattributes = ['type' => 'submit', 'class' => 'btn btn-primary btn-sm w-auto'];
    if (!$availability['available']) {
        $buttonattributes['disabled'] = 'disabled';
    }
    echo html_writer::tag('button', '<i class="fa fa-cogs me-1" aria-hidden="true"></i> Générer et compiler',
        $buttonattributes);
    echo html_writer::end_tag('form');
} else {
    $states = [
        'queued' => 'En attente de Moodle', 'running' => 'Compilation en cours',
        'ready' => 'Aperçu compilé — à vérifier', 'error' => 'Compilation interrompue',
    ];
    echo html_writer::tag('p', '<strong>' . s($states[$status['state']] ?? 'État inconnu') . '</strong> · '
        . s((string)($status['message'] ?? '')), ['class' => 'mb-3']);
    if ($status['state'] === 'ready') {
        $link = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_build.php', [
            'job' => $key, 'download' => 1, 'sesskey' => sesskey(),
        ]);
        echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2');
        echo html_writer::link($link, 'Télécharger l’aperçu compilé (ZIP)', ['class' => 'btn btn-outline-secondary btn-sm w-auto']);
        $previewurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_preview.php', [
            'job' => $key,
        ]);
        $campusurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/preview.php', [
            'lovablejob' => $key,
        ]);
        echo html_writer::link($campusurl, '<i class="fa fa-desktop me-1" aria-hidden="true"></i> Prévisualiser sur CampusFR',
            ['class' => 'btn btn-primary btn-sm w-auto', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
        echo html_writer::link($previewurl, '<i class="fa fa-eye me-1" aria-hidden="true"></i> Voir l’aperçu privé',
            ['class' => 'btn btn-outline-secondary btn-sm w-auto']);
        echo html_writer::end_div();
        $existingcms = (new \local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository($DB))
            ->get_by_key(Draft::draft_key($id));
        if ($existingcms && Draft::metadata($existingcms) !== null) {
            echo html_writer::link(new moodle_url(
                '/local/subscriptions/admin/commerce/showrooms/lovable_cms_edit.php',
                ['id' => (int)$existingcms->id]
            ), 'Ouvrir le brouillon CMS existant', ['class' => 'btn btn-outline-primary btn-sm w-auto mt-3']);
        } else {
            echo html_writer::start_tag('form', [
                'method' => 'post', 'action' => $url->out(false),
                'class' => 'd-flex flex-wrap align-items-center gap-2 mt-3',
            ]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'savecms']);
            echo html_writer::tag('button', 'Créer un brouillon CMS durable', [
                'type' => 'submit', 'class' => 'btn btn-outline-primary btn-sm w-auto',
            ]);
            echo html_writer::end_tag('form');
        }
        echo html_writer::tag('p', 'Cet aperçu n’est pas un showroom publiable : traductions, '
            . 'médias, sécurité et fonctionnalités Commerce restent à valider.',
            ['class' => 'text-muted small mt-2']);
    }
}
echo html_writer::end_div();

echo html_writer::start_div('card card-body mb-4 w-100', ['style' => 'max-width:1000px']);
echo html_writer::tag('h2', 'Journal technique', ['class' => 'h4']);
echo html_writer::tag('p', 'Le journal ci-dessous contient les commandes et résultats réels de la tâche. '
    . 'Il sera rempli par Moodle lorsque le traitement commencera.', ['class' => 'text-muted small']);
$log = Builder::read_log($dir);
if ($log === '') {
    echo html_writer::tag('p', $status ? 'Le moteur n’a pas encore écrit de sortie.' :
        'Aucune compilation lancée.', ['class' => 'text-muted']);
} else {
    echo html_writer::tag('pre', s($log), [
        'id' => 'campusfr-build-log',
        'style' => 'white-space:pre-wrap;max-height:420px;overflow:auto;background:#1f2430;color:#f6f6f6;'
            . 'padding:16px;border-radius:8px;font-size:12px;',
    ]);
    echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2');
    echo html_writer::tag('button', '<i class="fa fa-copy me-1" aria-hidden="true"></i> Copier le journal', [
        'id' => 'campusfr-copy-build-log', 'type' => 'button', 'class' => 'btn btn-outline-primary btn-sm w-auto',
    ]);
    echo html_writer::tag('span', '', [
        'id' => 'campusfr-copy-build-feedback', 'role' => 'status', 'aria-live' => 'polite',
        'class' => 'small text-muted',
    ]);
    // The empty-log branch has no action row yet.
}
if ($log === '') {
    echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2');
}
echo html_writer::link($url, 'Actualiser le journal', ['class' => 'btn btn-outline-secondary btn-sm w-auto']);
echo html_writer::end_div();
echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
