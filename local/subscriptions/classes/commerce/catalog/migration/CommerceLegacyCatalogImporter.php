<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\repository\CommerceLegacyProductMapRepository;
use local_subscriptions\commerce\catalog\service\CommerceProductAdminService;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/**
 * Idempotently imports both historical catalogues into Native Commerce.
 */
final class CommerceLegacyCatalogImporter {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceProductAdminService $admin,
        private readonly CommerceLegacyProductMapRepository $maps
    ) {
    }

    /**
     * @return array{processed:int,createdorupdated:int,prices:int,translations:int,entitlements:int,errors:array}
     */
    public function import(string $family = 'all', bool $execute = false): array {
        $family = strtolower(trim($family));
        if (!in_array($family, ['all', 'subscription', 'digital'], true)) {
            throw new \coding_exception('Unsupported Legacy catalogue family.');
        }

        $report = [
            'processed' => 0,
            'createdorupdated' => 0,
            'prices' => 0,
            'translations' => 0,
            'entitlements' => 0,
            'errors' => [],
        ];

        if ($family === 'all' || $family === 'subscription') {
            $this->import_subscription_plans($execute, $report);
        }
        if ($family === 'all' || $family === 'digital') {
            $this->import_digital_products($execute, $report);
        }

        return $report;
    }

    private function import_subscription_plans(bool $execute, array &$report): void {
        foreach ($this->db->get_records('subscription_plan', null, 'id ASC') as $record) {
            $report['processed']++;
            try {
                $sku = 'SUB.PLAN.' . (int)$record->id;
                $scope = $this->load_access_scope((int)($record->accessscopeid ?? 0));
                $product = new CommerceProduct(
                    $sku,
                    CommerceProductType::COURSE_ACCESS,
                    !empty($record->is_active) ? CommerceProductStatus::ACTIVE : CommerceProductStatus::INACTIVE,
                    trim((string)$record->name),
                    '',
                    [
                        'legacyfamily' => 'subscription',
                        'legacyid' => (int)$record->id,
                        'durationkey' => (string)($record->duration_key ?? ''),
                        'istrial' => !empty($record->is_trial),
                        'isrecurring' => !empty($record->is_recurring),
                        'accessscopeid' => (int)($record->accessscopeid ?? 0),
                        'accessscope' => $scope,
                    ]
                );

                if ($execute) {
                    $saved = $this->admin->save_product($product);
                    $this->maps->save((int)$saved->get_id(), 'subscription', 'subscription_plan', (int)$record->id);
                }
                $report['createdorupdated']++;

                foreach ($this->db->get_records('subscription_plan_price', ['planid' => $record->id]) as $price) {
                    $report['prices']++;
                    if ($execute) {
                        $this->admin->set_price(new CommerceProductPrice(
                            $sku,
                            CommerceMoney::from_major(self::normalise_major_amount($price->price), (string)$price->currency),
                            true,
                            !empty($price->stripe_price_id) ? 'stripe' : null,
                            !empty($price->stripe_price_id) ? (string)$price->stripe_price_id : null,
                            ['legacyid' => (int)$price->id]
                        ));
                    }
                }

                foreach ($this->db->get_records('subscription_plan_translation', ['planid' => $record->id]) as $translation) {
                    $report['translations']++;
                    if ($execute) {
                        $this->admin->set_translation(new CommerceProductTranslation(
                            $sku,
                            (string)$translation->lang,
                            (string)$translation->name,
                            '',
                            (string)($translation->description ?? ''),
                            ['legacyid' => (int)$translation->id]
                        ));
                    }
                }

                $definitions = [];
                $legacyentitlements = $this->db->get_records(
                    'subscription_plan_entitlement',
                    ['planid' => $record->id],
                    'priority ASC, id ASC'
                );
                foreach ($legacyentitlements as $entitlement) {
                    $report['entitlements']++;
                    $definitions[] = new CommerceProductEntitlementDefinition(
                        $sku,
                        'course_access',
                        'course:' . (int)$entitlement->courseid . ':' . (string)$entitlement->accesslevel,
                        null,
                        1,
                        [
                            'courseid' => (int)$entitlement->courseid,
                            'accesslevel' => (string)$entitlement->accesslevel,
                            'roleshortname' => (string)$entitlement->roleshortname,
                            'groupname' => $entitlement->groupname ?? null,
                            'legacysource' => 'subscription_plan_entitlement',
                            'legacyid' => (int)$entitlement->id,
                        ],
                        (int)$entitlement->priority
                    );
                }

                // Older plans may still define their access only through access_scope.course_ids.
                // Explicit plan entitlements remain authoritative whenever they exist.
                if ($definitions === [] && $scope !== null) {
                    foreach ($scope['courseids'] as $courseid) {
                        $report['entitlements']++;
                        $definitions[] = new CommerceProductEntitlementDefinition(
                            $sku,
                            'course_access',
                            'course:' . $courseid . ':full',
                            null,
                            1,
                            [
                                'courseid' => $courseid,
                                'accesslevel' => 'full',
                                'roleshortname' => 'student',
                                'groupname' => null,
                                'legacysource' => 'subscription_access_scope',
                                'legacyscopeid' => $scope['id'],
                            ],
                            100
                        );
                    }
                }
                if ($execute) {
                    $this->admin->replace_definition($sku, [], $definitions);
                }
            } catch (\Throwable $exception) {
                $report['errors'][] = 'subscription_plan#' . (int)$record->id . ': ' . $exception->getMessage();
            }
        }
    }

    private function import_digital_products(bool $execute, array &$report): void {
        foreach ($this->db->get_records('subscription_digital_product', null, 'id ASC') as $record) {
            $report['processed']++;
            try {
                $sku = 'DIGITAL.' . strtoupper(preg_replace('/[^A-Z0-9._:-]+/i', '.', (string)$record->slug));
                $product = new CommerceProduct(
                    $sku,
                    CommerceProductType::DIGITAL_DOWNLOAD,
                    !empty($record->enabled) ? CommerceProductStatus::ACTIVE : CommerceProductStatus::INACTIVE,
                    trim((string)$record->name),
                    (string)($record->description ?? ''),
                    [
                        'legacyfamily' => 'digital',
                        'legacyid' => (int)$record->id,
                        'slug' => (string)$record->slug,
                        'filename' => (string)$record->filename,
                        'mobilefilename' => $record->mobile_filename ?? null,
                    ]
                );

                if ($execute) {
                    $saved = $this->admin->save_product($product);
                    $this->maps->save((int)$saved->get_id(), 'digital', 'subscription_digital_product', (int)$record->id);
                }
                $report['createdorupdated']++;

                foreach (['EUR' => $record->price_eur, 'RUB' => $record->price_rub] as $currency => $amount) {
                    $report['prices']++;
                    if ($execute) {
                        $this->admin->set_price(new CommerceProductPrice(
                            $sku,
                            CommerceMoney::from_major(self::normalise_major_amount($amount), $currency),
                            true,
                            null,
                            null,
                            ['legacyfield' => 'price_' . strtolower($currency)]
                        ));
                    }
                }

                foreach ($this->db->get_records('subscription_digital_product_lang', ['productid' => $record->id]) as $translation) {
                    $report['translations']++;
                    if ($execute) {
                        $this->admin->set_translation(new CommerceProductTranslation(
                            $sku,
                            (string)$translation->lang,
                            (string)$translation->title,
                            (string)($translation->sales_intro ?? ''),
                            '',
                            [
                                'legacyid' => (int)$translation->id,
                                'contentitems' => $translation->content_items ?? null,
                                'forwhoitems' => $translation->forwho_items ?? null,
                            ]
                        ));
                    }
                }

                $report['entitlements']++;
                if ($execute) {
                    $this->admin->replace_definition($sku, [], [
                        new CommerceProductEntitlementDefinition(
                            $sku,
                            'digital_download',
                            'digital-product:' . (int)$record->id,
                            null,
                            1,
                            [
                                'legacyproductid' => (int)$record->id,
                                'filename' => (string)$record->filename,
                                'mobilefilename' => $record->mobile_filename ?? null,
                            ]
                        ),
                    ]);
                }
            } catch (\Throwable $exception) {
                $report['errors'][] = 'subscription_digital_product#' . (int)$record->id . ': ' . $exception->getMessage();
            }
        }
    }

    /**
     * Loads the Legacy access scope as migration metadata and entitlement fallback input.
     *
     * @return array{id:int,name:string,courseids:array<int>,translations:array}|null
     */
    private function load_access_scope(int $scopeid): ?array {
        if ($scopeid <= 0) {
            return null;
        }

        $scope = $this->db->get_record('subscription_access_scope', ['id' => $scopeid], '*', IGNORE_MISSING);
        if (!$scope) {
            return null;
        }

        $translations = [];
        foreach ($this->db->get_records('subscription_access_scope_translation', ['accessscopeid' => $scopeid]) as $translation) {
            $translations[(string)$translation->lang] = [
                'name' => (string)$translation->name,
                'description' => (string)($translation->description ?? ''),
            ];
        }

        return [
            'id' => (int)$scope->id,
            'name' => (string)$scope->name,
            'courseids' => self::parse_course_ids((string)$scope->course_ids),
            'translations' => $translations,
        ];
    }

    /** @return array<int> */
    private static function parse_course_ids(string $raw): array {
        $ids = preg_split('/[,;\s]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private static function normalise_major_amount(mixed $amount): string {
        $value = trim((string)$amount);
        if (!preg_match('/^(\d+)(?:\.(\d+))?$/', $value, $matches)) {
            throw new \coding_exception('A Legacy catalogue price must be a non-negative decimal amount.');
        }

        $fraction = $matches[2] ?? '';
        if (strlen($fraction) > 2) {
            $extra = substr($fraction, 2);
            if (trim($extra, '0') !== '') {
                throw new \coding_exception('A Legacy catalogue price has more than two significant decimal places.');
            }
            $fraction = substr($fraction, 0, 2);
        }

        return $fraction === '' ? $matches[1] : $matches[1] . '.' . str_pad($fraction, 2, '0');
    }

}
