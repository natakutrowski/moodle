<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxUserMatchRepository;

final class InboxContactReconciliationService {

    public function __construct(
        private readonly InboxContactRepository $contacts,
        private readonly InboxUserMatchRepository $matches,
        private readonly InboxUserMatcher $matcher
    ) {
    }

    public function reconcile_pending(
        int $limit = 500
    ): array {
        $processed = 0;
        $errors = 0;

        foreach (
            $this->contacts->get_reconcilable($limit)
            as $contact
        ) {
            try {
                $this->matcher->reconcile($contact);
                $processed++;
            } catch (\Throwable $exception) {
                $errors++;

                debugging(
                    'CRM Inbox contact reconciliation failed: ' .
                    $exception->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
        ];
    }

    public function reconcile_user(
        int $userid
    ): void {
        $email = $this->matches->get_user_email(
            $userid
        );

        if ($email === null) {
            return;
        }

        $normalizedemail = \core_text::strtolower(
            trim($email)
        );

        foreach (
            $this->contacts
                ->get_automatic_matches_for_user($userid)
            as $matchedcontact
        ) {
            if (
                $matchedcontact->normalizedemail !==
                $normalizedemail
            ) {
                $this->contacts->clear_automatic_match(
                    $matchedcontact->id
                );
            }
        }

        $contact = $this->contacts->find_by_email(
            $normalizedemail
        );

        if (!$contact || !$contact->can_be_reconciled()) {
            return;
        }

        $this->matcher->reconcile($contact);
    }
}