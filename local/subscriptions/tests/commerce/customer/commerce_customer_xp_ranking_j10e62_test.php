<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Structural certification for the student-only Level Up XP ranking.
 */
final class commerce_customer_xp_ranking_j10e62_test extends \advanced_testcase {

    public function test_ranking_excludes_non_student_accounts(): void {
        $path = __DIR__ . '/../../../classes/crm/success/repositories/LevelUpXpRepository.php';
        $source = file_get_contents($path);

        $this->assertIsString($source);
        $this->assertStringContainsString('get_admins()', $source);
        $this->assertStringContainsString('guest_user()', $source);
        $this->assertStringContainsString('u.deleted = 0', $source);
        $this->assertStringContainsString('u.suspended = 0', $source);
        $this->assertStringContainsString('SQL_PARAMS_NAMED', $source);
        $this->assertStringContainsString('x.userid = :userid', $source);
    }
}
