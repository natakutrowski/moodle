<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\selection;

defined('MOODLE_INTERNAL') || die();

/**
 * Explains why a Commerce currency was selected.
 *
 * The source is part of the domain contract: callers can persist, diagnose and
 * test currency selection without reverse-engineering resolver precedence.
 */
enum CommerceCurrencySelectionSource: string {
    case EXPLICIT = 'explicit';
    case ACTIVE_CART = 'active_cart';
    case ACTIVE_GUEST_CHECKOUT = 'active_guest_checkout';
    case USER_PREFERENCE = 'user_preference';
    case SESSION_PREFERENCE = 'session_preference';
    case MARKET_DEFAULT = 'market_default';
    case COMMERCE_DEFAULT = 'commerce_default';
}
