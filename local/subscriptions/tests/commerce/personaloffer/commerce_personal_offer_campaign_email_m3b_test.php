<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailPricingPresentationService;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_campaign_email_m3b_test extends advanced_testcase {
    public function test_ru_campaign_renderer_uses_safe_variables_authoritative_rub_price_and_campaign_cta(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Natalia', 'lastname' => 'Test', 'email' => 'nata-m3b@example.test',
        ]);
        // create_user() may normalise an unavailable language pack back to en.
        // Commerce supports RU independently of installed UI language packs, so persist the
        // beneficiary language exactly as production/Legacy data can contain it.
        $DB->set_field('user', 'lang', 'ru', ['id' => (int)$user->id]);
        $user->lang = 'ru';
        [$productid, $sku] = $this->create_product_with_prices('M3B.RU', 5500, 549000);
        $campaignid = $this->create_campaign('m3b-ru', $productid, (int)$user->id);
        $this->insert_content($campaignid, 'ru',
            'Вершина для {{firstname}} — {{offer_price}} — {{offer_start}}',
            '<p>Здравствуйте, {{firstname}}!</p><p>Старт: {{offer_start}}. Финиш: {{offer_end}}.</p><p>{{product_name}}: {{offer_price}} вместо {{regular_price}}.</p><script>alert(1)</script><p>{{unknown_variable}}</p>',
            'Начать восхождение',
            '<p>До встречи на вершине ❤️</p>',
            (int)$user->id
        );
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3b-ru', $productid, $user->email,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000]),
            'm3b-test', null, (int)$user->id, time() - 60, time() + DAYSECS
        ));

        $mail = CommercePersonalOfferMailService::create($DB)->queue_offer(
            (int)$issued->get_offer()->get_id(), $campaignid, null
        );
        $preview = (new CommerceMailAdminService())->preview((int)$mail->id);

        $this->assertStringContainsString('Natalia', $preview['subject']);
        $this->assertStringContainsString('₽', $preview['subject']);
        $this->assertStringNotContainsString('{{offer_start}}', $preview['subject']);
        $this->assertStringContainsString('Здравствуйте, Natalia!', $preview['html']);
        $this->assertStringContainsString('Старт:', $preview['html']);
        $this->assertStringContainsString('Финиш:', $preview['html']);
        $this->assertStringNotContainsString('{{offer_start}}', $preview['html']);
        $this->assertStringNotContainsString('{{offer_end}}', $preview['html']);
        $this->assertStringContainsString('Начать восхождение', $preview['html']);
        $this->assertStringContainsString('До встречи на вершине', $preview['html']);
        $this->assertStringContainsString('₽', $preview['html']);
        $this->assertStringContainsString('currency=RUB', html_entity_decode($preview['html']));
        $this->assertStringNotContainsString('{{unknown_variable}}', $preview['html']);
        $this->assertStringNotContainsString('<script', strtolower($preview['html']));

        $presentation = CommercePersonalOfferMailPricingPresentationService::create($DB)->resolve(
            $issued->get_offer()->get_offer_uuid(), 'ru'
        );
        $this->assertSame('RUB', $presentation['currency']);
        $this->assertSame(549000, $presentation['regularminor']);
        $this->assertSame(299000, $presentation['offerminor']);
        $this->assertSame(250000, $presentation['discountminor']);
        $this->assertSame($sku, (string)$DB->get_field('local_subs_commerce_product', 'sku', ['id' => $productid]));
    }

    public function test_campaign_content_falls_back_to_french_but_price_currency_follows_requested_language(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Alice', 'email' => 'alice-m3b@example.test', 'lang' => 'en',
        ]);
        [$productid] = $this->create_product_with_prices('M3B.FRFB', 5500, 549000);
        $campaignid = $this->create_campaign('m3b-frfb', $productid, (int)$user->id);
        $this->insert_content($campaignid, 'fr', 'Offre {{firstname}}', '<p>Bonjour {{firstname}}</p>', 'Découvrir', null, (int)$user->id);
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3b-frfb', $productid, $user->email,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000]),
            'm3b-test', null, (int)$user->id, time() - 60, time() + DAYSECS
        ));
        $mail = CommercePersonalOfferMailService::create($DB)->queue_offer((int)$issued->get_offer()->get_id(), $campaignid, null);
        $preview = (new CommerceMailAdminService())->preview((int)$mail->id);

        $this->assertSame('Offre Alice', $preview['subject']);
        $this->assertStringContainsString('Bonjour Alice', $preview['html']);
        $this->assertStringContainsString('Découvrir', $preview['html']);
        $this->assertStringContainsString('currency=EUR', html_entity_decode($preview['html']));
    }

    public function test_campaign_without_custom_content_keeps_historical_personal_offer_template(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$productid] = $this->create_product_with_prices('M3B.LEGACY', 5500, 549000);
        $campaignid = $this->create_campaign('m3b-legacy', $productid, 0);
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3b-legacy', $productid, 'legacy-m3b@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000]),
            'm3b-test', null, null, time() - 60, time() + DAYSECS
        ));
        $mail = CommercePersonalOfferMailService::create($DB)->queue_offer((int)$issued->get_offer()->get_id(), $campaignid, null);
        $preview = (new CommerceMailAdminService())->preview((int)$mail->id);

        $this->assertStringContainsString('M3B.LEGACY product', $preview['html']);
        $this->assertStringContainsString('EUR', $preview['html']);
        $this->assertStringContainsString('RUB', $preview['html']);
        $this->assertStringNotContainsString('currency=EUR', html_entity_decode($preview['html']));
        $this->assertStringNotContainsString('currency=RUB', html_entity_decode($preview['html']));
    }

    private function create_product_with_prices(string $sku, int $eurminor, int $rubminor): array {
        global $DB;
        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku, 'type' => 'digital', 'status' => 'active', 'name' => $sku . ' product', 'description' => '',
            'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        foreach (['EUR' => $eurminor, 'RUB' => $rubminor] as $currency => $minor) {
            $DB->insert_record('local_subs_commerce_prod_price', (object)[
                'productid' => $productid, 'currency' => $currency, 'amountminor' => $minor,
                'provider' => null, 'providerpriceid' => null, 'active' => 1, 'metadatajson' => '{}',
                'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        return [$productid, $sku];
    }

    private function create_campaign(string $key, int $productid, int $userid): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => $key, 'name' => $key, 'audiencetype' => 'list', 'sourceproductsku' => null,
            'targetproductid' => $productid, 'termsversion' => 1,
            'termsjson' => json_encode(CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])->get_data(), JSON_THROW_ON_ERROR),
            'criteriajson' => '{}', 'validfrom' => null, 'expiresat' => null, 'status' => 'draft',
            'timecreated' => $now, 'timemodified' => $now, 'usercreated' => $userid ?: null, 'usermodified' => $userid ?: null,
        ]);
    }

    private function insert_content(int $campaignid, string $language, string $subject, string $body, string $cta, ?string $closing, int $userid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_offer_campaign_email_content', (object)[
            'campaignid' => $campaignid, 'language' => $language, 'subject' => $subject, 'body' => $body,
            'bodyformat' => (int)FORMAT_HTML, 'ctalabel' => $cta, 'closing' => $closing,
            'closingformat' => (int)FORMAT_HTML, 'timecreated' => $now, 'timemodified' => $now,
            'usercreated' => $userid, 'usermodified' => $userid,
        ]);
    }
}
