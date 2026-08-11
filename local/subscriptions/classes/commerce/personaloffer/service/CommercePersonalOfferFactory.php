<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\security\CommercePersonalOfferTokenRepository;

final class CommercePersonalOfferFactory {
    public static function create(?\moodle_database $db = null): CommercePersonalOfferService {
        global $DB;
        $db ??= $DB;
        return new CommercePersonalOfferService(
            $db,
            new MoodleCommercePersonalOfferRepository($db),
            new CommercePersonalOfferTokenRepository($db)
        );
    }
}
