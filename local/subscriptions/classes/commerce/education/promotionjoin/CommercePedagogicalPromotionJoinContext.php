<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

/** Immutable identity of one owner joining one currently saleable cohort. */
final class CommercePedagogicalPromotionJoinContext {
    public function __construct(
        private readonly int $userid,
        private readonly string $productsku,
        private readonly int $productid,
        private readonly string $ownershipsource,
        private readonly int $promotionid,
        private readonly string $promotionkey,
        private readonly string $promotionname,
        private readonly int $courseid,
        private readonly ?int $remaining
    ) {
        if ($userid <= 0 || $productid <= 0 || $promotionid <= 0 || $courseid <= 0) {
            throw new \coding_exception('Promotion join context requires positive identifiers.');
        }
        if (trim($productsku) === '' || trim($ownershipsource) === '' || trim($promotionkey) === '') {
            throw new \coding_exception('Promotion join context requires stable commercial identities.');
        }
        if ($remaining !== null && $remaining < 0) {
            throw new \coding_exception('Promotion join remaining capacity cannot be negative.');
        }
    }

    public function get_user_id(): int { return $this->userid; }
    public function get_product_sku(): string { return $this->productsku; }
    public function get_product_id(): int { return $this->productid; }
    public function get_ownership_source(): string { return $this->ownershipsource; }
    public function get_promotion_id(): int { return $this->promotionid; }
    public function get_promotion_key(): string { return $this->promotionkey; }
    public function get_promotion_name(): string { return $this->promotionname; }
    public function get_course_id(): int { return $this->courseid; }
    public function get_remaining(): ?int { return $this->remaining; }

    /** @return array<string,mixed> */
    public function to_array(): array {
        return [
            'userid' => $this->userid,
            'productsku' => $this->productsku,
            'productid' => $this->productid,
            'ownershipsource' => $this->ownershipsource,
            'promotionid' => $this->promotionid,
            'promotionkey' => $this->promotionkey,
            'promotionname' => $this->promotionname,
            'courseid' => $this->courseid,
            'remaining' => $this->remaining,
            'operation' => CommercePedagogicalPromotionJoinOperation::OPERATION,
            'metadata' => CommercePedagogicalPromotionJoinOperation::metadata($this),
        ];
    }
}
