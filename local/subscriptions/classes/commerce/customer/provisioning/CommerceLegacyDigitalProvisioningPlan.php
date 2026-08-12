<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\provisioning;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityMatch;

/**
 * Dry-run for one Legacy Digital customer identity.
 */
final class CommerceLegacyDigitalProvisioningPlan {
    public const STATUS_CREATABLE = 'creatable';
    public const STATUS_EXISTING_ACCOUNT = 'existing_account';
    public const STATUS_AMBIGUOUS_ACCOUNT = 'ambiguous_account';
    public const STATUS_SIMILAR_ACCOUNT = 'similar_account';
    public const STATUS_INVALID_EMAIL = 'invalid_email';
    public const STATUS_EMPTY = 'empty';

    /**
     * @param int[] $purchaseids
     * @param int[] $exactuserids
     * @param CommerceCustomerIdentitySimilarityMatch[] $similaraccounts
     */
    public function __construct(
        public readonly string $email,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $language,
        public readonly array $purchaseids,
        public readonly string $status,
        public readonly array $exactuserids = [],
        public readonly array $similaraccounts = []
    ) {
    }

    public function purchase_count(): int {
        return count($this->purchaseids);
    }

    public function can_create(bool $allowSimilar = false): bool {
        if ($this->status === self::STATUS_CREATABLE) {
            return true;
        }

        return $allowSimilar
            && $this->status === self::STATUS_SIMILAR_ACCOUNT;
    }
}
