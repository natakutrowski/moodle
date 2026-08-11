<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\storefront;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;

/**
 * Resolves a course/access level to active Native Storefront products.
 *
 * Native product entitlements are authoritative. Legacy plan entitlements are
 * retained as a compatibility fallback while the catalogue migration remains
 * transitional.
 */
final class CommerceCourseStorefrontTargetResolver {
    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    /** @param int[] $courseids */
    public function resolve(array $courseids, string $accesslevel = 'full'): ?\stdClass {
        return $this->resolve_all($courseids, $accesslevel)[0] ?? null;
    }

    /** @param int[] $courseids @return \stdClass[] */
    public function resolve_all(array $courseids, string $accesslevel = 'full'): array {
        $courseids = $this->related_course_ids($courseids);
        if ($courseids === []) {
            return [];
        }

        $products = $this->resolve_native($courseids, $accesslevel);
        if ($products !== []) {
            return $products;
        }

        return $this->resolve_legacy($courseids, $accesslevel);
    }

    public function resolve_one(int $courseid, string $accesslevel = 'full'): ?\stdClass {
        return $this->resolve([$courseid], $accesslevel);
    }

    /** @param int[] $courseids */
    public function count_offers(array $courseids, string $accesslevel = 'subscriber'): int {
        return count($this->resolve_all($courseids, $accesslevel));
    }

    /**
     * @param int[] $courseids
     * @return array<string,mixed>
     */
    public function diagnose(array $courseids, string $accesslevel): array {
        $related = $this->related_course_ids($courseids);

        return [
            'requestedcourseids' => array_values(array_map('intval', $courseids)),
            'relatedcourseids' => $related,
            'accesslevel' => strtolower(trim($accesslevel)),
            'acceptedlevels' => $this->access_levels($accesslevel),
            'nativecandidates' => $this->native_candidates($related, $accesslevel),
            'legacycandidates' => $this->legacy_candidates($related, $accesslevel),
            'resolvedskus' => array_values(array_map(
                static fn(\stdClass $product): string => (string)$product->sku,
                $this->resolve_all($related, $accesslevel)
            )),
        ];
    }

    /**
     * One unique offer opens its product page; zero or many offers open the shop.
     *
     * @param int[] $courseids
     */
    public function url(
        array $courseids,
        string $accesslevel = 'full',
        bool $trialconversion = false,
        ?string $currency = null
    ): \moodle_url {
        $products = $this->resolve_all($courseids, $accesslevel);
        if (count($products) !== 1) {
            return new \moodle_url('/boutique');
        }

        $params = [
            'sku' => strtoupper(trim((string)$products[0]->sku)),
        ];
        $currency = strtoupper(trim((string)$currency));
        if (in_array($currency, ['EUR', 'RUB'], true)) {
            $params['currency'] = $currency;
        }

        return new \moodle_url(
            '/local/subscriptions/storefront_product.php',
            $params
        );
    }

    /** @param int[] $courseids @return \stdClass[] */
    private function resolve_native(array $courseids, string $accesslevel): array {
        $products = [];

        foreach ($this->native_candidates($courseids, $accesslevel) as $candidate) {
            if (!$this->is_available_product($candidate)) {
                continue;
            }

            $sku = strtoupper(trim((string)$candidate->sku));
            $products[$sku] = $candidate;
        }

        return array_values($products);
    }

    /** @param int[] $courseids @return \stdClass[] */
    private function native_candidates(array $courseids, string $accesslevel): array {
        if ($courseids === []) {
            return [];
        }

        [$productsql, $productparams] = $this->db->get_in_or_equal(
            $courseids,
            SQL_PARAMS_NAMED,
            'nativecourse'
        );

        $records = $this->db->get_records_sql(
            "SELECT e.id AS entitlementid,
                    e.resourcekey,
                    e.configurationjson,
                    p.*
               FROM {local_subs_commerce_prod_ent} e
               JOIN {local_subs_commerce_product} p ON p.id = e.productid
              WHERE p.status = :status
                AND p.type IN ('course_access', 'subscription')
           ORDER BY p.id ASC, e.sortorder ASC, e.id ASC",
            ['status' => 'active']
        );

        $levels = $this->access_levels($accesslevel);
        $matches = [];

        foreach ($records as $record) {
            $definition = $this->native_entitlement_definition($record);
            if (
                $definition['courseid'] <= 0 ||
                !in_array($definition['courseid'], $courseids, true) ||
                !in_array($definition['accesslevel'], $levels, true)
            ) {
                continue;
            }

            $record->resolvedcourseid = $definition['courseid'];
            $record->resolvedaccesslevel = $definition['accesslevel'];
            $matches[] = $record;
        }

        return $matches;
    }

    /** @param int[] $courseids @return \stdClass[] */
    private function resolve_legacy(array $courseids, string $accesslevel): array {
        $products = [];
        $resolver = new CommerceLegacyStorefrontProductResolver($this->db);

        foreach ($this->legacy_candidates($courseids, $accesslevel) as $candidate) {
            $product = $resolver->resolve_subscription_plan((int)$candidate->planid);
            if (!$this->is_available_product($product)) {
                continue;
            }

            $sku = strtoupper(trim((string)$product->sku));
            $products[$sku] = $product;
        }

        return array_values($products);
    }

