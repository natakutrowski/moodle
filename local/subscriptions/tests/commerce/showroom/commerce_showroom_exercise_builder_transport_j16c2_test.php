<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_builder_transport_j16c2_test extends \advanced_testcase {
    public function test_ajax_supports_exercise_preview_crud_and_zip_import(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/ajax.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString("case 'uploadexercisepreview':", $source);
        self::assertStringContainsString("case 'deleteexercisepreview':", $source);
        self::assertStringContainsString("case 'importexercisezip':", $source);
        self::assertStringContainsString('CommerceShowroomExercisePreviewZipImporter', $source);
    }

    public function test_builder_groups_exercises_and_media_slots(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/js/showroom_builder.js'
        );
        self::assertIsString($source);
        self::assertStringContainsString('renderExerciseEditor', $source);
        self::assertStringContainsString('uploadExercisePreview', $source);
        self::assertStringContainsString('importExerciseZip', $source);
        self::assertStringContainsString('exercise-media-batch-updated', $source);
    }
}
