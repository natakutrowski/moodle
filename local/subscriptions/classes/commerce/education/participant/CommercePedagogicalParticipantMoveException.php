<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\participant;

defined('MOODLE_INTERNAL') || die();

/**
 * Expected business rejection for an explicit participant group move.
 */
final class CommercePedagogicalParticipantMoveException extends \RuntimeException {
    public const GROUP_FULL = 'group_full';

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
