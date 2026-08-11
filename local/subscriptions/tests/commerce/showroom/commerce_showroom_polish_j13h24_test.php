<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_polish_j13h24_test extends \advanced_testcase {
    public function test_offer_pricing_and_builder_runtime_are_present(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $page = file_get_contents($root . '/admin/commerce/showrooms/edit.php');
        $runtime = file_get_contents($root . '/js/showroom_builder.js');
        self::assertStringContainsString("/local/subscriptions/js/showroom_builder.js", $page);
        self::assertStringContainsString("'id' => 'commerce-showroom-builder'", $page);
        self::assertStringContainsString('sesskey', $page);
        self::assertStringContainsString('fetch(config.endpoint', $runtime);

    }
}
