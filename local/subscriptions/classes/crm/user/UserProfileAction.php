<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

final class UserProfileAction {

    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $url,
        public readonly string $icon = 'action',
        public readonly string $style = 'secondary',
        public readonly bool $danger = false
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'label' => $this->label,
            'url' => $this->url,
            'icon' => $this->icon,
            'style' => $this->style,
            'danger' => $this->danger,
        ];
    }
}