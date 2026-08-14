<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerPreferredIdentityTransferService;

final class commerce_customer_preferred_identity_transfer_m13c_test extends advanced_testcase {
    public function test_login_identity_swap_keeps_retained_password_by_default(): void {
        global $DB;
        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user([
            'username' => 'richaccount', 'email' => 'rich@example.test',
            'firstname' => 'Sergey', 'lastname' => 'Sergeev', 'auth' => 'manual',
            'password' => 'TargetPassword-M14-choice-1!',
        ]);
        $source = $this->getDataGenerator()->create_user([
            'username' => 'preferredlogin', 'email' => 'preferred@example.test',
            'firstname' => 'Serhii', 'lastname' => 'Shastalo', 'auth' => 'manual',
            'password' => 'SourcePassword-M14-choice-2!', 'suspended' => 1,
        ]);
        $targetpassword = (string)$DB->get_field('user', 'password', ['id' => $target->id], MUST_EXIST);
        $sourcepassword = (string)$DB->get_field('user', 'password', ['id' => $source->id], MUST_EXIST);

        $result = (new CommerceCustomerPreferredIdentityTransferService($DB))->transfer((int)$target->id, (int)$source->id);
        $targetafter = $DB->get_record('user', ['id' => $target->id], '*', MUST_EXIST);
        $sourceafter = $DB->get_record('user', ['id' => $source->id], '*', MUST_EXIST);

        $this->assertSame('preferred@example.test', $targetafter->email);
        $this->assertSame('preferredlogin', $targetafter->username);
        $this->assertSame('Sergey', $targetafter->firstname);
        $this->assertSame('rich@example.test', $sourceafter->email);
        $this->assertSame('richaccount', $sourceafter->username);
        $this->assertSame($targetpassword, $targetafter->password);
        $this->assertSame($sourcepassword, $sourceafter->password);
        $this->assertSame((int)$target->id, $result['password_owner_userid']);
        $this->assertFalse($result['password_swapped']);
    }

    public function test_admin_can_explicitly_keep_preferred_identity_password(): void {
        global $DB;
        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user([
            'username' => 'targetlogin', 'email' => 'target@example.test', 'auth' => 'manual',
            'password' => 'TargetPassword-M14-choice-3!',
        ]);
        $source = $this->getDataGenerator()->create_user([
            'username' => 'sourcelogin', 'email' => 'source@example.test', 'auth' => 'manual',
            'password' => 'SourcePassword-M14-choice-4!', 'suspended' => 1,
        ]);
        $targetpassword = (string)$DB->get_field('user', 'password', ['id' => $target->id], MUST_EXIST);
        $sourcepassword = (string)$DB->get_field('user', 'password', ['id' => $source->id], MUST_EXIST);

        $result = (new CommerceCustomerPreferredIdentityTransferService($DB))->transfer(
            (int)$target->id,
            (int)$source->id,
            (int)$source->id
        );
        $targetafter = $DB->get_record('user', ['id' => $target->id], '*', MUST_EXIST);
        $sourceafter = $DB->get_record('user', ['id' => $source->id], '*', MUST_EXIST);

        $this->assertSame($sourcepassword, $targetafter->password);
        $this->assertSame($targetpassword, $sourceafter->password);
        $this->assertSame((int)$source->id, $result['password_owner_userid']);
        $this->assertTrue($result['password_swapped']);
        $this->assertArrayNotHasKey('target_before_password', $result);
        $this->assertArrayNotHasKey('source_before_password', $result);
    }

    public function test_external_auth_password_transfer_is_rejected(): void {
        global $DB;
        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user([
            'username' => 'manualtarget', 'email' => 'manualtarget@example.test', 'auth' => 'manual',
        ]);
        $source = $this->getDataGenerator()->create_user([
            'username' => 'externalsource', 'email' => 'externalsource@example.test', 'auth' => 'oauth2',
        ]);

        $this->expectException(\moodle_exception::class);
        (new CommerceCustomerPreferredIdentityTransferService($DB))->transfer(
            (int)$target->id,
            (int)$source->id,
            (int)$source->id
        );
    }

    public function test_merge_execution_records_identity_and_password_choice_in_audit_payload(): void {
        global $CFG;
        $execution = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/customer/merge/CommerceCustomerMergeExecutionService.php');
        $history = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/customer/merge/CommerceCustomerMergeHistoryService.php');
        $this->assertStringContainsString('preferredidentityuserid', $execution);
        $this->assertStringContainsString('preferredpassworduserid', $execution);
        $this->assertStringContainsString("'identitytransfer' => \$identitytransfer", $execution);
        $this->assertStringContainsString("'preferredpassworduserid' => isset(\$result['preferredpassworduserid']", $history);
        $this->assertStringContainsString("'identitytransfer' => is_array(\$result['identitytransfer']", $history);
    }
}
