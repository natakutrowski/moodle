<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_campaign_preview_m3h3_test extends \advanced_testcase {
    public function test_campaign_preview_loads_shared_mail_studio_styles_and_toolbar_structure(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/personal-offers/campaign_email_preview.php'
        );
        $css = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/commerce_mail_admin.css'
        );

        $this->assertStringContainsString(
            "\$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css')",
            $source
        );
        $this->assertStringContainsString('commerce-mail-preview-toolbar__navigation', $source);
        $this->assertStringContainsString('commerce-mail-preview-toolbar__font', $source);
        $this->assertStringContainsString('commerce-mail-preview-stage', $css);
        $this->assertStringContainsString('width: min(100%, 920px)', $css);
        $this->assertStringContainsString('height: 720px', $css);
    }
}
