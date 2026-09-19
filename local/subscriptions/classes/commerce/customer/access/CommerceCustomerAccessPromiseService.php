<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\access;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinContext;

/**
 * Customer-facing access promise shared by Commerce surfaces.
 *
 * This service deliberately returns semantic data rather than HTML. M6 can
 * reuse the same contract in Storefront, Cart, Checkout and Mon Campus.
 */
final class CommerceCustomerAccessPromiseService {
    public function __construct(
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommercePedagogicalPromotionRepository $promotions,
        private readonly CommercePedagogicalCalendarRepository $calendar
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        return new self(
            CommercePedagogicalPromotionOfferRepository::create($db),
            CommercePedagogicalPromotionRepository::create($db),
            CommercePedagogicalCalendarRepository::create($db)
        );
    }

    /**
     * @return array{
     *   kind:string,
     *   haspromotion:bool,
     *   promotionid:?int,
     *   promotionname:?string,
     *   startsat:?int,
     *   firstunlocksat:?int,
     *   progressive:bool,
     *   preservesexistingaccess:bool
     * }
     */
    public function resolve(
        string $sku,
        string $producttype,
        bool $owned,
        ?CommercePedagogicalPromotionJoinContext $promotionjoincontext,
        ?int $now = null
    ): array {
        $now = $now ?? time();
        $sku = strtoupper(trim($sku));
        $producttype = strtolower(trim($producttype));

        if ($producttype === 'bundle') {
            return $this->base('bundle');
        }

        if (in_array($producttype, ['digital', 'digital_download'], true)) {
            return $this->base($owned ? 'owned_digital' : 'immediate_digital');
        }

        if (!in_array($producttype, ['course_access', 'subscription'], true)) {
            return $this->base($owned ? 'owned_generic' : 'immediate_generic');
        }

        if ($promotionjoincontext !== null) {
            $promotion = $this->promotions->get_by_id(
                $promotionjoincontext->get_promotion_id()
            );
            if ($promotion !== null) {
                return $this->promotion_contract(
                    $promotion->get_id(),
                    $promotion->get_name(),
                    $promotion->get_starts_at(),
                    true
                );
            }
        }

        $link = $this->offers->sale_link_for_product($sku, $now);
        if ($link !== null) {
            $promotion = $link['promotion'];
            return $this->promotion_contract(
                $promotion->get_id(),
                $promotion->get_name(),
                $promotion->get_starts_at(),
                $owned
            );
        }

        return $this->base($owned ? 'owned_course' : 'immediate_course');
    }


    /**
     * Resolves the customer promise for a cart/checkout line.
     *
     * Unlike Storefront, cart lines carry a server-pinned operation in their
     * metadata. Promotion joins therefore keep their exact promotion even if
     * the public sale window changes while the seat reservation is still
     * valid.
     *
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function resolve_cart_item(
        string $sku,
        string $producttype,
        string $operation,
        array $metadata,
        bool $owned,
        ?int $now = null
    ): array {
        $operation = strtolower(trim($operation));

        if ($operation === 'promotion_join') {
            $promotionid = (int)($metadata['promotion_join_promotion_id'] ?? 0);
            if ($promotionid > 0) {
                $promotion = $this->promotions->get_by_id($promotionid);
                if ($promotion !== null) {
                    return $this->promotion_contract(
                        $promotion->get_id(),
                        $promotion->get_name(),
                        $promotion->get_starts_at(),
                        true
                    );
                }
            }

            return $this->base('owner_promotion_join');
        }

        if ($operation === 'upgrade') {
            return $this->base('upgrade');
        }

        return $this->resolve(
            $sku,
            $producttype,
            $owned,
            null,
            $now
        );
    }

    /** @return array<string,mixed> */
    private function promotion_contract(
        ?int $promotionid,
        string $promotionname,
        ?int $startsat,
        bool $preservesexistingaccess
    ): array {
        if ($promotionid === null) {
            return $this->base(
                $preservesexistingaccess ? 'owned_course' : 'immediate_course'
            );
        }

        $items = $this->calendar->for_promotion($promotionid);
        $firstunlock = null;
        foreach ($items as $item) {
            $timestamp = $item->get_unlocks_at();
            if ($firstunlock === null || $timestamp < $firstunlock) {
                $firstunlock = $timestamp;
            }
        }

        return [
            'kind' => $preservesexistingaccess
                ? 'owner_promotion_join'
                : ($items !== [] ? 'promotion_progressive' : 'promotion_course'),
            'haspromotion' => true,
            'promotionid' => $promotionid,
            'promotionname' => trim($promotionname),
            'startsat' => $startsat,
            'firstunlocksat' => $firstunlock,
            'progressive' => $items !== [],
            'preservesexistingaccess' => $preservesexistingaccess,
        ];
    }

    /** @return array<string,mixed> */
    private function base(string $kind): array {
        return [
            'kind' => $kind,
            'haspromotion' => false,
            'promotionid' => null,
            'promotionname' => null,
            'startsat' => null,
            'firstunlocksat' => null,
            'progressive' => false,
            'preservesexistingaccess' => false,
        ];
    }
}
