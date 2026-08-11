<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Input filters for a public catalogue listing. */
final class CommerceStorefrontListFilter {
    public function __construct(
        private readonly string $language,
        private readonly ?string $currency = null,
        private readonly ?string $type = null,
        private readonly string $query = ''
    ) {
    }

    public function get_language(): string {
        return strtolower(trim($this->language));
    }

    public function get_currency(): ?string {
        $currency = strtoupper(trim((string)$this->currency));
        return $currency !== '' ? $currency : null;
    }

    public function get_type(): ?string {
        $type = trim((string)$this->type);
        return $type !== '' ? $type : null;
    }

    public function get_query(): string {
        return trim($this->query);
    }
}
