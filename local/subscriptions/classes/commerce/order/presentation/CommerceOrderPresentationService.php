<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseGrantSummary;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseFulfillmentSummary;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayText;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;
use core_text;
use moodle_database;
use moodle_url;

/** Builds the shared Native order presentation used by I2, I4, I5 and I6. */
final class CommerceOrderPresentationService {
    private const PAID_STATUSES = ['paid', 'captured', 'completed', 'succeeded', 'success'];
    private const COMPLETE_FULFILLMENT_STATUSES = ['completed', 'fulfilled', 'delivered', 'success'];

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePurchaseReadRepository $purchases,
        private readonly ?CommerceOrderAccessActionResolver $accessresolver = null,
        private readonly ?CommerceProductDisplayNameResolver $displaynames = null
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self(
            $DB,
            new CommercePurchaseReadRepository($DB),
            CommerceOrderAccessActionResolver::create(),
            CommerceProductDisplayNameResolver::create($DB)
        );
    }

    public function find_for_user(
        string $reference,
        int $userid,
        bool $allowadmin = false,
        string $useremail = ''
    ): ?CommerceOrderPresentation {
        $details = $this->purchases->find_by_reference(trim($reference));
        if ($details === null) {
            return null;
        }
        $this->assert_access($details, $userid, $allowadmin, $useremail);
        return $this->present($details);
    }

    public function present(CommercePurchaseDetails $details): CommerceOrderPresentation {
        $summary = $details->summary;
        $payment = $this->select_payment($details->payments);
        $fulfillmentsbygrant = [];
        foreach ($details->fulfillments as $fulfillment) {
            $fulfillmentsbygrant[$fulfillment->reference] = $fulfillment;
        }
        $grantsbyitem = [];
        foreach ($details->grants as $grant) {
            $grantsbyitem[$grant->itemreference][] = $grant;
        }

        $items = [];
        foreach ($details->items as $record) {
            $itemreference = (string)$record->itemreference;
            $accesses = [];
            foreach ($grantsbyitem[$itemreference] ?? [] as $grant) {
                $accesses[] = $this->resolver()->resolve(
                    $summary->reference,
                    $grant,
                    $fulfillmentsbygrant[$grant->reference] ?? null
                );
            }
            $items[] = new CommerceOrderItemPresentation(
                $itemreference,
                (string)$record->itemtype,
                $this->resolve_item_label(
                    (string)$record->itemreference,
                    (string)$record->label,
                    $this->decode_json((string)$record->metadatajson),
                    array_values(array_map(
                        static fn($grant): string => (string)$grant->productsku,
                        $grantsbyitem[$itemreference] ?? []
                    ))
                ),
                (int)$record->quantity,
                (string)$record->currency,
                (int)$record->unitminor,
                (int)$record->grossminor,
                (int)$record->discountminor,
                (int)$record->netminor,
                $accesses,
                $this->decode_json((string)$record->metadatajson)
            );
        }

        $paymentstatus = $payment?->status ?? $summary->paymentstatus;
        $paidat = $payment?->paidat;
        $timeline = [new CommerceOrderTimelineEvent('order_created', 'completed', $summary->timecreated, 'order_created')];
        if ($payment !== null) {
            $timeline[] = new CommerceOrderTimelineEvent(
                'payment',
                $paymentstatus,
                $paidat ?? $summary->timecreated,
                in_array(strtolower($paymentstatus), self::PAID_STATUSES, true) ? 'payment_confirmed' : 'payment_' . strtolower($paymentstatus)
            );
        }
        foreach ($details->fulfillments as $fulfillment) {
            $timeline[] = new CommerceOrderTimelineEvent(
                'access',
                $fulfillment->status,
                $fulfillment->timecompleted ?? $fulfillment->timestarted ?? $summary->timecreated,
                in_array(strtolower($fulfillment->status), self::COMPLETE_FULFILLMENT_STATUSES, true)
                    ? 'access_available'
                    : 'access_' . strtolower($fulfillment->status),
                ['grantreference' => $fulfillment->reference, 'type' => $fulfillment->key]
            );
        }
        usort($timeline, static fn($a, $b): int => $a->timestamp <=> $b->timestamp);

        return new CommerceOrderPresentation(
            $summary->id,
            $summary->uuid,
            $summary->reference,
            $summary->type,
            $summary->customer->userid,
            $summary->customer->email,
            $summary->currency,
            $summary->totalminor,
            $summary->commercialstatus,
            $paymentstatus,
            $summary->fulfillmentstatus,
            $payment?->provider ?? $summary->provider,
            $paidat,
            $summary->timecreated,
            $items,
            $timeline,
            $this->order_actions($items),
            $details->metadata,
            $payment === null ? null : new CommerceOrderPaymentPresentation(
                $payment->status,
                $payment->provider,
                $payment->providerreference,
                $payment->transactionid,
                $payment->currency,
                $payment->amountminor,
                $payment->paidat,
                $payment->paymentrequest?->status,
                $payment->paymentrequest?->createdat,
                $payment->paymentrequest?->expiresat
            )
        );
    }

    private function assert_access(
        CommercePurchaseDetails $details,
        int $userid,
        bool $allowadmin,
        string $useremail = ''
    ): void {
        if ($allowadmin) {
            return;
        }
        $ownerid = $details->summary->customer->userid;
        if ($ownerid !== null && $ownerid === $userid) {
            return;
        }

        $owneremail = core_text::strtolower(trim($details->summary->customer->email));
        $useremail = core_text::strtolower(trim($useremail));
        if ($owneremail !== '' && $useremail !== '' && hash_equals($owneremail, $useremail)) {
            return;
        }

        foreach ($details->grants as $grant) {
            if ($grant->beneficiaryuserid !== null && $grant->beneficiaryuserid === $userid) {
                return;
            }

            $beneficiaryemail = core_text::strtolower(trim($grant->beneficiaryemail));
            if ($beneficiaryemail !== '' && $useremail !== '' && hash_equals($beneficiaryemail, $useremail)) {
                return;
            }
        }

        throw new CommerceOrderPresentationAccessDeniedException('The requested Native order does not belong to this user.');
    }

    private function select_payment(array $payments): ?object {
        if ($payments === []) {
            return null;
        }
        foreach (array_reverse($payments) as $payment) {
            if (in_array(strtolower($payment->status), self::PAID_STATUSES, true)) {
                return $payment;
            }
        }
        return $payments[array_key_last($payments)];
    }

    /**
     * Resolve the customer-facing product title once for the whole order stack.
     *
     * Priority: requested language -> FR -> EN -> RU -> any translation ->
     * Native product name -> persisted purchase label.
     *
     * @param array<string,mixed> $metadata
     */
    /**
     * @param array<string,mixed> $metadata
     * @param string[] $grantproductskus
     */
    private function resolve_item_label(
        string $itemreference,
        string $persistedlabel,
        array $metadata,
        array $grantproductskus = []
    ): string {
        $resolver = $this->displaynames
            ?? CommerceProductDisplayNameResolver::create($this->database);

        return $resolver->resolve(
            [
                (string)($metadata['productsku'] ?? ''),
                (string)($metadata['sku'] ?? ''),
                $itemreference,
                ...$grantproductskus,
            ],
            current_language(),
            $persistedlabel
        );
    }

    private function resolver(): CommerceOrderAccessActionResolver {
        return $this->accessresolver ?? new CommerceOrderAccessActionResolver($this->database, time());
    }

    private function order_actions(array $items): array {
        $actions = [];
        foreach ($items as $item) {
            foreach ($item->accesses as $access) {
                if ($access->available && $access->url !== null) {
                    $actions[] = ['type' => $access->type, 'label' => $access->label, 'url' => $access->url];
                }
            }
        }
        return $actions;
    }

    private function decode_json(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
