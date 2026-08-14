<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;
use stored_file;

/**
 * Stores one optional public end-of-email image per Personal Offer campaign.
 *
 * Campaign ID is the itemid. No DB column is required.
 */
final class CommercePersonalOfferCampaignMailFooterImageService {
    public const FILEAREA = 'personaloffer_campaign_footerimage';

    private const MAX_BYTES = 8 * 1024 * 1024;
    private const FILENAME = 'campaign-footer';

    public function save_uploaded_file(int $campaignid, array $upload): void {
        if ($campaignid <= 0 || empty($upload['tmp_name'])) {
            return;
        }

        $error = (int)($upload['error'] ?? UPLOAD_ERR_OK);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string)$upload['tmp_name'])) {
            throw new \moodle_exception(
                'commerce_personal_offer_campaign_footer_upload_error',
                'local_subscriptions'
            );
        }

        $size = (int)($upload['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \moodle_exception(
                'commerce_personal_offer_campaign_footer_too_large',
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
                'commerce_personal_offer_campaign_footer_invalid_type',
                'local_subscriptions'
            );
        }

        $this->delete($campaignid);

        $context = context_system::instance();
        get_file_storage()->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'local_subscriptions',
            'filearea' => self::FILEAREA,
            'itemid' => $campaignid,
            'filepath' => '/',
            'filename' => self::FILENAME . '.' . $extensions[$imagetype],
        ], (string)$upload['tmp_name']);
    }

    public function delete(int $campaignid): void {
        if ($campaignid <= 0) {
            return;
        }
        get_file_storage()->delete_area_files(
            context_system::instance()->id,
            'local_subscriptions',
            self::FILEAREA,
            $campaignid
        );
    }

    public function url(int $campaignid): string {
        $file = $this->get_file($campaignid);
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

    private function get_file(int $campaignid): ?stored_file {
        if ($campaignid <= 0) {
            return null;
        }

        $files = get_file_storage()->get_area_files(
            context_system::instance()->id,
            'local_subscriptions',
            self::FILEAREA,
            $campaignid,
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
