<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewZipImporter;

final class commerce_showroom_exercise_import_square_preview_j16c64_test extends \advanced_testcase {
    public function test_unicode_filename_decoder_and_stable_key_matching(): void {
        $decoded = CommerceShowroomExercisePreviewZipImporter::decode_unicode_filename(
            '#U0418#U0437#U0443#U0447#U0430#U0435#U043c.png'
        );
        self::assertSame('Изучаем.png', $decoded);

        $reflection = new \ReflectionClass(CommerceShowroomExercisePreviewZipImporter::class);
        $importer = $reflection->newInstanceWithoutConstructor();
        self::assertSame('01_learn_conjugation', $importer->match_filename('01_learn_conjugation_ru.png'));
        self::assertSame('12_listen_memory', $importer->match_filename('12_listen_memory.png'));
    }

    public function test_public_preview_keeps_square_geometry(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('aspect-ratio: 1 / 1;', $css);
    }
}
