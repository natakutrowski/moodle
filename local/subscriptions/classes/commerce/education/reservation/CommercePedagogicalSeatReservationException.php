<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\reservation;

defined('MOODLE_INTERNAL') || die();

final class CommercePedagogicalSeatReservationException extends \RuntimeException {
    public function __construct(
        private readonly string $codekey,
        string $message
    ) {
        parent::__construct($message);
    }

    public function get_code_key(): string {
        return $this->codekey;
    }
}
