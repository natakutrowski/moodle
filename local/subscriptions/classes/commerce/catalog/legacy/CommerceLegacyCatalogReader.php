<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\legacy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/**
 * Narrow compatibility reader used only by the hybrid catalogue service.
 */
final class CommerceLegacyCatalogReader {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function find_by_sku(string $sku): ?CommerceProduct {
        $sku = strtoupper(trim($sku));
        if (preg_match('/^SUB\.PLAN\.(\d+)$/', $sku, $matches)) {
            $record = $this->db->get_record('subscription_plan', ['id' => (int)$matches[1]]);
            return $record ? new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                !empty($record->is_active) ? CommerceProductStatus::ACTIVE : CommerceProductStatus::INACTIVE,
                (string)$record->name,
                '',
                ['source' => 'legacy', 'legacytable' => 'subscription_plan', 'legacyid' => (int)$record->id]
            ) : null;
        }

        if (str_starts_with($sku, 'DIGITAL.')) {
            foreach ($this->db->get_records('subscription_digital_product') as $record) {
                $recordsku = 'DIGITAL.' . strtoupper(preg_replace('/[^A-Z0-9._:-]+/i', '.', (string)$record->slug));
                if ($recordsku === $sku) {
                    return new CommerceProduct(
                        $sku,
                        CommerceProductType::DIGITAL_DOWNLOAD,
                        !empty($record->enabled) ? CommerceProductStatus::ACTIVE : CommerceProductStatus::INACTIVE,
                        (string)$record->name,
                        (string)($record->description ?? ''),
                        ['source' => 'legacy', 'legacytable' => 'subscription_digital_product', 'legacyid' => (int)$record->id]
                    );
                }
            }
        }

        return null;
    }
}
