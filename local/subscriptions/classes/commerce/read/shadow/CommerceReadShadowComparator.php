<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\dto\CommercePurchaseView;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Compares the stable fields required before migrating a consumer. */
final class CommerceReadShadowComparator {
    public function compare(CommercePurchasePersistenceSnapshot $legacy, CommercePurchaseView $native): CommerceReadShadowComparison {
        $record = $legacy->get_purchase()->to_record();
        $differences = [];

        $this->compare_field($differences, 'status', $record->status, $native->status, CommerceReadDifference::CRITICAL);
        $this->compare_field($differences, 'currency', $record->currency, $native->currency, CommerceReadDifference::CRITICAL);
        $this->compare_field($differences, 'totalminor', (int)$record->totalminor, $native->totalminor, CommerceReadDifference::CRITICAL);
        $this->compare_field($differences, 'userid', $record->userid, $native->userid, CommerceReadDifference::WARNING);
        $this->compare_field($differences, 'customeremail', $record->customeremail, $native->customeremail, CommerceReadDifference::WARNING);
        $this->compare_field($differences, 'type', $record->type, $native->type, CommerceReadDifference::WARNING);

        return new CommerceReadShadowComparison($differences);
    }

    private function compare_field(array &$differences, string $field, mixed $legacy, mixed $native, string $severity): void {
        if ($legacy === $native) {
            return;
        }

        $differences[] = new CommerceReadDifference($field, $legacy, $native, $severity);
    }
}
