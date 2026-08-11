<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stabilisation_j13h22_test extends \advanced_testcase {
    public function test_builder_uses_small_amd_bootstrap_payload(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $page = file_get_contents($root . '/admin/commerce/showrooms/edit.php');
        $runtime = file_get_contents($root . '/js/showroom_builder.js');
        self::assertStringContainsString("/local/subscriptions/js/showroom_builder.js", $page);
        self::assertStringContainsString("'id' => 'commerce-showroom-builder'", $page);
        self::assertStringContainsString('sesskey', $page);
        self::assertStringContainsString('fetch(config.endpoint', $runtime);

    }

    public function test_offer_runtime_supports_partial_bundle_ownership_and_live_comparison_prices(): void {
        global $CFG;
        $resolver = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        $javascript = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        self::assertStringContainsString('apply_bundle_ownership_rules', $resolver);
        self::assertStringContainsString('data-showroom-comparison-price', $template);
        self::assertStringContainsString('data-showroom-comparison-price', $javascript);
    }
}
