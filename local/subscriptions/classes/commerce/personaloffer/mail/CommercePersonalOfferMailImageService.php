<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;
use stored_file;

/**
 * Stores an optional public illustration for a Personal Offer email.
 *
 * The file is keyed by offer ID. No extra DB field is required:
 * no file means the global Personal Offer fallback image is used.
 */
final class CommercePersonalOfferMailImageService {
    public const FILEAREA = 'personaloffer_mailimage';

    private const MAX_BYTES = 8 * 1024 * 1024;
    private const FILENAME = 'offer-image';

    public function save_uploaded_file(int $offerid, array $upload): void {
        if ($offerid <= 0 || empty($upload['tmp_name'])) {
            return;
        }

        $error = (int)($upload['error'] ?? UPLOAD_ERR_OK);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string)$upload['tmp_name'])) {
            throw new \moodle_exception(
                'commerce_personal_offer_mail_image_upload_error',
                'local_subscriptions'
            );
        }

        $size = (int)($upload['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \moodle_exception(
                'commerce_personal_offer_mail_image_too_large',
                'local_subscriptions'
            );
        }

        $imagetype = @exif_imagetype((string)$upload['tmp_name']);
        $extensions = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
        ];
        if (!isset($extensions[$imagetype])) {
            throw new \moodle_exception(
                'commerce_personal_offer_mail_image_invalid_type',
                'local_subscriptions'
            );
        }

        $this->delete($offerid);

        $context = context_system::instance();
        get_file_storage()->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'local_subscriptions',
            'filearea' => self::FILEAREA,
            'itemid' => $offerid,
            'filepath' => '/',
            'filename' => self::FILENAME . '.' . $extensions[$imagetype],
        ], (string)$upload['tmp_name']);
    }

    public function copy(int $sourceofferid, int $targetofferid): void {
        if ($sourceofferid <= 0 || $targetofferid <= 0 || $sourceofferid === $targetofferid) {
            return;
        }

        $source = $this->get_file($sourceofferid);
        if ($source === null) {
            return;
        }

        $this->delete($targetofferid);

        $context = context_system::instance();
        get_file_storage()->create_file_from_storedfile([
            'contextid' => $context->id,
            'component' => 'local_subscriptions',
            'filearea' => self::FILEAREA,
            'itemid' => $targetofferid,
            'filepath' => '/',
            'filename' => $source->get_filename(),
        ], $source);
    }

    public function delete(int $offerid): void {
        if ($offerid <= 0) {
            return;
        }

        $context = context_system::instance();
        get_file_storage()->delete_area_files(
            $context->id,
            'local_subscriptions',
            self::FILEAREA,
            $offerid
        );
    }

    public function url(int $offerid): ?moodle_url {
        $file = $this->get_file($offerid);
        if ($file === null) {
            return null;
        }

        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        );
    }

    private function get_file(int $offerid): ?stored_file {
        if ($offerid <= 0) {
            return null;
        }

        $context = context_system::instance();
        $files = get_file_storage()->get_area_files(
            $context->id,
            'local_subscriptions',
            self::FILEAREA,
            $offerid,
            'id ASC',
            false
        );

        if ($files === []) {
            return null;
        }

        $file = reset($files);
        return $file instanceof stored_file ? $file : null;
    }
}
