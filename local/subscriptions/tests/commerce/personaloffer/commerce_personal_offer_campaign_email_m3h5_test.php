<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_campaign_email_m3h5_test extends \advanced_testcase {
    public function test_test_email_issues_short_lived_signed_navigation_offer_instead_of_admin_preview_link(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailPreviewService.php'
        );

        $this->assertStringContainsString('CommercePersonalOfferIssueRequest', $source);
        $this->assertStringContainsString("'campaignemailtest' => true", $source);
        $this->assertMatchesRegularExpression(
            '/new CommercePersonalOfferIssueRequest\([\s\S]*?null,\s*null,\s*\$now,\s*\$expiresat,/',
            $source
        );
        $this->assertStringContainsString('$now + (2 * HOURSECS)', $source);
        $this->assertStringContainsString('bin2hex(random_bytes(6))', $source);
        $this->assertStringNotContainsString('floor($now / HOURSECS)', $source);
        $this->assertStringContainsString('->secure_url($issued->get_offer())', $source);
        $this->assertStringContainsString("'campaignpreview' => \$offeruuid === ''", $source);
        $this->assertStringContainsString('$issued->get_offer()->get_offer_uuid()', $source);
    }

    public function test_static_preview_remains_preissue_and_does_not_need_a_real_offer(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailPreviewService.php'
        );

        $this->assertStringContainsString(
            "\$request = \$this->request(\$campaignid, \$language, 'preview@example.invalid', \$firstname);",
            $source
        );
        $this->assertStringContainsString("string \$offeruuid = ''", $source);
        $this->assertStringContainsString("string \$offerurl = ''", $source);
    }
}
