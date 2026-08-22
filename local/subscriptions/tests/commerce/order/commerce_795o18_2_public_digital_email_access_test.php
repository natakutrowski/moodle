<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

/** Guards session-independent digital access links sent by transactional email. */
final class commerce_795o18_2_public_digital_email_access_test extends advanced_testcase {
    public function test_order_access_allows_only_matching_native_digital_grant_before_owner_session_check(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_access.php');
        $this->assertIsString($source);

        $publiclookup = strpos($source, '$publicdigital = $DB->get_record');
        $ownercheck = strpos($source, 'if (isloggedin() && !isguestuser())');

        $this->assertNotFalse($publiclookup);
        $this->assertNotFalse($ownercheck);
        $this->assertLessThan($ownercheck, $publiclookup);
        $this->assertStringContainsString('\'grantreference\' => $grantreference', $source);
        $this->assertStringContainsString('\'purchasereference\' => $reference', $source);
        $this->assertStringContainsString("'local_subs_commerce_dig_access'", $source);
        $this->assertStringContainsString('$servedigital($publicdigital)', $source);
    }

    public function test_public_path_reuses_canonical_download_validation_and_does_not_open_course_access(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_access.php');
        $this->assertIsString($source);

        $this->assertStringContainsString('new CommerceNativeDigitalDownloadResolver($DB)', $source);
        $this->assertStringContainsString('->resolve((string)$record->downloadtoken, time(), $version)', $source);
        $this->assertStringContainsString('->register_download(', $source);
        $this->assertStringContainsString('if ($access->type === \'course_access\')', $source);
        $this->assertStringContainsString('redirect(new moodle_url(\'/course/view.php\'', $source);
        $this->assertStringContainsString('CommerceOrderPresentationAccessDeniedException', $source);
    }

    public function test_download_token_stays_server_side_for_legacy_order_access_url(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_access.php');
        $this->assertIsString($source);

        $this->assertStringNotContainsString("required_param('token'", $source);
        $this->assertStringContainsString('(string)$record->downloadtoken', $source);
    }
}
