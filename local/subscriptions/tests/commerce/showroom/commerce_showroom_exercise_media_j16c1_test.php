<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewMediaManager;

final class commerce_showroom_exercise_media_j16c1_test extends \advanced_testcase {
    public function test_localised_preview_falls_back_to_default(): void {
        $this->resetAfterTest(true);

        $context = \context_system::instance();
        $manager = new CommerceShowroomExercisePreviewMediaManager($context);
        $blockid = 16001;
        $key = '01_learn_conjugation';
        $field = CommerceShowroomExercisePreviewMediaManager::field_name($key, 'default');

        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_subscriptions',
            'filearea' => 'showroom_block_media',
            'itemid' => $blockid,
            'filepath' => '/' . $field . '/',
            'filename' => $field . '.png',
        ], base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwC'
            . 'AAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));

        $resolved = $manager->resolve($blockid, $key, 'fr');
        $this->assertNotNull($resolved);
        $this->assertSame('default', $resolved['language']);
        $this->assertStringContainsString('showroom_block_media', $resolved['url']->out(false));
    }

    public function test_localised_preview_has_priority_over_default(): void {
        $this->resetAfterTest(true);

        $context = \context_system::instance();
        $manager = new CommerceShowroomExercisePreviewMediaManager($context);
        $blockid = 16002;
        $key = '02_memory_pairs';
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwC'
            . 'AAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        foreach (['default', 'en'] as $language) {
            $field = CommerceShowroomExercisePreviewMediaManager::field_name($key, $language);
            get_file_storage()->create_file_from_string([
                'contextid' => $context->id,
                'component' => 'local_subscriptions',
                'filearea' => 'showroom_block_media',
                'itemid' => $blockid,
                'filepath' => '/' . $field . '/',
                'filename' => $field . '.png',
            ], $png);
        }

        $resolved = $manager->resolve($blockid, $key, 'en');
        $this->assertNotNull($resolved);
        $this->assertSame('en', $resolved['language']);
    }
}
