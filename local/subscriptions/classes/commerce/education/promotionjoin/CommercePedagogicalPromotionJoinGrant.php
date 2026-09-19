<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/** Typed view over the dedicated Native grant used by promotion_join purchases. */
final class CommercePedagogicalPromotionJoinGrant {
    public const GRANT_TYPE = 'pedagogical_promotion_join';

    private function __construct(
        private readonly CommerceEntitlementGrant $grant,
        private readonly int $userid,
        private readonly int $promotionid,
        private readonly int $courseid,
        private readonly int $productid,
        private readonly string $productsku,
        private readonly string $ownershipsource
    ) {
    }

    public static function resource_key(int $promotionid, int $courseid, int $productid): string {
        if ($promotionid <= 0 || $courseid <= 0 || $productid <= 0) {
            throw new \coding_exception('Promotion join grant resource identifiers must be positive.');
        }

        return sprintf(
            'promotion:%d:course:%d:product:%d',
            $promotionid,
            $courseid,
            $productid
        );
    }

    public static function from_grant(CommerceEntitlementGrant $grant): self {
        if ($grant->get_type() !== self::GRANT_TYPE) {
            throw new \coding_exception('Expected a pedagogical promotion join grant.');
        }

        $configuration = $grant->get_configuration();
        if (
            strtolower(trim((string)($configuration['commerceoperation'] ?? '')))
                !== CommercePedagogicalPromotionJoinOperation::OPERATION
        ) {
            throw new \coding_exception('Promotion join grant lost its Commerce operation identity.');
        }

        $userid = (int)($configuration['promotion_join_user_id'] ?? 0);
        $promotionid = (int)($configuration['promotion_join_promotion_id'] ?? 0);
        $courseid = (int)($configuration['promotion_join_course_id'] ?? 0);
        $productid = (int)($configuration['promotion_join_product_id'] ?? 0);
        $productsku = strtoupper(trim((string)($configuration['promotion_join_product_sku'] ?? '')));
        $ownershipsource = trim((string)($configuration['promotion_join_ownership_source'] ?? ''));

        if ($userid <= 0 || $promotionid <= 0 || $courseid <= 0 || $productid <= 0) {
            throw new \coding_exception('Promotion join grant requires positive canonical identifiers.');
        }
        if ($productsku === '' || $ownershipsource === '') {
            throw new \coding_exception('Promotion join grant requires stable product and ownership identities.');
        }
        if ($grant->get_quantity() !== 1) {
            throw new \coding_exception('Promotion join grant quantity must be exactly one.');
        }
        if ($grant->get_beneficiary_user_id() !== $userid) {
            throw new \coding_exception('Promotion join grant beneficiary does not match the canonical owner.');
        }
        if ($grant->get_product_sku() !== $productsku) {
            throw new \coding_exception('Promotion join grant product does not match the canonical product.');
        }
        if ($grant->get_resource_key() !== self::resource_key($promotionid, $courseid, $productid)) {
            throw new \coding_exception('Promotion join grant resource key does not match its canonical context.');
        }

        return new self(
            $grant,
            $userid,
            $promotionid,
            $courseid,
            $productid,
            $productsku,
            $ownershipsource
        );
    }

    public function get_grant(): CommerceEntitlementGrant { return $this->grant; }
    public function get_user_id(): int { return $this->userid; }
    public function get_promotion_id(): int { return $this->promotionid; }
    public function get_course_id(): int { return $this->courseid; }
    public function get_product_id(): int { return $this->productid; }
    public function get_product_sku(): string { return $this->productsku; }
    public function get_ownership_source(): string { return $this->ownershipsource; }
}
