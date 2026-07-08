<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationContext {

    public function __construct(
        public readonly string $triggerkey,
        public readonly int $userid = 0,
        public readonly string $entitytype = '',
        public readonly int $entityid = 0,
        public readonly int $actorid = 0,
        public readonly int $timecreated = 0,
        private readonly array $data = []
    ) {
    }

    public static function for_user(string $triggerkey, int $userid, array $data = []): self {
        return new self($triggerkey, $userid, '', 0, 0, time(), $data);
    }

    public static function for_entity(
        string $triggerkey,
        string $entitytype,
        int $entityid,
        int $userid = 0,
        array $data = []
    ): self {
        return new self($triggerkey, $userid, $entitytype, $entityid, 0, time(), $data);
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    public function data(): array {
        return $this->data;
    }

    public function with(string $key, mixed $value): self {
        $data = $this->data;
        $data[$key] = $value;

        return new self(
            $this->triggerkey,
            $this->userid,
            $this->entitytype,
            $this->entityid,
            $this->actorid,
            $this->timecreated,
            $data
        );
    }

    public static function for_user_action(
        string $triggerkey,
        int $userid,
        int $actorid = 0,
        array $data = []
    ): self {
        return new self($triggerkey, $userid, AutomationEntityTypes::USER, $userid, $actorid, time(), $data);
    }

}