<?php

namespace local_subscriptions\crm\help\guides;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

final class HelpGuideStep {

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $icon,
        public readonly ?moodle_url $url = null,
        public readonly ?string $actionlabel = null
    ) {
    }

    public function has_action(): bool {
        return $this->url !== null &&
            $this->actionlabel !== null &&
            $this->actionlabel !== '';
    }
}