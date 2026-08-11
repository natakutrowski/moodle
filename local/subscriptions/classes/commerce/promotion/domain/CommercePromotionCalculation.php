<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\domain;

defined('MOODLE_INTERNAL') || die();

/** Result of evaluating all promotions for one cart calculation. */
final class CommercePromotionCalculation {
    /**
     * @param CommercePromotionAdjustment[] $adjustments
     * @param array<int, array{code:string,reason:string,context:array}> $rejections
     */
    public function __construct(
        private readonly array $adjustments,
        private readonly array $rejections = []
    ) {
        foreach ($adjustments as $adjustment) {
            if (!$adjustment instanceof CommercePromotionAdjustment) {
                throw new \coding_exception('Invalid Commerce promotion adjustment collection.');
            }
        }
    }

    /** @return CommercePromotionAdjustment[] */
    public function get_adjustments(): array { return $this->adjustments; }
    public function get_rejections(): array { return $this->rejections; }
}
