<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when a normal CRM User360 cannot be loaded.
 */
final class UserProfileNotFoundException extends \RuntimeException {

    public function __construct(
        private readonly int $userid,
        private readonly string $status =
            UserProfileLookupResult::STATUS_MISSING
    ) {
        parent::__construct(
            sprintf(
                'CRM user profile unavailable for Moodle user ID %d (%s)',
                $userid,
                $status
            )
        );
    }

    /**
     * Returns the requested Moodle user ID.
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Returns the profile lookup state.
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Whether the Moodle user has been deleted.
     */
    public function is_deleted(): bool {
        return $this->status ===
            UserProfileLookupResult::STATUS_DELETED;
    }

    /**
     * Whether no Moodle user record exists.
     */
    public function is_missing(): bool {
        return $this->status ===
            UserProfileLookupResult::STATUS_MISSING;
    }
}