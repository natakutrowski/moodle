<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * Maps immutable entitlement grants to the Native Commerce grant ledger.
 */
final class CommerceEntitlementGrantRecordMapper {
    public function to_record(
        CommerceEntitlementGrant $grant,
        string $status = 'planned',
        ?int $now = null
    ): \stdClass {
        $status = strtolower(trim($status));
        $now ??= time();

        if (!preg_match('/^[a-z][a-z0-9_]{1,19}$/', $status)) {
            throw new \coding_exception('Invalid Native Commerce entitlement grant status.');
        }

        if ($now <= 0) {
            throw new \coding_exception('A Native Commerce entitlement record timestamp must be positive.');
        }

        return (object)[
            'grantreference' => $grant->get_reference(),
            'idempotencykey' => $grant->get_idempotency_key(),
            'purchasereference' => $grant->get_purchase_reference(),
            'itemreference' => $grant->get_item_reference(),
            'productsku' => $grant->get_product_sku(),
            'type' => $grant->get_type(),
            'resourcekey' => $grant->get_resource_key(),
            'quantity' => $grant->get_quantity(),
            'beneficiaryuserid' => $grant->get_beneficiary_user_id(),
            'beneficiaryemail' => $grant->get_beneficiary_email(),
            'validfrom' => $grant->get_valid_from(),
            'validuntil' => $grant->get_valid_until(),
            'status' => $status,
            'configurationjson' => $this->encode_json($grant->get_configuration()),
            'metadatajson' => $this->encode_json($grant->get_metadata()),
            'timecreated' => $now,
            'timemodified' => $now,
        ];
    }

    public function from_record(\stdClass $record): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            (string)$record->grantreference,
            (string)$record->purchasereference,
            (string)$record->itemreference,
            (string)$record->productsku,
            (string)$record->type,
            (string)$record->resourcekey,
            (int)$record->quantity,
            $record->beneficiaryuserid === null ? null : (int)$record->beneficiaryuserid,
            (string)$record->beneficiaryemail,
            (int)$record->validfrom,
            $record->validuntil === null ? null : (int)$record->validuntil,
            $this->decode_json((string)$record->configurationjson),
            $this->decode_json((string)$record->metadatajson)
        );
    }

    public function payload_hash(CommerceEntitlementGrant $grant): string {
        $payload = $grant->to_array();
        unset($payload['reference']);

        return hash('sha256', $this->encode_json($payload));
    }

    public function record_payload_hash(\stdClass $record): string {
        $payload = [
            'purchasereference' => (string)$record->purchasereference,
            'itemreference' => (string)$record->itemreference,
            'productsku' => (string)$record->productsku,
            'type' => (string)$record->type,
            'resourcekey' => (string)$record->resourcekey,
            'quantity' => (int)$record->quantity,
            'beneficiaryuserid' => $record->beneficiaryuserid === null
                ? null
                : (int)$record->beneficiaryuserid,
            'beneficiaryemail' => (string)$record->beneficiaryemail,
            'validfrom' => (int)$record->validfrom,
            'validuntil' => $record->validuntil === null
                ? null
                : (int)$record->validuntil,
            'configuration' => $this->decode_json((string)$record->configurationjson),
            'metadata' => $this->decode_json((string)$record->metadatajson),
        ];

        return hash('sha256', $this->encode_json($payload));
    }

    private function encode_json(array $value): string {
        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (\JsonException $exception) {
            throw new \coding_exception(
                'Native Commerce entitlement JSON encoding failed: ' . $exception->getMessage()
            );
        }
    }

    private function decode_json(string $value): array {
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \coding_exception(
                'Native Commerce entitlement JSON decoding failed: ' . $exception->getMessage()
            );
        }

        if (!is_array($decoded)) {
            throw new \coding_exception('Native Commerce entitlement JSON must decode to an array.');
        }

        return $decoded;
    }
}
