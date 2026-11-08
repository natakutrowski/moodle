<?php
// DEV-only V4.1 upgrade. One specific V3 Lovable draft; preserve 65 CMS keys / product SKUs.
declare(strict_types=1);
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
if (realpath($CFG->dirroot) !== realpath('/var/www/moodle-dev')) {
    fwrite(STDERR, "STOP : usage DEV uniquement.\n");
    exit(2);
}

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

const DESIGN_ID = 'articles_alf_reimport';
// Exact SHA of the V3 that passed all suites and was installed in draft #7.
const EXPECTED_V3_SHA256 = '77e32101f0a3b43651b7b19265ee4104ca3bf27e7c2e5378ef23c531246d53ca';
if (PHP_SAPI !== 'cli' || count($argv) !== 3 || $argv[1] !== 'upgrade') {
    fwrite(STDERR, "Usage: php upgrade_cms_commerce_preview_v41.php upgrade /private/new_compiled.zip\n");
    exit(2);
}
$path = $argv[2];
if (!is_file($path) || is_link($path) || filesize($path) < 256 || filesize($path) > 20971520) {
    fwrite(STDERR, "STOP : ZIP compilé manquant ou invalide.\n");
    exit(2);
}
try {
    $record = (new CommerceShowroomCmsRepository($DB))->get_by_key(Draft::draft_key(DESIGN_ID));
    if (!$record) {
        throw new invalid_parameter_exception('Brouillon cible inexistant.');
    }
    $id = (int)$record->id;
    [$record, $meta] = Draft::require_draft($DB, $id);
    if (($meta['designid'] ?? '') !== DESIGN_ID || empty($meta['commerce_preview_v3'])
            || !empty($meta['commerce_preview_v41']) || !empty($meta['commerce_connected'])
            || (string)$record->status !== 'draft') {
        throw new invalid_parameter_exception('Brouillon V3 requis ; modification refusée.');
    }
    if (!hash_equals(EXPECTED_V3_SHA256, (string)($meta['archive_sha256'] ?? ''))) {
        throw new invalid_parameter_exception('Empreinte V3 inattendue : ne pas forcer l’import.');
    }
    $oldfile = Draft::require_archive($id, $meta);
    $oldpath = Draft::materialise_archive($oldfile, $meta);
    try {
        if (!hash_equals(EXPECTED_V3_SHA256, hash_file('sha256', $oldpath))) {
            throw new invalid_parameter_exception('Archive V3 sauvegardée non conforme à la référence.');
        }
        // Validate all archive boundaries and the current display text keys.
        $page = Draft::displayed_page($meta, 'ru');
        Preview::render_frame($path, DESIGN_ID, $page, ['currency' => 'EUR', 'offers' => []]);
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new invalid_parameter_exception('ZIP V4.1 illisible.');
        }
        try {
            $prefix = 'draft/' . DESIGN_ID . '/';
            $raw = $zip->getFromName($prefix . 'content/ru.json');
            $catalog = is_string($raw) ? json_decode($raw, true, 32, JSON_THROW_ON_ERROR) : null;
            $js = $zip->getFromName($prefix . 'dist/main.js');
            if (!is_array($catalog) || !is_array($catalog['page'] ?? null)
                    || count($catalog['page']) !== 65
                    || array_keys($catalog['page']) !== array_keys($meta['catalogues']['ru'])) {
                throw new invalid_parameter_exception('65 clés CMS modifiées : import V4.1 refusé.');
            }
            if (!is_string($js) || !str_contains($js, 'data-commerce-readonly')
                    || !str_contains($js, 'data-commerce-review-v3')
                    || !str_contains($js, 'campusfr-preview-currency-v41')
                    || !str_contains($js, 'campusfr-preview-currency-result-v41')) {
                throw new invalid_parameter_exception('Marqueurs V3/V4.1 Commerce et sécurité absents.');
            }
            if (str_contains($js, 'window.location.assign') || str_contains($js, 'data-bridge-buy')) {
                throw new invalid_parameter_exception('Navigation ou activation de paiement détectée.');
            }
        } finally {
            $zip->close();
        }
        $settings = json_decode((string)$record->settingsjson, true, 64, JSON_THROW_ON_ERROR);
        $updatedmeta = $meta;
        $updatedmeta['archive_sha256'] = hash_file('sha256', $path);
        $updatedmeta['commerce_preview_v41'] = true;
        $updatedmeta['commerce_connected'] = false;
        $settings[Draft::SETTINGS_KEY] = $updatedmeta;
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($encoded) > 55000) {
            throw new invalid_parameter_exception('Réglages CMS trop volumineux.');
        }
        // Do not reuse a previous backup directory. Private files 0600, directory 0700.
        $backup = '/tmp/campusfr_gustave_visual_v41_backup_' . date('Ymd_His') . '_' . $id;
        if (file_exists($backup) || !mkdir($backup, 0700)) {
            throw new invalid_parameter_exception('Sauvegarde privée déjà existante ou impossible.');
        }
        chmod($backup, 0700);
        $backups = [
            'compiled-before.zip' => file_get_contents($oldpath),
            'settings-before.json' => (string)$record->settingsjson,
            'products-before.json' => (string)$record->productsjson,
            'sha256-before.txt' => hash_file('sha256', $oldpath) . "\n",
            'sha256-after.txt' => hash_file('sha256', $path) . "\n",
        ];
        foreach ($backups as $filename => $bytes) {
            if (!is_string($bytes) || file_put_contents($backup . '/' . $filename, $bytes, LOCK_EX) !== strlen($bytes)) {
                throw new invalid_parameter_exception('Sauvegarde privée incomplète : ' . $backup);
            }
            chmod($backup . '/' . $filename, 0600);
        }
        $transaction = $DB->start_delegated_transaction();
        try {
            if (!$oldfile->delete()) {
                throw new invalid_parameter_exception('Impossible de remplacer la référence File API.');
            }
            $newfile = get_file_storage()->create_file_from_pathname([
                'contextid' => (int)context_system::instance()->id,
                'component' => 'local_subscriptions', 'filearea' => Draft::FILEAREA,
                'itemid' => $id, 'filepath' => '/', 'filename' => Draft::FILENAME,
                'userid' => (int)($record->usermodified ?? 1),
            ], $path);
            if (!$newfile || $newfile->get_contenthash() !== sha1_file($path)) {
                throw new invalid_parameter_exception('Contrôle de la nouvelle archive File API échoué.');
            }
            $DB->update_record('local_subs_showroom', (object)[
                'id' => $id, 'settingsjson' => $encoded,
                'timemodified' => time(), 'usermodified' => (int)($record->usermodified ?? 1),
            ]);
            $transaction->allow_commit();
        } catch (Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }
        echo "OK : showroom DEV #$id, V4.1 appliquée, 65 textes CMS conservés, produits inchangés.\n";
        echo "SAUVEGARDE CMS : $backup\n";
        echo "RESTORE : php /var/www/moodle-dev/local/subscriptions/cli/lovable_builder/restore_cms_commerce_preview_v41.php restore $backup\n";
    } finally {
        @unlink($oldpath);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'STOP V4.1 : ' . $e->getMessage() . "\n");
    exit(1);
}
