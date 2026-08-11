<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_mail_test extends advanced_testcase {
    public function test_queue_offer_is_idempotent_and_uses_existing_commerce_outbox(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'offer@example.test']);
        $productid = $this->create_product('K9.MAIL.1');
        $issued = CommercePersonalOfferFactory::create()->issue(new CommercePersonalOfferIssueRequest(
            'k9-mail-test', $productid, $user->email,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000, 'RUB' => 299000]),
            'k9-test', null, (int)$user->id, time() - 60, time() + DAYSECS
        ));

        $service = CommercePersonalOfferMailService::create($DB);
        $first = $service->queue_offer((int)$issued->get_offer()->get_id());
        $second = $service->queue_offer((int)$issued->get_offer()->get_id());

        $this->assertSame((int)$first->id, (int)$second->id);
        $this->assertSame(CommerceMailType::PERSONAL_OFFER, $first->mailtype);
        $this->assertSame('queued', $first->status);
        $this->assertSame($user->email, $first->recipientemail);
        $context = json_decode((string)$first->contextjson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('K9.MAIL.1', $context['personaloffer']['productsku']);
        $this->assertSame(3000, $context['personaloffer']['pricing']['amounts']['EUR']);
        $this->assertStringContainsString('/local/subscriptions/offer.php?token=', $context['personaloffer']['url']);
    }

    public function test_personal_offer_template_renders_premium_offer_card_and_cta(): void {
        global $DB;
        $this->resetAfterTest(true);
        $productid = $this->create_product('K9.MAIL.2');
        $issued = CommercePersonalOfferFactory::create()->issue(new CommercePersonalOfferIssueRequest(
            'k9-render-test', $productid, 'guest@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'k9-render', null, null, time() - 60, time() + DAYSECS
        ));
        $record = CommercePersonalOfferMailService::create($DB)->queue_offer((int)$issued->get_offer()->get_id());
        $preview = (new \local_subscriptions\commerce\mail\admin\CommerceMailAdminService())->preview((int)$record->id);
        $this->assertStringContainsString('K9 premium product', $preview['html']);
        $this->assertStringContainsString('30', $preview['html']);
        $this->assertStringContainsString('/local/subscriptions/offer.php?token=', $preview['html']);
    }

    public function test_due_filter_keeps_personal_offer_mail_out_of_transactional_worker(): void {
        global $DB;
        $this->resetAfterTest(true);
        $productid = $this->create_product('K9.MAIL.3');
        $issued = CommercePersonalOfferFactory::create()->issue(new CommercePersonalOfferIssueRequest(
            'k9-filter-test', $productid, 'filter@example.test',
            CommercePersonalOfferTerms::percentage_discount(2000)
        ));
        CommercePersonalOfferMailService::create($DB)->queue_offer((int)$issued->get_offer()->get_id());
        $repo = new CommerceMailQueueRepository();
        $personal = $repo->get_due(10, time(), [CommerceMailType::PERSONAL_OFFER]);
        $transactional = $repo->get_due(10, time(), null, [CommerceMailType::PERSONAL_OFFER]);
        $this->assertCount(1, $personal);
        $this->assertCount(0, $transactional);
    }

    private function create_product(string $sku): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => 'digital', 'status' => 'active', 'name' => 'K9 premium product', 'description' => '',
            'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }
}
