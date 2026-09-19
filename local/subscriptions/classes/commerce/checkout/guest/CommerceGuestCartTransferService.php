<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\repository\CommerceCartRepository;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

/** Transfers and merges the anonymous session cart into a resolved Moodle account. */
final class CommerceGuestCartTransferService {
    public function __construct(
        private readonly CommerceCartRepository $repository,
        private readonly CommerceCartSessionKeyResolver $keys,
        private readonly ?CommercePedagogicalSeatReservationService $reservations = null
    ) {}

    public static function create(): self {
        return new self(
            new CommerceSessionCartRepository(),
            new CommerceCartSessionKeyResolver(),
            CommercePedagogicalSeatReservationService::create()
        );
    }

    /**
     * Captures a durable representation before Moodle regenerates the session at login.
     */
    public function capture(string $currency): ?array {
        $guest = $this->repository->find($this->keys->resolve(0, $currency));
        if ($guest === null || $guest->is_empty()) {
            return null;
        }
        return $guest->to_array();
    }

    /**
     * Restore the durable Guest Checkout snapshot to the anonymous session cart
     * before an explicitly requested identity reset.
     *
     * @param array<string,mixed> $durableguestcart
     */
    public function restore_anonymous(
        int $previoususerid,
        string $currency,
        array $durableguestcart
    ): CommerceCart {
        $currency = strtoupper(trim($currency));
        $candidate = CommerceCart::from_array($durableguestcart);

        if (
            $candidate->get_customer_id() !== 0
            || $candidate->get_currency() !== $currency
            || $candidate->is_empty()
        ) {
            throw new \coding_exception(
                'The durable Guest Checkout cart cannot be restored as an anonymous cart.'
            );
        }

        $guestkey = $this->keys->resolve(0, $currency);
        $restored = new CommerceCart(
            $candidate->get_uuid(),
            0,
            $currency,
            $candidate->get_items(),
            array_replace(
                $candidate->get_metadata(),
                ['guest_identity_reset_at' => time()]
            ),
            $candidate->get_time_created(),
            time()
        );

        $this->repository->save(
            $guestkey,
            $restored
        );

        $persisted =
            $this->repository->find(
                $guestkey
            );

        if (
            $persisted === null
            || $persisted->is_empty()
        ) {
            throw new \RuntimeException(
                'Guest Checkout identity reset could not restore the anonymous cart.'
            );
        }

        if ($previoususerid > 0) {
            $this->repository->delete(
                $this->keys->resolve(
                    $previoususerid,
                    $currency
                )
            );
        }

        return $persisted;
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

        if ($guest === null || $guest->is_empty()) {
            // Direct Purchase keeps its authoritative item outside the session
            // cart. An empty anonymous cart is therefore a valid no-op here,
            // not a failed transfer.
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

        // Move pedagogical holds together with the cart identity. This keeps
        // the original countdown when a provisional/existing user cart UUID
        // differs from the anonymous cart UUID and closes the last-seat race.
        $this->reservations?->transfer_cart(
            $guest->get_uuid(),
            $persisted->get_uuid(),
            $userid,
            time()
        );

        // Delete the anonymous copy only after the target cart and its seat
        // reservations have been transferred successfully.
        $this->repository->delete($guestkey);
        return $persisted;
    }
}
