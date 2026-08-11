<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_problem_block_j16j1_test extends \advanced_testcase {
    public function test_problem_block_supports_current_accent_and_solution_fields(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('problem');
        $names = array_column($schema['fields'], 'name');
        foreach (['title', 'titleaccent', 'solutiontitle', 'solutiontitleaccent', 'solutiontext', 'solutiontextaccent'] as $name) {
            self::assertContains($name, $names);
        }
    }
}
