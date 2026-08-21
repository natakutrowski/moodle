<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n78_test extends advanced_testcase {
    public function test_individual_offer_form_hides_skus_and_keeps_currency_inputs_dynamic(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/create.php'
        );

        self::assertStringContainsString(
            'business_product_label',
            $source
        );
        self::assertStringContainsString(
            'CommercePersonalOfferConditionsRenderer::pricing',
            $source
        );
        $conditions = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommercePersonalOfferConditionsRenderer.php'
        );
        self::assertStringContainsString(
            'foreach ($currencies as $currency)',
            $conditions
        );
        self::assertStringContainsString(
            'crm-offers-access-currency-input',
            $conditions
        );
        self::assertStringNotContainsString(
            'commerce_offers_access_product_legacy',
            $source
        );
    }

    public function test_individual_offer_requires_expiration_or_explicit_no_expiry(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/create.php'
        );

        foreach ([
            "optional_param('noexpiration'",
            'commerce_personal_offer_expiration_required',
            'expiresAt.required = !duration',
            'validitytimezone',
            'validitymode',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
        $conditions = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommercePersonalOfferConditionsRenderer.php'
        );
        self::assertStringContainsString(
            "type' => 'datetime-local'",
            $conditions
        );
        self::assertStringContainsString(
            'commerce_personal_offer_no_expiration',
            $conditions
        );
    }

    public function test_individual_offer_can_select_active_mail_studio_template(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/create.php'
        );
        $bridge = file_get_contents(
            $root
            . '/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferIndividualMailStudioBridge.php'
        );

        self::assertStringContainsString(
            'mailtemplateid',
            $source
        );
        self::assertStringContainsString(
            'template_options()',
            $source
        );
        self::assertStringContainsString(
            'CommerceMailLibrary::STATUS_ACTIVE',
            $bridge
        );
        self::assertStringContainsString(
            'mailtemplatesnapshot',
            $source
        );
    }

    public function test_selected_template_is_frozen_into_offer_metadata_and_used_by_mail_runtime(): void {
        $root = dirname(__DIR__, 3);
        $manager = file_get_contents(
            $root
            . '/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignManager.php'
        );
        $mailservice = file_get_contents(
            $root
            . '/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferMailService.php'
        );
        $template = file_get_contents(
            $root
            . '/classes/commerce/mail/template/'
            . 'CommercePersonalOfferTemplate.php'
        );

        self::assertStringContainsString(
            "'mailtemplatesnapshot' => \$data['mailtemplatesnapshot']",
            $manager
        );
        self::assertStringContainsString(
            "'mailtemplatesnapshot'=>\$mailtemplatesnapshot",
            $mailservice
        );
        self::assertStringContainsString(
            'CommercePersonalOfferIndividualMailStudioBridge::create()',
            $template
        );
    }

    public function test_n78_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
