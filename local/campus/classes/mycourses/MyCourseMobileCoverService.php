<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

/** Stores and resolves the dedicated 4:3 mobile artwork used by My courses. */
final class MyCourseMobileCoverService {
    public const COMPONENT = 'local_campus';
    public const FILEAREA = 'course_mobile_cover';
    public const MAX_BYTES = 5 * 1024 * 1024;

    /** @var string[] */
    public const ACCEPTED_TYPES = ['.jpg', '.jpeg', '.png', '.webp'];

    /** @var array<int, string|null> */
    private array $urlcache = [];

    /** @return array<string, mixed> */
    public static function filemanager_options(): array {
        return [
            'subdirs' => 0,
            'maxbytes' => self::MAX_BYTES,
            'maxfiles' => 1,
            'accepted_types' => self::ACCEPTED_TYPES,
            'return_types' => FILE_INTERNAL,
        ];
    }

    public function prepare_draft(int $courseid, int $draftitemid): void {
        $context = \context_course::instance($courseid);
        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $courseid,
            self::filemanager_options()
        );
    }

    public function save_draft(int $courseid, int $draftitemid): void {
        $context = \context_course::instance($courseid);
        file_save_draft_area_files(
            $draftitemid,
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $courseid,
            self::filemanager_options()
        );
        unset($this->urlcache[$courseid]);
    }

    public function delete(int $courseid): void {
        $context = \context_course::instance($courseid);
        get_file_storage()->delete_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $courseid
        );
        unset($this->urlcache[$courseid]);
    }

    public function get_url(int $courseid): ?string {
        if ($courseid <= 0) {
            return null;
        }
        if (array_key_exists($courseid, $this->urlcache)) {
            return $this->urlcache[$courseid];
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return $this->urlcache[$courseid] = null;
        }

        $files = get_file_storage()->get_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $courseid,
            'sortorder DESC, id DESC',
            false
        );
        $file = reset($files);
        if (!$file instanceof \stored_file) {
            return $this->urlcache[$courseid] = null;
        }

        $url = \moodle_url::make_pluginfile_url(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $courseid,
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);

        return $this->urlcache[$courseid] = $url;
    }
}
