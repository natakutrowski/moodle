<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingCourseCampaignGate;

/** Regression checks for the course-campaign release switch UI and safe default. */
final class commerce_marketing_course_settings_test extends \advanced_testcase {
    public function test_course_campaign_release_switch_defaults_to_off_and_can_be_toggled(): void {
        $this->resetAfterTest(true);
        unset_config('commerce_mail_course_campaigns_enabled', 'local_subscriptions');
        self::assertFalse(CommerceMarketingCourseCampaignGate::enabled());
        set_config('commerce_mail_course_campaigns_enabled', '1', 'local_subscriptions');
        self::assertTrue(CommerceMarketingCourseCampaignGate::enabled());
        set_config('commerce_mail_course_campaigns_enabled', '0', 'local_subscriptions');
        self::assertFalse(CommerceMarketingCourseCampaignGate::enabled());
    }

    public function test_admin_setting_declares_exact_flag_and_disabled_default(): void {
        $root = dirname(__DIR__, 3);
        $code = (string)file_get_contents($root . '/settings.php');
        self::assertStringContainsString(
            "'local_subscriptions/commerce_mail_course_campaigns_enabled'", $code);
        self::assertMatchesRegularExpression(
            '~new admin_setting_configcheckbox\(\s*' .
            "'local_subscriptions/commerce_mail_course_campaigns_enabled'" .
            '[\s\S]*?\s0\s*\)~', $code);
    }

    public function test_campaign_editor_links_disabled_notice_to_real_configuration_toggle(): void {
        $root = dirname(__DIR__, 3);
        $editor = (string)file_get_contents($root . '/admin/commerce/mail/campaigns/edit.php');
        $config = (string)file_get_contents($root . '/admin/commerce/mail/configuration.php');

        self::assertStringContainsString(
            "new moodle_url('/local/subscriptions/admin/commerce/mail/configuration.php')", $editor);
        self::assertStringContainsString("->set_anchor('mail-config-course_campaigns_enabled')", $editor);
        self::assertStringContainsString("'commerce_campaign_e9c_open_settings'", $editor);
        self::assertStringContainsString("'course_campaigns_enabled'", $config);
        self::assertStringContainsString("'id' => 'mail-config-' . \$name", $config);
    }

    public function test_mail_studio_and_commerce_configuration_use_same_flag(): void {
        $root = dirname(__DIR__, 3);
        $mail = (string)file_get_contents($root . '/admin/commerce/mail/configuration.php');
        $commerce = (string)file_get_contents($root . '/admin/commerce/configuration/section.php');
        self::assertStringContainsString("'commerce_mail_course_campaigns_enabled' => optional_param(", $mail);
        self::assertStringContainsString("'course_campaigns_enabled' => \$configbool(", $mail);
        self::assertStringContainsString("'course_campaigns_enabled',", $mail);
        self::assertStringContainsString(
            "\$field('commerce_mail_course_campaigns_enabled',", $commerce);
        self::assertStringContainsString(
            "'settings:course_marketing_enabled_desc', 'checkbox', 0", $commerce);
    }
}
