<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\audience;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

/**
 * M10 advanced Personal Offer audience rules.
 *
 * A filter definition is an OR of groups. Rules inside a group are ANDed.
 * This deliberately gives the CRM a useful boolean model without exposing
 * arbitrary nested expressions to operators.
 */
final class CommercePersonalOfferAudienceRuleEvaluator {
    private const SUCCESS_NATIVE = ['paid', 'completed', 'captured', 'succeeded', 'success'];
    private const SUCCESS_LEGACY_DIGITAL = ['paid', 'completed', 'succeeded', 'success'];

    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * @param array<string,mixed> $candidate
     * @param array<int,array<string,mixed>> $groups
     * @return array{matched:bool,evidence:array<int,string>}
     */
    public function evaluate(array $candidate, array $groups): array {
        $groups = $this->normalise_groups($groups);
        if ($groups === []) {
            return ['matched' => true, 'evidence' => []];
        }

        $evidence = [];
        foreach ($groups as $groupindex => $group) {
            $groupmatched = true;
            foreach ($group['rules'] as $ruleindex => $rule) {
                $owns = $this->owns($candidate, $rule['sourcetype'], $rule['sourceid']);
                $rulematched = $rule['operator'] === 'owns' ? $owns : !$owns;
                $evidence[] = sprintf(
                    'm10_rule:g%d:r%d:%s:%s:%d:%s',
                    $groupindex + 1,
                    $ruleindex + 1,
                    $rule['operator'],
                    $rule['sourcetype'],
                    $rule['sourceid'],
                    $rulematched ? 'matched' : 'not_matched'
                );
                if (!$rulematched) {
                    $groupmatched = false;
                }
            }
            if ($groupmatched) {
                return ['matched' => true, 'evidence' => $evidence];
            }
        }

        return ['matched' => false, 'evidence' => $evidence];
    }

