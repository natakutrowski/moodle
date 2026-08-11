<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();


final class commerce_responsive_certification_j15h1b_test extends \advanced_testcase {
    public function test_bundle_components_use_central_display_name_resolver(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/order/presentation/CommerceBundleComponentResolver.php');
        self::assertIsString($source);
        self::assertStringContainsString('CommerceProductDisplayNameResolver', $source);
        self::assertStringContainsString('CommerceProductDisplayNameResolver::create($this->database)', $source);
        self::assertStringContainsString('$displaynames->resolve(', $source);
    }
}
