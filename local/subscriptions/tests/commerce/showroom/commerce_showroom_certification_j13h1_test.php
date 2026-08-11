<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_certification_j13h1_test extends \advanced_testcase {
    public function test_showroom_hero_is_clean_and_currency_is_near_offers(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        self::assertIsString($source);
        self::assertStringNotContainsString('commerce-showroom-hero__topline', $source);
        self::assertStringNotContainsString('{{shoplabel}}', $source);
        self::assertStringNotContainsString('{{cartlabel}}', $source);
        self::assertStringContainsString('data-showroom-currency-toolbar', $source);
        self::assertStringContainsString('data-showroom-currency-ajax', $source);
        self::assertStringContainsString('data-showroom-smart-anchor', $source);
    }

    public function test_showroom_ajax_currency_endpoint_exists(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/ajax/showroom_prices.php');
        self::assertIsString($source);
        self::assertStringContainsString('CommerceShowroomProductResolver', $source);
        self::assertStringContainsString("'offers' => \$payload", $source);
    }

    public function test_crm_navigation_exposes_showrooms(): void {
        global $CFG;
        $keys = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationKeys.php');
        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php');
        self::assertStringContainsString("public const SHOWROOMS = 'showrooms'", (string)$keys);
        self::assertStringContainsString('CrmNavigationKeys::SHOWROOMS', (string)$registry);
        self::assertStringContainsString('admin/commerce/showrooms/index.php', (string)$registry);
    }
}
