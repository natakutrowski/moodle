<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/**
 * Unified transactional entry point for future Commerce administration pages.
 */
final class CommerceCatalogAdminFacade {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceProductAdminService $admin
    ) {
    }

    /**
     * Saves a complete catalogue aggregate atomically.
     *
     * @param CommerceProduct $product
     * @param array $prices CommerceProductPrice[]
     * @param array $translations CommerceProductTranslation[]
     * @param array $components CommerceProductComponent[]
     * @param array $entitlements CommerceProductEntitlementDefinition[]
     */
    public function save_complete(
        CommerceProduct $product,
        array $prices = [],
        array $translations = [],
        array $components = [],
        array $entitlements = []
    ): CommerceProduct {
        $transaction = $this->db->start_delegated_transaction();
        $saved = $this->admin->save_product($product);
        foreach ($prices as $price) {
            $this->admin->set_price($price);
        }
        foreach ($translations as $translation) {
            $this->admin->set_translation($translation);
        }
        $this->admin->replace_definition($saved->get_sku(), $components, $entitlements);
        $transaction->allow_commit();
        return $saved;
    }
}
