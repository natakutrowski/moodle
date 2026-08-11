<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;

final class commerce_showroom_block_editors_j13g3_test extends \advanced_testcase {
    public function test_all_addable_block_types_have_dedicated_editor_schemas(): void {
        $schemas = CommerceShowroomBlockEditorRegistry::schemas();
        foreach (CommerceShowroomBlockTypeRegistry::definitions() as $type => $definition) {
            if (($definition['addable'] ?? true) === false) {
                continue;
            }
            self::assertArrayHasKey($type, $schemas, 'Missing editor schema for addable block: ' . $type);
            self::assertNotEmpty($schemas[$type]['fields']);
        }
    }

    public function test_legacy_aliases_are_not_offered_as_new_editor_blocks(): void {
        $definitions = CommerceShowroomBlockTypeRegistry::definitions();
        foreach (['journey', 'method', 'cta'] as $type) {
            self::assertFalse($definitions[$type]['addable'] ?? true);
        }
    }
}
