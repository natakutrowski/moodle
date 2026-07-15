<?php

namespace local_subscriptions\crm\inbox\ai\safety;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;

final class InboxAiSafetyPolicy {

    private const FORBIDDEN_AUTOMATIC_ACTIONS = [
        'refund',
        'grant_access',
        'cancel_payment',
        'suspend_account',
        'delete_account',
        'send_reply',
    ];

    public function evaluate(
        string $capability,
        array $context = []
    ): InboxAiSafetyDecision {
        if (
            !InboxAiCapability::is_valid(
                $capability
            )
        ) {
            return new InboxAiSafetyDecision(
                false,
                [],
                'Unsupported AI capability.'
            );
        }

        $requestedaction = trim(
            (string)(
                $context['requestedaction']
                ?? ''
            )
        );

        if (
            $requestedaction !== '' &&
            in_array(
                $requestedaction,
                self::FORBIDDEN_AUTOMATIC_ACTIONS,
                true
            )
        ) {
            return new InboxAiSafetyDecision(
                false,
                [],
                'The requested action requires a human decision.'
            );
        }

        $warnings = [];

        if (
            $capability ===
            InboxAiCapability::REPLY_SUGGESTION
        ) {
            $warnings[] =
                'The suggested reply must be reviewed before sending.';
        }

        return new InboxAiSafetyDecision(
            true,
            $warnings
        );
    }
}