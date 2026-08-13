<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_campaign_m111_secondary_cta_test extends \advanced_testcase {
    public function test_secondary_cta_wiring_and_positionable_marker_are_present(): void {
        global $CFG;

        $install = file_get_contents($CFG->dirroot . '/local/subscriptions/db/install.xml');
        $upgrade = file_get_contents($CFG->dirroot . '/local/subscriptions/db/upgrade.php');
        $editor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/personal-offers/campaign_email.php'
        );
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );
        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignEmailService.php'
        );

        $this->assertStringContainsString('FIELD NAME="secondaryctalabel"', $install);
        $this->assertStringContainsString('FIELD NAME="secondaryctaurl"', $install);
        $this->assertStringContainsString('2026081303', $upgrade);
        $this->assertStringContainsString('secondaryctalabel_', $editor);
        $this->assertStringContainsString('secondaryctaurl_', $editor);

        $this->assertStringContainsString("{{secondary_cta}}", $renderer);
        $this->assertStringContainsString('CAMPUSFR_SECONDARY_CTA_MARKER_9F31D7', $renderer);
        $this->assertStringContainsString('substr_count($bodycontent, $secondaryctamarker)', $renderer);
        $this->assertStringContainsString('str_replace($secondaryctamarker, $secondaryctasentinel', $renderer);
        $this->assertStringContainsString('strpos($bodyhtml, $secondaryctasentinel)', $renderer);
        $this->assertStringContainsString('substr_replace(', $renderer);
        $this->assertStringContainsString('str_replace($secondaryctasentinel, \'\', $bodyhtml)', $renderer);

        $this->assertStringContainsString('FILTER_VALIDATE_URL', $renderer);
        $this->assertStringContainsString("['http', 'https']", $renderer);
        $this->assertStringContainsString(
            'Secondary CTA label and URL must be configured together.',
            $service
        );
    }

    public function test_secondary_cta_is_not_automatically_appended_after_body(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );

        $this->assertStringNotContainsString(
            '$bodyhtml .= \'<table role="presentation"',
            $renderer
        );
        $this->assertStringContainsString(
            '$secondaryctahtml = \'<table role="presentation"',
            $renderer
        );
    }

    public function test_help_mentions_marker_in_all_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $lang . '/local_subscriptions.php'
            );
            $this->assertStringContainsString(
                'commerce_personal_offer_campaign_email_secondary_cta_help',
                $source,
                $lang
            );
            $this->assertStringContainsString('{{secondary_cta}}', $source, $lang);
        }
    }
}