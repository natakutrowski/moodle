<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\lifecycle;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\repository\CommerceCartRepository;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;

/** Finalises the active session cart after a successful purchase conversion. */
final class CommerceCartLifecycleService {
    public function __construct(
        private readonly CommerceCartRepository $repository,
        private readonly CommerceCartSessionKeyResolver $keys
    ) {
    }

    public static function create(): self {
        return new self(
            new CommerceSessionCartRepository(),
            new CommerceCartSessionKeyResolver()
        );
    }

    /**
     * Deletes the active cart only when it is the exact cart frozen into the purchase.
     *
     * This UUID guard prevents a late payment callback from deleting a newer cart that
     * the customer may have created after starting checkout.
     */
    public function clear_converted_cart(
        int $customerid,
        string $currency,
        string $expecteduuid
    ): bool {
        $expecteduuid = strtolower(trim($expecteduuid));
        if (!preg_match('/^[a-f0-9]{32}$/', $expecteduuid)) {
            return false;
        }

        $key = $this->keys->resolve($customerid, $currency);
        $cart = $this->repository->find($key);
        if ($cart === null || !hash_equals($expecteduuid, $cart->get_uuid())) {
            return false;
        }

        $this->repository->delete($key);
        return true;
    }
}
