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
        return $this->resend($reference, $actorid, CommerceMailType::PURCHASE_RECEIPT);
    }

    public function resend_access(string $reference, int $actorid): array {
        return $this->resend($reference, $actorid, CommerceMailType::PURCHASE_ACCESS);
    }

    private function resend(string $reference, int $actorid, string $type): array {
        if ($actorid <= 0) {
            throw new \coding_exception('A manual Commerce mail resend requires a valid actor ID.');
        }
        if (!in_array($type, [CommerceMailType::PURCHASE_RECEIPT, CommerceMailType::PURCHASE_ACCESS], true)) {
            throw new \coding_exception('Unsupported manual Commerce mail type.');
        }

        // M12c: CommercePurchaseMailContextFactory resolves the current canonical
        // communication identity while the persisted purchase remains unchanged.
        $mail = CommercePurchaseMailContextFactory::create()->build_by_reference($reference);
        $context = $mail['context']->with('manualresend', [
            'actorid' => $actorid,
            'requestedat' => time(),
            'type' => $type,
            'recipientemail' => $mail['recipient']->get_email(),
        ]);
        $key = CommerceMailIdempotencyKey::normalise(
            'purchase:' . $mail['purchaseid']
                . ':' . $type
                . ':manual:' . time()
                . ':' . $actorid
                . ':' . bin2hex(random_bytes(4))
        );

        $record = CommerceMailRuntime::queue_service()->queue(
            new CommerceMailRequest(
                $type,
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
            'recipientemail' => $mail['recipient']->get_email(),
            'sent' => (int)$result['sent'] === 1,
            'queued' => (int)$result['retried'] === 1,
            'failed' => (int)$result['failed'] === 1,
            'cancelled' => (int)($result['cancelled'] ?? 0) === 1,
        ];
    }
}
