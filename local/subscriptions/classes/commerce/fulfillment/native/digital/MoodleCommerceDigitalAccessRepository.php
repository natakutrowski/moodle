<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/** Moodle database implementation of Native digital access persistence. */
final class MoodleCommerceDigitalAccessRepository implements CommerceDigitalAccessRepository {
    private const TABLE = 'local_subs_commerce_dig_access';

    public function find_by_grant_reference(string $grantreference): ?\stdClass {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['grantreference' => trim($grantreference)]);
        return $record === false ? null : $record;
    }

    public function grant(
        CommerceEntitlementGrant $grant,
        string $token,
        ?int $maxdownloads,
        int $now
    ): array {
        global $DB;

        $existing = $this->find_by_grant_reference($grant->get_reference());
        if ($existing === null) {
            $record = (object) [
                'grantreference' => $grant->get_reference(),
                'idempotencykey' => $grant->get_idempotency_key(),
                'purchasereference' => $grant->get_purchase_reference(),
                'productsku' => $grant->get_product_sku(),
                'resourcekey' => $grant->get_resource_key(),
                'beneficiaryuserid' => $grant->get_beneficiary_user_id(),
                'beneficiaryemail' => $grant->get_beneficiary_email(),
                'downloadtoken' => $token,
                'maxdownloads' => $maxdownloads,
                'downloadcount' => 0,
                'validfrom' => $grant->get_valid_from(),
                'validuntil' => $grant->get_valid_until(),
                'status' => 'active',
                'lastdownloadat' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $record->id = $DB->insert_record(self::TABLE, $record);
            return ['action' => 'created', 'accessid' => (int) $record->id, 'token' => $token];
        }

        $changed = false;
        $newvaliduntil = $this->merge_valid_until(
            $existing->validuntil === null ? null : (int) $existing->validuntil,
            $grant->get_valid_until()
        );
        $newmaxdownloads = $this->merge_max_downloads(
            $existing->maxdownloads === null ? null : (int) $existing->maxdownloads,
            $maxdownloads
        );

        foreach ([
            'beneficiaryuserid' => $grant->get_beneficiary_user_id(),
            'beneficiaryemail' => $grant->get_beneficiary_email(),
            'validfrom' => min((int) $existing->validfrom, $grant->get_valid_from()),
            'validuntil' => $newvaliduntil,
            'maxdownloads' => $newmaxdownloads,
            'status' => 'active',
        ] as $field => $value) {
            if ($existing->{$field} != $value) {
                $existing->{$field} = $value;
                $changed = true;
            }
        }

        if ($changed) {
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
        }

        return [
            'action' => $changed ? 'updated' : 'unchanged',
            'accessid' => (int) $existing->id,
            'token' => (string) $existing->downloadtoken,
        ];
    }

    private function merge_valid_until(?int $existing, ?int $incoming): ?int {
        if ($existing === null || $incoming === null) {
            return null;
        }
        return max($existing, $incoming);
    }

    private function merge_max_downloads(?int $existing, ?int $incoming): ?int {
        if ($existing === null || $incoming === null) {
            return null;
        }
        return max($existing, $incoming);
    }
}
