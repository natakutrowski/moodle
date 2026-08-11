<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\promotion\domain\CommercePromotion;

/** Persistence boundary for Native Commerce promotions. */
interface CommercePromotionRepository {
    public function get_by_code(string $code): ?CommercePromotion;
    /** @return CommercePromotion[] */
    public function find_automatic(int $at): array;
    public function save(CommercePromotion $promotion): CommercePromotion;
    public function count_redemptions(int $promotionid, ?int $userid = null): int;
}
