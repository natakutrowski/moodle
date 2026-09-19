<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\admin;

defined('MOODLE_INTERNAL') || die();

/** Compact navigation inside the Pedagogy workspace. */
final class CommerceEducationNavigationRenderer {
    public const PROMOTIONS = 'promotions';
    public const COURSES = 'courses';

    public static function render(string $active): string {
        $items = [
            self::PROMOTIONS => [
                get_string('commerce_education_promotions_title', 'local_subscriptions'),
                new \moodle_url('/local/subscriptions/admin/commerce/education/promotions.php'),
                'fa-flag-checkered',
            ],
            self::COURSES => [
                get_string('commerce_education_courses_title', 'local_subscriptions'),
                new \moodle_url('/local/subscriptions/admin/commerce/education/courses.php'),
                'fa-unlock-alt',
            ],
        ];

        if (!isset($items[$active])) {
            throw new \coding_exception('Unknown Pedagogy navigation key: ' . $active);
        }

        $links = '';
        foreach ($items as $key => [$label, $url, $icon]) {
            $links .= \html_writer::link(
                $url,
                \html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true'])
                    . \html_writer::span($label),
                [
                    'class' => 'commerce-ped-m75-local-nav-link' . ($key === $active ? ' is-active' : ''),
                    'aria-current' => $key === $active ? 'page' : null,
                ]
            );
        }

        return \html_writer::tag(
            'nav',
            \html_writer::div($links, 'commerce-ped-m75-local-nav-list'),
            [
                'class' => 'commerce-ped-m75-local-nav',
                'aria-label' => get_string('commerce_education_m75_navigation', 'local_subscriptions'),
            ]
        );
    }
}
