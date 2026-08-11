<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\accessscope;

defined('MOODLE_INTERNAL') || die();

/** Read-only relation between a sellable plan and its reusable access scope. */
final class CommerceAccessScopeRelation {
    public function __construct(
        private readonly ?int $planid,
        private readonly ?string $planname,
        private readonly ?int $scopeid,
        private readonly ?string $scopename,
        private readonly array $courses,
        private readonly string $source
    ) {
    }

    public function get_plan_id(): ?int { return $this->planid; }
    public function get_plan_name(): ?string { return $this->planname; }
    public function get_scope_id(): ?int { return $this->scopeid; }
    public function get_scope_name(): ?string { return $this->scopename; }
    public function get_courses(): array { return $this->courses; }
    public function get_source(): string { return $this->source; }
    public function is_linked(): bool { return $this->planid !== null && $this->scopeid !== null; }
}
