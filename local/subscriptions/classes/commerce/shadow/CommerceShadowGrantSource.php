<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/** Read-only source of Native grants for Shadow execution. */
interface CommerceShadowGrantSource {
    /** @return CommerceEntitlementGrant[] */
    public function find_for_purchase(string $purchasereference): array;
}
