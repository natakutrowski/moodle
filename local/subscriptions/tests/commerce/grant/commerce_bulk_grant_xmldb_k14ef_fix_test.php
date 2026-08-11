<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_bulk_grant_xmldb_k14ef_fix_test extends advanced_testcase {

    public function test_campaign_member_user_foreign_key_has_no_duplicate_userid_index(): void {
        $root = dirname(__DIR__, 3);

        $install = (string)file_get_contents($root . '/db/install.xml');
        $upgrade = (string)file_get_contents($root . '/db/upgrade.php');

        $tablestart = strpos(
            $install,
            '<TABLE NAME="local_subs_commerce_grant_campaign_member"'
        );
        $this->assertNotFalse($tablestart);

        $tableend = strpos($install, '</TABLE>', $tablestart);
        $this->assertNotFalse($tableend);

        $membertable = substr(
            $install,
            $tablestart,
            $tableend - $tablestart + strlen('</TABLE>')
        );

        $this->assertStringContainsString(
            'KEY NAME="user_fk" TYPE="foreign" FIELDS="userid"',
            $membertable
        );
        $this->assertStringNotContainsString(
            'INDEX NAME="userid_idx"',
            $membertable
        );

        $this->assertStringContainsString(
            '$member->add_key(\'user_fk\', XMLDB_KEY_FOREIGN, [\'userid\'], \'user\', [\'id\']);',
            $upgrade
        );
        $this->assertStringNotContainsString(
            '$member->add_index(\'userid_idx\'',
            $upgrade
        );
    }
}