<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\repair;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceLegacyProductMapRepository;

/**
 * Repairs the contract between one canonical Native course product and a
 * retained Legacy subscription plan.
 *
 * The Legacy plan is never activated or otherwise modified.
 */
final class CommerceNativeCourseProductRepairService {
    private const PRODUCT_TABLE = 'local_subs_commerce_product';
    private const ENTITLEMENT_TABLE = 'local_subs_commerce_prod_ent';
    private const MAP_TABLE = 'local_subs_commerce_prod_map';
    private const LEGACY_PLAN_TABLE = 'subscription_plan';
    private const LEGACY_ENTITLEMENT_TABLE = 'subscription_plan_entitlement';

    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * @return array<string,mixed>
     */
    public function inspect(
        string $sku,
        int $sourceplanid,
        int $courseid,
        string $accesslevel,
        string $roleshortname
    ): array {
        $sku = strtoupper(trim($sku));
        $accesslevel = strtolower(trim($accesslevel));
        $roleshortname = trim($roleshortname);

        if (
            $sku === '' ||
            $sourceplanid <= 0 ||
            $courseid <= 0 ||
            $accesslevel === '' ||
            $roleshortname === ''
        ) {
            throw new \invalid_parameter_exception(
                'SKU, source plan, course, access level and role are required.'
            );
        }

        $product = $this->db->get_record(
            self::PRODUCT_TABLE,
            ['sku' => $sku],
            '*',
            MUST_EXIST
        );
        $plan = $this->db->get_record(
            self::LEGACY_PLAN_TABLE,
            ['id' => $sourceplanid],
            '*',
            MUST_EXIST
        );
        $course = $this->db->get_record(
            'course',
            ['id' => $courseid],
            'id,fullname,shortname',
            MUST_EXIST
        );

        if (
            strtolower(trim((string)$product->status)) !== 'active' ||
            !in_array(
                strtolower(trim((string)$product->type)),
                ['course_access', 'subscription'],
                true
            )
        ) {
            throw new \RuntimeException(
                'The canonical Native product must be an active course product.'
            );
        }

        $legacyentitlement = $this->db->get_record(
            self::LEGACY_ENTITLEMENT_TABLE,
            [
                'planid' => $sourceplanid,
                'courseid' => $courseid,
                'accesslevel' => $accesslevel,
            ],
            '*',
            MUST_EXIST
        );

        if (trim((string)$legacyentitlement->roleshortname) !== $roleshortname) {
            throw new \RuntimeException(
                'The Legacy entitlement role does not match the requested role.'
            );
        }

        $mapping = $this->db->get_record(
            self::MAP_TABLE,
            [
                'legacytable' => self::LEGACY_PLAN_TABLE,
                'legacyid' => $sourceplanid,
            ],
            '*',
            IGNORE_MISSING
        );

        $productmapping = $this->db->get_record(
            self::MAP_TABLE,
            [
                'productid' => (int)$product->id,
                'legacyfamily' => 'subscription',
            ],
            '*',
            IGNORE_MISSING
        );

        $resourcekey = sprintf(
            'course:%d:%s',
            $courseid,
            $accesslevel
        );

        $entitlement = $this->db->get_record(
            self::ENTITLEMENT_TABLE,
            [
                'productid' => (int)$product->id,
                'type' => 'course_access',
                'resourcekey' => $resourcekey,
            ],
            '*',
            IGNORE_MISSING
        );

        $conflictingentitlements = array_values($this->db->get_records_select(
            self::ENTITLEMENT_TABLE,
            'productid = :productid
             AND type = :type
             AND resourcekey LIKE :resourcepattern
             AND resourcekey <> :resourcekey',
            [
                'productid' => (int)$product->id,
                'type' => 'course_access',
                'resourcepattern' => 'course:' . $courseid . ':%',
                'resourcekey' => $resourcekey,
            ],
            'id ASC'
        ));

        $errors = [];
        $warnings = [];
        $actions = [];

        if ($mapping && (int)$mapping->productid !== (int)$product->id) {
            $errors[] =
                'Legacy plan #' . $sourceplanid
                . ' is already mapped to product #' . (int)$mapping->productid . '.';
        }

        if (
            $productmapping &&
            (int)$productmapping->legacyid !== $sourceplanid
        ) {
            $errors[] =
                'Product ' . $sku
                . ' is already mapped to Legacy plan #'
                . (int)$productmapping->legacyid . '.';
        }

        if ($entitlement) {
            $configuration = json_decode(
                (string)$entitlement->configurationjson,
                true
            );
            $configuration = is_array($configuration)
                ? $configuration
                : [];

            $matches = (int)($configuration['courseid'] ?? 0) === $courseid
                && strtolower(trim((string)(
                    $configuration['accesslevel'] ?? ''
                ))) === $accesslevel
                && trim((string)(
                    $configuration['roleshortname'] ?? ''
                )) === $roleshortname;

            if (!$matches) {
                $errors[] =
                    'The existing Native entitlement has conflicting configuration.';
            }
        }

        if ($conflictingentitlements !== []) {
            $errors[] =
                'The product already has another course entitlement for course #'
                . $courseid . '.';
        }

        if (!$mapping) {
            $actions[] =
                'Create subscription_plan #' . $sourceplanid
                . ' -> ' . $sku . ' mapping.';
        }

        if (!$entitlement) {
            $actions[] =
                'Create Native entitlement ' . $resourcekey
                . ' with role ' . $roleshortname . '.';
        }

        if ((int)$plan->is_active !== 0) {
            $warnings[] =
                'Legacy plan #' . $sourceplanid
                . ' is active. The repair will not change this state.';
        }

        if ($actions === [] && $errors === []) {
            $warnings[] = 'The Native course product contract is already complete.';
        }

        return [
            'sku' => $sku,
            'product' => $product,
            'sourceplan' => $plan,
            'course' => $course,
            'legacyentitlement' => $legacyentitlement,
            'mapping' => $mapping ?: null,
            'productmapping' => $productmapping ?: null,
            'entitlement' => $entitlement ?: null,
            'conflictingentitlements' => $conflictingentitlements,
            'resourcekey' => $resourcekey,
            'accesslevel' => $accesslevel,
            'roleshortname' => $roleshortname,
            'errors' => $errors,
            'warnings' => $warnings,
            'actions' => $actions,
            'ready' => $errors === [],
            'complete' => $errors === [] && $actions === [],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function apply(
        string $sku,
        int $sourceplanid,
        int $courseid,
        string $accesslevel,
        string $roleshortname
    ): array {
        $inspection = $this->inspect(
            $sku,
            $sourceplanid,
            $courseid,
            $accesslevel,
            $roleshortname
        );

        if (!$inspection['ready']) {
            throw new \RuntimeException(
                'The repair cannot be applied because conflicts were detected.'
            );
        }

        if ($inspection['complete']) {
            return $inspection;
        }

        $transaction = $this->db->start_delegated_transaction();

        try {
            $now = time();
            $productid = (int)$inspection['product']->id;
            $legacyentitlement = $inspection['legacyentitlement'];

            if ($inspection['mapping'] === null) {
                (new CommerceLegacyProductMapRepository($this->db))->link_product(
                    $productid,
                    'subscription',
                    self::LEGACY_PLAN_TABLE,
                    $sourceplanid
                );
            }

            if ($inspection['entitlement'] === null) {
                $this->db->insert_record(
                    self::ENTITLEMENT_TABLE,
                    (object)[
                        'productid' => $productid,
                        'type' => 'course_access',
                        'resourcekey' => $inspection['resourcekey'],
                        'durationseconds' => null,
                        'quantity' => 1,
                        'configurationjson' => json_encode(
                            [
                                'courseid' => $courseid,
                                'accesslevel' => $inspection['accesslevel'],
                                'roleshortname' => $inspection['roleshortname'],
                                'groupname' =>
                                    (string)($legacyentitlement->groupname ?? ''),
                                'sourceplanid' => $sourceplanid,
                                'legacysource' =>
                                    'subscription_plan_entitlement',
                                'legacyid' => (int)$legacyentitlement->id,
                            ],
                            JSON_UNESCAPED_SLASHES
                            | JSON_UNESCAPED_UNICODE
                        ),
                        'sortorder' => (int)$legacyentitlement->priority,
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ]
                );
            }

            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }

        return $this->inspect(
            $sku,
            $sourceplanid,
            $courseid,
            $accesslevel,
            $roleshortname
        );
    }
}