    /** @param int[] $courseids @return \stdClass[] */
    private function legacy_candidates(array $courseids, string $accesslevel): array {
        if ($courseids === []) {
            return [];
        }

        [$coursesql, $params] = $this->db->get_in_or_equal(
            $courseids,
            SQL_PARAMS_NAMED,
            'legacycourse'
        );
        [$levelsql, $levelparams] = $this->db->get_in_or_equal(
            $this->access_levels($accesslevel),
            SQL_PARAMS_NAMED,
            'legacylevel'
        );
        $params += $levelparams;

        return array_values($this->db->get_records_sql(
            "SELECT e.id,
                    e.planid,
                    e.courseid,
                    e.accesslevel,
                    e.roleshortname,
                    e.priority,
                    p.name AS planname,
                    p.is_active
               FROM {subscription_plan_entitlement} e
               JOIN {subscription_plan} p ON p.id = e.planid
              WHERE e.courseid {$coursesql}
                AND e.accesslevel {$levelsql}
                AND p.is_active = 1
           ORDER BY e.priority DESC, p.id ASC, e.id ASC",
            $params
        ));
    }

    /** @return array{courseid:int,accesslevel:string} */
    private function native_entitlement_definition(\stdClass $record): array {
        $courseid = 0;
        $accesslevel = '';

        if (preg_match(
            '/^course:(\d+)(?::([a-z0-9_-]+))?$/i',
            trim((string)$record->resourcekey),
            $matches
        )) {
            $courseid = (int)$matches[1];
            $accesslevel = strtolower(trim((string)($matches[2] ?? '')));
        }

        $configuration = json_decode(
            (string)($record->configurationjson ?? ''),
            true
        );
        if (is_array($configuration)) {
            $courseid = $courseid > 0
                ? $courseid
                : (int)($configuration['courseid'] ?? $configuration['course_id'] ?? 0);

            $accesslevel = $accesslevel !== ''
                ? $accesslevel
                : strtolower(trim((string)(
                    $configuration['accesslevel']
                    ?? $configuration['access_level']
                    ?? ''
                )));
        }

        // Imported Full products sometimes used a bare "course:<id>" key.
        if ($courseid > 0 && $accesslevel === '') {
            $accesslevel = 'full';
        }

        return [
            'courseid' => $courseid,
            'accesslevel' => $accesslevel,
        ];
    }

    /** @param int[] $courseids @return int[] */
    private function related_course_ids(array $courseids): array {
        $result = [];
        foreach ($courseids as $courseid) {
            $courseid = (int)$courseid;
            if ($courseid > 0) {
                $result[$courseid] = $courseid;
            }
        }
        if ($result === []) {
            return [];
        }

        $fields = $this->db->get_records_select(
            'customfield_field',
            "shortname IN ('realcourseid', 'trialcourseid')",
            [],
            '',
            'id'
        );
        $fieldids = array_map(
            static fn(\stdClass $record): int => (int)$record->id,
            array_values($fields)
        );
        if ($fieldids === []) {
            return array_values($result);
        }

        [$fieldsql, $fieldparams] = $this->db->get_in_or_equal(
            $fieldids,
            SQL_PARAMS_NAMED,
            'field'
        );
        [$instancesql, $instanceparams] = $this->db->get_in_or_equal(
            array_values($result),
            SQL_PARAMS_NAMED,
            'instance'
        );
        [$valuesql, $valueparams] = $this->db->get_in_or_equal(
            array_values($result),
            SQL_PARAMS_NAMED,
            'value'
        );

        $records = $this->db->get_records_sql(
            "SELECT d.id, d.instanceid, d.value
               FROM {customfield_data} d
              WHERE d.fieldid {$fieldsql}
                AND (
                    d.instanceid {$instancesql}
                    OR CAST(d.value AS UNSIGNED) {$valuesql}
                )",
            $fieldparams + $instanceparams + $valueparams
        );

        foreach ($records as $record) {
            foreach ([(int)$record->instanceid, (int)$record->value] as $courseid) {
                if ($courseid > 0) {
                    $result[$courseid] = $courseid;
                }
            }
        }

        return array_values($result);
    }

    /** @return string[] */
    private function access_levels(string $accesslevel): array {
        return match (strtolower(trim($accesslevel))) {
            'grammar' => ['grammar'],
            'full' => ['full'],
            default => ['full', 'grammar', 'subscriber'],
        };
    }

    private function is_available_product(?\stdClass $product): bool {
        if (
            $product === null ||
            strtolower(trim((string)$product->status)) !== 'active' ||
            trim((string)$product->sku) === ''
        ) {
            return false;
        }

        $now = time();
        return !(
            ($product->availablefrom !== null && (int)$product->availablefrom > $now)
            || ($product->availableuntil !== null && (int)$product->availableuntil < $now)
        );
    }
}
