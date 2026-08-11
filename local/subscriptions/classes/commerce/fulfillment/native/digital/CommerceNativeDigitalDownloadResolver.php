<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use moodle_database;

final class CommerceNativeDigitalDownloadResolver {
    public function __construct(private readonly moodle_database $database) {
    }

    public function resolve(string $token, int $now, string $version = 'desktop'): array {
        global $CFG;

        $access = $this->database->get_record('local_subs_commerce_dig_access', [
            'downloadtoken' => trim($token),
        ], '*', IGNORE_MISSING);
        if ($access === false) {
            throw new \moodle_exception('invalidtoken', 'error');
        }
        if ((string)$access->status !== 'active') {
            throw new \moodle_exception('digital_download_expired', 'local_subscriptions');
        }
        if ((int)$access->validfrom > $now || ($access->validuntil !== null && (int)$access->validuntil < $now)) {
            throw new \moodle_exception('digital_download_expired', 'local_subscriptions');
        }
        if ($access->maxdownloads !== null && (int)$access->downloadcount >= (int)$access->maxdownloads) {
            throw new \moodle_exception('digital_download_expired', 'local_subscriptions');
        }

        $product = $this->database->get_record('local_subs_commerce_product', [
            'sku' => (string)$access->productsku,
        ], '*', IGNORE_MISSING);
        if ($product === false) {
            throw new \moodle_exception('invalidrecord', 'error');
        }
        $nativefiles = new CommerceCatalogDigitalFileManager(\context_system::instance());
        $role = $version === 'mobile'
            ? CommerceCatalogDigitalFileManager::ROLE_MOBILE
            : CommerceCatalogDigitalFileManager::ROLE_DESKTOP;
        $storedfile = $nativefiles->get_file((int)$product->id, $role);
        if ($storedfile === null && $role === CommerceCatalogDigitalFileManager::ROLE_DESKTOP) {
            $storedfile = $nativefiles->get_file((int)$product->id, CommerceCatalogDigitalFileManager::ROLE_MOBILE);
        }
        if ($storedfile !== null) {
            return [
                'access' => $access,
                'filename' => $storedfile->get_filename(),
                'storedfile' => $storedfile,
                'filepath' => null,
            ];
        }

        // Compatibility fallback for digital products imported before H4.8.5.
        $metadata = json_decode((string)($product->metadatajson ?? ''), true);
        $filenamekey = $version === 'mobile' ? 'mobilefilename' : 'filename';
        $filename = is_array($metadata) ? trim((string)($metadata[$filenamekey] ?? '')) : '';
        if ($filename === '' && preg_match('/^digital-product:(\d+)$/', (string)$access->resourcekey, $matches)) {
            $legacyfield = $version === 'mobile' ? 'mobile_filename' : 'filename';
            $filename = trim((string)$this->database->get_field('subscription_digital_product', $legacyfield, [
                'id' => (int)$matches[1],
            ], IGNORE_MISSING));
        }
        $filename = basename($filename);
        $filepath = $CFG->dataroot . '/local_subscriptions/private_pdfs/' . $filename;
        if ($filename === '' || !is_readable($filepath)) {
            throw new \moodle_exception('digital_download_file_missing', 'local_subscriptions');
        }

        return ['access' => $access, 'filename' => $filename, 'storedfile' => null, 'filepath' => $filepath];
    }

    public function register_download(\stdClass $access, int $now): void {
        $transaction = $this->database->start_delegated_transaction();
        $current = $this->database->get_record('local_subs_commerce_dig_access', ['id' => (int)$access->id], '*', MUST_EXIST);
        if ($current->maxdownloads !== null && (int)$current->downloadcount >= (int)$current->maxdownloads) {
            throw new \moodle_exception('digital_download_expired', 'local_subscriptions');
        }
        $current->downloadcount = (int)$current->downloadcount + 1;
        $current->lastdownloadat = $now;
        $current->timemodified = $now;
        $this->database->update_record('local_subs_commerce_dig_access', $current);
        $transaction->allow_commit();
    }
}
