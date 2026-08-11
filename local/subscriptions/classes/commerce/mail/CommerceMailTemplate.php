<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Renders one transactional Commerce mail intention.
 */
interface CommerceMailTemplate {

    public function get_type(): string;

    public function render(CommerceMailRequest $request): CommerceMailMessage;
}
