<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template\studio;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;

final class CommerceMailHeaderImageService {
    public const FILEAREA = 'commerce_mail_template_header';

    public static function url(int $templateid): string {
        $files = get_file_storage()->get_area_files(
            context_system::instance()->id,
            'local_subscriptions',
            self::FILEAREA,
            $templateid,
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
            $templateid,
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);
    }

    private function __construct() {
    }
}
