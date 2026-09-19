<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;

/**
 * Adds reservation UX state to a calculated cart presentation.
 *
 * This class is intentionally read-only: K2/K3 remain authoritative for
 * reservation creation and mutation.
 */
final class CommerceCartSeatReservationPresenter {
    public function __construct(
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommercePedagogicalSeatReservationRepository $reservations
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            CommercePedagogicalPromotionOfferRepository::create($db),
            CommercePedagogicalSeatReservationRepository::create($db)
        );
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function decorate(
        array $data,
        string $cartuuid,
        int $now
    ): array {
        $items = [];
        $haspedagogicalitems = false;
        $allvalid = true;
        $earliestexpiry = null;

        foreach ((array)($data['items'] ?? []) as $item) {
            $sku = strtoupper(trim((string)($item['productsku'] ?? '')));
            $links = $sku !== ''
                ? $this->offers->links_for_product($sku)
                : [];

            if ($links === []) {
                $item['hasseatreservation'] = false;
                $item['seatreservationactive'] = false;
                $item['seatreservationexpired'] = false;
                $items[] = $item;
                continue;
            }

            $haspedagogicalitems = true;
            $productid = (int)$links[0]['offer']->productid;
            $reservation = $this->reservations->find_for_cart_product(
                $cartuuid,
                $productid
            );

            $active = $reservation !== null
                && $reservation->is_active_at($now);
            $expired = !$active;

            $item['hasseatreservation'] = true;
            $item['seatreservationactive'] = $active;
            $item['seatreservationexpired'] = $expired;
            $item['seatreservationexpiresat'] =
                $active ? $reservation->get_expires_at() : 0;
            $item['seatreservationlabel'] = $active
                ? get_string(
                    'commerce_cart_seat_reserved',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_cart_seat_expired',
                    'local_subscriptions'
                );
            $item['seatreservationtimelabel'] = $active
                ? get_string(
                    'commerce_cart_seat_reserved_until',
                    'local_subscriptions',
                    userdate(
                        $reservation->get_expires_at(),
                        get_string('strftimetime', 'langconfig')
                    )
                )
                : get_string(
                    'commerce_cart_seat_expired_help',
                    'local_subscriptions'
                );

            if ($active) {
                $expiry = $reservation->get_expires_at();
                $earliestexpiry = $earliestexpiry === null
                    ? $expiry
                    : min($earliestexpiry, $expiry);
            } else {
                $allvalid = false;
            }

            $items[] = $item;
        }

        $data['items'] = $items;
        $data['haspedagogicalitems'] = $haspedagogicalitems;
        $data['seatreservationsvalid'] =
            !$haspedagogicalitems || $allvalid;
        $data['seatreservationsexpired'] =
            $haspedagogicalitems && !$allvalid;
        $data['seatreservationexpiresat'] =
            $earliestexpiry ?? 0;
        $data['seatreservationsummarylabel'] =
            $haspedagogicalitems && $allvalid
                ? get_string(
                    'commerce_cart_seats_reserved_summary',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_cart_seats_expired_summary',
                    'local_subscriptions'
                );
        $data['seatreservationcountdownlabel'] =
            get_string(
                'commerce_cart_seat_countdown',
                'local_subscriptions'
            );
        $data['seatreservationrenewlabel'] =
            get_string(
                'commerce_cart_seat_renew',
                'local_subscriptions'
            );

        return $data;
    }
}
