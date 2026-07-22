<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents the result of resolving a Moodle user for the CRM User360.
 */
final class UserProfileLookupResult {

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_MISSING = 'missing';

    private function __construct(
        private readonly int $userid,
        private readonly string $status,
        private readonly ?\stdClass $user
    ) {
    }

    /**
     * Creates a result for an active Moodle user.
     */
    public static function active(
        \stdClass $user
    ): self {
        return new self(
            (int)$user->id,
            self::STATUS_ACTIVE,
            $user
        );
    }

    /**
     * Creates a result for a deleted Moodle user.
     */
    public static function deleted(
        \stdClass $user
    ): self {
        return new self(
            (int)$user->id,
            self::STATUS_DELETED,
            $user
        );
    }

    /**
     * Creates a result when no Moodle user record exists.
     */
    public static function missing(
        int $userid
    ): self {
        return new self(
            $userid,
            self::STATUS_MISSING,
            null
        );
    }

    /**
     * Returns the requested Moodle user ID.
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Returns the resolved state.
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Returns the Moodle user record when it exists.
     */
    public function get_user(): ?\stdClass {
        return $this->user;
    }

    /**
     * Whether the resolved Moodle user is active.
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Whether the resolved Moodle user has been deleted.
     */
    public function is_deleted(): bool {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Whether no Moodle user record exists.
     */
    public function is_missing(): bool {
        return $this->status === self::STATUS_MISSING;
    }
}