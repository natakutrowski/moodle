<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\mail;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryAlfDonationFooterImageService;
use local_subscriptions\commerce\mail\transactional\CommerceTransactionalMailStudioBridge;

/** 7.98C.5.14.2 — configurable Alf photo for donation-only receipts. */
final class commerce_798c5142_alf_donation_footer_image_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_mail_studio_footer_photo_is_rendered_only_for_alf_donation_only(): void {
        global $DB;

        $admin = get_admin();
        $bridge = CommerceTransactionalMailStudioBridge::create($DB);
        $template = $bridge->migrate(CommerceMailType::PURCHASE_RECEIPT, (int)$admin->id);

        get_file_storage()->create_file_from_string([
            'contextid' => context_system::instance()->id,
            'component' => 'local_subscriptions',
            'filearea' => CommerceMailLibraryAlfDonationFooterImageService::FILEAREA,
            'itemid' => (int)$template->id,
            'filepath' => '/',
            'filename' => 'alf-donation-footer.jpg',
            'mimetype' => 'image/jpeg',
        ], 'fake-image-content');

        $donation = $this->render($this->donation_context(), 951401);
        self::assertStringContainsString(
            '/' . CommerceMailLibraryAlfDonationFooterImageService::FILEAREA . '/' . (int)$template->id . '/',
            $donation->get_html()
        );
        self::assertStringContainsString('alt="Alf"', $donation->get_html());

        $mixed = $this->render($this->course_donation_context(), 951402);
        self::assertStringNotContainsString(
            '/' . CommerceMailLibraryAlfDonationFooterImageService::FILEAREA . '/' . (int)$template->id . '/',
            $mixed->get_html()
        );
    }

    public function test_mail_studio_editor_exposes_dedicated_upload_only_for_purchase_receipt_runtime(): void {
        global $CFG;

        $editor = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/mail/templates/library_edit.php'
        );
        $receipt = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/CommercePurchaseReceiptTemplate.php'
        );
        $mustache = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/commerce/mail/purchase_receipt.mustache'
        );
        $lib = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/lib.php');

        self::assertStringContainsString('alf_donation_footer_image', $editor);
        self::assertStringContainsString('multipart/form-data', $editor);
        self::assertStringContainsString('CommerceMailType::PURCHASE_RECEIPT', $editor);
        self::assertStringContainsString('alfdonationfooterimageurl', $receipt);
        self::assertStringContainsString('editorial_footerimageurl', $mustache);
        self::assertStringContainsString('CommerceMailLibraryAlfDonationFooterImageService::FILEAREA', $lib);
    }

    private function render(CommerceMailContext $context, int $purchaseid) {
        return CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_RECEIPT)
            ->render(new CommerceMailRequest(
                CommerceMailType::PURCHASE_RECEIPT,
                new CommerceMailRecipient('nata@example.test', 'Nata CampusFR'),
                $context,
                'fr',
                CommerceMailIdempotencyKey::for_purchase($purchaseid, CommerceMailType::PURCHASE_RECEIPT),
                $purchaseid
            ));
    }

    private function donation_context(): CommerceMailContext {
        return new CommerceMailContext([
            'customer' => ['fullname' => 'Nata CampusFR'],
            'purchase' => [
                'reference' => 'CFR-C5142-DONATION',
                'totalformatted' => '10,00 €',
                'donationformatted' => '10,00 €',
            ],
            'items' => [[
                'type' => 'donation',
                'title' => 'Soutien à l’opération d’Alf',
                'productsku' => 'DONATION.ALF',
                'totalformatted' => '10,00 €',
            ]],
            'links' => [
                'order' => 'https://example.test/order',
                'campus' => 'https://example.test/campus',
            ],
        ]);
    }

    private function course_donation_context(): CommerceMailContext {
        return new CommerceMailContext([
            'customer' => ['fullname' => 'Nata CampusFR'],
            'purchase' => [
                'reference' => 'CFR-C5142-MIXED',
                'totalformatted' => '35,00 €',
                'donationformatted' => '10,00 €',
            ],
            'items' => [
                [
                    'type' => 'course',
                    'title' => 'Les articles français',
                    'productsku' => 'COURSE_ACCESS.ARTICLES',
                    'totalformatted' => '25,00 €',
                ],
                [
                    'type' => 'donation',
                    'title' => 'Soutien à l’opération d’Alf',
                    'productsku' => 'DONATION.ALF',
                    'totalformatted' => '10,00 €',
                ],
            ],
            'links' => [
                'order' => 'https://example.test/order',
                'campus' => 'https://example.test/campus',
            ],
        ]);
    }
}
