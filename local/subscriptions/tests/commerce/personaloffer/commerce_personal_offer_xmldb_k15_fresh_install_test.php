<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_xmldb_k15_fresh_install_test extends advanced_testcase {

    private function table_block(string $install, string $tablename): string {
        $start = strpos($install, '<TABLE NAME="' . $tablename . '"');
        $this->assertNotFalse($start);

        $end = strpos($install, '</TABLE>', $start);
        $this->assertNotFalse($end);

        return substr(
            $install,
            $start,
            $end - $start + strlen('</TABLE>')
        );
    }

    public function test_existing_offer_foreign_key_is_only_on_campaign_member_table(): void {
        $root = dirname(__DIR__, 3);
        $install = (string)file_get_contents($root . '/db/install.xml');

        $token = $this->table_block(
            $install,
            'local_subs_commerce_offer_token'
        );
        $member = $this->table_block(
            $install,
            'local_subs_commerce_offer_campaign_member'
        );

        $this->assertStringNotContainsString('existingofferid', $token);
        $this->assertStringNotContainsString('existingoffer_fk', $token);

        $this->assertStringContainsString(
            'FIELD NAME="existingofferid"',
            $member
        );
        $this->assertStringContainsString(
            'KEY NAME="existingoffer_fk" TYPE="foreign" FIELDS="existingofferid"',
            $member
        );
    }
}
