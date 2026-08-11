<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_grant_transactional_mail_k14g_fullname_test extends advanced_testcase {

    public function test_grant_mail_factory_loads_full_moodle_user_for_fullname(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/mail/context/CommerceGrantAccessMailContextFactory.php'
        );

        $this->assertStringContainsString(
            "['id' => \$userid, 'deleted' => 0],\n            '*'",
            $source
        );
        $this->assertStringContainsString('fullname($user)', $source);
        $this->assertStringNotContainsString(
            "'id,firstname,lastname,email,lang'",
            $source
        );
    }
}
