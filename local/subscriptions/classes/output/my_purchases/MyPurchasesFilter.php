<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_purchases;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable filter state for "Mes achats".
 *
 * I4.1 introduces the contract without changing the visible behaviour. Future
 * I4 sub-phases can add type, status, period or search filters here without
 * growing the HTTP controller again.
 */
final class MyPurchasesFilter {
    public function __construct(
        private readonly ?string $type = null,
        private readonly ?string $status = null,
        private readonly ?string $query = null
    ) {
    }

    public static function from_request(): self {
        $type = optional_param('type', '', PARAM_ALPHA);
        $status = optional_param('status', '', PARAM_ALPHANUMEXT);
        $query = optional_param('q', '', PARAM_TEXT);

        return new self(
            self::normalise($type),
            self::normalise($status),
            self::normalise($query)
        );
    }

    public function has_active_filters(): bool {
        return $this->type !== null || $this->status !== null || $this->query !== null;
    }

    public function get_type(): ?string {
        return $this->type;
    }

    public function get_status(): ?string {
        return $this->status;
    }

    public function get_query(): ?string {
        return $this->query;
    }

    private static function normalise(string $value): ?string {
        $value = trim($value);
        return $value !== '' ? $value : null;
    }
}
