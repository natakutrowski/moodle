<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\validation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogProductSummary;

final class CommerceCatalogValidator {
    public function validate(CommerceCatalogProductSummary $product): CommerceCatalogValidationResult {
        $issues = [];
        $activeprices = array_filter($product->get_prices(), static fn($p): bool => $p->is_active());
        if ($activeprices === []) {
            $issues[] = new CommerceCatalogValidationIssue('error', 'no_active_price', get_string('commerce_validation_no_active_price', 'local_subscriptions'));
        }
        if ($product->get_visibility() === 'hidden') {
            $issues[] = new CommerceCatalogValidationIssue('info', 'hidden', get_string('commerce_validation_hidden', 'local_subscriptions'));
        }
        if ($product->get_availability() !== 'on_sale') {
            $issues[] = new CommerceCatalogValidationIssue('info', 'not_on_sale', get_string('commerce_validation_not_on_sale', 'local_subscriptions'));
        }
        return new CommerceCatalogValidationResult($issues);
    }
}
