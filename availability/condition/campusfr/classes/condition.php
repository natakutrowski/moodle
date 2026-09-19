<?php

declare(strict_types=1);

namespace availability_campusfr;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessResolver;
use local_subscriptions\commerce\education\access\CommercePedagogicalSectionLockPresentation;

/**
 * Individual CampusFR pedagogical-calendar availability condition.
 *
 * This condition is attached automatically to non-general course sections.
 * Administrators do not configure it manually.
 */
final class condition extends \core_availability\condition {
    public function __construct($structure) {
        if (
            !is_object($structure)
            || !isset($structure->type)
            || $structure->type !== 'campusfr'
        ) {
            throw new \coding_exception(
                'Invalid CampusFR availability condition structure.'
            );
        }
    }

    public function save(): \stdClass {
        return (object)['type' => 'campusfr'];
    }

    public function is_available(
        $not,
        \core_availability\info $info,
        $grabthelot,
        $userid
    ): bool {
        if (!$info instanceof \core_availability\info_section) {
            // CampusFR wires this condition to sections only. Fail open if a
            // malformed/manual condition is ever attached elsewhere.
            return $not ? false : true;
        }

        $course = $info->get_course();
        $section = $info->get_section();

        $allowed =
            CommerceStudentCourseAccessResolver::create()
                ->resolve(
                    (int)$course->id,
                    (int)$userid,
                    time()
                )
                ->can_access_section(
                    (int)$section->id,
                    (int)$section->section
                );

        return $not ? !$allowed : $allowed;
    }

    public function get_description(
        $full,
        $not,
        \core_availability\info $info
    ): string {
        global $USER;

        if (
            $not
            || !$info instanceof \core_availability\info_section
        ) {
            return get_string(
                $not ? 'description_not' : 'description',
                'availability_campusfr'
            );
        }

        $course = $info->get_course();
        $section = $info->get_section();
        $now = time();

        $unlockat =
            CommercePedagogicalSectionLockPresentation::create()
                ->unlock_at(
                    (int)$course->id,
                    (int)$USER->id,
                    (int)$section->id,
                    $now
                );

        $message = $unlockat !== null
            ? get_string(
                'unlockat',
                'availability_campusfr',
                userdate($unlockat)
            )
            : get_string(
                'locked',
                'availability_campusfr'
            );

        $coverurl = new \moodle_url(
            '/theme/edly/section_cover.php',
            ['sectionid' => (int)$section->id]
        );

        return \html_writer::div(
            \html_writer::div(
                \html_writer::div(
                    \html_writer::span(
                        \html_writer::tag(
                            'i',
                            '',
                            ['class' => 'ri-lock-2-line']
                        ),
                        'campus-section-restricted-cover__icon',
                        ['aria-hidden' => 'true']
                    )
                    . \html_writer::span(
                        s($message),
                        'campus-section-restricted-cover__text'
                    ),
                    'campus-section-restricted-cover__message'
                ),
                'campus-section-restricted-cover campusfr-pedagogical-lock__visual',
                [
                    'role' => 'note',
                    'aria-live' => 'polite',
                    'style' => '--campusfr-lock-cover:url(\''
                        . s($coverurl->out(false))
                        . '\')',
                ]
            ),
            'campusfr-pedagogical-lock',
            [
                'data-sectionid' => (int)$section->id,
                'data-unlockat' => $unlockat ?? '',
            ]
        );
    }

    protected function get_debug_string(): string {
        return 'CampusFR pedagogical calendar';
    }
}
