<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\showroom\cms\CommerceShowroomExerciseCatalog;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExerciseExplorerPresenter;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_runtime_j16c3_test extends \advanced_testcase {
    public function test_public_presenter_exposes_twelve_canonical_exercises(): void {
        $this->resetAfterTest();

        $config = [
            'translations' => [
                'ru' => [
                    'exercise01title' => 'Заголовок из Builder',
                    'exercise01text' => 'Подзаголовок из Builder',
                ],
            ],
        ];

        $data = (new CommerceShowroomExerciseExplorerPresenter(
            \context_system::instance()
        ))->apply([], $config, null, 'ru');

        self::assertCount(12, $data['exercises']);
        self::assertSame(
            CommerceShowroomExerciseCatalog::keys(),
            array_column($data['exercises'], 'key')
        );
        self::assertSame('Заголовок из Builder', $data['exercises'][0]['title']);
        self::assertSame('Подзаголовок из Builder', $data['exercises'][0]['text']);
        self::assertTrue($data['exercises'][0]['active']);
        self::assertFalse($data['exercises'][1]['active']);
        self::assertFalse($data['exerciseinitialhaspreview']);
    }

    public function test_catalog_is_used_when_builder_override_is_empty(): void {
        $this->resetAfterTest();

        $data = (new CommerceShowroomExerciseExplorerPresenter(
            \context_system::instance()
        ))->apply([], [], null, 'en');

        self::assertSame('Learn the conjugation', $data['exercises'][0]['title']);
        self::assertSame(
            'Listen to and memorise the verb conjugation.',
            $data['exercises'][0]['text']
        );
    }
}
