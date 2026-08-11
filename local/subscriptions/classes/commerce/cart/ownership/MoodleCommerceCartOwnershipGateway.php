<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\ownership;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

/** Reuses the certified effective-ownership resolver in the Cart runtime. */
final class MoodleCommerceCartOwnershipGateway implements CommerceCartOwnershipGateway {
    public function __construct(
        private readonly CommerceStorefrontOwnershipResolver $resolver
    ) {
    }

    public function owns(int $customerid, string $productsku): bool {
        return $customerid > 0 && $this->resolver->owns($customerid, $productsku);
    }
}
