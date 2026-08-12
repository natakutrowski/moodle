<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeExecutionService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner;

/**
 * @covers \local_subscriptions\commerce\customer\merge\CommerceCustomerMergeExecutionService
 */
final class commerce_customer_merge_execution_m42d_test extends advanced_testcase {
    public function test_safe_commerce_and_crm_data_are_moved_and_source_is_suspended(): void {
        global $DB;

        $this->resetAfterTest(true);

        $target = $this->getDataGenerator()->create_user([
            'email' => 'target@example.com',
        ]);
        $source = $this->getDataGenerator()->create_user([
            'email' => 'source@example.com',
        ]);

        $now = time();
        $purchaseid = $DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object)[
                'purchaseuuid' => bin2hex(random_bytes(16)),
                'reference' => 'M42D-' . random_int(100000, 999999),
                'type' => 'digital',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => $source->id,
                'customeremail' => $source->email,
                'status' => 'paid',
                'currency' => 'EUR',
                'subtotalminor' => 100,
                'discountminor' => 0,
                'totalminor' => 100,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $DB->insert_record('local_subscriptions_user_tag', (object)[
            'userid' => $source->id,
            'tag' => 'legacy-digital',
            'createdby' => 0,
            'timecreated' => $now,
        ]);

        $service = new CommerceCustomerMergeExecutionService(
            $DB,
            new CommerceCustomerMergePlanner($DB)
        );

        $result = $service->execute(
            [$source->id, $target->id],
            (int)$target->id,
            (int)$target->id
        );

        self::assertGreaterThan(0, $result->mergeid);
        self::assertSame(
            (int)$target->id,
            (int)$DB->get_field(
                'local_subscriptions_commerce_purchase',
                'userid',
                ['id' => $purchaseid]
            )
        );
        self::assertTrue($DB->record_exists(
            'local_subscriptions_user_tag',
            ['userid' => $target->id, 'tag' => 'legacy-digital']
        ));
        self::assertEquals(
            1,
            $DB->get_field('user', 'suspended', ['id' => $source->id])
        );
        self::assertTrue($DB->record_exists(
            'local_subs_identity_merge_source',
            ['sourceuserid' => $source->id]
        ));
    }

    public function test_source_with_pedagogical_history_is_consolidated_and_suspended(): void {
        global $DB;

        $this->resetAfterTest(true);

        $target = $this->getDataGenerator()->create_user();
        $source = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($source->id, $course->id);

        $planner = new CommerceCustomerMergePlanner($DB);
        $service = new CommerceCustomerMergeExecutionService($DB, $planner);
        $plan = $planner->build(
            [(int)$source->id, (int)$target->id],
            (int)$target->id
        );

        self::assertNotContains(
            CommerceCustomerMergeExecutionService::BLOCK_PEDAGOGICAL_HISTORY,
            array_column($service->blockers($plan), 'type')
        );

        $result = $service->execute(
            [(int)$source->id, (int)$target->id],
            (int)$target->id,
            (int)$target->id
        );

        self::assertGreaterThan(0, $result->mergeid);
        self::assertGreaterThan(0, $result->transfers['learning_enrolments']);
        self::assertEquals(
            1,
            $DB->get_field('user', 'suspended', ['id' => $source->id])
        );
        self::assertGreaterThan(0, $DB->count_records_sql(
            'SELECT COUNT(1)
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = :userid
                AND e.courseid = :courseid',
            ['userid' => $target->id, 'courseid' => $course->id]
        ));
    }

    public function test_duplicate_tags_are_deduplicated_without_unique_key_failure(): void {
        global $DB;

        $this->resetAfterTest(true);

        $target = $this->getDataGenerator()->create_user();
        $source = $this->getDataGenerator()->create_user();
        $now = time();

        foreach ([$target->id, $source->id] as $userid) {
            $DB->insert_record('local_subscriptions_user_tag', (object)[
                'userid' => $userid,
                'tag' => 'vip',
                'createdby' => 0,
                'timecreated' => $now,
            ]);
        }

        $result = (new CommerceCustomerMergeExecutionService(
            $DB,
            new CommerceCustomerMergePlanner($DB)
        ))->execute(
            [$source->id, $target->id],
            (int)$target->id,
            (int)$target->id
        );

        self::assertSame(1, $result->transfers['tagsdeduplicated']);
        self::assertSame(
            1,
            $DB->count_records(
                'local_subscriptions_user_tag',
                ['userid' => $target->id, 'tag' => 'vip']
            )
        );
    }
}
