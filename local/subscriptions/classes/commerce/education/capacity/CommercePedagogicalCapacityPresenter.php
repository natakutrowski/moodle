<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\capacity;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;

/**
 * Public-safe capacity presentation shared by Storefront and Showroom.
 */
final class CommercePedagogicalCapacityPresenter {
    public function __construct(
        private readonly CommercePedagogicalCapacityService $capacity,
        private readonly CommercePedagogicalPromotionOfferRepository $offers
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        return new self(
            CommercePedagogicalCapacityService::create($db),
            CommercePedagogicalPromotionOfferRepository::create($db)
        );
    }

    /** @return array<string,mixed> */
    public function for_product(
        string $sku,
        int $now,
        ?string $excludedcartuuid = null
    ): array {
        $snapshot = $this->capacity->for_product($sku, $now);

        if (!$snapshot->is_pedagogically_linked()) {
            return [
                'haspedagogicalcapacity' => false,
                'pedagogicalavailable' => true,
                'pedagogicalbuyavailable' => true,
                'pedagogicaldirectresume' => false,
                'pedagogicalsoldout' => false,
                'pedagogicalremaining' => null,
                'pedagogicalcapacitylabel' => '',
                'pedagogicalcapacityclass' => '',
                'pedagogicalsalesclosed' => false,
                'haspedagogicalsalesclose' => false,
                'pedagogicalsalesclosesat' => null,
                'pedagogicalsalescloselabel' => '',
                'pedagogicalsalescloseurgent' => false,
                'pedagogicaldisabledctlabel' => '',
            ];
        }

        $directresume = false;
        if (
            $excludedcartuuid !== null
            && !$snapshot->is_available()
        ) {
            $resume = $this->capacity->for_product(
                $sku,
                $now,
                $excludedcartuuid
            );
            $directresume = $resume->is_available();
        }

        $remaining = $snapshot->get_remaining();
        $available = $snapshot->is_available();
        $buyavailable = $available || $directresume;
        $soldout = $snapshot->is_sold_out() && !$directresume;
        $salesclosed = !$snapshot->are_sales_open();

        $link = $this->offers->sale_link_for_product($sku);
        $salesclosesat = $link !== null
            ? $link['promotion']->get_sales_closes_at()
            : null;
        $salescloselabel = $salesclosesat === null
            ? ''
            : get_string(
                'commerce_capacity_sales_close_at',
                'local_subscriptions',
                userdate(
                    $salesclosesat,
                    get_string('strftimedatetime', 'langconfig')
                )
            );
        $salescloseurgent =
            $salesclosesat !== null
            && $salesclosesat > $now
            && ($salesclosesat - $now) <= DAYSECS;
        $disabledctlabel = $salesclosed
            ? get_string(
                'commerce_capacity_sales_closed_cta',
                'local_subscriptions'
            )
            : get_string(
                'commerce_capacity_sold_out_cta',
                'local_subscriptions'
            );

        if ($directresume) {
            $label = get_string(
                'commerce_cart_seat_reserved',
                'local_subscriptions'
            );
            $class = 'is-available';
        } else if (!$snapshot->are_sales_open()) {
            $label = get_string(
                'commerce_capacity_sales_closed',
                'local_subscriptions'
            );
            $class = 'is-closed';
        } else if ($soldout) {
            $label = get_string(
                'commerce_capacity_sold_out',
                'local_subscriptions'
            );
            $class = 'is-sold-out';
        } else if ($remaining !== null) {
            $label = get_string(
                $remaining === 1
                    ? 'commerce_capacity_one_left'
                    : 'commerce_capacity_places_left',
                'local_subscriptions',
                $remaining
            );
            $class = $remaining <= 3 ? 'is-low' : 'is-available';
        } else {
            $label = get_string(
                'commerce_capacity_available',
                'local_subscriptions'
            );
            $class = 'is-available';
        }

        return [
            'haspedagogicalcapacity' => true,
            'pedagogicalavailable' => $available,
            'pedagogicalbuyavailable' => $buyavailable,
            'pedagogicaldirectresume' => $directresume,
            'pedagogicalsoldout' => $soldout,
            'pedagogicalremaining' => $remaining,
            'pedagogicalcapacitylabel' => $label,
            'pedagogicalcapacityclass' => $class,
            'pedagogicalsalesclosed' => $salesclosed,
            'haspedagogicalsalesclose' => $salesclosesat !== null,
            'pedagogicalsalesclosesat' => $salesclosesat,
            'pedagogicalsalescloselabel' => $salescloselabel,
            'pedagogicalsalescloseurgent' => $salescloseurgent,
            'pedagogicaldisabledctlabel' => $disabledctlabel,
        ];
    }
}
