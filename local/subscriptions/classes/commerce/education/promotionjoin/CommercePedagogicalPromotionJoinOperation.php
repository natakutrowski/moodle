<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

/** Stable commercial contract for an existing owner joining a pedagogical promotion. */
final class CommercePedagogicalPromotionJoinOperation {
    public const OPERATION = 'promotion_join';

    /**
     * Canonical metadata that later cart/checkout/purchase phases must preserve.
     *
     * @return array<string,int|string>
     */
    public static function metadata(CommercePedagogicalPromotionJoinContext $context): array {
        return [
            'operation' => self::OPERATION,
            'promotion_join_user_id' => $context->get_user_id(),
            'promotion_join_promotion_id' => $context->get_promotion_id(),
            'promotion_join_promotion_key' => $context->get_promotion_key(),
            'promotion_join_course_id' => $context->get_course_id(),
            'promotion_join_product_id' => $context->get_product_id(),
            'promotion_join_product_sku' => $context->get_product_sku(),
            'promotion_join_ownership_source' => $context->get_ownership_source(),
        ];
    }
}
