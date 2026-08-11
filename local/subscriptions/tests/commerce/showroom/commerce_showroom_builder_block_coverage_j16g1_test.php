<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;

final class commerce_showroom_builder_block_coverage_j16g1_test extends \advanced_testcase {
    public function test_builder_schema_coverage_matches_addable_registry(): void {
        $schemas = CommerceShowroomBlockEditorRegistry::schemas();
        foreach (CommerceShowroomBlockTypeRegistry::definitions() as $type => $definition) {
            if (($definition['addable'] ?? true) === false) {
                continue;
            }
            self::assertArrayHasKey($type, $schemas);
        }
        self::assertArrayHasKey('testimonials', $schemas);
        self::assertContains('items', array_column($schemas['testimonials']['fields'], 'name'));
    }
}
