<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\admin\CommercePedagogicalPromotionAdminRenderer;

final class commerce_797m71_admin_pedagogy_cockpit_test extends advanced_testcase {
    public function test_all_promotion_scoped_admin_pages_use_shared_cockpit(): void {
        $root = dirname(__DIR__, 3);
        $pages = [
            'promotion_edit.php' => 'OVERVIEW',
            'offers.php' => 'OFFERS',
            'calendar.php' => 'CALENDAR',
            'groups.php' => 'GROUPS',
            'participants.php' => 'PARTICIPANTS',
        ];

        foreach ($pages as $page => $active) {
            $source = file_get_contents(
                $root . '/admin/commerce/education/' . $page
            );

            self::assertStringContainsString(
                'CommercePedagogicalPromotionAdminRenderer::render(',
                $source,
                $page
            );
            self::assertStringContainsString(
                'CommercePedagogicalPromotionAdminRenderer::' . $active,
                $source,
                $page
            );
        }
    }

    public function test_cockpit_exposes_all_operational_sections_and_counts(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/education/admin/CommercePedagogicalPromotionAdminRenderer.php'
        );

        foreach ([
            'local_subs_commerce_ped_offer',
            'local_subs_commerce_ped_cal',
            'local_subs_commerce_ped_group',
            'local_subs_commerce_ped_join',
        ] as $table) {
            self::assertStringContainsString($table, $source);
        }

        self::assertStringContainsString('sales_are_open(time())', $source);
        self::assertStringContainsString('commerce-ped-admin-tabs', $source);
    }

    public function test_promotions_list_is_operational_dashboard_not_link_wall(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/promotions.php'
        );

        self::assertStringContainsString('commerce-ped-admin-metrics', $source);
        self::assertStringContainsString('commerce_education_admin_manage', $source);
        self::assertStringContainsString('local_subs_commerce_ped_join', $source);
        self::assertStringNotContainsString(". ' · '", $source);
    }

    public function test_cockpit_has_responsive_styles(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles.css');

        self::assertStringContainsString('.commerce-ped-admin-context', $css);
        self::assertStringContainsString('.commerce-ped-admin-tabs', $css);
        self::assertStringContainsString('@media (max-width: 575.98px)', $css);
    }
}
