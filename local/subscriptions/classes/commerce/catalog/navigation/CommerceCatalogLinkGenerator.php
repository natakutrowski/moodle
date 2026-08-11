<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\navigation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\contract\CommerceCatalogProductContract;

/** Centralised URLs for the federated catalogue. */
final class CommerceCatalogLinkGenerator {
    public static function view_url(CommerceCatalogProductContract $product): \moodle_url {
        $id = $product->get_id();
        if ($id === null) {
            throw new \InvalidArgumentException('A persisted catalogue product is required to build a view URL.');
        }

        $identity = new CommerceCatalogIdentity($product->get_origin(), $id);

        return new \moodle_url(
            '/local/subscriptions/admin/commerce/products/view.php',
            ['catalogkey' => $identity->to_string()]
        );
    }
}
