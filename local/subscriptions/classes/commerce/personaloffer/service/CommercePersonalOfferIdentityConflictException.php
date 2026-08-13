<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

/**
 * A valid Personal Offer was opened while another Moodle identity is authenticated.
 *
 * The offer stays valid. This exception exists only so the public boundary can render
 * a safe account-switch UX without weakening checkout identity enforcement.
 */
final class CommercePersonalOfferIdentityConflictException extends \moodle_exception {
    public function __construct(
        public readonly string $beneficiaryemail,
        public readonly string $currentemail
    ) {
        parent::__construct('commerce_personal_offer_identity_mismatch', 'local_subscriptions');
    }
}
