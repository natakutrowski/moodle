<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExerciseCatalog;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_builder_j16c2_test extends \advanced_testcase {
    public function test_exercise_editor_exposes_twelve_grouped_multilingual_exercises(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('exercise_explorer');
        $exercise = array_values(array_filter(
            $schema['fields'],
            static fn(array $field): bool => !empty($field['exercise'])
        ));

        self::assertCount(24, $exercise);
        self::assertCount(12, array_unique(array_column($exercise, 'exercisekey')));
        self::assertSame(CommerceShowroomExerciseCatalog::keys(), array_values(array_unique(
            array_column($exercise, 'exercisekey')
        )));

        foreach ($exercise as $field) {
            self::assertTrue($field['translatable']);
            self::assertArrayHasKey('fallbacks', $field);
            self::assertSame(['fr', 'en', 'ru'], array_keys($field['fallbacks']));
        }
    }

    public function test_normalisation_persists_exercise_editor_content(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise('exercise_explorer', [
            'translations' => [
                'fr' => ['exercise01title' => 'Titre FR', 'exercise01text' => 'Texte FR'],
                'en' => ['exercise01title' => 'Title EN', 'exercise01text' => 'Text EN'],
                'ru' => ['exercise01title' => 'Заголовок', 'exercise01text' => 'Текст'],
            ],
        ]);

        self::assertSame('Titre FR', $config['translations']['fr']['exercise01title']);
        self::assertSame('Title EN', $config['translations']['en']['exercise01title']);
        self::assertSame('Заголовок', $config['translations']['ru']['exercise01title']);
    }
}
