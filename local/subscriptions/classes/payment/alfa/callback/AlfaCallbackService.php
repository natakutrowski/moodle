<?php

declare(strict_types=1);

namespace local_subscriptions\payment\alfa\callback;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationFinalizerInterface;

/**
 * Safe Alfa callback application boundary.
 *
 * A callback never asserts that a payment is paid. The supplied order identity is
 * revalidated against Alfa and only checkout_completed is allowed to enter the
 * Commerce/Legacy post-payment pipeline. Pending/declined notifications are
 * acknowledged without downgrading a Campus payment; M8C remains the recovery
 * authority for delayed deposits.
 */
final class AlfaCallbackService {
    public function __construct(
        private readonly AlfaCallbackRequestNormalizer $normalizer,
        private readonly AlfaCallbackStatusProbeInterface $probe,
        private readonly AlfaPaymentReconciliationFinalizerInterface $finalizer
    ) {
    }

    public static function create(): self {
        return new self(
            new AlfaCallbackRequestNormalizer(),
            new AlfaGatewayCallbackStatusProbe(),
            new \local_subscriptions\commerce\payment\reconciliation\alfa\EventRouterAlfaPaymentReconciliationFinalizer()
        );
    }

    /**
     * @return array{result:string,eventtype:string,identity:array}
     */
    public function handle(
        string $rawbody,
        array $headers = [],
        array $query = [],
        array $post = []
    ): array {
        $identity = $this->normalizer->normalize($rawbody, $query, $post);
        $event = $this->probe->probe($identity, $headers);

        if ($event->type !== 'checkout_completed') {
            return [
                'result' => 'acknowledged_nonpaid',
                'eventtype' => $event->type,
                'identity' => $identity,
            ];
        }

        $event->meta['callback_source'] = 'alfa_server_callback';
        $event->meta['callback_received_at'] = time();
        $this->finalizer->finalize($event);

        return [
            'result' => 'reconciled',
            'eventtype' => $event->type,
            'identity' => $identity,
        ];
    }
}
