<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\action;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailContextFactory;

/**
 * Manual CRM communication actions for one Commerce purchase.
 */
final class CommercePurchaseCommunicationActionService {
    public function resend_receipt(string $reference, int $actorid): array {
        if ($actorid <= 0) {
            throw new \coding_exception('A manual receipt resend requires a valid actor ID.');
        }

        $mail = CommercePurchaseMailContextFactory::create()->build_by_reference($reference);
        $context = $mail['context']->with('manualresend', [
            'actorid' => $actorid,
            'requestedat' => time(),
        ]);
        $key = CommerceMailIdempotencyKey::normalise(
            'purchase:' . $mail['purchaseid']
                . ':' . CommerceMailType::PURCHASE_RECEIPT
                . ':manual:' . time()
                . ':' . $actorid
                . ':' . bin2hex(random_bytes(4))
        );

        $record = CommerceMailRuntime::queue_service()->queue(
            new CommerceMailRequest(
                CommerceMailType::PURCHASE_RECEIPT,
                $mail['recipient'],
                $context,
                $mail['language'],
                $key,
                $mail['purchaseid']
            )
        );

        $result = CommerceMailRuntime::processor()->process_ids([(int)$record->id]);

        return [
            'recordid' => (int)$record->id,
            'sent' => (int)$result['sent'] === 1,
            'queued' => (int)$result['retried'] === 1,
            'failed' => (int)$result['failed'] === 1,
        ];
    }
}
