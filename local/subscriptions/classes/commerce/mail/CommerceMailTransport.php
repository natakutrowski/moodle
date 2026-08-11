<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Transport boundary for transactional Commerce messages.
 */
interface CommerceMailTransport {

    public function send(CommerceMailMessage $message): void;
}
