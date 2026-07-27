<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/**
 * Builds transient Shadow grants from a persisted Native purchase projection.
 *
 * The adapter is deliberately read-only: it never writes to the entitlement
 * ledger. This allows Legacy-authoritative checkouts to exercise the Native
 * fulfillment kernel in dry-run mode before Native grants are persisted.
 */
final class CommerceProjectedPurchaseShadowGrantSource implements CommerceShadowGrantSource {
    public function __construct(private readonly ?moodle_database $database = null) {
    }

    public function find_for_purchase(string $purchasereference): array {
        $db = $this->database();
        $purchasereference = trim($purchasereference);
        if ($purchasereference === '') {
            throw new \coding_exception('A purchase reference is required to plan Shadow grants.');
        }

        $purchase = $db->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $purchasereference],
            '*',
            MUST_EXIST
        );
        $items = $db->get_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => (int)$purchase->id],
            'position ASC, id ASC'
        );

        $grants = [];
        $position = 0;
        foreach ($items as $item) {
            $fulfillment = $this->decode_json((string)$item->fulfillmentjson);
            $itemtype = strtolower(trim((string)$item->itemtype));

            if ($itemtype === 'subscription') {
                foreach ($this->subscription_definitions($fulfillment) as $definition) {
                    $position++;
                    $grants[] = $this->grant($purchase, $item, $definition, $position);
                }
                continue;
            }

            if ($itemtype === 'digital') {
                $position++;
                $productid = (int)($fulfillment['product_id'] ?? $fulfillment['productid'] ?? $fulfillment['legacy_id'] ?? 0);
                $slug = trim((string)($fulfillment['slug'] ?? ''));
                $resourcekey = $slug !== '' ? 'digital-product:' . $slug : 'digital-product:' . $productid;
                $grants[] = $this->grant($purchase, $item, [
                    'type' => 'digital_download',
                    'resourcekey' => $resourcekey,
                    'productsku' => strtoupper(str_replace(':', '.', $resourcekey)),
                    'configuration' => [
                        'productid' => $productid > 0 ? $productid : null,
                        'filename' => $fulfillment['filename'] ?? null,
                    ],
                ], $position);
            }
        }

        return $grants;
    }

    private function subscription_definitions(array $fulfillment): array {
        $db = $this->database();
        $planid = (int)($fulfillment['plan_id'] ?? $fulfillment['planid'] ?? $fulfillment['legacy_id'] ?? 0);
        if ($planid <= 0) {
            throw new \coding_exception('A projected subscription purchase has no plan identifier.');
        }

        $definitions = [];
        $entitlements = $db->get_records(
            'subscription_plan_entitlement',
            ['planid' => $planid],
            'priority ASC, id ASC'
        );
        foreach ($entitlements as $entitlement) {
            $accesslevel = strtolower(trim((string)$entitlement->accesslevel)) ?: 'full';
            $definitions[] = [
                'type' => 'course_access',
                'resourcekey' => 'course:' . (int)$entitlement->courseid . ':' . $accesslevel,
                'productsku' => 'SUB.PLAN.' . $planid,
                'configuration' => [
                    'courseid' => (int)$entitlement->courseid,
                    'accesslevel' => $accesslevel,
                    'roleshortname' => (string)$entitlement->roleshortname,
                    'groupname' => $entitlement->groupname ?? null,
                ],
            ];
        }
        if ($definitions !== []) {
            return $definitions;
        }

        $scopeid = (int)($fulfillment['access_scope_id'] ?? $fulfillment['accessscopeid'] ?? 0);
        if ($scopeid <= 0) {
            $scopeid = (int)$db->get_field('subscription_plan', 'accessscopeid', ['id' => $planid], IGNORE_MISSING);
        }
        if ($scopeid <= 0) {
            return [];
        }

        $courseids = (string)$db->get_field('subscription_access_scope', 'course_ids', ['id' => $scopeid], MUST_EXIST);
        foreach ($this->positive_integer_list($courseids) as $courseid) {
            $definitions[] = [
                'type' => 'course_access',
                'resourcekey' => 'course:' . $courseid . ':full',
                'productsku' => 'SUB.PLAN.' . $planid,
                'configuration' => [
                    'courseid' => $courseid,
                    'accesslevel' => 'full',
                    'roleshortname' => 'student',
                ],
            ];
        }
        return $definitions;
    }

    private function grant(\stdClass $purchase, \stdClass $item, array $definition, int $position): CommerceEntitlementGrant {
        $reference = 'shadow-ent-' . substr(hash('sha256', (string)$purchase->reference . '|' . (string)$item->itemreference . '|' . $position), 0, 32);
        return new CommerceEntitlementGrant(
            $reference,
            (string)$purchase->reference,
            (string)$item->itemreference,
            (string)$definition['productsku'],
            (string)$definition['type'],
            (string)$definition['resourcekey'],
            max(1, (int)$item->quantity),
            !empty($purchase->userid) ? (int)$purchase->userid : null,
            (string)$purchase->customeremail,
            max(1, (int)($purchase->timecreated ?? time())),
            null,
            array_filter((array)($definition['configuration'] ?? []), static fn(mixed $value): bool => $value !== null),
            ['shadowtransient' => true]
        );
    }

    private function decode_json(string $json): array {
        if (trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \coding_exception('A projected Commerce fulfillment snapshot must decode to an array.');
        }
        return $decoded;
    }

    private function positive_integer_list(string $value): array {
        $decoded = json_decode($value, true);
        $values = is_array($decoded) ? $decoded : preg_split('/[^0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($values ?: [] as $candidate) {
            $candidate = (int)$candidate;
            if ($candidate > 0) {
                $result[$candidate] = $candidate;
            }
        }
        return array_values($result);
    }

    private function database(): moodle_database {
        global $DB;
        return $this->database ?? $DB;
    }
}
