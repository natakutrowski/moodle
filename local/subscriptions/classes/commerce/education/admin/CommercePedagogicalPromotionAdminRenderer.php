<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

/**
 * Shared administrator cockpit header for one pedagogical promotion.
 *
 * M7 keeps all promotion-scoped administration under one stable visual
 * context instead of making Offers / Calendar / Groups / Participants feel
 * like unrelated pages.
 */
final class CommercePedagogicalPromotionAdminRenderer {
    public const OVERVIEW = 'overview';
    public const OFFERS = 'offers';
    public const CALENDAR = 'calendar';
    public const GROUPS = 'groups';
    public const PARTICIPANTS = 'participants';

    public static function render(
        CommercePedagogicalPromotion $promotion,
        string $active,
        ?\moodle_database $db = null
    ): string {
        global $DB;
        $db = $db ?? $DB;

        $promotionid = (int)$promotion->get_id();
        if ($promotionid <= 0) {
            return '';
        }

        $course = get_course($promotion->get_course_id());
        $coursecontext = \context_course::instance((int)$course->id);
        $coursename = format_string(
            (string)$course->fullname,
            true,
            ['context' => $coursecontext]
        );

        $counts = [
            self::OFFERS => $db->count_records(
                'local_subs_commerce_ped_offer',
                ['promotionid' => $promotionid]
            ),
            self::CALENDAR => $db->count_records(
                'local_subs_commerce_ped_cal',
                ['promotionid' => $promotionid]
            ),
            self::GROUPS => $db->count_records(
                'local_subs_commerce_ped_group',
                ['promotionid' => $promotionid, 'active' => 1]
            ),
            self::PARTICIPANTS => $db->count_records(
                'local_subs_commerce_ped_join',
                ['promotionid' => $promotionid, 'state' => 'active']
            ),
        ];

        $statusclass = match ($promotion->get_status()) {
            CommercePedagogicalPromotionStatus::OPEN => 'text-bg-success',
            CommercePedagogicalPromotionStatus::STARTED => 'text-bg-primary',
            CommercePedagogicalPromotionStatus::SCHEDULED => 'text-bg-info',
            CommercePedagogicalPromotionStatus::FULL => 'text-bg-warning',
            CommercePedagogicalPromotionStatus::FINISHED => 'text-bg-dark',
            default => 'text-bg-secondary',
        };

        $statuslabel = get_string(
            'commerce_education_promotion_status_' . $promotion->get_status(),
            'local_subscriptions'
        );

        $salesopen = $promotion->sales_are_open(time());
        $badges = \html_writer::span(
            $statuslabel,
            'badge rounded-pill ' . $statusclass
        );
        $badges .= ' ' . \html_writer::span(
            $promotion->is_published()
                ? get_string('commerce_education_admin_published', 'local_subscriptions')
                : get_string('commerce_education_admin_unpublished', 'local_subscriptions'),
            'badge rounded-pill ' . ($promotion->is_published() ? 'text-bg-light border' : 'text-bg-secondary')
        );
        $badges .= ' ' . \html_writer::span(
            $salesopen
                ? get_string('commerce_education_admin_sales_open', 'local_subscriptions')
                : get_string('commerce_education_admin_sales_closed', 'local_subscriptions'),
            'badge rounded-pill ' . ($salesopen ? 'text-bg-success' : 'text-bg-light border')
        );

        $meta = [];
        $meta[] = self::meta(
            'fa-graduation-cap',
            get_string('course'),
            $coursename
        );
        if ($promotion->get_starts_at() !== null) {
            $meta[] = self::meta(
                'fa-calendar-check-o',
                get_string('commerce_education_starts_at', 'local_subscriptions'),
                userdate($promotion->get_starts_at())
            );
        }
        if ($promotion->get_sales_closes_at() !== null) {
            $meta[] = self::meta(
                'fa-shopping-cart',
                get_string('commerce_education_sales_closes_at', 'local_subscriptions'),
                userdate($promotion->get_sales_closes_at())
            );
        }
        $capacity = $promotion->get_capacity_total();
        $meta[] = self::meta(
            'fa-users',
            get_string('commerce_education_capacity', 'local_subscriptions'),
            $capacity !== null
                ? (string)$capacity
                : get_string('commerce_education_capacity_unlimited', 'local_subscriptions')
        );

        $tabs = [
            self::OVERVIEW => [
                get_string('commerce_education_admin_overview', 'local_subscriptions'),
                new \moodle_url(
                    '/local/subscriptions/admin/commerce/education/promotion_edit.php',
                    ['id' => $promotionid]
                ),
                null,
            ],
            self::OFFERS => [
                get_string('commerce_education_offers', 'local_subscriptions'),
                new \moodle_url(
                    '/local/subscriptions/admin/commerce/education/offers.php',
                    ['promotionid' => $promotionid]
                ),
                $counts[self::OFFERS],
            ],
            self::CALENDAR => [
                get_string('commerce_education_calendar', 'local_subscriptions'),
                new \moodle_url(
                    '/local/subscriptions/admin/commerce/education/calendar.php',
                    ['promotionid' => $promotionid]
                ),
                $counts[self::CALENDAR],
            ],
            self::GROUPS => [
                get_string('commerce_education_groups', 'local_subscriptions'),
                new \moodle_url(
                    '/local/subscriptions/admin/commerce/education/groups.php',
                    ['promotionid' => $promotionid]
                ),
                $counts[self::GROUPS],
            ],
            self::PARTICIPANTS => [
                get_string('commerce_education_participants', 'local_subscriptions'),
                new \moodle_url(
                    '/local/subscriptions/admin/commerce/education/participants.php',
                    ['promotionid' => $promotionid]
                ),
                $counts[self::PARTICIPANTS],
            ],
        ];

        $nav = '';
        foreach ($tabs as $key => [$label, $url, $count]) {
            $content = \html_writer::span((string)$label, 'commerce-ped-admin-tab-label');
            if ($count !== null) {
                $content .= \html_writer::span((string)$count, 'commerce-ped-admin-tab-count');
            }
            $nav .= \html_writer::link(
                $url,
                $content,
                [
                    'class' => 'commerce-ped-admin-tab' . ($active === $key ? ' is-active' : ''),
                    'aria-current' => $active === $key ? 'page' : null,
                ]
            );
        }

        return \html_writer::start_div('commerce-ped-admin-context')
            . \html_writer::start_div('commerce-ped-admin-context-main')
            . \html_writer::start_div('commerce-ped-admin-context-copy')
            . \html_writer::div(
                s($promotion->get_name()),
                'commerce-ped-admin-context-title'
            )
            . \html_writer::div(
                s($promotion->get_promotion_key()),
                'commerce-ped-admin-context-key'
            )
            . \html_writer::end_div()
            . \html_writer::start_div('commerce-ped-admin-context-actions')
            . \html_writer::link(
                new \moodle_url('/local/subscriptions/admin/commerce/education/promotions.php'),
                \html_writer::tag('i', '', ['class' => 'fa fa-arrow-left', 'aria-hidden' => 'true'])
                    . \html_writer::span(get_string('commerce_education_m75_back_to_promotions', 'local_subscriptions')),
                ['class' => 'commerce-ped-admin-back-link']
            )
            . \html_writer::div($badges, 'commerce-ped-admin-context-badges')
            . \html_writer::end_div()
            . \html_writer::end_div()
            . \html_writer::div(
                implode('', $meta),
                'commerce-ped-admin-context-meta'
            )
            . \html_writer::div(
                $nav,
                'commerce-ped-admin-tabs',
                ['role' => 'navigation', 'aria-label' => get_string('commerce_education_admin_navigation', 'local_subscriptions')]
            )
            . \html_writer::end_div();
    }

    private static function meta(string $icon, string $label, string $value): string {
        return \html_writer::start_div('commerce-ped-admin-meta-item')
            . \html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true'])
            . \html_writer::start_div('commerce-ped-admin-meta-copy')
            . \html_writer::div(s($label), 'commerce-ped-admin-meta-label')
            . \html_writer::div(s($value), 'commerce-ped-admin-meta-value')
            . \html_writer::end_div()
            . \html_writer::end_div();
    }
}
