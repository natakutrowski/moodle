<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationFinalizerInterface;
use local_subscriptions\payment\alfa\callback\AlfaCallbackRequestNormalizer;
use local_subscriptions\payment\alfa\callback\AlfaCallbackService;
use local_subscriptions\payment\alfa\callback\AlfaCallbackStatusProbeInterface;
use local_subscriptions\payment\dto\InternalEvent;

/** @covers \local_subscriptions\payment\alfa\callback\AlfaCallbackService */
final class commerce_alfa_callback_hardening_m8d_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_normalizer_accepts_json_order_id_and_discards_untrusted_metadata(): void {
        $normalizer = new AlfaCallbackRequestNormalizer();

        $result = $normalizer->normalize(json_encode([
            'orderId' => 'alfa-order-1',
            'payment_request_table' => 'user',
            'commerce_payment_id' => '999',
        ]));

        self::assertSame(['orderId' => 'alfa-order-1'], $result);
    }

    public function test_normalizer_accepts_mdorder_query_alias(): void {
        $normalizer = new AlfaCallbackRequestNormalizer();

        self::assertSame(
            ['orderId' => 'alfa-order-md'],
            $normalizer->normalize('', ['mdOrder' => 'alfa-order-md'])
        );
    }

    public function test_paid_callback_is_revalidated_and_finalized_once(): void {
        $probe = new m8d_probe(new InternalEvent('checkout_completed', [
            'amount_minor' => 99000,
            'currency' => 'RUB',
            'meta' => [
                'provider' => 'alfa',
                'session' => 'alfa-order-paid',
            ],
        ]));
        $finalizer = new m8d_finalizer();

        $service = new AlfaCallbackService(
            new AlfaCallbackRequestNormalizer(),
            $probe,
            $finalizer
        );

        $result = $service->handle(
            'orderId=alfa-order-paid',
            [],
            [],
            []
        );

        self::assertSame('reconciled', $result['result']);
        self::assertSame('checkout_completed', $result['eventtype']);
        self::assertSame(1, $probe->calls);
        self::assertSame(1, $finalizer->calls);
        self::assertSame('alfa_server_callback', $finalizer->event->meta['callback_source']);
    }

    public function test_nonpaid_callback_is_acknowledged_without_mutating_campus(): void {
        $probe = new m8d_probe(new InternalEvent('payment_failed', [
            'amount_minor' => 99000,
            'currency' => 'RUB',
            'meta' => [
                'provider' => 'alfa',
                'session' => 'alfa-order-pending',
                'reason' => 'registered_not_paid',
            ],
        ]));
        $finalizer = new m8d_finalizer();

        $service = new AlfaCallbackService(
            new AlfaCallbackRequestNormalizer(),
            $probe,
            $finalizer
        );

        $result = $service->handle(
            '',
            [],
            ['orderId' => 'alfa-order-pending'],
            []
        );

        self::assertSame('acknowledged_nonpaid', $result['result']);
        self::assertSame('payment_failed', $result['eventtype']);
        self::assertSame(1, $probe->calls);
        self::assertSame(0, $finalizer->calls);
    }

    public function test_missing_provider_identity_is_rejected_before_provider_call(): void {
        $probe = new m8d_probe(new InternalEvent('checkout_completed', [
            'meta' => ['provider' => 'alfa'],
        ]));
        $finalizer = new m8d_finalizer();

        $service = new AlfaCallbackService(
            new AlfaCallbackRequestNormalizer(),
            $probe,
            $finalizer
        );

        $this->expectException(\invalid_parameter_exception::class);
        try {
            $service->handle('{}');
        } finally {
            self::assertSame(0, $probe->calls);
            self::assertSame(0, $finalizer->calls);
        }
    }

    public function test_public_endpoint_bootstraps_moodle_from_plugin_depth(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/webhook/alfa.php'
        );

        self::assertStringContainsString(
            "require_once dirname(__DIR__, 3) . '/config.php';",
            $source
        );
        self::assertStringNotContainsString(
            "__DIR__.'/../../config.php'",
            $source
        );
    }
}

final class m8d_probe implements AlfaCallbackStatusProbeInterface {
    public int $calls = 0;

    public function __construct(private readonly InternalEvent $event) {
    }

    public function probe(array $identity, array $headers = []): InternalEvent {
        $this->calls++;
        return $this->event;
    }
}

final class m8d_finalizer implements AlfaPaymentReconciliationFinalizerInterface {
    public int $calls = 0;
    public ?InternalEvent $event = null;

    public function finalize(InternalEvent $event): void {
        $this->calls++;
        $this->event = $event;
    }
}
