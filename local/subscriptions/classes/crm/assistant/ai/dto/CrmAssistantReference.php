<?php

namespace local_subscriptions\crm\assistant\ai\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * CRM object referenced by an assistant answer.
 */
final class CrmAssistantReference {

    public const USER = 'user';
    public const RECOMMENDATION = 'recommendation';
    public const WORK_ITEM = 'work_item';
    public const INBOX_THREAD = 'inbox_thread';
    public const PAYMENT = 'payment';

    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly string $label,
        public readonly ?string $reason = null
    ) {
        if (
            !in_array(
                $this->type,
                self::types(),
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid CRM Assistant reference type.'
            );
        }

        if ($this->id <= 0) {
            throw new \InvalidArgumentException(
                'CRM Assistant reference ID must be greater than zero.'
            );
        }

        if (trim($this->label) === '') {
            throw new \InvalidArgumentException(
                'CRM Assistant reference label is required.'
            );
        }
    }

    /**
     * @return string[]
     */
    public static function types(): array {
        return [
            self::USER,
            self::RECOMMENDATION,
            self::WORK_ITEM,
            self::INBOX_THREAD,
            self::PAYMENT,
        ];
    }

    public function to_object(): \stdClass {
        return (object)[
            'type' => $this->type,
            'id' => $this->id,
            'label' => $this->label,
            'reason' => $this->reason,
        ];
    }
}