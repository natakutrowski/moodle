<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\catalog;

defined('MOODLE_INTERNAL') || die();

/** Cart-facing catalogue boundary. */
interface CommerceCartCatalogGateway {
    public function quote(
        string $productsku,
        int $priceid,
        string $currency,
        string $language,
        ?int $at = null
    ): CommerceCartProductQuote;
}
