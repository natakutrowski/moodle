<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerPreferredIdentityTransferService;

final class commerce_customer_preferred_identity_transfer_m13c_test extends advanced_testcase {
    public function test_login_identity_is_swapped_without_moving_names_or_userids(): void {
        global $DB;
        $this->resetAfterTest(true);
        $target = $this->getDataGenerator()->create_user([
            'username' => 'richaccount', 'email' => 'rich@example.test', 'firstname' => 'Sergey', 'lastname' => 'Sergeev',
        ]);
        $source = $this->getDataGenerator()->create_user([
            'username' => 'preferredlogin', 'email' => 'preferred@example.test', 'firstname' => 'Serhii', 'lastname' => 'Shastalo', 'suspended' => 1,
        ]);
        $result = (new CommerceCustomerPreferredIdentityTransferService($DB))->transfer((int)$target->id, (int)$source->id);
        $targetafter = $DB->get_record('user', ['id' => $target->id], '*', MUST_EXIST);
        $sourceafter = $DB->get_record('user', ['id' => $source->id], '*', MUST_EXIST);
        $this->assertSame('preferred@example.test', $targetafter->email);
        $this->assertSame('preferredlogin', $targetafter->username);
        $this->assertSame('Sergey', $targetafter->firstname);
        $this->assertSame('rich@example.test', $sourceafter->email);
        $this->assertSame('richaccount', $sourceafter->username);
        $this->assertSame((int)$source->id, $result['sourceuserid']);
    }

    public function test_merge_execution_records_identity_transfer_in_audit_payload(): void {
        global $CFG;
        $execution = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/customer/merge/CommerceCustomerMergeExecutionService.php');
        $history = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/customer/merge/CommerceCustomerMergeHistoryService.php');
        $this->assertStringContainsString('preferredidentityuserid', $execution);
        $this->assertStringContainsString("'identitytransfer' => \$identitytransfer", $execution);
        $this->assertStringContainsString("'identitytransfer' => is_array(\$result['identitytransfer']", $history);
    }
}
