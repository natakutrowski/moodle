<?php
// DEV-only CLI: install reviewed, container-compiled READ-ONLY Commerce preview into one existing CMS draft.
declare(strict_types=1);
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;

if (PHP_SAPI !== 'cli' || count($argv) !== 4 || $argv[1] !== 'upgrade') {
    fwrite(STDERR, "Usage: php upgrade_cms_commerce_preview_v2.php upgrade auto /private/compiled.zip\n");
    exit(2);
}
$id = $argv[2] === 'auto' ? 0 : filter_var($argv[2], FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]);
if ($id === 0) {
    $existing = (new \local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository($DB))
        ->get_by_key(Draft::draft_key('articles_alf_reimport'));
    $id = $existing ? (int)$existing->id : false;
}
$path = $argv[3];
if ($id === false || !is_file($path) || is_link($path) || filesize($path) > 20971520) {
    fwrite(STDERR, "STOP : id ou archive invalide.\n");
    exit(2);
}
try {
    [$record, $meta] = Draft::require_draft($DB, (int)$id);
    if ((string)$meta['designid'] !== 'articles_alf_reimport') {
        throw new invalid_parameter_exception('Seul Articles / Alf est autorisé dans ce delta.');
    }
    if (empty($meta['commerce_preview_v1']) || !empty($meta['commerce_preview_v2'])) {
        throw new invalid_parameter_exception('Version V1 requise et V2 non encore appliquée.');
    }
    $page = Draft::displayed_page($meta, 'ru');
    // The newly compiled JS and CSS must satisfy all original preview ZIP restrictions.
    Preview::render_frame($path, 'articles_alf_reimport', $page, ['currency' => 'EUR', 'offers' => []]);
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::RDONLY) !== true) {
        throw new invalid_parameter_exception('ZIP produit illisible.');
    }
    try {
        $content = $zip->getFromName('draft/articles_alf_reimport/content/ru.json');
        $decoded = is_string($content) ? json_decode($content, true, 32, JSON_THROW_ON_ERROR) : null;
        if (!is_array($decoded) || !isset($decoded['page']) || !is_array($decoded['page']) ||
                array_keys($decoded['page']) !== array_keys($meta['catalogues']['ru'])) {
            throw new invalid_parameter_exception('Les 65 clés CMS d’origine ont changé : mise à niveau refusée.');
        }
        $js = $zip->getFromName('draft/articles_alf_reimport/dist/main.js');
        if (!is_string($js) || !str_contains($js, 'data-commerce-readonly') || !str_contains($js, 'data-commerce-review-v2')) {
            throw new invalid_parameter_exception('Composant React Commerce non identifiable.');
        }
    } finally {
        $zip->close();
    }
    $oldfile = Draft::require_archive((int)$id, $meta);
    $oldpath = Draft::materialise_archive($oldfile, $meta);
    try {
        $settings = json_decode((string)$record->settingsjson, true, 64, JSON_THROW_ON_ERROR);
        $backup = '/tmp/campusfr_gustave_offer_v2_backup_' . date('Ymd_His') . '_' . (int)$id;
        if (file_exists($backup) || !mkdir($backup, 0700)) {
            throw new invalid_parameter_exception('Sauvegarde privée impossible.');
        }
        chmod($backup, 0700);
        if (!copy($oldpath, $backup . '/compiled-before.zip')) {
            throw new invalid_parameter_exception('Sauvegarde ZIP impossible.');
        }
        chmod($backup . '/compiled-before.zip', 0600);
        file_put_contents($backup . '/settings-before.json', (string)$record->settingsjson, LOCK_EX);
        chmod($backup . '/settings-before.json', 0600);
        file_put_contents($backup . '/sha256-before.txt', hash_file('sha256', $oldpath) . "\n", LOCK_EX);
        chmod($backup . '/sha256-before.txt', 0600);
        $meta['archive_sha256'] = hash_file('sha256', $path);
        $meta['commerce_preview_v1'] = true;
        $meta['commerce_preview_v2'] = true;
        $meta['commerce_connected'] = false; // Still no checkout; publication stays blocked.
        $settings[Draft::SETTINGS_KEY] = $meta;
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($encoded) > 55000) {
            throw new invalid_parameter_exception('Settings CMS trop volumineux.');
        }
        $transaction = $DB->start_delegated_transaction();
        try {
            if (!$oldfile->delete()) {
                throw new invalid_parameter_exception('Suppression de référence File API refusée.');
            }
            get_file_storage()->create_file_from_pathname([
                'contextid' => (int)context_system::instance()->id,
                'component' => 'local_subscriptions',
                'filearea' => Draft::FILEAREA,
                'itemid' => (int)$id,
                'filepath' => '/',
                'filename' => Draft::FILENAME,
                'userid' => (int)($record->usermodified ?? 1),
            ], $path);
            $DB->update_record('local_subs_showroom', (object)[
                'id' => (int)$id,
                'settingsjson' => $encoded,
                'timemodified' => time(),
                'usermodified' => (int)($record->usermodified ?? 1),
            ]);
            $transaction->allow_commit();
        } catch (Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }
        [$updated, $updatedmeta] = Draft::require_draft($DB, (int)$id);
        $check = Draft::require_archive((int)$id, $updatedmeta);
        if ((string)$check->get_contenthash() !== sha1_file($path)) {
            throw new invalid_parameter_exception('Vérification File API après opération échouée : ' . $backup);
        }
        echo "OK : React Commerce V2 enregistré dans le brouillon #$id.\n";
        echo "Sauvegarde privée initiale : $backup\n";
        echo "65 clés CMS préservées ; aucune publication ni checkout.\n";
    } finally {
        @unlink($oldpath);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'STOP CMS Commerce : ' . $exception->getMessage() . "\n");
    exit(1);
}
