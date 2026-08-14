<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerAdvancedProfileMergeService;

final class commerce_customer_advanced_merge_m16_test extends advanced_testcase {
    public function test_advanced_profile_choice_copies_selected_fields_only(): void {
        global $DB;
        $this->resetAfterTest();
        $target = $this->getDataGenerator()->create_user(['firstname' => 'Target', 'lastname' => 'Keep', 'city' => 'Nice']);
        $source = $this->getDataGenerator()->create_user(['firstname' => 'Source', 'lastname' => 'Other', 'city' => 'Paris']);

        $changed = (new CommerceCustomerAdvancedProfileMergeService($DB))->apply(
            (int)$target->id,
            ['firstname' => (int)$source->id, 'city' => (int)$source->id],
            [(int)$target->id, (int)$source->id]
        );
        $final = $DB->get_record('user', ['id' => $target->id], '*', MUST_EXIST);

        $this->assertSame('Source', $final->firstname);
        $this->assertSame('Keep', $final->lastname);
        $this->assertSame('Paris', $final->city);
        $this->assertSame((int)$source->id, $changed['firstname']);
        $this->assertSame((int)$source->id, $changed['city']);
    }

    public function test_merge_ui_exposes_advanced_ab_profile_resolution_and_levelup_preview(): void {
        global $CFG;
        $merge = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/customer-identities/merge.php');
        $this->assertStringContainsString("optional_param('advancedmerge'", $merge);
        $this->assertStringContainsString("'name' => 'profilechoice[' . \$field . ']'", $merge);
        $this->assertStringContainsString('commerce_identity_merge_gamification_summary', $merge);
        $this->assertStringContainsString('CommerceCustomerGamificationMergeService', $merge);
    }
}