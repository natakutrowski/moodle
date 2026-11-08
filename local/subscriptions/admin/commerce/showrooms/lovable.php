<?php
// This file is part of Moodle - http://moodle.org/.
// CampusFR 7.98X.2.2.1 - Lovable ZIP preparation wizard. DEV only until validated.

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');
// Moodle's download helper is not loaded by config.php in all entry points.
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeLovableUiService.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeLovableUiService as LovableUi;
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

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable.php');
$title = 'Importer depuis Lovable';
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-showroom-import-page');

// Never accept a pathname, an output folder, or a command from the HTTP client.
$jobroot = make_temp_directory('local_subscriptions/lovable_source_imports');
if (!$jobroot || !is_dir($jobroot)) {
    throw new moodle_exception('error');
}

$downloadkey = optional_param('download', '', PARAM_ALPHANUM);
if ($downloadkey !== '') {
    require_sesskey();
    $job = $SESSION->campusfrlovablejob ?? null;
    if (!LovableUi::valid_job_key($downloadkey) || !is_array($job)
            || !hash_equals((string)($job['key'] ?? ''), $downloadkey)
            || (int)($job['userid'] ?? 0) !== (int)$USER->id
            || time() - (int)($job['created'] ?? 0) > LovableUi::MAX_JOB_AGE) {
        throw new moodle_exception('nopermissions', 'error', '', 'Téléchargement expiré ou non autorisé.');
    }
    $id = (string)$job['id'];
    $file = $jobroot . '/' . (int)$USER->id . '_' . $downloadkey
        . '/campusfr-lovable-' . $id . '-a-revoir.zip';
    if (!is_file($file) || is_link($file)) {
        throw new moodle_exception('filenotfound', 'error');
    }
    // Serve a file path (not its literal text), force ZIP download, disable caching.
    // Unlike send_temp_file(), send_file() preserves the archive for repeat downloads.
    send_file($file, 'campusfr-lovable-' . $id . '-a-revoir.zip', 0, 0, false, true, 'application/zip');
    exit;
}

$error = '';
$success = null;
$idvalue = '';
$labelvalue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $idvalue = trim(optional_param('designid', '', PARAM_RAW_TRIMMED));
    $labelvalue = trim(optional_param('designlabel', '', PARAM_RAW_TRIMMED));
    try {
        // Never discard the source of an asynchronous compilation that is still running.
        $activejob = $SESSION->campusfrlovablejob ?? null;
        if (is_array($activejob) && LovableUi::valid_job_key((string)($activejob['key'] ?? ''))
                && (int)($activejob['userid'] ?? 0) === (int)$USER->id) {
            $activepath = $jobroot . '/' . (int)$USER->id . '_' . $activejob['key']
                . '/campusfr-compile-status.json';
            if (is_file($activepath) && !is_link($activepath)) {
                $active = json_decode((string)file_get_contents($activepath), true);
                if (is_array($active) && in_array(($active['state'] ?? ''), ['queued', 'running'], true)) {
                    throw new RuntimeException('Une compilation est en cours. Consulte son journal avant de préparer un autre projet.');
                }
            }
        }
        $identity = LovableUi::validate_identity($idvalue, $labelvalue);
        $upload = $_FILES['lovablezip'] ?? [];
        LovableUi::validate_upload_details(
            (string)($upload['name'] ?? ''),
            (int)($upload['size'] ?? 0),
            (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE)
        );
        $source = (string)($upload['tmp_name'] ?? '');
        if ($source === '' || !is_uploaded_file($source)) {
            throw new invalid_parameter_exception('Fichier ZIP non reçu correctement par Moodle.');
        }

        $key = bin2hex(random_bytes(16));
        $jobdir = $jobroot . '/' . (int)$USER->id . '_' . $key;
        if (!mkdir($jobdir, 0700) || is_link($jobdir)) {
            throw new RuntimeException('Impossible de créer le dossier de préparation privé.');
        }
        $sourcezip = $jobdir . '/lovable-upload.zip';
        if (!move_uploaded_file($source, $sourcezip)) {
            throw new RuntimeException('Impossible de déplacer le fichier reçu.');
        }
        @chmod($sourcezip, 0600);
        try {
            // Reading/rewriting ZIP bytes only; no user-supplied JS/TS/npm/PHP is executed.
            @set_time_limit(65);
            $report = LovableUi::prepare(
                $sourcezip,
                $identity['id'],
                $identity['label'],
                $jobdir,
                $CFG->dirroot . '/local/subscriptions/cli/bridge_lovable.py'
            );
        } finally {
            @unlink($sourcezip);
        }
        // Store only a compact summary in Moodle's session; the full report is in the ZIP.
        $previous = $SESSION->campusfrlovablejob ?? null;
        $SESSION->campusfrlovablejob = [
            'key' => $key,
            'userid' => (int)$USER->id,
            'id' => $identity['id'],
            'created' => time(),
            'report' => [
                'designId' => $report['designId'],
                'page' => $report['page'],
                'sourceFiles' => $report['sourceFiles'],
                'omittedCount' => count((array)$report['omittedFiles']),
            ],
        ];
        // Only one downloadable job is retained per admin session, to limit disk use.
        if (is_array($previous) && (int)($previous['userid'] ?? 0) === (int)$USER->id
                && LovableUi::valid_job_key((string)($previous['key'] ?? ''))
                && !hash_equals((string)$previous['key'], $key)) {
            $oldpath = $jobroot . '/' . (int)$USER->id . '_' . $previous['key'];
            if (is_dir($oldpath) && !is_link($oldpath)) {
                remove_dir($oldpath);
            }
        }
        $success = $SESSION->campusfrlovablejob;
    } catch (\Throwable $exception) {
        // Render an actionable error in the wizard, do not expose file contents or paths.
        $error = $exception->getMessage();
    }
} else {
    $previous = $SESSION->campusfrlovablejob ?? null;
    if (is_array($previous) && (int)($previous['userid'] ?? 0) === (int)$USER->id
            && time() - (int)($previous['created'] ?? 0) < LovableUi::MAX_JOB_AGE) {
        $success = $previous;
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_showroom_cms_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title,
    'Dépose un export ZIP Lovable pour préparer les fichiers d’un nouveau showroom, sans le publier.',
    HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::SHOWROOMS, $context);
// Plain status indicator, not an action or extra navigation control.
echo html_writer::start_div('d-flex align-items-center flex-wrap gap-2 mb-3 small',
    ['aria-label' => 'Étapes de l’import']);
foreach ([1 => 'Importer', 2 => 'Compiler', 3 => 'Examiner'] as $number => $label) {
    $current = $number === 1;
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
if ($success !== null) {
    $report = $success['report'];
    $url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable.php', [
        'download' => $success['key'],
        'sesskey' => sesskey(),
    ]);
    echo html_writer::start_div('card card-body mb-4 w-100', ['style' => 'max-width:1000px']);
    echo html_writer::tag('h2', 'Projet préparé !', ['class' => 'h4']);
    echo html_writer::tag('p', 'Le ZIP a été analysé et les fichiers de préparation sont prêts. '
        . 'Ce projet doit encore être vérifié et finalisé avant de pouvoir être publié.');
    echo html_writer::tag('p', '<strong>' . s((string)($report['designId'] ?? '')) . '</strong> · '
        . s((string)($report['page'] ?? '')) . ' · '
        . (int)($report['sourceFiles'] ?? 0) . ' fichiers copiés · '
        . (int)($report['omittedCount'] ?? 0) . ' ignorés.');
    echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2');
    echo html_writer::link($url,
        '<i class="fa fa-download me-1" aria-hidden="true"></i> Télécharger le projet préparé (ZIP)',
        ['class' => 'btn btn-outline-secondary btn-sm w-auto']);
    $buildurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_build.php', [
        'job' => $success['key'],
    ]);
    echo html_writer::link($buildurl,
        '<i class="fa fa-cogs me-1" aria-hidden="true"></i> Générer et compiler',
        ['class' => 'btn btn-primary btn-sm w-auto']);
    echo html_writer::end_div();
    echo html_writer::tag('p', 'À télécharger sous 2 heures. La revue du code, les traductions, '
        . 'le raccordement Commerce et le build restent obligatoires avant tout déploiement.',
        ['class' => 'text-muted small mt-3 mb-0']);
    echo html_writer::end_div();
}

echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'action' => $pageurl->out(false),
    'class' => 'card card-body mb-4 w-100',
    'style' => 'max-width:1000px',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('h2', 'Préparer un nouvel export Lovable', ['class' => 'h4']);
echo html_writer::tag('p', 'Préparation uniquement : aucun design existant n’est modifié, '
    . 'aucune publication ni aucun paiement n’est déclenché.', ['class' => 'text-muted']);
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', 'Archive ZIP exportée depuis Lovable',
    ['for' => 'lovablezip', 'class' => 'form-label fw-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'file', 'name' => 'lovablezip', 'id' => 'lovablezip',
    'class' => 'form-control', 'accept' => '.zip,application/zip', 'required' => 'required',
]);
echo html_writer::tag('div', 'Maximum : 20 Mo compressés. Un seul projet Lovable par ZIP.',
    ['class' => 'form-text']);
echo html_writer::end_div();
echo html_writer::start_div('row g-3 mb-3');
echo html_writer::start_div('col-md-5');
echo html_writer::tag('label', 'Identifiant du design', ['for' => 'designid', 'class' => 'form-label fw-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'designid', 'id' => 'designid',
    'class' => 'form-control', 'value' => $idvalue,
    'pattern' => '[a-z][a-z0-9_]{2,47}', 'maxlength' => '48',
    'placeholder' => 'exemple_articles_a2', 'required' => 'required',
]);
echo html_writer::end_div();
echo html_writer::start_div('col-md-7');
echo html_writer::tag('label', 'Nom du showroom', ['for' => 'designlabel', 'class' => 'form-label fw-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'designlabel', 'id' => 'designlabel',
    'class' => 'form-control', 'value' => $labelvalue,
    'maxlength' => '150', 'placeholder' => 'Nouveau showroom CampusFR',
    'required' => 'required',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::tag('button',
    '<i class="fa fa-wand-magic-sparkles me-1" aria-hidden="true"></i> Préparer le design',
    ['type' => 'submit', 'class' => 'btn btn-primary align-self-start w-auto px-4']);
echo html_writer::end_tag('form');

echo html_writer::start_div('card card-body w-100', ['style' => 'max-width:1000px']);
echo html_writer::tag('h2', 'Étapes suivantes', ['class' => 'h5']);
echo html_writer::start_tag('ol', ['class' => 'mb-2 ps-4']);
echo html_writer::tag('li', 'Télécharge le ZIP préparé et fais vérifier le code du design.');
echo html_writer::tag('li', 'Vérifie les textes et prépare les traductions.');
echo html_writer::tag('li', 'Raccorde les offres à Commerce : panier, tarifs, devises et dons.');
echo html_writer::tag('li', 'Compile le design et teste le showroom sur DEV avant sa publication.');
echo html_writer::end_tag('ol');
echo html_writer::tag('p', 'La préparation ne connecte pas automatiquement les moyens de paiement.',
    ['class' => 'text-muted small mb-0']);
echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
