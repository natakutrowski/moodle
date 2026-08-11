<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/** Resolves persisted entitlements and safe course-access fallbacks from the effective Access Scope. */
final class CommerceEffectiveEntitlementResolver {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductEntitlementRepository $entitlements
    ) {
    }

    /** @return CommerceProductEntitlementDefinition[] */
    public function resolve_by_product_sku(string $sku): array {
        $product = $this->products->find_by_sku($sku);
        return $product === null ? [] : $this->resolve($product);
    }

    /** @return CommerceProductEntitlementDefinition[] */
    public function resolve(CommerceProduct $product): array {
        $persisted = $this->entitlements->find_by_product_sku($product->get_sku());
        if ($persisted !== []) {
            return $persisted;
        }

        if ($product->get_type() !== CommerceProductType::COURSE_ACCESS) {
            return [];
        }

        $metadata = $product->get_metadata();
        $access = is_array($metadata['access'] ?? null) ? $metadata['access'] : [];
        $sourceplanid = (int)($access['sourceplanid'] ?? 0);
        $scopeid = (int)($access['scopeid'] ?? 0);

        if ($scopeid <= 0 && $sourceplanid > 0) {
            $scopeid = (int)$this->db->get_field(
                'subscription_plan',
                'accessscopeid',
                ['id' => $sourceplanid],
                IGNORE_MISSING
            );
        }

        if ($scopeid <= 0) {
            return [];
        }

        $scope = $this->db->get_record(
            'subscription_access_scope',
            ['id' => $scopeid],
            'id,course_ids',
            IGNORE_MISSING
        );
        if (!$scope) {
            return [];
        }

        $courseids = $this->parse_course_ids((string)$scope->course_ids);
        if ($courseids === []) {
            return [];
        }

        $planentitlements = [];
        if ($sourceplanid > 0) {
            foreach ($this->db->get_records(
                'subscription_plan_entitlement',
                ['planid' => $sourceplanid],
                'priority ASC, id ASC'
            ) as $record) {
                $planentitlements[(int)$record->courseid] = $record;
            }
        }

        $definitions = [];
        foreach ($courseids as $index => $courseid) {
            $legacy = $planentitlements[$courseid] ?? null;
            $accesslevel = strtolower(trim((string)($legacy->accesslevel ?? 'full')));
            if ($accesslevel === '') {
                $accesslevel = 'full';
            }
            $roleshortname = trim((string)($legacy->roleshortname ?? 'student'));
            if ($roleshortname === '') {
                $roleshortname = $this->role_for_access_level($accesslevel);
            }

            $definitions[] = new CommerceProductEntitlementDefinition(
                $product->get_sku(),
                'course_access',
                'course:' . $courseid . ':' . $accesslevel,
                null,
                1,
                [
                    'courseid' => $courseid,
                    'accesslevel' => $accesslevel,
                    'roleshortname' => $roleshortname,
                    'groupname' => $legacy->groupname ?? null,
                    'legacysource' => 'native_access_scope',
                    'legacyscopeid' => $scopeid,
                    'sourceplanid' => $sourceplanid > 0 ? $sourceplanid : null,
                ],
                $legacy ? (int)$legacy->priority : 100 + $index
            );
        }

        return $definitions;
    }

    /** @return int[] */
    private function parse_course_ids(string $value): array {
        $decoded = json_decode($value, true);
        $values = is_array($decoded)
            ? $decoded
            : preg_split('/[^0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY);

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $values ?: []),
            static fn(int $id): bool => $id > 0
        )));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private function role_for_access_level(string $accesslevel): string {
        return match ($accesslevel) {
            'trial' => 'trialstudent',
            'grammar' => 'grammarstudent',
            default => 'student',
        };
    }
}
