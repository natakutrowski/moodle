<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable CRM object targeted by a recommendation.
 */
final class RecommendationTarget {

    public const USER = 'user';
    public const INBOX_THREAD = 'inbox_thread';
    public const INBOX_CONTACT = 'inbox_contact';
    public const WORK_ITEM = 'work_item';
    public const PAYMENT = 'payment';
    public const SUBSCRIPTION = 'subscription';
    public const DIGITAL_PURCHASE = 'digital_purchase';

    /**
     * @param string $objecttype Stable CRM target type.
     * @param int $objectid Database identifier of the target object.
     */
    public function __construct(
        public readonly string $objecttype,
        public readonly int $objectid
    ) {
        if (!self::is_valid_type($this->objecttype)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation target type.'
            );
        }

        if ($this->objectid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation target object ID must be greater than zero.'
            );
        }
    }

    /**
     * Return all supported target types.
     *
     * @return string[]
     */
    public static function types(): array {
        return [
            self::USER,
            self::INBOX_THREAD,
            self::INBOX_CONTACT,
            self::WORK_ITEM,
            self::PAYMENT,
            self::SUBSCRIPTION,
            self::DIGITAL_PURCHASE,
        ];
    }

    /**
     * Check whether a target type is supported.
     */
    public static function is_valid_type(string $objecttype): bool {
        return in_array($objecttype, self::types(), true);
    }

    /**
     * Stable identity used by deduplication and correlation services.
     */
    public function identity(): string {
        return $this->objecttype . ':' . $this->objectid;
    }

    /**
     * Serialize the target for DTOs, APIs and renderers.
     */
    public function to_object(): \stdClass {
        return (object)[
            'objecttype' => $this->objecttype,
            'objectid' => $this->objectid,
            'identity' => $this->identity(),
        ];
    }
}