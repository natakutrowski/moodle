<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

use local_subscriptions\commerce\persistence\CommercePersistenceJsonCodec;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentRefundRepository {
    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePersistenceJsonCodec $jsoncodec =
            new CommercePersistenceJsonCodec()
    ) {}

    public function find(int $id): ?CommercePaymentRefundRecord {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_REFUND,
            ['id' => $id],
            '*',
            IGNORE_MISSING
        );

        return $record === false ? null : $this->hydrate($record);
    }

    public function find_by_provider_refund_id(
        string $provider,
        string $providerrefundid
    ): ?CommercePaymentRefundRecord {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_REFUND,
            [
                'provider' => strtolower(trim($provider)),
                'providerrefundid' => trim($providerrefundid),
            ],
            '*',
            IGNORE_MISSING
        );

        return $record === false ? null : $this->hydrate($record);
    }

    public function find_by_idempotency_key(
        string $key
    ): ?CommercePaymentRefundRecord {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_REFUND,
            ['idempotencykey' => trim($key)],
            '*',
            IGNORE_MISSING
        );

        return $record === false ? null : $this->hydrate($record);
    }

    /** @return CommercePaymentRefundRecord[] */
    public function find_for_payment(int $paymentid): array {
        $records = $this->database->get_records(
            CommercePersistenceSchema::TABLE_REFUND,
            ['paymentid' => $paymentid],
            'id ASC'
        );

        return array_map(
            fn(\stdClass $record): CommercePaymentRefundRecord =>
                $this->hydrate($record),
            array_values($records)
        );
    }

    public function create_pending(
        int $paymentid,
        string $provider,
        string $idempotencykey,
        string $currency,
        int $amountminor,
        ?string $reason,
        array $metadata,
        ?int $createdby
    ): CommercePaymentRefundRecord {
        $now = time();

        $record = (object)[
            'paymentid' => $paymentid,
            'provider' => strtolower(trim($provider)),
            'providerrefundid' => null,
            'idempotencykey' => trim($idempotencykey),
            'status' => CommercePaymentRefundResult::STATUS_PENDING,
            'currency' => strtoupper(trim($currency)),
            'amountminor' => $amountminor,
            'reason' => $this->normalise_nullable($reason),
            'metadatajson' => $this->jsoncodec->encode($metadata),
            'providerpayload' => null,
            'createdby' => $createdby,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $record->id = (int)$this->database->insert_record(
            CommercePersistenceSchema::TABLE_REFUND,
            $record
        );

        return $this->hydrate($record);
    }

    public function complete(
        int $id,
        CommercePaymentRefundResult $result
    ): CommercePaymentRefundRecord {
        $record = $this->require_record($id);
        $record->providerrefundid =
            $this->normalise_nullable(
                $result->get_provider_refund_id()
            );
        $record->status = $result->get_status();
        $record->providerpayload = $this->jsoncodec->encode(
            $result->get_metadata()
        );
        $record->timemodified = time();

        $this->database->update_record(
            CommercePersistenceSchema::TABLE_REFUND,
            $record
        );

        return $this->hydrate($record);
    }

    public function mark_failed(
        int $id,
        array $providerpayload = []
    ): CommercePaymentRefundRecord {
        $record = $this->require_record($id);
        $record->status = CommercePaymentRefundResult::STATUS_FAILED;
        $record->providerpayload = $this->jsoncodec->encode(
            $providerpayload
        );
        $record->timemodified = time();

        $this->database->update_record(
            CommercePersistenceSchema::TABLE_REFUND,
            $record
        );

        return $this->hydrate($record);
    }

    public function import_provider_refund(
        int $paymentid,
        CommercePaymentRefundResult $result,
        ?int $createdby = null,
        array $metadata = []
    ): CommercePaymentRefundRecord {
        $existing = $this->find_by_provider_refund_id(
            $result->get_provider_key(),
            $result->get_provider_refund_id()
        );
        if ($existing !== null) {
            if (
                !empty($result->get_metadata()['aggregate'])
                && (
                    $existing->get_amount_minor()
                        !== $result->get_amount_minor()
                    || $existing->get_status()
                        !== $result->get_status()
                )
            ) {
                $record = $this->require_record(
                    $existing->get_id()
                );
                $record->amountminor =
                    $result->get_amount_minor();
                $record->status =
                    $result->get_status();
                $record->providerpayload =
                    $this->jsoncodec->encode(
                        $result->get_metadata()
                    );
                $record->timemodified = time();

                $this->database->update_record(
                    CommercePersistenceSchema::TABLE_REFUND,
                    $record
                );

                return $this->hydrate($record);
            }

            return $existing;
        }

        $now = time();
        $record = (object)[
            'paymentid' => $paymentid,
            'provider' => $result->get_provider_key(),
            'providerrefundid' => $result->get_provider_refund_id(),
            'idempotencykey' =>
                'provider-import-'
                . $result->get_provider_key()
                . '-'
                . $result->get_provider_refund_id(),
            'status' => $result->get_status(),
            'currency' => $result->get_currency(),
            'amountminor' => $result->get_amount_minor(),
            'reason' => null,
            'metadatajson' => $this->jsoncodec->encode(
                array_merge(
                    ['source' => 'provider_import'],
                    $metadata
                )
            ),
            'providerpayload' => $this->jsoncodec->encode(
                $result->get_metadata()
            ),
            'createdby' => $createdby,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $record->id = (int)$this->database->insert_record(
            CommercePersistenceSchema::TABLE_REFUND,
            $record
        );

        return $this->hydrate($record);
    }

    public function refundable_amount_minor(
        int $paymentid,
        int $paymentamountminor
    ): int {
        $used = 0;

        foreach ($this->find_for_payment($paymentid) as $refund) {
            if ($refund->is_counted_against_refundable_amount()) {
                $used += $refund->get_amount_minor();
            }
        }

        return max(0, $paymentamountminor - $used);
    }

    /**
     * Sum only provider-confirmed successful refunds for lifecycle decisions.
     *
     * Pending refunds intentionally count against the refundable balance to
     * prevent over-refunding, but they must never terminate access/support.
     */
    public function successful_refunded_amount_minor(int $paymentid): int {
        $total = 0;

        foreach ($this->find_for_payment($paymentid) as $refund) {
            if ($refund->get_status() === CommercePaymentRefundResult::STATUS_SUCCEEDED) {
                $total += $refund->get_amount_minor();
            }
        }

        return max(0, $total);
    }

    private function require_record(int $id): \stdClass {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_REFUND,
            ['id' => $id],
            '*',
            MUST_EXIST
        );

        return $record;
    }

    private function hydrate(\stdClass $record): CommercePaymentRefundRecord {
        return new CommercePaymentRefundRecord(
            (int)$record->id,
            (int)$record->paymentid,
            (string)$record->provider,
            $this->normalise_nullable($record->providerrefundid ?? null),
            (string)$record->idempotencykey,
            (string)$record->status,
            strtoupper((string)$record->currency),
            (int)$record->amountminor,
            $this->normalise_nullable($record->reason ?? null),
            $this->jsoncodec->decode(
                (string)($record->metadatajson ?? '[]')
            ),
            $this->decode_nullable(
                $record->providerpayload ?? null
            ),
            isset($record->createdby) && $record->createdby !== null
                ? (int)$record->createdby
                : null,
            (int)$record->timecreated,
            (int)$record->timemodified
        );
    }

    private function decode_nullable(?string $json): ?array {
        if ($json === null || trim($json) === '') {
            return null;
        }

        return $this->jsoncodec->decode($json);
    }

    private function normalise_nullable(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        return $value !== '' ? $value : null;
    }
}
