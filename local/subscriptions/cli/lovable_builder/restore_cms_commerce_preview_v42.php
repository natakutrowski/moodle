<?php
// DEV-only rollback: restore EXACT previous CMS archive and settings, refuse overwritten edits.
declare(strict_types=1);
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
if (realpath($CFG->dirroot) !== realpath('/var/www/moodle-dev')) {
    fwrite(STDERR, "STOP : usage DEV uniquement.\n");
    exit(2);
}

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;

if (PHP_SAPI !== 'cli' || count($argv) !== 3 || $argv[1] !== 'restore') {
    fwrite(STDERR, "Usage: php restore_cms_commerce_preview_v42.php restore /tmp/private_backup\n");
    exit(2);
}
$dir = $argv[2];
if (!preg_match('~^/tmp/campusfr_gustave_visual_v42_backup_[0-9]{8}_[0-9]{6}_([0-9]+)$~D', $dir, $m)
        || !is_dir($dir) || is_link($dir) || ((fileperms($dir) & 0777) !== 0700)) {
    fwrite(STDERR, "STOP : dossier de sauvegarde V4.2 non conforme.\n");
    exit(2);
}
$id = (int)$m[1];
foreach (['compiled-before.zip','settings-before.json','products-before.json',
          'sha256-before.txt','sha256-after.txt'] as $file) {
    if (!is_file($dir . '/' . $file) || is_link($dir . '/' . $file)) {
        fwrite(STDERR, "STOP : sauvegarde incomplète.\n");
        exit(2);
    }
}
$oldzip = $dir . '/compiled-before.zip';
$oldsha = trim(file_get_contents($dir . '/sha256-before.txt'));
$newsha = trim(file_get_contents($dir . '/sha256-after.txt'));
if (!preg_match('/^[a-f0-9]{64}$/D', $oldsha) || !preg_match('/^[a-f0-9]{64}$/D', $newsha)
        || !hash_equals($oldsha, hash_file('sha256', $oldzip))) {
    fwrite(STDERR, "STOP : intégrité de la sauvegarde invalide.\n");
    exit(2);
}
try {
    [$record, $meta] = Draft::require_draft($DB, $id);
    if (($meta['designid'] ?? '') !== 'articles_alf_reimport' || empty($meta['commerce_preview_v42'])
            || !hash_equals($newsha, (string)($meta['archive_sha256'] ?? ''))) {
        throw new invalid_parameter_exception('Brouillon différent de la V4.2 sauvegardée.');
    }
    if ((string)$record->productsjson !== file_get_contents($dir . '/products-before.json')) {
        throw new invalid_parameter_exception('Produits CMS modifiés depuis V4.2 : rollback refusé.');
    }
    $saved = file_get_contents($dir . '/settings-before.json');
    $before = json_decode($saved, true, 64, JSON_THROW_ON_ERROR);
    $current = json_decode((string)$record->settingsjson, true, 64, JSON_THROW_ON_ERROR);
    $expected = $before;
    $expected[Draft::SETTINGS_KEY]['archive_sha256'] = $newsha;
    $expected[Draft::SETTINGS_KEY]['commerce_preview_v42'] = true;
    $expected[Draft::SETTINGS_KEY]['commerce_connected'] = false;
    if ($current !== $expected) {
        throw new invalid_parameter_exception('Textes ou paramètres changés depuis V4.2 : rollback refusé.');
    }
    Preview::render_frame($oldzip, 'articles_alf_reimport', Draft::displayed_page($before[Draft::SETTINGS_KEY], 'ru'));
    $currentfile = Draft::require_archive($id, $meta);
    $active = Draft::materialise_archive($currentfile, $meta);
    try {
        if (!hash_equals($newsha, hash_file('sha256', $active))) {
            throw new invalid_parameter_exception('Archive CMS modifiée depuis V4.2.');
        }
    } finally {
        @unlink($active);
    }
    $transaction = $DB->start_delegated_transaction();
    try {
        if (!$currentfile->delete()) throw new invalid_parameter_exception('File API delete refusé.');
        $restored = get_file_storage()->create_file_from_pathname([
            'contextid' => (int)context_system::instance()->id,
            'component' => 'local_subscriptions', 'filearea' => Draft::FILEAREA,
            'itemid' => $id, 'filepath' => '/', 'filename' => Draft::FILENAME,
            'userid' => (int)($record->usermodified ?? 1),
        ], $oldzip);
        if (!$restored || $restored->get_contenthash() !== sha1_file($oldzip)) {
            throw new invalid_parameter_exception('Contrôle File API du rollback refusé.');
        }
        $DB->update_record('local_subs_showroom', (object)[
            'id' => $id, 'settingsjson' => $saved,
            'timemodified' => time(), 'usermodified' => (int)($record->usermodified ?? 1),
        ]);
        $transaction->allow_commit();
    } catch (Throwable $e) {
        $transaction->rollback($e);
        throw $e;
    }
    echo "OK : brouillon #$id restauré en V4.1 ; produits CMS et traductions préservés.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'STOP restauration V4.2 : ' . $e->getMessage() . "\n");
    exit(1);
}
