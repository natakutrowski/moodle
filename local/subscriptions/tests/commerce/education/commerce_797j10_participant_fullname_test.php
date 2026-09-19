<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantRepository;

final class commerce_797j10_participant_fullname_test extends advanced_testcase {
    public function test_participant_read_model_contains_all_moodle_fullname_fields(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'DEV',
            'lastname' => 'Élève',
        ]);

        $now = time();
        $promotionid = $DB->insert_record(
            'local_subs_commerce_ped_promo',
            (object)[
                'promotionkey' => 'fullname-test',
                'name' => 'Fullname test',
                'courseid' => (int)$course->id,
                'status' => 'open',
                'published' => 1,
                'salesopensat' => null,
                'salesclosesat' => null,
                'startsat' => $now,
                'endsat' => null,
                'capacitytotal' => null,
                'createdby' => null,
                'modifiedby' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $DB->insert_record(
            'local_subs_commerce_ped_access',
            (object)[
                'courseid' => (int)$course->id,
                'userid' => (int)$user->id,
                'promotionid' => (int)$promotionid,
                'profile' => 'promotion_progressive',
                'createdby' => null,
                'modifiedby' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $participants =
            CommercePedagogicalParticipantRepository::create($DB)
                ->for_promotion((int)$promotionid);

        self::assertCount(1, $participants);
        $participant = reset($participants);

        foreach ([
            'firstname',
            'lastname',
            'firstnamephonetic',
            'lastnamephonetic',
            'middlename',
            'alternatename',
        ] as $field) {
            self::assertTrue(
                property_exists($participant, $field),
                'Missing Moodle fullname field: ' . $field
            );
        }

        self::assertSame('DEV Élève', fullname($participant));
    }
}
