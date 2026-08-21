<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerManualMergeCandidateService;

/**
 * M7.3/M7.4 — explicit account selection and guarded merge preview.
 *
 * @covers \local_subscriptions\commerce\customer\merge\CommerceCustomerManualMergeCandidateService
 */
final class commerce_customer_manual_merge_m73_m74_test extends advanced_testcase {
    public function test_manual_search_finds_accounts_without_similarity_candidate(): void {
        global $DB;

        $this->resetAfterTest(true);

        $alpha = $this->getDataGenerator()->create_user([
            'firstname' => 'Manual',
            'lastname' => 'Alpha',
            'username' => 'manual.alpha',
            'email' => 'manual.alpha@example.test',
        ]);
        $beta = $this->getDataGenerator()->create_user([
            'firstname' => 'Completely',
            'lastname' => 'Different',
            'username' => 'unrelated.beta',
            'email' => 'unrelated.beta@example.test',
        ]);

        $service = new CommerceCustomerManualMergeCandidateService($DB);

        $byemail = $service->search('manual.alpha@example.test');
        self::assertNotEmpty($byemail);
        self::assertSame((int)$alpha->id, (int)$byemail[0]->id);

        $byid = $service->search((string)$beta->id);
        self::assertNotEmpty($byid);
        self::assertSame((int)$beta->id, (int)$byid[0]->id);

        $byname = $service->search('Completely Different');
        self::assertNotEmpty($byname);
        self::assertSame((int)$beta->id, (int)$byname[0]->id);

        // Moodle 5 fullname() requires all configured name fields to exist
        // on partial user records returned by custom SQL.
        foreach ([
            'firstnamephonetic',
            'lastnamephonetic',
            'middlename',
            'alternatename',
        ] as $field) {
            self::assertTrue(property_exists($byname[0], $field), 'Missing user name field: ' . $field);
        }
        self::assertNotSame('', fullname($byname[0]));
    }

    public function test_search_excludes_already_selected_accounts(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Excluded',
            'lastname' => 'Merge',
            'email' => 'excluded.merge@example.test',
        ]);

        $service = new CommerceCustomerManualMergeCandidateService($DB);
        self::assertSame([], $service->search('excluded.merge@example.test', [(int)$user->id]));

        $selected = $service->selected([(int)$user->id]);
        self::assertCount(1, $selected);
        foreach ([
            'firstnamephonetic',
            'lastnamephonetic',
            'middlename',
            'alternatename',
        ] as $field) {
            self::assertTrue(property_exists($selected[0], $field), 'Missing selected user name field: ' . $field);
        }
        self::assertNotSame('', fullname($selected[0]));
    }

    public function test_manual_merge_page_keeps_preview_and_execution_guarded(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/customer-identities/merge.php'
        );

        self::assertStringContainsString('CommerceCustomerManualMergeCandidateService', $source);
        self::assertStringContainsString("'name' => 'q'", $source);
        self::assertStringContainsString("'adduserid'", $source);
        self::assertStringContainsString("'removeuserid'", $source);
        self::assertStringContainsString('commerce-identity-merge-decisions', $source);
        self::assertStringContainsString("'name' => 'preferredidentityuserid'", $source);
        self::assertStringContainsString("'name' => 'targetuserid'", $source);
        self::assertStringContainsString("'name' => 'confirmmerge'", $source);
        self::assertStringContainsString("'value' => 'execute'", $source);
        self::assertStringContainsString('CommerceCustomerMergeExecutionService', $source);
        self::assertStringNotContainsString('$DB->update_record(', $source);
        self::assertStringNotContainsString('$DB->delete_records(', $source);
    }
}
