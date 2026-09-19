<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797m72_promotion_editor_ux_test extends advanced_testcase {
    public function test_editor_is_split_into_operational_sections(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/promotion_edit.php'
        );

        foreach ([
            'commerce_education_m72_identity_title',
            'commerce_education_m72_cycle_title',
            'commerce_education_m72_sales_title',
            'commerce_education_m72_capacity_title',
        ] as $key) {
            self::assertStringContainsString($key, $source);
        }

        self::assertStringContainsString(
            'commerce-ped-promotion-editor-grid',
            $source
        );
        self::assertStringContainsString(
            'commerce-ped-promotion-editor-actions',
            $source
        );
    }

    public function test_editor_keeps_the_existing_business_inputs(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/promotion_edit.php'
        );

        foreach ([
            "'name' => 'promotionkey'",
            "'name' => 'name'",
            "'courseid'",
            "'status'",
            "'name' => 'published'",
            "'name' => 'salesopensat'",
            "'name' => 'salesclosesat'",
            "'name' => 'startsat'",
            "'name' => 'endsat'",
            "'name' => 'capacitytotal'",
        ] as $needle) {
            self::assertStringContainsString($needle, $source, $needle);
        }
    }

    public function test_existing_promotion_shows_effective_sales_state(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/promotion_edit.php'
        );

        self::assertStringContainsString(
            '$existing->sales_are_open(time())',
            $source
        );
        self::assertStringContainsString(
            'commerce_education_m72_sales_effective_open',
            $source
        );
        self::assertStringContainsString(
            'commerce_education_m72_sales_effective_closed',
            $source
        );
    }

    public function test_editor_has_accessible_labels_and_responsive_styles(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/promotion_edit.php'
        );
        $css = file_get_contents($root . '/styles.css');

        foreach ([
            'ped-promo-name',
            'ped-promo-key',
            'ped-promo-course',
            'ped-promo-status',
            'ped-promo-starts',
            'ped-promo-ends',
            'ped-promo-published',
            'ped-promo-sales-open',
            'ped-promo-sales-close',
            'ped-promo-capacity',
        ] as $id) {
            self::assertStringContainsString($id, $source);
        }

        self::assertStringContainsString(
            '.commerce-ped-promotion-editor-section',
            $css
        );
        self::assertStringContainsString(
            '.commerce-ped-promotion-fields-grid',
            $css
        );
        self::assertStringContainsString(
            '@media (max-width: 575.98px)',
            $css
        );
    }
}
