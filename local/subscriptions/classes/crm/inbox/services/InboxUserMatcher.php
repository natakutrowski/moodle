<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxContact;
use local_subscriptions\crm\inbox\domain\InboxContactMatch;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxUserMatchRepository;

final class InboxUserMatcher {

    public function __construct(
        private readonly InboxContactRepository $contacts,
        private readonly InboxUserMatchRepository $matches
    ) {
    }

    public function reconcile(
        InboxContact $contact
    ): void {
        if (!$contact->can_be_reconciled()) {
            return;
        }

        $userids =
            $this->matches->find_userids_by_email(
                $contact->normalizedemail
            );

        if (count($userids) === 1) {
            $this->contacts->set_automatic_match(
                $contact->id,
                $userids[0],
                InboxContactMatch::SOURCE_MOODLE_EMAIL,
                1.0
            );

            return;
        }

        if (count($userids) > 1) {
            $this->contacts->mark_ambiguous(
                $contact->id,
                InboxContactMatch::SOURCE_MOODLE_EMAIL
            );

            return;
        }

        $purchaseuserids =
            $this->matches
                ->find_userids_by_purchase_email(
                    $contact->normalizedemail
                );

        $purchaseuserids = array_values(
            array_unique($purchaseuserids)
        );

        if (count($purchaseuserids) === 1) {
            $this->contacts->set_automatic_match(
                $contact->id,
                $purchaseuserids[0],
                InboxContactMatch::SOURCE_PURCHASE_EMAIL,
                0.95
            );

            return;
        }

        if (count($purchaseuserids) > 1) {
            $this->contacts->mark_ambiguous(
                $contact->id,
                InboxContactMatch::SOURCE_PURCHASE_EMAIL
            );
        }
    }

}