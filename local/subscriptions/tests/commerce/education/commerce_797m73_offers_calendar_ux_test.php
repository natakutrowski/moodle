<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797m73_offers_calendar_ux_test extends advanced_testcase {
    public function test_offers_page_is_card_based_operational_view(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/offers.php'
        );

        foreach ([
            'commerce-ped-m73-metrics',
            'commerce-ped-offer-card',
            'commerce-ped-offer-card-metrics',
            'commerce-ped-offer-owner-form',
            'commerce_education_m73_offer_pricing_title',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            'local_subs_commerce_ped_join',
            $source
        );
        self::assertStringContainsString(
            'local_subs_commerce_ped_jprice',
            $source
        );
        self::assertStringNotContainsString(
            'html_writer::table($table)',
            $source
        );
    }

    public function test_offers_page_keeps_existing_business_controls(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/offers.php'
        );

        foreach ([
            "\$action === 'link' || \$action === 'update'",
            "'name' => 'action', 'value' => 'update'",
            "'name' => 'action', 'value' => 'joinprice'",
            "'name' => 'action', 'value' => 'unlink'",
            "'name' => 'capacity'",
            "'name' => 'joinprice[' . \$currency . ']'",
        ] as $needle) {
            self::assertStringContainsString($needle, $source, $needle);
        }
    }

    public function test_calendar_is_timeline_with_live_release_states(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/calendar.php'
        );

        foreach ([
            'commerce-ped-calendar-timeline',
            'commerce-ped-calendar-item',
            'is_unlocked_at($now)',
            '$nextitem',
            'commerce_education_m73_calendar_next',
            'commerce-ped-m73-calendar-coverage',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            'html_writer::table($table)',
            $source
        );
    }

    public function test_calendar_add_form_only_offers_unscheduled_sections(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/calendar.php'
        );

        self::assertStringContainsString(
            '$scheduledbysection[(int)$section->id]',
            $source
        );
        self::assertStringContainsString(
            '!isset($scheduledbysection[(int)$section->id])',
            $source
        );
        self::assertStringContainsString(
            'commerce_education_m73_calendar_all_scheduled',
            $source
        );
    }

    public function test_m73_has_responsive_styles(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles.css');

        foreach ([
            '.commerce-ped-m73-layout',
            '.commerce-ped-offer-card',
            '.commerce-ped-calendar-timeline',
            '.commerce-ped-calendar-item.is-next',
            '@media (max-width: 575.98px)',
        ] as $needle) {
            self::assertStringContainsString($needle, $css);
        }
    }
}
