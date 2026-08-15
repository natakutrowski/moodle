<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\library;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;

final class CommerceMailLibraryHeaderImageService {
    public const FILEAREA = 'commerce_mail_library_header';

    public static function url(int $contentid): string {
        if ($contentid <= 0) {
            return '';
        }
        $files = get_file_storage()->get_area_files(
            context_system::instance()->id,
            'local_subscriptions',
            self::FILEAREA,
            $contentid,
            'sortorder ASC, id ASC',
            false
        );
        $file = reset($files);
        if (!$file) {
            return '';
        }
        return moodle_url::make_pluginfile_url(
            context_system::instance()->id,
            'local_subscriptions',
            self::FILEAREA,
            $contentid,
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);
    }

    public static function copy_from_legacy(int $legacytemplateid, int $contentid): void {
        if ($legacytemplateid <= 0 || $contentid <= 0) {
            return;
        }
        $contextid = context_system::instance()->id;
        $fs = get_file_storage();
        $sourcefiles = $fs->get_area_files(
            $contextid,
            'local_subscriptions',
            \local_subscriptions\commerce\mail\template\studio\CommerceMailHeaderImageService::FILEAREA,
            $legacytemplateid,
            'sortorder ASC, id ASC',
            false
        );
        $source = reset($sourcefiles);
        if (!$source) {
            return;
        }
        $fs->delete_area_files(
            $contextid,
            'local_subscriptions',
            self::FILEAREA,
            $contentid
        );
        $record = [
            'contextid' => $contextid,
            'component' => 'local_subscriptions',
            'filearea' => self::FILEAREA,
            'itemid' => $contentid,
            'filepath' => $source->get_filepath(),
            'filename' => $source->get_filename(),
        ];
        $fs->create_file_from_storedfile($record, $source);
    }

    private function __construct() {}
}
