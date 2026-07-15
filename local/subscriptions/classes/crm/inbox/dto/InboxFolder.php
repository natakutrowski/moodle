<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxFolder {

    public function __construct(
        public readonly string $name,
        public readonly string $delimiter,
        public readonly array $attributes,
        public readonly ?string $specialuse = null
    ) {
    }
}