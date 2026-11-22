<?php
// CampusFR D2.5 — CRM editorial story export/import, QA-only non-destructive intake.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderStoryTransferService as Transfer;

if (!has_capability('moodle/site:config', \context_system::instance())) {
    throw new \moodle_exception('nopermissions', 'error', '', 'transfert d’histoires');
}
global $CFG, $SESSION, $USER;
$root = make_temp_directory('cfr-story-transfer');
$realroot = realpath($root);
if ($realroot === false) { throw new \RuntimeException(get_string('cfr_fix_transfers_01', 'local_subscriptions')); }

function cfr_d25_delete_stage(): void {
    global $SESSION;
    $stage = $SESSION->cfr_d25_stage ?? null;
    if (is_array($stage) && isset($stage['path']) && is_string($stage['path'])) {
        $candidate = $stage['path'];
        $dir = realpath(dirname($candidate));
        if ($dir === realpath(make_temp_directory('cfr-story-transfer')) &&
            preg_match('/\Acfrstory-[0-9]+-[a-f0-9]{32}\.zip\z/D', basename($candidate)) &&
            !is_link($candidate) && is_file($candidate)) { @unlink($candidate); }
    }
    unset($SESSION->cfr_d25_stage);
}
function cfr_d25_stage_path(): string {
    global $USER, $SESSION;
    $stage = $SESSION->cfr_d25_stage ?? null;
    if (!is_array($stage) || ($stage['userid'] ?? null) !== (int)$USER->id ||
        ($stage['created'] ?? 0) < time() - 1800 || ($stage['created'] ?? 0) > time() + 30 ||
        !isset($stage['path'], $stage['sha256']) || !is_string($stage['path']) ||
        !preg_match('/\A[a-f0-9]{64}\z/D', (string)$stage['sha256'])) {
        throw new \InvalidArgumentException(get_string('cfr_fix_transfers_02', 'local_subscriptions'));
    }
    $p = $stage['path'];
    if (realpath(dirname($p)) !== realpath(make_temp_directory('cfr-story-transfer')) ||
        !preg_match('/\Acfrstory-[0-9]+-[a-f0-9]{32}\.zip\z/D', basename($p)) ||
        is_link($p) || !is_file($p) || !hash_equals($stage['sha256'], hash_file('sha256', $p))) {
        throw new \InvalidArgumentException(get_string('cfr_fix_transfers_03', 'local_subscriptions'));
    }
    return $p;
}
// Discard old temporary uploads left behind by interrupted sessions.
foreach (glob($root . '/cfrstory-*.zip') ?: [] as $file) {
    if (preg_match('/\Acfrstory-[0-9]+-[a-f0-9]{32}\.zip\z/D', basename($file)) &&
        is_file($file) && !is_link($file) && filemtime($file) < time() - 7200) { @unlink($file); }
}
$error = ''; $notice = ''; $summary = null; $newbookid = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = optional_param('transfer_action', '', PARAM_ALPHA);
    try {
        if ($action === 'analyse') {
            cfr_d25_delete_stage();
            $file = $_FILES['storyzip'] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
                !isset($file['tmp_name'], $file['size']) ||
                !is_uploaded_file($file['tmp_name']) || (int)$file['size'] <= 0 ||
                (int)$file['size'] > 268435456) {
                throw new \InvalidArgumentException(get_string('cfr_fix_transfers_04', 'local_subscriptions'));
            }
            $token = bin2hex(random_bytes(16));
            $target = $root . '/cfrstory-' . (int)$USER->id . '-' . $token . '.zip';
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                throw new \RuntimeException(get_string('cfr_fix_transfers_05', 'local_subscriptions'));
            }
            chmod($target, 0600);
            try {
                $summary = Transfer::inspect_zip($target);
                $SESSION->cfr_d25_stage = ['path' => $target, 'userid' => (int)$USER->id,
                    'sha256' => $summary['zipsha256'], 'created' => time()];
            } catch (\Throwable $e) { @unlink($target); throw $e; }
        } else if ($action === 'import') {
            if (optional_param('confirm', 0, PARAM_INT) !== 1) {
                throw new \InvalidArgumentException(get_string('cfr_fix_transfers_06', 'local_subscriptions'));
            }
            $path = cfr_d25_stage_path();
            $digest = required_param('archive_sha256', PARAM_RAW_TRIMMED);
            if (!hash_equals((string)$SESSION->cfr_d25_stage['sha256'], $digest)) {
                throw new \InvalidArgumentException(get_string('cfr_fix_transfers_07', 'local_subscriptions'));
            }
            $collectionid = required_param('targetcollection', PARAM_INT);
            $newslug = required_param('newslug', PARAM_RAW_TRIMMED);
            $newbookid = Transfer::import_qa($path, $collectionid, $newslug, (int)$USER->id, $digest);
            cfr_d25_delete_stage();
            redirect(cfr_d21_url('book.php', ['id' => $newbookid]),
                get_string('cfr_d25_import_success', 'local_subscriptions'));
        } else if ($action === 'cancel') {
            cfr_d25_delete_stage();
            $notice = get_string('cfr_d25_cancelled', 'local_subscriptions');
        } else {
            throw new \InvalidArgumentException(get_string('cfr_fix_transfers_08', 'local_subscriptions'));
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
        if (isset($SESSION->cfr_d25_stage)) {
            try { $summary = Transfer::inspect_zip(cfr_d25_stage_path()); } catch (\Throwable $unused) { cfr_d25_delete_stage(); }
        }
    }
}
if (!$summary && !empty($SESSION->cfr_d25_stage)) {
    try { $summary = Transfer::inspect_zip(cfr_d25_stage_path()); }
    catch (\Throwable $e) { cfr_d25_delete_stage(); }
}
$collections = Editorial::collections();
$stories = [];
$qa = [];
foreach ($collections as $collection) {
    if ((int)$collection->published === 0 && preg_match('/(?:^|-)(qa|test)(?:-|$)/', (string)$collection->slug)) {
        $qa[] = $collection;
    }
    foreach (Editorial::books((int)$collection->id) as $book) {
        $stories[] = [$collection, $book];
    }
}
$title = get_string('cfr_d25_title', 'local_subscriptions');
cfr_d21_start($title, get_string('cfr_d25_intro', 'local_subscriptions'),
    [['label' => $title, 'url' => null]]);
