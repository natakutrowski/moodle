<?php

namespace local_subscriptions\commerce\payment\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\persistence\CommercePersistenceJsonCodec;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/**
 * Native SQL repository for Commerce payment attempts.
 */
final class CommercePaymentRepository {

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePersistenceJsonCodec $jsoncodec = new CommercePersistenceJsonCodec()
    ) {
    }

    /**
     * Create a new attempt for a purchase.
     *
     * One row is inserted for every user-initiated payment attempt.
     */
    public function create(
        string $purchaseuuid,
        string $provider,
        int $amountminor,
        string $currency,
        array $metadata = []
    ): CommercePaymentAttempt {
        $purchaseid = $this->resolve_purchase_id($purchaseuuid);
        $now = time();
        $sequence = $this->next_sequence($purchaseid);

        $record = (object) [
            'purchaseid' => $purchaseid,
            'sequence' => $sequence,
            'provider' => strtolower(trim($provider)),
            'providerreference' => null,
            'providerorderid' => null,
            'status' => CommercePaymentAttemptStatus::CREATED,
            'currency' => strtoupper(trim($currency)),
            'amountminor' => $amountminor,
            'transactionid' => null,
            'legacyrequestid' => null,
            'paidat' => null,
            'metadatajson' => $this->jsoncodec->encode($metadata),
            'paymenturl' => null,
            'providerpayload' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $record->id = (int) $this->database->insert_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            $record
        );

        return $this->hydrate($record, $purchaseuuid);
    }

    /**
     * Persist provider launch details after a checkout session/order is created.
     */
    public function record_provider_launch(
        int $paymentid,
        ?string $providerreference,
        ?string $providerorderid,
        ?string $paymenturl,
        ?array $providerpayload = null
    ): CommercePaymentAttempt {
        $record = $this->get_record($paymentid);
        $record->providerreference = $this->normalise_nullable_string($providerreference);
        $record->providerorderid = $this->normalise_nullable_string($providerorderid);
        $record->paymenturl = $this->normalise_nullable_string($paymenturl);
        $record->providerpayload = $providerpayload === null
            ? null
            : $this->jsoncodec->encode($providerpayload);
        $record->status = CommercePaymentAttemptStatus::REDIRECTED;
        $record->timemodified = time();

        $this->database->update_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            $record
        );

        return $this->find($paymentid);
    }

    /**
     * Update the normalised lifecycle status of one payment attempt.
     */
    public function update_status(
        int $paymentid,
        string $status,
        ?string $transactionid = null,
        ?array $providerpayload = null,
        ?int $paidat = null
    ): CommercePaymentAttempt {
        $record = $this->get_record($paymentid);
        $status = CommercePaymentAttemptStatus::normalise($status);

        $record->status = $status;
        $record->transactionid = $this->normalise_nullable_string($transactionid)
            ?? $record->transactionid;
        $record->providerpayload = $providerpayload === null
            ? $record->providerpayload
            : $this->jsoncodec->encode($providerpayload);

        if (in_array($status, [
            CommercePaymentAttemptStatus::PAID,
            CommercePaymentAttemptStatus::COMPLETED,
        ], true)) {
            $record->paidat = $paidat ?? time();
        }

        $record->timemodified = time();

        $this->database->update_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            $record
        );

        return $this->find($paymentid);
    }

    public function find(int $paymentid): ?CommercePaymentAttempt {
        if ($paymentid <= 0) {
            return null;
        }

        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['id' => $paymentid],
            '*',
            IGNORE_MISSING
        );

        if ($record === false) {
            return null;
        }

        return $this->hydrate(
            $record,
            $this->resolve_purchase_uuid((int) $record->purchaseid)
        );
    }

    public function find_by_provider_reference(
        string $provider,
        string $providerreference
    ): ?CommercePaymentAttempt {
        return $this->find_by_provider_field(
            $provider,
            'providerreference',
            $providerreference
        );
    }

    public function find_by_provider_order_id(
        string $provider,
        string $providerorderid
    ): ?CommercePaymentAttempt {
        return $this->find_by_provider_field(
            $provider,
            'providerorderid',
            $providerorderid
        );
    }

    /**
     * @return CommercePaymentAttempt[] Newest attempt first.
     */
    public function find_for_purchase(string $purchaseuuid): array {
        $purchaseid = $this->resolve_purchase_id($purchaseuuid);
        $records = $this->database->get_records(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => $purchaseid],
            'sequence DESC'
        );

        return array_map(
            fn(\stdClass $record): CommercePaymentAttempt => $this->hydrate($record, $purchaseuuid),
            array_values($records)
        );
    }

    private function find_by_provider_field(
        string $provider,
        string $field,
        string $value
    ): ?CommercePaymentAttempt {
        $provider = strtolower(trim($provider));
        $value = trim($value);

        if ($provider === '' || $value === '') {
            return null;
        }

        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            [
                'provider' => $provider,
                $field => $value,
            ],
            '*',
            IGNORE_MULTIPLE
        );

        if ($record === false) {
            return null;
        }

        return $this->hydrate(
            $record,
            $this->resolve_purchase_uuid((int) $record->purchaseid)
        );
    }

    private function next_sequence(int $purchaseid): int {
        $maximum = $this->database->get_field_sql(
            'SELECT MAX(sequence)
               FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '}
              WHERE purchaseid = :purchaseid',
            ['purchaseid' => $purchaseid]
        );

        return $maximum === false || $maximum === null
            ? 0
            : ((int) $maximum + 1);
    }

    private function resolve_purchase_id(string $purchaseuuid): int {
        $purchaseuuid = strtolower(trim($purchaseuuid));

        $purchaseid = $this->database->get_field(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'id',
            ['purchaseuuid' => $purchaseuuid],
            IGNORE_MISSING
        );

        if ($purchaseid === false) {
            throw new \RuntimeException(
                'Unable to create a payment attempt for an unknown Commerce purchase.'
            );
        }

        return (int) $purchaseid;
    }

    private function resolve_purchase_uuid(int $purchaseid): string {
        $purchaseuuid = $this->database->get_field(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'purchaseuuid',
            ['id' => $purchaseid],
            MUST_EXIST
        );

        return (string) $purchaseuuid;
    }

    private function get_record(int $paymentid): \stdClass {
        if ($paymentid <= 0) {
            throw new \InvalidArgumentException('A Commerce payment attempt identifier must be positive.');
        }

        return $this->database->get_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['id' => $paymentid],
            '*',
            MUST_EXIST
        );
    }

    private function hydrate(
        \stdClass $record,
        string $purchaseuuid
    ): CommercePaymentAttempt {
        return new CommercePaymentAttempt(
            (int) $record->id,
            $purchaseuuid,
            (int) $record->sequence,
            (string) $record->provider,
            (string) $record->status,
            (int) $record->amountminor,
            (string) $record->currency,
            $record->providerreference ?? null,
            $record->providerorderid ?? null,
            $record->transactionid ?? null,
            isset($record->legacyrequestid) && $record->legacyrequestid !== null
                ? (int) $record->legacyrequestid
                : null,
            $record->paymenturl ?? null,
            $this->jsoncodec->decode((string) ($record->metadatajson ?? '[]')),
            $this->decode_nullable_payload($record->providerpayload ?? null),
            isset($record->paidat) && $record->paidat !== null
                ? (int) $record->paidat
                : null,
            isset($record->timecreated) ? (int) $record->timecreated : null,
            isset($record->timemodified) ? (int) $record->timemodified : null
        );
    }

    private function decode_nullable_payload(?string $payload): ?array {
        if ($payload === null || trim($payload) === '') {
            return null;
        }

        return $this->jsoncodec->decode($payload);
    }

    private function normalise_nullable_string(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
