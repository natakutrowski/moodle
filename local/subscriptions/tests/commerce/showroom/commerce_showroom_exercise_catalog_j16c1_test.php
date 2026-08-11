<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomExerciseCatalog;

final class commerce_showroom_exercise_catalog_j16c1_test extends \advanced_testcase {
    public function test_catalog_exposes_twelve_stable_multilingual_exercises(): void {
        $exercises = CommerceShowroomExerciseCatalog::all('ru');
        $this->assertCount(12, $exercises);
        $this->assertSame('01_learn_conjugation', $exercises[0]['key']);
        $this->assertSame('12_listen_memory', $exercises[11]['key']);

        foreach ($exercises as $exercise) {
            $this->assertArrayHasKey('translations', $exercise);
            foreach (['fr', 'en', 'ru'] as $language) {
                $this->assertNotEmpty($exercise['translations'][$language]['title']);
                $this->assertNotEmpty($exercise['translations'][$language]['text']);
            }
        }
    }

    public function test_initial_russian_screenshot_titles_resolve_to_stable_keys(): void {
        $this->assertSame(
            '05_true_false',
            CommerceShowroomExerciseCatalog::key_from_source_title('Верно или неверно')
        );
        $this->assertSame(
            '12_listen_memory',
            CommerceShowroomExerciseCatalog::key_from_source_title('Послушать и записать по памяти')
        );
    }
}
