<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailQueueService;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailContextFactory;
use local_subscriptions\commerce\mail\service\CommerceTransactionalPurchaseMailService;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderItemPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPaymentPresentation;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;

final class commerce_transactional_purchase_mail_service_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_context_factory_exports_native_order_without_domain_objects(): void {
        global $DB;
        $factory = CommercePurchaseMailContextFactory::create();
        $order = new CommerceOrderPresentation(
            42, 'uuid-42', 'CFR-42', 'native', null, 'guest@example.test', 'EUR', 9900,
            'completed', 'paid', 'fulfilled', 'stripe', time(), time(),
            [new CommerceOrderItemPresentation('item-1', 'course', 'Cours A1', 1, 'EUR', 9900, 9900, 0, 9900)],
            [], [], [], new CommerceOrderPaymentPresentation('paid', 'stripe', 'pi_42', 'pi_42', 'EUR', 9900, time())
        );
        $context = $factory->context_from_order($order, 'Guest CampusFR');
        $this->assertSame(
            (new CommercePublicOrderReference())->from_internal('CFR-42', $order->timecreated),
            $context['purchase']['reference']
        );
        $this->assertSame('Cours A1', $context['items'][0]['title']);
        $this->assertSame('course', $context['items'][0]['type']);
        $this->assertSame('Stripe', $context['payment']['providerlabel']);
        $this->assertIsString(json_encode($context, JSON_THROW_ON_ERROR));
    }

    public function test_queue_service_is_idempotent_for_access_and_receipt(): void {
        $recipient = new \local_subscriptions\commerce\mail\CommerceMailRecipient('test@example.test');
        $context = new \local_subscriptions\commerce\mail\CommerceMailContext(['purchase' => ['reference' => 'CFR-42']]);
        $queue = new CommerceMailQueueService(new CommerceMailQueueRepository());
        foreach ([CommerceMailType::PURCHASE_ACCESS, CommerceMailType::PURCHASE_RECEIPT] as $type) {
            $request = new \local_subscriptions\commerce\mail\CommerceMailRequest(
                $type, $recipient, $context, 'fr',
                \local_subscriptions\commerce\mail\CommerceMailIdempotencyKey::for_purchase(42, $type), 42
            );
            $first = $queue->queue($request);
            $second = $queue->queue($request);
            $this->assertSame((int)$first->id, (int)$second->id);
        }
    }
}
