<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\repository\CommerceCartRepository;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;

/** Transfers and merges the anonymous session cart into a resolved Moodle account. */
final class CommerceGuestCartTransferService {
    public function __construct(
        private readonly CommerceCartRepository $repository,
        private readonly CommerceCartSessionKeyResolver $keys
    ) {}

    public static function create(): self {
        return new self(new CommerceSessionCartRepository(), new CommerceCartSessionKeyResolver());
    }

    /**
     * Captures a durable representation before Moodle regenerates the session at login.
     */
    public function capture(string $currency): ?array {
        $guest = $this->repository->find($this->keys->resolve(0, $currency));
        return $guest?->to_array();
    }

    /**
     * @param array<string, mixed>|null $durableguestcart
     */
    public function transfer(int $userid, string $currency, ?array $durableguestcart = null): ?CommerceCart {
        if ($userid <= 0) {
            throw new \coding_exception('Guest cart transfer requires a resolved Moodle user.');
        }

        $currency = strtoupper(trim($currency));
        $guestkey = $this->keys->resolve(0, $currency);
        $userkey = $this->keys->resolve($userid, $currency);
        $guest = $this->repository->find($guestkey);
        $existing = $this->repository->find($userkey);

        if ($guest === null && $durableguestcart !== null) {
            $candidate = CommerceCart::from_array($durableguestcart);
            if ($candidate->get_customer_id() !== 0 || $candidate->get_currency() !== $currency) {
                throw new \coding_exception('The durable Guest Checkout cart does not match the requested transfer.');
            }
            $guest = $candidate;
        }

        if ($guest === null) {
            return $existing;
        }

        $items = $existing?->get_items() ?? [];
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item->get_key()] = $item;
        }
        foreach ($guest->get_items() as $item) {
            $current = $indexed[$item->get_key()] ?? null;
            $indexed[$item->get_key()] = $current === null
                ? $item
                : $current->with_quantity(max($current->get_quantity(), $item->get_quantity()));
        }

        $metadata = array_replace(
            $guest->get_metadata(),
            $existing?->get_metadata() ?? [],
            ['guest_cart_transferred_at' => time()]
        );
        $target = new CommerceCart(
            $existing?->get_uuid() ?? $guest->get_uuid(),
            $userid,
            $currency,
            array_values($indexed),
            $metadata,
            $existing?->get_time_created() ?? $guest->get_time_created(),
            time()
        );

        $this->repository->save($userkey, $target);
        $persisted = $this->repository->find($userkey);
        if ($persisted === null || $persisted->is_empty()) {
            throw new \RuntimeException('The Guest Checkout cart transfer could not be persisted.');
        }

        // Delete the anonymous copy only after the target cart has been verified.
        $this->repository->delete($guestkey);
        return $persisted;
    }
}
