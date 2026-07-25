<?php

namespace local_subscriptions\commerce\persistence\record;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Immutable database-neutral record for one Commerce purchase. */
final class CommercePurchaseRecord {

    public function __construct(
        private readonly string $purchaseuuid,
        private readonly string $reference,
        private readonly string $type,
        private readonly ?string $legacyfamily,
        private readonly ?int $legacyid,
        private readonly ?int $userid,
        private readonly ?string $customeremail,
        private readonly string $status,
        private readonly string $currency,
        private readonly int $subtotalminor,
        private readonly int $discountminor,
        private readonly int $totalminor,
        private readonly string $customerjson,
        private readonly string $snapshotjson,
        private readonly string $metadatajson,
        private readonly int $snapshotversion,
        private readonly ?int $timecreated,
        private readonly ?int $timemodified
    ) {
        self::require_length($purchaseuuid, CommercePersistenceSchema::PURCHASE_ID_LENGTH, 'purchase UUID');
        self::require_max_length($reference, CommercePersistenceSchema::PURCHASE_REFERENCE_LENGTH, 'purchase reference');
        self::require_max_length($type, CommercePersistenceSchema::TYPE_LENGTH, 'purchase type');
        self::require_max_length($status, CommercePersistenceSchema::STATUS_LENGTH, 'purchase status');

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception('A persisted Commerce currency must be a three-letter ISO code.');
        }
        if ($subtotalminor < 0 || $discountminor < 0 || $totalminor < 0) {
            throw new \coding_exception('Persisted Commerce purchase amounts cannot be negative.');
        }
        if ($subtotalminor - $discountminor !== $totalminor) {
            throw new \coding_exception('Persisted Commerce purchase totals are inconsistent.');
        }
        if (($legacyfamily === null) !== ($legacyid === null)) {
            throw new \coding_exception('A persisted Legacy reference requires both family and identifier.');
        }
        if ($legacyid !== null && $legacyid <= 0) {
            throw new \coding_exception('A persisted Legacy identifier must be positive.');
        }
        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception('A persisted Commerce user identifier must be positive.');
        }
        if ($customeremail !== null && !validate_email($customeremail)) {
            throw new \coding_exception('A persisted Commerce customer email is invalid.');
        }
        if ($snapshotversion <= 0) {
            throw new \coding_exception('A Commerce persistence snapshot version must be positive.');
        }
    }

    public function get_purchase_uuid(): string { return $this->purchaseuuid; }
    public function get_reference(): string { return $this->reference; }
    public function get_type(): string { return $this->type; }
    public function get_status(): string { return $this->status; }
    public function get_currency(): string { return $this->currency; }
    public function get_total_minor(): int { return $this->totalminor; }

    public function to_record(): \stdClass {
        return (object)[
            'purchaseuuid' => $this->purchaseuuid,
            'reference' => $this->reference,
            'type' => $this->type,
            'legacyfamily' => $this->legacyfamily,
            'legacyid' => $this->legacyid,
            'userid' => $this->userid,
            'customeremail' => $this->customeremail,
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotalminor' => $this->subtotalminor,
            'discountminor' => $this->discountminor,
            'totalminor' => $this->totalminor,
            'customerjson' => $this->customerjson,
            'snapshotjson' => $this->snapshotjson,
            'metadatajson' => $this->metadatajson,
            'snapshotversion' => $this->snapshotversion,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
    }

    private static function require_length(string $value, int $length, string $label): void {
        if (strlen($value) !== $length) {
            throw new \coding_exception('A persisted Commerce ' . $label . ' must contain exactly ' . $length . ' characters.');
        }
    }

    private static function require_max_length(string $value, int $length, string $label): void {
        if (trim($value) === '' || \core_text::strlen($value) > $length) {
            throw new \coding_exception('Invalid persisted Commerce ' . $label . '.');
        }
    }
}
