<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommercePedagogicalAvailabilitySynchronizer;

final class commerce_797j14_runtime_availability_test extends advanced_testcase {
    public function test_add_condition_to_empty_availability(): void {
        $json =
            CommercePedagogicalAvailabilitySynchronizer::add_campusfr_condition(
                null
            );
        $decoded = json_decode($json);

        self::assertSame('&', $decoded->op);
        self::assertCount(1, $decoded->c);
        self::assertSame('campusfr', $decoded->c[0]->type);
        self::assertSame([true], $decoded->showc);
    }

    public function test_add_condition_preserves_existing_and_conditions(): void {
        $original = json_encode((object)[
            'op' => '&',
            'c' => [
                (object)[
                    'type' => 'date',
                    'd' => '>=',
                    't' => 123,
                ],
            ],
            'showc' => [false],
        ]);

        $json =
            CommercePedagogicalAvailabilitySynchronizer::add_campusfr_condition(
                $original
            );
        $decoded = json_decode($json);

        self::assertCount(2, $decoded->c);
        self::assertSame('date', $decoded->c[0]->type);
        self::assertSame('campusfr', $decoded->c[1]->type);
        self::assertSame([false, true], $decoded->showc);
    }

    public function test_add_condition_is_idempotent(): void {
        $first =
            CommercePedagogicalAvailabilitySynchronizer::add_campusfr_condition(
                null
            );
        $second =
            CommercePedagogicalAvailabilitySynchronizer::add_campusfr_condition(
                $first
            );

        self::assertSame($first, $second);
    }

    public function test_remove_condition_restores_unrestricted_section(): void {
        $withcondition =
            CommercePedagogicalAvailabilitySynchronizer::add_campusfr_condition(
                null
            );

        self::assertNull(
            CommercePedagogicalAvailabilitySynchronizer::remove_campusfr_condition(
                $withcondition
            )
        );
    }

    public function test_runtime_condition_source_uses_individual_resolver(): void {
        $moodleroot = dirname(__DIR__, 5);
        $source = file_get_contents(
            $moodleroot
            . '/availability/condition/campusfr/classes/condition.php'
        );

        self::assertStringContainsString(
            'CommerceStudentCourseAccessResolver::create()',
            $source
        );
        self::assertStringContainsString(
            'can_access_section(',
            $source
        );
        self::assertStringContainsString(
            '$info->get_section()',
            $source
        );
    }
}
