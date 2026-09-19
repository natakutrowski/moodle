<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13162_personal_offer_identity_destination_test extends \advanced_testcase {
    public function test_new_individual_offers_no_longer_use_fake_campaign_key(): void {
        global $CFG;

        $manager = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignManager.php'
        );

        self::assertStringNotContainsString(
            ":'crm-individual'",
            $manager
        );
        self::assertStringContainsString(
            "'campaignsource' => 'crm_individual'",
            $manager
        );
    }

    public function test_individual_offer_can_store_checkout_or_showroom_destination(): void {
        global $CFG;

        $manager = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignManager.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/service/'
            . 'CommercePersonalOfferDestinationResolver.php'
        );

        self::assertStringContainsString(
            'individual_destination',
            $manager
        );
        self::assertStringContainsString(
            'individual_showroom_id',
            $manager
        );
        self::assertStringContainsString(
            'CommercePersonalOfferIndividualDestinationService',
            $resolver
        );
    }

    public function test_individual_admin_form_exposes_destination_without_campaign(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/personal-offers/create.php'
        );

        self::assertStringContainsString(
            "'individualdestination'",
            $source
        );
        self::assertStringContainsString(
            "'individualshowroomid'",
            $source
        );
        self::assertStringContainsString(
            'data-product-ids',
            $source
        );
        self::assertStringContainsString(
            'syncIndividualDestination',
            $source
        );
    }

    public function test_personal_offer_guest_uses_signed_link_proof_not_otp(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $endpoint = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/ajax/guest_personal_offer_identity_confirm.php'
        );

        self::assertStringContainsString(
            "identity_proof' => 'personal_offer_signed_link'",
            $checkout
        );
        self::assertStringContainsString(
            'personalofferbearerproof',
            $template
        );
        self::assertStringContainsString(
            'CommerceGuestCheckoutService::create()->identify(',
            $endpoint
        );
        self::assertStringContainsString(
            'CommerceGuestIdentityVerificationState::locked_metadata(',
            $endpoint
        );
    }

    public function test_existing_account_still_requires_login_after_signed_link_proof(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/ajax/guest_personal_offer_identity_confirm.php'
        );

        self::assertStringContainsString(
            "\$resolved->get_status() === 'existing_account'",
            $endpoint
        );
        self::assertStringContainsString(
            "'requiresLogin'",
            $endpoint
        );
    }

    public function test_offer_entry_logs_real_known_error_code_for_authenticated_diagnosis(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/offer.php'
        );

        self::assertStringContainsString(
            '[local_subscriptions][personal_offer_entry][',
            $source
        );
        self::assertStringContainsString(
            '$exception->errorcode',
            $source
        );
    }
}
