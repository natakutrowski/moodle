<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_campaign_banner_m3h6_test extends \advanced_testcase {
    public function test_campaign_email_builder_exposes_safe_banner_upload_and_delete_controls(): void {
        global $CFG;

        $page = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/personal-offers/campaign_email.php'
        );
        $builder = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignEmailBuilderService.php'
        );

        $this->assertStringContainsString("'enctype' => 'multipart/form-data'", $page);
        $this->assertStringContainsString("'name' => 'campaignbanner'", $page);
        $this->assertStringContainsString('image/jpeg,image/png,image/webp', $page);
        $this->assertStringContainsString("'name' => 'deletebanner'", $page);
        $this->assertStringContainsString('CommercePersonalOfferCampaignMailBannerService', $builder);
    }

    public function test_campaign_banner_is_public_pluginfile_and_overrides_only_personal_offer_header(): void {
        global $CFG;

        $pluginfile = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/lib.php');
        $abstract = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );
        $personaloffer = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );

        $this->assertStringContainsString('CommercePersonalOfferCampaignMailBannerService::FILEAREA', $pluginfile);
        $this->assertStringContainsString("!empty(\$editorial['headerimageurl'])", $abstract);
        $this->assertStringContainsString("['headerimageurl'] = \$bannerurl", $personaloffer);
    }

    public function test_banner_service_restricts_size_and_image_types(): void {
        global $CFG;

        $service = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignMailBannerService.php'
        );

        $this->assertStringContainsString('8 * 1024 * 1024', $service);
        $this->assertStringContainsString('IMAGETYPE_JPEG', $service);
        $this->assertStringContainsString('IMAGETYPE_PNG', $service);
        $this->assertStringContainsString('IMAGETYPE_WEBP', $service);
        $this->assertStringNotContainsString('PARAM_URL', $service);
    }
}
