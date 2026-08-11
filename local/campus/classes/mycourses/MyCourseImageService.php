<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

/** Resolves the presentation image of a Moodle course without depending on the Edly block. */
final class MyCourseImageService {
    public function get_image_url(\stdClass $course): ?string {
        $courseinlist = new \core_course_list_element($course);

        foreach ($courseinlist->get_course_overviewfiles() as $file) {
            if (!$file->is_valid_image()) {
                continue;
            }

            return \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,
                $file->get_filepath(),
                $file->get_filename(),
                false
            )->out(false);
        }

        return null;
    }
}