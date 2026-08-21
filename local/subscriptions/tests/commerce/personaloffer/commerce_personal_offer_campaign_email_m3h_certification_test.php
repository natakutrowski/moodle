<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferDestinationResolver;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_campaign_email_m3h_certification_test extends advanced_testcase {
    public function test_nata_ru_campaign_renders_authoritative_price_deadline_and_dual_secure_paths(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Natalia',
            'lastname' => 'Test',
            'email' => 'nata-m3h@example.test',
        ]);
        $DB->set_field('user', 'lang', 'ru', ['id' => (int)$user->id]);

        $productid = $this->create_product('M3H.VERBS', 5500, 549000);
        $showroomid = $this->create_showroom('m3h-verbs', 'M3H.VERBS');

        $paris = new \DateTimeZone('Europe/Paris');
        $expiresat = (new \DateTimeImmutable('tomorrow 12:00:00', $paris))->getTimestamp();

        $campaignid = $this->create_campaign($productid, 'm3h-nata-ru', $expiresat);
        $this->configure_email($campaignid, $showroomid);
        $this->insert_nata_ru_content($campaignid);

        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3h-nata-ru',
            $productid,
            $user->email,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000]),
            'm3h-nata-ru',
            null,
            (int)$user->id,
            time() - 60,
            $expiresat
        ));

        $destination = CommercePersonalOfferDestinationResolver::create($DB)->resolve($issued->get_offer());
        $this->assertSame('showroom', $destination['destination']);
        $this->assertSame('m3h-verbs', $destination['showroomkey']);

        $mail = CommercePersonalOfferMailService::create($DB)->queue_offer(
            (int)$issued->get_offer()->get_id(),
            $campaignid,
            null
        );
        $preview = (new CommerceMailAdminService())->preview((int)$mail->id);
        $html = html_entity_decode($preview['html']);

        $this->assertSame(
            'Станьте первым на вершине глаголов 3 группы',
            $preview['subject']
        );
        $this->assertStringContainsString('Bonjour, Natalia!', $html);
        $this->assertStringContainsString('Вы уже занимаетесь по нашим карточкам', $html);
        $this->assertStringContainsString('Начать восхождение', $html);
        $this->assertStringContainsString('До встречи на вершине', $html);

        // Editorial copy contains no marketing-entered price; authoritative pricing is injected by Commerce.
        $content = (string)$DB->get_field(
            'local_subs_commerce_offer_campaign_email_content',
            'body',
            ['campaignid' => $campaignid, 'language' => 'ru'],
            MUST_EXIST
        );
        $this->assertStringNotContainsString('2990', $content);
        $this->assertStringNotContainsString('5490', $content);
        $this->assertStringContainsString('{{offer_end}}', $content);
        $this->assertStringContainsString('₽', $html);
        $this->assertMatchesRegularExpression('/2\s*990/u', strip_tags($html));
        $this->assertMatchesRegularExpression('/5\s*490/u', strip_tags($html));

        // New fixed-datetime validity must preserve the precise local deadline.
        $this->assertStringNotContainsString('{{offer_end}}', $html);
        $this->assertStringContainsString('12:00', $html);

        // Primary link keeps Campaign routing (Showroom); secondary link safely forces checkout only.
        $this->assertStringContainsString('currency=RUB', $html);
        $this->assertStringContainsString('Ваше персональное предложение', $html);
        $this->assertStringContainsString('Предложение действует', $html);
        $this->assertStringContainsString('Перейти к оплате', $html);
        $this->assertStringNotContainsString('Your personal offer', $html);
        $this->assertStringNotContainsString('Pay directly', $html);
        $this->assertStringContainsString('destination=checkout', $html);
        $this->assertStringNotContainsString('price=2990', $html);
        $this->assertStringNotContainsString('price=5490', $html);
    }

    public function test_direct_checkout_override_is_one_way_and_never_accepts_price_or_showroom_override(): void {
        $root = dirname(__DIR__, 3);
        $entry = (string)file_get_contents($root . '/offer.php');
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );

        $this->assertStringContainsString(
            "optional_param('destination', '', PARAM_ALPHA)",
            $entry
        );
        $this->assertStringContainsString(
            "in_array(\$requesteddestination, ['', 'checkout'], true)",
            $entry
        );
        $this->assertStringContainsString(
            "DESTINATION_CHECKOUT",
            $entry
        );
        $this->assertStringNotContainsString(
            "optional_param('price'",
            $entry
        );
        $this->assertStringNotContainsString(
            "optional_param('showroom'",
            $entry
        );
        $this->assertStringContainsString(
            "'commerce_mail_personal_offer_direct_checkout'",
            $template
        );
    }

    private function create_product(string $sku, int $eurminor, int $rubminor): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => 'digital',
            'status' => 'active',
            'name' => 'Тренажёр глаголов 3 группы',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        foreach (['EUR' => $eurminor, 'RUB' => $rubminor] as $currency => $minor) {
            $DB->insert_record('local_subs_commerce_prod_price', (object)[
                'productid' => $id,
                'currency' => $currency,
                'amountminor' => $minor,
                'provider' => null,
                'providerpriceid' => null,
                'active' => 1,
                'metadatajson' => '{}',
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        return $id;
    }

    private function create_showroom(string $key, string $sku): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => $key,
            'status' => 'published',
            'name' => 'Showroom M3H',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'm3h-verbes-fr',
            'slugen' => 'm3h-verbs-en',
            'slugru' => 'm3h-glagoly',
            'titlekey' => null,
            'descriptionkey' => null,
            'productsjson' => json_encode(['course' => $sku], JSON_THROW_ON_ERROR),
            'settingsjson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
        $DB->insert_record('local_subs_showroom_block', (object)[
            'showroomid' => $id,
            'blockkey' => 'hero',
            'blocktype' => 'hero',
            'sortorder' => 10,
            'enabled' => 1,
            'configjson' => '{"title":"M3H"}',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
        return $id;
    }

    private function create_campaign(int $productid, string $key, int $expiresat): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => $key,
            'name' => 'Nata RU certification',
            'audiencetype' => 'list',
            'sourceproductsku' => null,
            'targetproductid' => $productid,
            'termsversion' => 1,
            'termsjson' => json_encode(
                CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000])->get_data(),
                JSON_THROW_ON_ERROR
            ),
            'criteriajson' => '{}',
            'validfrom' => null,
            'expiresat' => $expiresat,
            'validitymode' => 'fixed_datetime',
            'validityduration' => null,
            'validitytimezone' => 'Europe/Paris',
            'status' => 'issued',
            'timecreated' => $now,
            'timemodified' => $now,
            'usercreated' => null,
            'usermodified' => null,
        ]);
    }

    private function configure_email(int $campaignid, int $showroomid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_offer_campaign_email_config', (object)[
            'campaignid' => $campaignid,
            'ctadestination' => 'showroom',
            'showroomid' => $showroomid,
            'timecreated' => $now,
            'timemodified' => $now,
            'usercreated' => null,
            'usermodified' => null,
        ]);
    }

    private function insert_nata_ru_content(int $campaignid): void {
        global $DB;
        $now = time();
        $body = <<<'RU'
Bonjour, {{firstname}}!

Вы уже занимаетесь по нашим карточкам с глаголами 3 группы, они помогают разобраться в спряжениях и быстро их вспомнить. Пора решиться на следующий шаг — натренировать спряжения так, чтобы в разговоре не приходилось каждый раз останавливаться и вспоминать нужную форму.

Для этого мы создали тренажёр, с помощью которого вы:
⭐️ Выучите спряжение глаголов через игровые задания
⭐️ Запомните их passé composé и основы для futur simple
⭐️ Начнёте узнавать формы глаголов на слух
⭐️ Доведете глаголы 3 группы до автоматизма

Как всегда обучение в CampusFR — это приключение. В этот раз мы поднимемся на самую высокую вершину Альп. Каждый урок — новый этап восхождения на Монблан🏔

Только для владельцев карточек до {{offer_end}} действует специальная цена:
RU;

        $DB->insert_record('local_subs_commerce_offer_campaign_email_content', (object)[
            'campaignid' => $campaignid,
            'language' => 'ru',
            'subject' => 'Станьте первым на вершине глаголов 3 группы',
            'body' => $body,
            'bodyformat' => (int)FORMAT_PLAIN,
            'ctalabel' => 'Начать восхождение',
            'closing' => "До встречи на вершине ❤️\nКоманда CampusFR",
            'closingformat' => (int)FORMAT_PLAIN,
            'timecreated' => $now,
            'timemodified' => $now,
            'usercreated' => 0,
            'usermodified' => 0,
        ]);
    }
}
