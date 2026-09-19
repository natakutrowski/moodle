<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\merchant;

use local_subscriptions\commerce\legal\entity\CommerceLegalEntity;

defined('MOODLE_INTERNAL') || die();

/** Deterministic and auditable merchant/legal-entity resolution result. */
final class CommerceMerchantResolutionResult {
    public function __construct(
        private readonly CommerceLegalEntity $entity,
        private readonly string $marketcountry,
        private readonly string $rule
    ) {
        if (trim($rule) === '') {
            throw new \coding_exception('Commerce merchant resolution rule is required.');
        }
    }

    public function get_entity(): CommerceLegalEntity {
        return $this->entity;
    }

    public function get_entity_key(): string {
        return $this->entity->get_key();
    }

    public function get_market_country(): string {
        return strtoupper(trim($this->marketcountry));
    }

    public function get_rule(): string {
        return trim($this->rule);
    }

    /** @return array<string, string> Audit-friendly decision metadata. */
    public function to_audit_array(): array {
        return [
            'legal_entity_key' => $this->get_entity_key(),
            'market_country' => $this->get_market_country(),
            'rule' => $this->get_rule(),
        ];
    }
}