    /**
     * @param array<int,array<string,mixed>> $groups
     * @return array<int,array{rules:array<int,array{operator:string,sourcetype:string,sourceid:int}>}>
     */
    public function normalise_groups(array $groups): array {
        $out = [];
        $allowedtypes = [
            CommercePersonalOfferNativeProductAudienceProvider::TYPE,
            CommercePersonalOfferLegacyDigitalAudienceProvider::TYPE,
            CommercePersonalOfferLegacyPlanAudienceProvider::TYPE,
        ];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $rules = [];
            foreach (($group['rules'] ?? []) as $rule) {
                if (!is_array($rule)) {
                    continue;
                }
                $operator = strtolower(trim((string)($rule['operator'] ?? '')));
                $sourcetype = strtolower(trim((string)($rule['sourcetype'] ?? '')));
                $sourceid = (int)($rule['sourceid'] ?? 0);
                if (!in_array($operator, ['owns', 'not_owns'], true)
                    || !in_array($sourcetype, $allowedtypes, true)
                    || $sourceid <= 0) {
                    continue;
                }
                $rules[] = [
                    'operator' => $operator,
                    'sourcetype' => $sourcetype,
                    'sourceid' => $sourceid,
                ];
            }
            if ($rules !== []) {
                $out[] = ['rules' => $rules];
            }
        }
        return $out;
    }

    /** @param array<string,mixed> $candidate */
    private function owns(array $candidate, string $sourcetype, int $sourceid): bool {
        return match ($sourcetype) {
            CommercePersonalOfferNativeProductAudienceProvider::TYPE =>
                $this->owns_native($candidate, $sourceid),
            CommercePersonalOfferLegacyDigitalAudienceProvider::TYPE =>
                $this->owns_legacy_digital($candidate, $sourceid),
            CommercePersonalOfferLegacyPlanAudienceProvider::TYPE =>
                $this->owns_legacy_plan($candidate, $sourceid),
            default => false,
        };
    }

    /** @param array<string,mixed> $candidate */
    private function owns_native(array $candidate, int $productid): bool {
        $product = $this->db->get_record(
            'local_subs_commerce_product',
            ['id' => $productid],
            'id,sku',
            IGNORE_MISSING
        );
        if (!$product) {
            return false;
        }
        $sku = strtoupper((string)$product->sku);

        if (!empty($candidate['userid'])
            && (new CommerceStorefrontOwnershipResolver($this->db))->owns((int)$candidate['userid'], $sku)) {
            return true;
        }

        $email = strtolower(trim((string)($candidate['email'] ?? '')));
        $identity = [];
        $params = ['sku' => $sku];
        if (!empty($candidate['userid'])) {
            $identity[] = 'p.userid = :userid';
            $params['userid'] = (int)$candidate['userid'];
        }
        if ($email !== '') {
            $identity[] = $this->db->sql_equal('p.customeremail', ':email', false, false);
            $params['email'] = $email;
        }
        if ($identity !== []) {
            $statuses = [];
            foreach (self::SUCCESS_NATIVE as $i => $status) {
                $params['s' . $i] = $status;
                $statuses[] = ':s' . $i;
            }
            $sql = "SELECT 1
                      FROM {local_subscriptions_commerce_purchase} p
                      JOIN {local_subscriptions_commerce_purchase_item} i ON i.purchaseid = p.id
                     WHERE (" . implode(' OR ', $identity) . ")
                       AND UPPER(i.itemreference) = :sku
                       AND EXISTS (
                           SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                            WHERE pay.purchaseid = p.id
                              AND pay.status IN (" . implode(',', $statuses) . ")
                       )";
            if ($this->db->record_exists_sql($sql, $params)) {
                return true;
            }
        }

        $grantidentity = [];
        $grantparams = [
            'sku' => $sku,
            'now' => time(),
            'now2' => time(),
            'rootpattern' => '%"rootsku":"' . $this->db->sql_like_escape($sku) . '"%',
            'gactive' => 'active',
            'ggranted' => 'granted',
            'gcompleted' => 'completed',
        ];
        if (!empty($candidate['userid'])) {
            $grantidentity[] = 'beneficiaryuserid = :guserid';
            $grantparams['guserid'] = (int)$candidate['userid'];
        }
        if ($email !== '') {
            $grantidentity[] = $this->db->sql_equal('beneficiaryemail', ':gemail', false, false);
            $grantparams['gemail'] = $email;
        }
        if ($grantidentity === []) {
            return false;
        }

        return $this->db->record_exists_sql(
            "SELECT 1 FROM {local_subs_commerce_grant}
              WHERE status IN (:gactive, :ggranted, :gcompleted)
                AND validfrom <= :now
                AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :now2)
                AND (" . implode(' OR ', $grantidentity) . ")
                AND (UPPER(productsku) = :sku OR metadatajson LIKE :rootpattern)",
            $grantparams
        );
    }

    /** @param array<string,mixed> $candidate */
    private function owns_legacy_digital(array $candidate, int $productid): bool {
        $identity = [];
        $params = ['productid' => $productid];
        if (!empty($candidate['userid'])) {
            $identity[] = 'userid = :userid';
            $params['userid'] = (int)$candidate['userid'];
        }
        $email = strtolower(trim((string)($candidate['email'] ?? '')));
        if ($email !== '') {
            $identity[] = $this->db->sql_equal('email', ':email', false, false);
            $params['email'] = $email;
        }
        if ($identity === []) {
            return false;
        }
        $statuses = [];
        foreach (self::SUCCESS_LEGACY_DIGITAL as $i => $status) {
            $params['s' . $i] = $status;
            $statuses[] = ':s' . $i;
        }
        return $this->db->record_exists_sql(
            'SELECT 1 FROM {subscription_digital_payment_request}
              WHERE productid = :productid
                AND (' . implode(' OR ', $identity) . ')
                AND status IN (' . implode(',', $statuses) . ')',
            $params
        );
    }

    /** @param array<string,mixed> $candidate */
    private function owns_legacy_plan(array $candidate, int $planid): bool {
        if (empty($candidate['userid'])) {
            return false;
        }
        return $this->db->record_exists_select(
            'user_subscription',
            'userid = :userid AND planid = :planid AND status IN (:active, :completed)',
            [
                'userid' => (int)$candidate['userid'],
                'planid' => $planid,
                'active' => 'active',
                'completed' => 'completed',
            ]
        );
    }
}
