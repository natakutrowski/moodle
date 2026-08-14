<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerPreferredIdentityPasswordRepairService;

final class commerce_customer_preferred_identity_password_repair_m14_test extends advanced_testcase {
    public function test_dry_run_then_execute_repairs_old_swap_and_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user([
            'username' => 'preferredlogin',
            'email' => 'preferred@example.test',
            'auth' => 'manual',
            'password' => 'TargetPassword-M14-test-1!',
        ]);
        $source = $this->getDataGenerator()->create_user([
            'username' => 'oldtarget',
            'email' => 'oldtarget@example.test',
            'auth' => 'manual',
            'password' => 'SourcePassword-M14-test-2!',
            'suspended' => 1,
        ]);
        // The generator does not guarantee that the returned user object contains
        // the persisted password hash. Read the hashes back from Moodle's user table.
        $targetpassword = (string)$DB->get_field('user', 'password', ['id' => $target->id], MUST_EXIST);
        $sourcepassword = (string)$DB->get_field('user', 'password', ['id' => $source->id], MUST_EXIST);
        $result = [
            'targetuserid' => (int)$target->id,
            'sourceuserids' => [(int)$source->id],
            'identitytransfer' => [
                'targetuserid' => (int)$target->id,
                'sourceuserid' => (int)$source->id,
                'target_after_email' => 'preferred@example.test',
                'target_after_username' => 'preferredlogin',
                'source_after_email' => 'oldtarget@example.test',
                'source_after_username' => 'oldtarget',
            ],
        ];
        $mergeid = (int)$DB->insert_record('local_subs_identity_merge', (object)[
            'mergeuuid' => bin2hex(random_bytes(16)), 'targetuserid' => (int)$target->id,
            'status' => 'completed', 'planjson' => '{}',
            'resultjson' => json_encode($result), 'performedby' => 2,
            'timecreated' => time(), 'timemodified' => time(),
        ]);

        $service = new CommerceCustomerPreferredIdentityPasswordRepairService($DB);
        $dryrun = $service->repair_by_preferred_email('preferred@example.test');
        $this->assertSame('ready', $dryrun['status']);
        $this->assertSame($targetpassword, $DB->get_field('user', 'password', ['id' => $target->id]));

        $done = $service->repair_by_preferred_email('preferred@example.test', true);
        $this->assertSame('repaired', $done['status']);
        $this->assertSame($sourcepassword, $DB->get_field('user', 'password', ['id' => $target->id]));
        $this->assertSame($targetpassword, $DB->get_field('user', 'password', ['id' => $source->id]));

        $again = $service->repair_by_preferred_email('preferred@example.test', true);
        $this->assertSame('already_repaired', $again['status']);
        $audit = json_decode((string)$DB->get_field('local_subs_identity_merge', 'resultjson', ['id' => $mergeid]), true);
        $this->assertTrue($audit['identitytransfer']['password_repair']['completed']);
        $this->assertStringNotContainsString($sourcepassword, json_encode($audit));
        $this->assertStringNotContainsString($targetpassword, json_encode($audit));
    }
}