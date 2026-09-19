<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797j11_participant_render_fullname_test extends advanced_testcase {
    public function test_participants_page_does_not_strip_moodle_name_fields(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/participants.php'
        );

        self::assertStringContainsString(
            'fullname($participant)',
            $source
        );
        self::assertStringNotContainsString(
            '$user = (object)[',
            $source
        );
        self::assertStringNotContainsString(
            'fullname($user)',
            $source
        );
    }

    public function test_participant_repository_fetches_every_fullname_field(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/education/participant/'
            . 'CommercePedagogicalParticipantRepository.php'
        );

        foreach ([
            'u.firstname',
            'u.lastname',
            'u.firstnamephonetic',
            'u.lastnamephonetic',
            'u.middlename',
            'u.alternatename',
        ] as $field) {
            self::assertStringContainsString($field, $source);
        }
    }
}