if ($error !== '') { echo '<div class="alert alert-danger" role="alert">' . s($error) . '</div>'; }
if ($notice !== '') { echo '<div class="alert alert-info">' . s($notice) . '</div>'; }
echo '<div class="cfr-reader-editor__grid">';
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d25_export', 'local_subscriptions')) . '</h3>';
echo '<p>' . s(get_string('cfr_d25_export_help', 'local_subscriptions')) . '</p>';
echo '<form class="cfr-reader-editor__transfer-form" method="post" action="' . s(cfr_d21_url('story_export.php')->out(false)) . '">';
echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
echo '<label for="cfr-d25-source">' . s(get_string('cfr_d25_source', 'local_subscriptions')) . '</label>';
echo '<select class="form-control" id="cfr-d25-source" name="bookid" required>';
foreach ($stories as [$collection, $book]) {
    echo '<option value="' . (int)$book->id . '">' . s($collection->title . ' — ' . $book->title) . '</option>';
}
echo '</select><p><button class="btn btn-primary" type="submit"' . (!$stories ? ' disabled' : '') . '>' .
    s(get_string('cfr_d25_download', 'local_subscriptions')) . '</button></p></form></section>';
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d25_import', 'local_subscriptions')) . '</h3>';
echo '<p>' . s(get_string('cfr_d25_import_help', 'local_subscriptions')) . '</p>';
if (!$summary) {
    echo '<form class="cfr-reader-editor__transfer-form" method="post" enctype="multipart/form-data" action="' . s(cfr_d21_url('transfers.php')->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="transfer_action" value="analyse">';
    echo '<label for="cfr-d25-file">' . s(get_string('cfr_d25_zip', 'local_subscriptions')) . '</label>';
    echo '<input class="form-control" id="cfr-d25-file" type="file" name="storyzip" accept=".zip,application/zip" required>';
    echo '<p><button type="submit" class="btn btn-primary">' .
        s(get_string('cfr_d25_analyse', 'local_subscriptions')) . '</button></p></form>';
} else {
    echo '<div class="cfr-reader-editor__info"><strong>' . s($summary['title']) . '</strong><p>Slug : <code>' .
        s($summary['slug']) . '</code> · ' . (int)$summary['pages'] . ' pages · ' . (int)$summary['media'] .
        get_string('cfr_fix_transfer_media', 'local_subscriptions') . number_format($summary['bytes'] / 1048576, 2, ',', ' ') . ' Mio</p>';
    echo '<p>SHA-256 : <code>' . s($summary['zipsha256']) . '</code></p></div>';
    echo '<form class="cfr-reader-editor__transfer-form" method="post" action="' . s(cfr_d21_url('transfers.php')->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="transfer_action" value="import">';
    echo '<input type="hidden" name="archive_sha256" value="' . s($summary['zipsha256']) . '">';
    echo '<p><label for="cfr-d25-col">' . s(get_string('cfr_d25_target', 'local_subscriptions')) . '</label>';
    echo '<select class="form-control" id="cfr-d25-col" name="targetcollection" required>';
    foreach ($qa as $collection) {
        echo '<option value="' . (int)$collection->id . '">' . s($collection->title . ' (' . $collection->slug . ')') . '</option>';
    }
    echo '</select></p><p><label for="cfr-d25-slug">' . s(get_string('cfr_d25_newslug', 'local_subscriptions')) . '</label>';
    echo '<input class="form-control" id="cfr-d25-slug" type="text" name="newslug" maxlength="64" pattern="[a-z0-9]+(-[a-z0-9]+)*" required value="qa-' . s($summary['slug']) . '"></p>';
    echo '<label><input type="checkbox" name="confirm" value="1" required> ' .
        s(get_string('cfr_d25_confirm', 'local_subscriptions')) . '</label>';
    echo '<p><button class="btn btn-primary" type="submit"' . (!$qa ? ' disabled' : '') . '>' .
        s(get_string('cfr_d25_import_button', 'local_subscriptions')) . '</button></p></form>';
    echo '<form class="cfr-reader-editor__transfer-form" method="post" action="' . s(cfr_d21_url('transfers.php')->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="transfer_action" value="cancel">';
    echo '<button class="btn btn-outline-secondary" type="submit">' .
        s(get_string('cancel')) . '</button></form>';
}
echo '</section></div>';
echo '<p><a class="btn btn-outline-primary" href="' . s(cfr_d21_url('bundles.php')->out(false)) . '">' . s(get_string('cfr_d26_title', 'local_subscriptions')) . '</a></p>';
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d25_limits', 'local_subscriptions')) . '</p>';
cfr_d21_end();
