<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

/** Decision returned before any owner-only promotion join purchase is exposed. */
final class CommercePedagogicalPromotionJoinEligibility {
    public const AUTHENTICATION_REQUIRED = 'authentication_required';
    public const OWNERSHIP_REQUIRED = 'ownership_required';
    public const NO_PROMOTION = 'no_pedagogical_promotion';
    public const UNSUPPORTED_PRODUCT = 'unsupported_product';
    public const COURSE_MISMATCH = 'promotion_course_mismatch';
    public const ALREADY_JOINED = 'already_joined';
    public const JOIN_IN_PROGRESS = 'join_in_progress';

    public function __construct(
        private readonly bool $eligible,
        private readonly ?string $reason,
        private readonly string $ownershipsource,
        private readonly ?CommercePedagogicalPromotionJoinContext $context
    ) {
        if ($eligible && ($reason !== null || $context === null)) {
            throw new \coding_exception('Eligible promotion join requires a context and no blocking reason.');
        }
    }

    public static function denied(
        string $reason,
        string $ownershipsource = 'none',
        ?CommercePedagogicalPromotionJoinContext $context = null
    ): self {
        return new self(false, trim($reason), trim($ownershipsource) ?: 'none', $context);
    }

    public static function allowed(CommercePedagogicalPromotionJoinContext $context): self {
        return new self(true, null, $context->get_ownership_source(), $context);
    }

    public function is_eligible(): bool { return $this->eligible; }
    public function get_reason(): ?string { return $this->reason; }
    public function get_ownership_source(): string { return $this->ownershipsource; }
    public function get_context(): ?CommercePedagogicalPromotionJoinContext { return $this->context; }

    /** @return array<string,mixed> */
    public function to_array(): array {
        return [
            'eligible' => $this->eligible,
            'reason' => $this->reason,
            'ownershipsource' => $this->ownershipsource,
            'context' => $this->context?->to_array(),
        ];
    }
}
