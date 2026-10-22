<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\library;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;
use stored_file;

/**
 * Stores the optional Alf photo shown at the end of donation-only receipts.
 *
 * The Mail Studio template ID is the itemid so one shared photo is used for
 * every language of the PURCHASE_RECEIPT transactional template.
 */
final class CommerceMailLibraryAlfDonationFooterImageService {
    public const FILEAREA = 'commerce_mail_library_alf_donation_footer';

    private const MAX_BYTES = 8 * 1024 * 1024;
    private const FILENAME = 'alf-donation-footer';

    public function save_uploaded_file(int $templateid, array $upload): void {
        if ($templateid <= 0 || empty($upload['tmp_name'])) {
            return;
        }

        $error = (int)($upload['error'] ?? UPLOAD_ERR_OK);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string)$upload['tmp_name'])) {
            throw new \moodle_exception(
                'commerce_mail_alf_donation_footer_upload_error',
                'local_subscriptions'
            );
        }

        $size = (int)($upload['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \moodle_exception(
                'commerce_mail_alf_donation_footer_too_large',
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
                'commerce_mail_alf_donation_footer_invalid_type',
                'local_subscriptions'
            );
        }

        $this->delete($templateid);

        $context = context_system::instance();
        get_file_storage()->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'local_subscriptions',
            'filearea' => self::FILEAREA,
            'itemid' => $templateid,
            'filepath' => '/',
            'filename' => self::FILENAME . '.' . $extensions[$imagetype],
        ], (string)$upload['tmp_name']);
    }

    public function delete(int $templateid): void {
        if ($templateid <= 0) {
            return;
        }
        get_file_storage()->delete_area_files(
            context_system::instance()->id,
            'local_subscriptions',
            self::FILEAREA,
            $templateid
        );
    }

    public function url(int $templateid): string {
        $file = $this->get_file($templateid);
        if ($file === null) {
            return '';
        }

        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);
    }

    private function get_file(int $templateid): ?stored_file {
        if ($templateid <= 0) {
            return null;
        }

        $files = get_file_storage()->get_area_files(
            context_system::instance()->id,
            'local_subscriptions',
            self::FILEAREA,
            $templateid,
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
