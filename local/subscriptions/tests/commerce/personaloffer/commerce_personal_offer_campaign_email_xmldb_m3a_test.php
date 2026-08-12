<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_campaign_email_xmldb_m3a_test extends advanced_testcase {
    private function table_block(string $install, string $tablename): string {
        $start = strpos($install, '<TABLE NAME="' . $tablename . '"');
        $this->assertNotFalse($start);
        $end = strpos($install, '</TABLE>', $start);
        $this->assertNotFalse($end);
        return substr($install, $start, $end - $start + strlen('</TABLE>'));
    }

    public function test_fresh_install_contains_normalised_campaign_email_tables(): void {
        $root = dirname(__DIR__, 3);
        $install = (string)file_get_contents($root . '/db/install.xml');

        $config = $this->table_block($install, 'local_subs_commerce_offer_campaign_email_config');
        $content = $this->table_block($install, 'local_subs_commerce_offer_campaign_email_content');

        $this->assertStringContainsString('FIELD NAME="ctadestination"', $config);
        $this->assertStringContainsString('FIELD NAME="showroomid"', $config);
        $this->assertStringContainsString('KEY NAME="campaign_fk" TYPE="foreign-unique" FIELDS="campaignid"', $config);
        $this->assertStringContainsString('REFTABLE="local_subs_showroom"', $config);

        $this->assertStringContainsString('FIELD NAME="language"', $content);
        $this->assertStringContainsString('FIELD NAME="subject"', $content);
        $this->assertStringContainsString('FIELD NAME="bodyformat"', $content);
        $this->assertStringContainsString('FIELD NAME="ctalabel"', $content);
        $this->assertStringContainsString('FIELD NAME="closingformat"', $content);
        $this->assertStringContainsString(
            'INDEX NAME="campaign_language_uix" UNIQUE="true" FIELDS="campaignid,language"',
            $content
        );
    }
}
