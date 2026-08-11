<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when no template is registered for a transactional mail type.
 */
final class CommerceMailTemplateNotFoundException extends \RuntimeException {
}
