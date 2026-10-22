<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/** 7.98C5.0 — admin preview and token surface for Alf receipt variants. */
final class commerce_798c50_mail_preview_variants_test extends \advanced_testcase {

    public function test_preview_exposes_standard_course_donation_and_donation_only_scenarios(): void {
        global $CFG;
        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/mail/templates/preview.php'
        );

        self::assertStringContainsString("'alf_course_donation'", $source);
        self::assertStringContainsString("'alf_donation'", $source);
        self::assertStringContainsString("'productsku' => 'DONATION.ALF'", $source);
        self::assertStringContainsString("'donationformatted' => '10,00 €'", $source);
        self::assertStringContainsString('commerce_mail_preview_scenario', $source);
    }

    public function test_donation_amount_is_a_supported_transactional_mail_token(): void {
        global $CFG;
        $resolver = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/studio/CommerceMailTokenResolver.php'
        );
        $builder = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/builder/CommerceMailBuilder.php'
        );
        $form = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/forms/commerce/mail/CommerceMailTemplateForm.php'
        );

        self::assertStringContainsString("'donation_amount' =>", $resolver);
        self::assertStringContainsString("'donation_amount',", $builder);
        self::assertStringContainsString("'{donation_amount}'", $form);
    }

    public function test_special_receipt_keeps_single_purchase_receipt_mail_type(): void {
        global $CFG;
        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/CommercePurchaseReceiptTemplate.php'
        );
        $service = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/service/CommerceTransactionalPurchaseMailService.php'
        );

        self::assertStringContainsString('hasalfdonationonly', $template);
        self::assertStringContainsString('hasalfcourseanddonation', $template);
        self::assertStringContainsString(
            'CommerceMailType::PURCHASE_RECEIPT',
            $service
        );
        self::assertStringNotContainsString('PURCHASE_ALF_DONATION', $service);
    }
}
