<?php
declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeCertificationService;

final class local_subscriptions_commerce_customer_merge_certification_m77_test extends advanced_testcase {
    public function test_certification_accepts_clean_post_merge_state(): void {
        global $DB;
        $this->resetAfterTest(true);
        $g = $this->getDataGenerator();
        $target = $g->create_user(['email' => 'merge-target@example.test']);
        $source = $g->create_user(['email' => 'merge-source@example.test', 'suspended' => 1]);

        $result = (new CommerceCustomerMergeCertificationService($DB))->certify(
            [(int)$source->id], (int)$target->id, (string)$target->email, [], []
        );
        self::assertTrue($result['passed']);
        self::assertSame(0, $result['summary']['failed']);
        self::assertGreaterThan(0, $result['summary']['passed']);
    }

    public function test_certification_rejects_active_old_account(): void {
        global $DB;
        $this->resetAfterTest(true);
        $g = $this->getDataGenerator();
        $target = $g->create_user(['email' => 'merge-target2@example.test']);
        $source = $g->create_user(['email' => 'merge-source2@example.test', 'suspended' => 0]);

        $result = (new CommerceCustomerMergeCertificationService($DB))->certify(
            [(int)$source->id], (int)$target->id, (string)$target->email, [], []
        );
        self::assertFalse($result['passed']);
        self::assertGreaterThan(0, $result['summary']['failed']);
    }

    public function test_merge_admin_strings_do_not_expose_internal_phase_names(): void {
        global $CFG;
        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = (string)file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $lang . '/local_subscriptions.php'
            );
            preg_match_all(
                '/^\$string\[[\'"]commerce_identity_merge_[^\'"]+[\'"]\]\s*=\s*([\'"])(.*?)\1;/ms',
                $source, $matches
            );
            self::assertDoesNotMatchRegularExpression(
                '/\bM7(?:\.\d+[A-Z]?)?\b/i',
                implode("\n", $matches[2])
            );
        }
    }
}
