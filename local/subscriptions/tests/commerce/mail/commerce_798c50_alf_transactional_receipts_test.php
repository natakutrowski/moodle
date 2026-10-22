<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\presentation\CommerceMailPurchasePresentation;

/** 7.98C5.0 — specialised Alf donation purchase confirmations. */
final class commerce_798c50_alf_transactional_receipts_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_presentation_exposes_donation_shape_and_exact_amount(): void {
        $donation = CommerceMailPurchasePresentation::from_context(
            $this->context('alf_donation')
        )->export();

        self::assertTrue($donation['hasdonation']);
        self::assertTrue($donation['hasonlydonations']);
        self::assertFalse($donation['hascourseanddonation']);
        self::assertTrue($donation['hasalfdonation']);
        self::assertTrue($donation['hasalfdonationonly']);
        self::assertFalse($donation['hasalfcourseanddonation']);
        self::assertSame('10,00 €', $donation['donationformatted']);

        $mixed = CommerceMailPurchasePresentation::from_context(
            $this->context('alf_course_donation')
        )->export();

        self::assertTrue($mixed['hasdonation']);
        self::assertFalse($mixed['hasonlydonations']);
        self::assertTrue($mixed['hascourseanddonation']);
        self::assertTrue($mixed['hasalfdonation']);
        self::assertFalse($mixed['hasalfdonationonly']);
        self::assertTrue($mixed['hasalfcourseanddonation']);
        self::assertSame('10,00 €', $mixed['donationformatted']);
    }

    /** @dataProvider donation_language_provider */
    public function test_donation_only_receipt_has_special_copy_and_order_cta(
        string $language,
        string $subjectfragment,
        string $bodyfragment
    ): void {
        $message = $this->render('alf_donation', $language);

        self::assertStringContainsString($subjectfragment, $message->get_subject());
        self::assertStringContainsString($bodyfragment, $message->get_html());
        self::assertStringContainsString('10,00 €', $message->get_html());
        self::assertStringNotContainsString('{donation_amount}', $message->get_html());
        self::assertStringContainsString('https://example.test/order', $message->get_html());
        self::assertStringNotContainsString('https://example.test/campus', $message->get_html());
        self::assertSame('purchase_receipt', $message->get_metadata()['template']);
    }

    public static function donation_language_provider(): array {
        return [
            'fr' => ['fr', 'Merci du fond du cœur', 'Vous avez choisi d’aider Alf sans acheter le cours'],
            'en' => ['en', 'Thank you from the heart', 'You chose to help Alf without purchasing the course'],
            'ru' => ['ru', 'Спасибо от всего сердца', 'Вы решили помочь Альфу, не покупая курс'],
        ];
    }

    /** @dataProvider course_donation_language_provider */
    public function test_course_plus_donation_receipt_has_special_copy_and_campus_cta(
        string $language,
        string $subjectfragment,
        string $bodyfragment
    ): void {
        $message = $this->render('alf_course_donation', $language);

        self::assertStringContainsString($subjectfragment, $message->get_subject());
        self::assertStringContainsString($bodyfragment, $message->get_html());
        self::assertStringContainsString('10,00 €', $message->get_html());
        self::assertStringContainsString('25,00 €', $message->get_html());
        self::assertStringNotContainsString('{donation_amount}', $message->get_html());
        self::assertStringContainsString('https://example.test/campus', $message->get_html());
    }

    public static function course_donation_language_provider(): array {
        return [
            'fr' => ['fr', 'Votre cours est confirmé', 'même cours et tous les mêmes contenus'],
            'en' => ['en', 'Your course is confirmed', 'same complete course and the same content'],
            'ru' => ['ru', 'Ваш курс подтверждён', 'точно такой же полный курс'],
        ];
    }

    public function test_course_plus_donation_access_mail_stays_standard(): void {
        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_ACCESS)
            ->render(new CommerceMailRequest(
                CommerceMailType::PURCHASE_ACCESS,
                new CommerceMailRecipient('nata@example.test', 'Nata CampusFR'),
                $this->context('alf_course_donation'),
                'fr',
                CommerceMailIdempotencyKey::for_purchase(805001, CommerceMailType::PURCHASE_ACCESS),
                805001
            ));

        self::assertStringContainsString('Vos accès CampusFR sont disponibles', $message->get_subject());
        self::assertStringNotContainsString('Merci du fond du cœur', $message->get_html());
    }

    public function test_standard_course_receipt_keeps_generic_editorial(): void {
        $message = $this->render('standard', 'fr');

        self::assertStringContainsString('Confirmation de votre achat CampusFR', $message->get_subject());
        self::assertStringContainsString('Nous sommes ravis de confirmer votre achat', $message->get_html());
        self::assertStringNotContainsString('Merci du fond du cœur', $message->get_html());
    }

    public function test_non_alf_donation_does_not_receive_alf_editorial(): void {
        $context = new CommerceMailContext([
            'customer' => ['fullname' => 'Nata CampusFR'],
            'purchase' => [
                'reference' => 'CFR-OTHER-DONATION',
                'totalformatted' => '10,00 €',
                'donationformatted' => '10,00 €',
            ],
            'items' => [[
                'type' => 'donation',
                'title' => 'Generic contribution',
                'productsku' => 'DONATION.OTHER',
                'totalformatted' => '10,00 €',
            ]],
            'links' => [
                'order' => 'https://example.test/order',
                'campus' => 'https://example.test/campus',
            ],
        ]);

        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_RECEIPT)
            ->render($this->request($context, 'fr', 805099));

        self::assertStringContainsString('Confirmation de votre achat CampusFR', $message->get_subject());
        self::assertStringNotContainsString('Merci du fond du cœur', $message->get_html());
    }

    public function test_receipt_idempotency_key_does_not_change_with_variant(): void {
        $key = CommerceMailIdempotencyKey::for_purchase(805001, CommerceMailType::PURCHASE_RECEIPT);
        self::assertSame($key, CommerceMailIdempotencyKey::for_purchase(805001, CommerceMailType::PURCHASE_RECEIPT));
    }

    private function render(string $scenario, string $language) {
        return CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_RECEIPT)
            ->render($this->request($this->context($scenario), $language, 805001));
    }

    private function request(CommerceMailContext $context, string $language, int $purchaseid): CommerceMailRequest {
        return new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('nata@example.test', 'Nata CampusFR'),
            $context,
            $language,
            CommerceMailIdempotencyKey::for_purchase($purchaseid, CommerceMailType::PURCHASE_RECEIPT),
            $purchaseid
        );
    }

    private function context(string $scenario): CommerceMailContext {
        $purchase = [
            'reference' => 'CFR-C50-STANDARD',
            'totalformatted' => '25,00 €',
        ];
        $items = [[
            'type' => 'course',
            'title' => 'Mini-cours — Les articles français',
            'productsku' => 'COURSE_ACCESS.ARTICLES',
            'totalformatted' => '25,00 €',
        ]];

        if ($scenario === 'alf_donation') {
            $purchase = [
                'reference' => 'CFR-C50-DONATION',
                'totalformatted' => '10,00 €',
                'donationformatted' => '10,00 €',
            ];
            $items = [[
                'type' => 'donation',
                'title' => 'Soutien à l’opération d’Alf',
                'productsku' => 'DONATION.ALF',
                'totalformatted' => '10,00 €',
            ]];
        } elseif ($scenario === 'alf_course_donation') {
            $purchase = [
                'reference' => 'CFR-C50-COURSE-DONATION',
                'totalformatted' => '35,00 €',
                'donationformatted' => '10,00 €',
            ];
            $items = [
                [
                    'type' => 'course',
                    'title' => 'Mini-cours — Les articles français',
                    'productsku' => 'COURSE_ACCESS.ARTICLES',
                    'totalformatted' => '25,00 €',
                ],
                [
                    'type' => 'donation',
                    'title' => 'Soutien à l’opération d’Alf',
                    'productsku' => 'DONATION.ALF',
                    'totalformatted' => '10,00 €',
                ],
            ];
        }

        return new CommerceMailContext([
            'customer' => ['firstname' => 'Nata', 'fullname' => 'Nata CampusFR'],
            'purchase' => $purchase,
            'items' => $items,
            'links' => [
                'order' => 'https://example.test/order',
                'purchases' => 'https://example.test/purchases',
                'courses' => 'https://example.test/courses',
                'resources' => 'https://example.test/resources',
                'campus' => 'https://example.test/campus',
            ],
        ]);
    }
}
