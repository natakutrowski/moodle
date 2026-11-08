<?php
// DEV-only controlled rollback of the one Articles / Alf CMS zip and metadata.
declare(strict_types=1);
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository as Repository;

if (PHP_SAPI !== 'cli' || count($argv) !== 3 || $argv[1] !== 'restore') {
    fwrite(STDERR, "Usage: php restore_cms_commerce_preview_v2.php restore /tmp/private_backup\n");
    exit(2);
}
$backup = $argv[2];
if (!preg_match('~^/tmp/campusfr_gustave_offer_v2_backup_[0-9]{8}_[0-9]{6}_([0-9]+)$~D', $backup, $matches)
        || !is_dir($backup) || is_link($backup)
        || ((fileperms($backup) & 0777) !== 0700)) {
    fwrite(STDERR, "STOP : dossier de sauvegarde invalide.\n");
    exit(2);
}
$id = (int)$matches[1];
$zipPath = $backup . '/compiled-before.zip';
$settingsPath = $backup . '/settings-before.json';
$hashPath = $backup . '/sha256-before.txt';
if (!is_file($zipPath) || !is_file($settingsPath) || !is_file($hashPath)
        || !hash_equals(trim(file_get_contents($hashPath)), hash_file('sha256', $zipPath))) {
    fwrite(STDERR, "STOP : sauvegarde incomplète.\n");
    exit(2);
}
try {
    [$record, $current] = Draft::require_draft($DB, $id);
    if ((string)$current['designid'] !== 'articles_alf_reimport'
            || empty($current['commerce_preview_v2'])) {
        throw new invalid_parameter_exception('Ce showroom ne correspond pas au brouillon mis à niveau.');
    }
    $oldjson = file_get_contents($settingsPath);
    $oldsettings = json_decode($oldjson, true, 64, JSON_THROW_ON_ERROR);
    $oldmeta = $oldsettings[Draft::SETTINGS_KEY] ?? null;
    if (!is_array($oldmeta) || !hash_equals((string)($oldmeta['archive_sha256'] ?? ''), hash_file('sha256', $zipPath))) {
        throw new invalid_parameter_exception('Métadonnées de sauvegarde invalides.');
    }
    $nowsettings = json_decode((string)$record->settingsjson, true, 64, JSON_THROW_ON_ERROR);
    $nowmeta = $nowsettings[Draft::SETTINGS_KEY] ?? [];
    // Refuse to overwrite ANY changes made by a user after the upgrade.
    unset($nowmeta['commerce_preview_v2']);
    $nowmeta['archive_sha256'] = $oldmeta['archive_sha256'];
    $nowsettings[Draft::SETTINGS_KEY] = $nowmeta;
    if ($nowsettings !== $oldsettings) {
        throw new invalid_parameter_exception('Brouillon modifié depuis la mise à niveau : restauration refusée.');
    }
    Preview::render_frame($zipPath, 'articles_alf_reimport', Draft::displayed_page($oldmeta, 'ru'));
    $oldfile = Draft::require_archive($id, $current);
    $transaction = $DB->start_delegated_transaction();
    try {
        if (!$oldfile->delete()) {
            throw new invalid_parameter_exception('Suppression de la référence du ZIP actuel impossible.');
        }
        get_file_storage()->create_file_from_pathname([
            'contextid' => (int)context_system::instance()->id,
            'component' => 'local_subscriptions',
            'filearea' => Draft::FILEAREA, 'itemid' => $id,
            'filepath' => '/', 'filename' => Draft::FILENAME,
            'userid' => (int)($record->usermodified ?? 1),
        ], $zipPath);
        $DB->update_record('local_subs_showroom', (object)[
            'id' => $id, 'settingsjson' => $oldjson,
            'timemodified' => time(), 'usermodified' => (int)($record->usermodified ?? 1),
        ]);
        $transaction->allow_commit();
    } catch (Throwable $exception) {
        $transaction->rollback($exception);
        throw $exception;
    }
    echo "OK : ZIP V1 et réglages CMS restaurés pour #$id.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'STOP restauration CMS : ' . $exception->getMessage() . "\n");
    exit(1);
}
