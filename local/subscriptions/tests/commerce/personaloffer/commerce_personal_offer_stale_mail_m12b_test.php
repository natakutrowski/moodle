<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailDispatcher;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailQueueProcessor;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRetryPolicy;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailTransport;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

/**
 * M12b: queued Personal Offer mail becomes terminal when the offer is no longer deliverable.
 */
final class commerce_personal_offer_stale_mail_m12b_test extends advanced_testcase {

    public function test_redeemed_offer_cancels_queued_mail_without_transport_or_retry(): void {
        global $DB;

        $this->resetAfterTest(true);

        [$record, $offerid] = $this->queue_offer('M12B.REDEEMED', time() + DAYSECS);

        $DB->update_record('local_subs_commerce_offer', (object)[
            'id' => $offerid,
            'status' => 'redeemed',
            'redeemedat' => time(),
            'redeemedpurchaseid' => 3322,
            'timemodified' => time(),
        ]);

        [$result, $transport, $after] = $this->process((int)$record->id);

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['cancelled']);
        $this->assertSame(0, $result['retried']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, $transport->attempts);
        $this->assertSame(CommerceMailStatus::CANCELLED, $after->status);
        $this->assertSame(1, (int)$after->attemptcount);
        $this->assertStringContainsString('personal_offer_redeemed', (string)$after->lasterror);
        $this->assertStringContainsString('purchase #3322', (string)$after->lasterror);
        $this->assertNull($after->timesent);
        $this->assertNull($after->timeprocessing);
    }

    public function test_revoked_offer_cancels_queued_mail_without_transport(): void {
        global $DB;

        $this->resetAfterTest(true);

        [$record, $offerid] = $this->queue_offer('M12B.REVOKED', time() + DAYSECS);

        $DB->update_record('local_subs_commerce_offer', (object)[
            'id' => $offerid,
            'status' => 'revoked',
            'revokedat' => time(),
            'revokedbyuserid' => 2,
            'revokereason' => 'test',
            'timemodified' => time(),
        ]);

        [$result, $transport, $after] = $this->process((int)$record->id);

        $this->assertSame(1, $result['cancelled']);
        $this->assertSame(0, $transport->attempts);
        $this->assertSame(CommerceMailStatus::CANCELLED, $after->status);
        $this->assertStringContainsString('personal_offer_revoked', (string)$after->lasterror);
    }

    public function test_expired_offer_cancels_queued_mail_without_transport(): void {
        global $DB;

        $this->resetAfterTest(true);

        [$record, $offerid] = $this->queue_offer('M12B.EXPIRED', time() + DAYSECS);

        $DB->set_field('local_subs_commerce_offer', 'expiresat', time() - 1, ['id' => $offerid]);

        [$result, $transport, $after] = $this->process((int)$record->id);

        $this->assertSame(1, $result['cancelled']);
        $this->assertSame(0, $transport->attempts);
        $this->assertSame(CommerceMailStatus::CANCELLED, $after->status);
        $this->assertStringContainsString('personal_offer_expired', (string)$after->lasterror);
    }

    /** @return array{\stdClass,int} */
    private function queue_offer(string $sku, int $expiresat): array {
        global $DB;

        $productid = $this->create_product($sku);
        $issued = CommercePersonalOfferFactory::create()->issue(new CommercePersonalOfferIssueRequest(
            'm12b-stale-mail',
            $productid,
            'stale@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 2900, 'RUB' => 290000]),
            'm12b',
            null,
            null,
            time() - 60,
            $expiresat
        ));

        $offer = $issued->get_offer();
        $record = CommercePersonalOfferMailService::create($DB)->queue_offer((int)$offer->get_id());

        return [$record, (int)$offer->get_id()];
    }

    /**
     * @return array{
     *   0:array{processed:int,sent:int,retried:int,failed:int,cancelled:int,skipped:int},
     *   1:object,
     *   2:\stdClass
     * }
     */
    private function process(int $mailid): array {
        $repository = new CommerceMailQueueRepository();
        $transport = new class implements CommerceMailTransport {
            public int $attempts = 0;

            public function send(CommerceMailMessage $message): void {
                $this->attempts++;
            }
        };

        $templates = CommerceMailRuntime::template_registry();
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, $transport),
            new CommerceMailRetryPolicy()
        );

        $before = $repository->find_by_id($mailid);
        $result = $processor->process_ids([$mailid], (int)$before->nextruntime);
        $after = $repository->find_by_id($mailid);

        return [$result, $transport, $after];
    }

    private function create_product(string $sku): int {
        global $DB;

        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => 'digital',
            'status' => 'active',
            'name' => 'M12b stale mail product',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
