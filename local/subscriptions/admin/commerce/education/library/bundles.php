<?php
// D2.6 — Private export/import bundles; QA only; no checkout, grants or publication.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderLibraryBundleService as Bundle;
use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Recueils;

if (!has_capability('moodle/site:config', \context_system::instance())) {
    throw new \moodle_exception('nopermissions', 'error', '', 'transfert des collections');
}
// D2.17.3.0 : contrôle en lecture seule des copies QA dans le CRM.
if (optional_param('view', '', PARAM_ALPHA) === 'unifiedplan') {
    require(__DIR__ . '/_unified_qa_plan.php');
    return;
}
if (optional_param('view', '', PARAM_ALPHA) === 'unified') {
    require(__DIR__ . '/_unified_qa_audit.php');
    return;
}
global $DB, $SESSION, $USER;
$root = make_temp_directory('cfr-library-bundles');
if (!is_dir($root)) { throw new \RuntimeException(get_string('cfr_i18n_bundle_temp_error', 'local_subscriptions')); }
function cfr_d26_destroy(): void {
    global $SESSION;
    $state = $SESSION->cfr_d26_stage ?? null;
    if (is_array($state) && is_string($state['path'] ?? null)) {
        $path = $state['path'];
        if (!is_link($path) && is_file($path) &&
            realpath(dirname($path)) === realpath(make_temp_directory('cfr-library-bundles')) &&
            preg_match('/\Acfrbundle-[0-9]+-[a-f0-9]{32}\.zip\z/D', basename($path))) {
            @unlink($path);
        }
    }
    unset($SESSION->cfr_d26_stage);
}
function cfr_d26_stage(): array {
    global $USER, $SESSION;
    $v = $SESSION->cfr_d26_stage ?? null;
    if (!is_array($v) || ($v['user'] ?? null) !== (int)$USER->id ||
        !is_int($v['at'] ?? null) || $v['at'] < time() - 1800 || $v['at'] > time() + 30 ||
        !is_string($v['path'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/D', (string)($v['sha'] ?? '')) ||
        realpath(dirname($v['path'])) !== realpath(make_temp_directory('cfr-library-bundles')) ||
        !preg_match('/\Acfrbundle-[0-9]+-[a-f0-9]{32}\.zip\z/D', basename($v['path'])) ||
        is_link($v['path']) || !is_file($v['path']) ||
        !hash_equals($v['sha'], hash_file('sha256', $v['path']))) {
        throw new \InvalidArgumentException(get_string('cfr_i18n_bundle_expired', 'local_subscriptions'));
    }
    return $v;
}
foreach (glob($root . '/cfrbundle-*.zip') ?: [] as $candidate) {
    if (preg_match('/\Acfrbundle-[0-9]+-[a-f0-9]{32}\.zip\z/D', basename($candidate)) &&
        !is_link($candidate) && is_file($candidate) && filemtime($candidate) < time() - 7200) {
        @unlink($candidate);
    }
}
$error = ''; $notice = ''; $summary = null; $plan = null;
$chosenSlug = ''; $chosenCollection = 0; $chosenCourse = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = optional_param('operation', '', PARAM_ALPHA);
    try {
        if ($action === 'analyse') {
            cfr_d26_destroy();
            $file = $_FILES['bundlezip'] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
                !is_uploaded_file($file['tmp_name'] ?? '') || (int)($file['size'] ?? 0) < 100 ||
                (int)($file['size'] ?? 0) > 2147483648) {
                throw new \InvalidArgumentException(get_string('cfr_i18n_bundle_zip_invalid', 'local_subscriptions'));
            }
            $temp = $root . '/cfrbundle-' . (int)$USER->id . '-' . bin2hex(random_bytes(16)) . '.zip';
            if (!move_uploaded_file($file['tmp_name'], $temp)) {
                throw new \RuntimeException(get_string('cfr_i18n_bundle_temp_unavailable', 'local_subscriptions'));
            }
            chmod($temp, 0600);
            try {
                $summary = Bundle::inspect_zip($temp);
                $SESSION->cfr_d26_stage = ['user' => (int)$USER->id, 'path' => $temp,
                    'sha' => $summary['zipsha256'], 'at' => time(), 'plan' => null];
            } catch (\Throwable $e) { @unlink($temp); throw $e; }
        } else if ($action === 'plan' || $action === 'import') {
            $state = cfr_d26_stage();
            $summary = Bundle::inspect_zip($state['path']);
            $chosenSlug = required_param('newslug', PARAM_RAW_TRIMMED);
            $chosenCollection = optional_param('qacollectionid', 0, PARAM_INT);
            $chosenCourse = optional_param('qacourseid', 0, PARAM_INT);
            $plan = Bundle::plan_qa($summary['manifest'], $chosenSlug, $chosenCollection, $chosenCourse);
            $identity = hash('sha256', implode('|', [
                $summary['zipsha256'], $chosenSlug, (string)$chosenCollection, (string)$chosenCourse,
            ]));
            if ($action === 'plan') {
                $SESSION->cfr_d26_stage['plan'] = $plan['ready'] ? $identity : null;
            } else {
                if (!$plan['ready'] || !hash_equals((string)($state['plan'] ?? ''), $identity) ||
                    optional_param('confirm', 0, PARAM_INT) !== 1) {
                    throw new \InvalidArgumentException(get_string('cfr_i18n_bundle_plan_invalid', 'local_subscriptions'));
                }
                $result = Bundle::import_qa($state['path'], $state['sha'], (int)$USER->id,
                    $chosenSlug, $chosenCollection, $chosenCourse);
                cfr_d26_destroy();
                $url = $result['kind'] === 'anthology' ? 'anthology.php' : 'collection.php';
                redirect(cfr_d21_url($url, ['id' => $result['id']]),
                    s(get_string('cfr_i18n_bundle_importsuccess', 'local_subscriptions')) . '' . $result['stories'] . s(get_string('cfr_i18n_bundle_importbooks', 'local_subscriptions')) . '' . $result['revisions'] . s(get_string('cfr_i18n_bundle_importrevisions', 'local_subscriptions')) . '');
            }
        } else if ($action === 'cancel') {
            cfr_d26_destroy(); $notice = get_string('cfr_i18n_bundle_cancelled', 'local_subscriptions');
        } else {
            throw new \InvalidArgumentException(get_string('cfr_i18n_bundle_unknown', 'local_subscriptions'));
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}
if (!$summary && !empty($SESSION->cfr_d26_stage)) {
    try { $summary = Bundle::inspect_zip(cfr_d26_stage()['path']); }
    catch (\Throwable $e) { cfr_d26_destroy(); $error = $e->getMessage(); }
}
if ($summary && $chosenSlug === '') {
    $chosenSlug = substr('qa-' . $summary['slug'], 0, 64);
    $chosenSlug = rtrim($chosenSlug, '-');
}
$anthologies = Recueils::list_all();
$collections = Editorial::collections();
$calendars = [];
foreach ($collections as $c) {
    $needed = \local_subscriptions\crm\library\ReaderEditorialCalendarService::count($c);
    if ($needed >= 1 && $needed <= 31 &&
        $DB->count_records('local_cr_slot', ['collectionid' => (int)$c->id]) >= $needed) {
        $calendars[] = $c;
    }
}
$qa = [];
foreach ($collections as $collection) {
    if ((int)$collection->published === 0 &&
        preg_match('/(?:^|-)(?:qa|test)(?:-|$)/', (string)$collection->slug)) { $qa[] = $collection; }
}
$courses = [];
foreach ($DB->get_records_select('course', 'id <> :siteid', ['siteid' => 1], 'id ASC',
    'id,fullname,shortname', 0, 1000) as $c) {
    if (!preg_match('/qa|test/i', (string)$c->shortname . ' ' . (string)$c->fullname) ||
        $DB->record_exists('local_cr_collection', ['courseid' => $c->id])) { continue; }
    $courses[] = $c;
}
$title = get_string('cfr_d26_title', 'local_subscriptions');
cfr_d21_start($title, get_string('cfr_d26_intro', 'local_subscriptions'),
    [['label' => $title, 'url' => null]]);
if ($error) { echo '<div class="alert alert-danger" role="alert">' . s($error) . '</div>'; }
if ($notice) { echo '<div class="alert alert-info">' . s($notice) . '</div>'; }
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d26_safety', 'local_subscriptions')) . '</p>';
echo '<div class="cfr-reader-editor__grid">';
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d26_export', 'local_subscriptions')) . '</h3>';
echo '<p>' . s(get_string('cfr_d26_export_help', 'local_subscriptions')) . '</p>';
foreach (['anthology' => $anthologies, 'calendar' => $calendars] as $exportkind => $items) {
    echo '<h4>' . s($exportkind === 'anthology' ? get_string('cfr_i18n_bundle_anthology', 'local_subscriptions') : get_string('cfr_i18n_bundle_calendar', 'local_subscriptions')) . '</h4>';
    echo '<form class="cfr-reader-editor__transfer-form" method="post" action="' . s(cfr_d21_url('bundle_export.php')->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="kind" value="' . s($exportkind) . '">';
    echo '<label for="d26-' . s($exportkind) . '">' . s(get_string('cfr_d26_source', 'local_subscriptions')) . '</label>';
    echo '<select id="d26-' . s($exportkind) . '" name="id" class="form-control" required>';
    foreach ($items as $item) {
        echo '<option value="' . (int)$item->id . '">' . s($item->title . ' — ' . $item->slug) . '</option>';
    }
    echo '</select><p><button type="submit" class="btn btn-primary"' . (!$items ? ' disabled' : '') . '>' .
        s(get_string('cfr_d26_download', 'local_subscriptions')) . '</button></p></form>';
}
echo '</section>';
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d26_import', 'local_subscriptions')) . '</h3>';
if (!$summary) {
    echo '<form class="cfr-reader-editor__transfer-form" method="post" enctype="multipart/form-data" action="' . s(cfr_d21_url('bundles.php')->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '"><input type="hidden" name="operation" value="analyse">';
    echo '<label for="d26file">ZIP</label><input type="file" id="d26file" name="bundlezip" accept=".zip,application/zip" class="form-control" required>';
    echo '<p><button type="submit" class="btn btn-primary">' . s(get_string('cfr_d26_analyse', 'local_subscriptions')) . '</button></p></form>';
} else {
    echo '<div class="cfr-reader-editor__info"><strong>' . s($summary['title']) . '</strong><p>' . s($summary['kind']) .
        ' · ' . count($summary['stories']) . s(get_string('cfr_i18n_bundle_stories', 'local_subscriptions')) . '' . $summary['revisions'] . s(get_string('cfr_i18n_bundle_versions', 'local_subscriptions')) . '' . $summary['slots'] . s(get_string('cfr_i18n_bundle_slots', 'local_subscriptions')) . '</p>';
    echo '<p>SHA-256 : <code>' . s($summary['zipsha256']) . '</code></p></div>';
    echo '<div><strong>' . s(get_string('cfr_d26_content', 'local_subscriptions')) . '</strong><ul>';
    foreach ($summary['stories'] as $story) {
        echo '<li>' . s($story['title']) . ' (' . (int)$story['pages'] . ' pages, ' . (int)$story['media'] . s(get_string('cfr_i18n_bundle_media', 'local_subscriptions')) . '</li>';
    }
    echo '</ul></div>';
    echo '<form class="cfr-reader-editor__transfer-form" method="post" action="' . s(cfr_d21_url('bundles.php')->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<label for="d26slug">' . s(get_string('cfr_d26_newslug', 'local_subscriptions')) . '</label>';
    echo '<input type="text" id="d26slug" class="form-control" name="newslug" value="' . s($chosenSlug) . '" maxlength="64" pattern="[a-z0-9]+(-[a-z0-9]+)*" required>';
    if ($summary['kind'] === 'anthology') {
        echo '<label for="d26target">' . s(get_string('cfr_d26_targetcoll', 'local_subscriptions')) . '</label>';
        echo '<select class="form-control" id="d26target" name="qacollectionid" required>';
        foreach ($qa as $c) { echo '<option value="' . (int)$c->id . '"' . ((int)$chosenCollection === (int)$c->id ? ' selected' : '') . '>' . s($c->title) . '</option>'; }
        echo '</select>';
    } else {
        echo '<label for="d26course">' . s(get_string('cfr_d26_targetcourse', 'local_subscriptions')) . '</label>';
        echo '<select class="form-control" id="d26course" name="qacourseid" required>';
        foreach ($courses as $c) { echo '<option value="' . (int)$c->id . '"' . ((int)$chosenCourse === (int)$c->id ? ' selected' : '') . '>' . s($c->fullname) . ' (#' . (int)$c->id . ')</option>'; }
        echo '</select><p class="cfr-reader-editor__hint">' . s(get_string('cfr_d26_legacycourse', 'local_subscriptions')) . '</p>';
    }
    echo '<p><button class="btn btn-outline-primary" type="submit" name="operation" value="plan">' .
        s(get_string('cfr_d26_plan', 'local_subscriptions')) . '</button></p>';
    if ($plan) {
        echo '<div class="cfr-reader-editor__info"><strong>' . s($plan['ready'] ? 'READY_FOR_QA_IMPORT' : 'BLOCKED') . '</strong>';
        foreach ($plan['problems'] as $p) { echo '<p>' . s($p) . '</p>'; }
        echo '</div>';
        if ($plan['ready']) {
            echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' .
                s(get_string('cfr_d26_confirm', 'local_subscriptions')) . '</label></p>';
            echo '<button class="btn btn-primary" type="submit" name="operation" value="import">' .
                s(get_string('cfr_d26_execute', 'local_subscriptions')) . '</button>';
        }
    }
    echo '</form><form class="cfr-reader-editor__transfer-form" method="post" action="' . s(cfr_d21_url('bundles.php')->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '"><input type="hidden" name="operation" value="cancel">';
    echo '<button type="submit" class="btn btn-outline-secondary">' . s(get_string('cancel')) . '</button></form>';
}
echo '</section></div>';
echo '<p><a class="btn btn-outline-primary" href="' . s(cfr_d21_url('bundles.php', ['view' => 'unified'])->out(false)) . '">' . s(get_string('cfr_i18n_bundle_qa_audit', 'local_subscriptions')) . '</a></p>';
echo '<p><a class="btn btn-outline-primary" href="' . s(cfr_d21_url('bundles.php', ['view' => 'unifiedplan'])->out(false)) . '">' . s(get_string('cfr_i18n_bundle_qa_plan', 'local_subscriptions')) . '</a></p>';
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d26_limits', 'local_subscriptions')) . '</p>';
cfr_d21_end();
