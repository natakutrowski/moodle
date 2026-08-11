<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewZipImporter;

final class commerce_showroom_exercise_zip_import_j16c1_test extends \advanced_testcase {
    public function test_importer_recognises_supplied_windows_unicode_filename_pattern(): void {
        $manager = new CommerceShowroomExercisePreviewMediaManager(\context_system::instance());
        $importer = new CommerceShowroomExercisePreviewZipImporter($manager);

        $this->assertSame(
            '05_true_false',
            $importer->match_filename(
                '#U0412#U0435#U0440#U043d#U043e #U0438#U043b#U0438 #U043d#U0435#U0432#U0435#U0440#U043d#U043e.png'
            )
        );
    }

    public function test_importer_recognises_future_stable_key_filename(): void {
        $manager = new CommerceShowroomExercisePreviewMediaManager(\context_system::instance());
        $importer = new CommerceShowroomExercisePreviewZipImporter($manager);

        $this->assertSame(
            '10_complete_sentence',
            $importer->match_filename('10_complete_sentence-fr.png')
        );
    }
}
