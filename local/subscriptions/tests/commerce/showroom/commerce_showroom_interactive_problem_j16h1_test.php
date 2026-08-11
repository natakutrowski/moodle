<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockDefaultsCatalog;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;

final class commerce_showroom_interactive_problem_j16h1_test extends advanced_testcase {
    public function test_interactive_problem_is_a_first_class_builder_block(): void {
        $types = CommerceShowroomBlockTypeRegistry::definitions();
        $this->assertArrayHasKey('problem_interactive', $types);
        $schema = CommerceShowroomBlockEditorRegistry::schema('problem_interactive');
        $names = array_column($schema['fields'], 'name');
        $this->assertContains('choices', $names);
        $this->assertContains('correctanswer', $names);
        $this->assertContains('consequences', $names);
    }

    public function test_interactive_problem_has_three_language_defaults(): void {
        $defaults = CommerceShowroomBlockDefaultsCatalog::for_block('third-group-verbs', 'problem_interactive');
        $this->assertArrayHasKey('translations', $defaults);
        foreach (['fr', 'en', 'ru'] as $language) {
            $this->assertArrayHasKey($language, $defaults['translations']);
            $this->assertNotEmpty($defaults['translations'][$language]['title']);
            $this->assertSame('Je prends', $defaults['translations'][$language]['correctanswer']);
        }
    }
}
