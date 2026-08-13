<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutCrmService;

final class User360GuestCheckoutRecoveryRenderer {
    public static function render(int $userid): string {
        $candidate = CommerceUnfinishedGuestCheckoutCrmService::create()->candidate_for_user($userid);
        if ($candidate === null) {
            return '';
        }

        $queueurl = new \moodle_url(
            '/local/subscriptions/admin/commerce/unfinished-checkouts/index.php',
            ['userid' => $userid]
        );

        $badges = [
            'provider_paid_pending' => 'danger',
            'multiple_pending' => 'warning',
            'pending_purchase' => 'warning',
            'stuck_identity' => 'warning',
            'provisional_no_purchase' => 'secondary',
        ];
        $class = $badges[$candidate['classification']] ?? 'secondary';

        $out = \html_writer::div(
            \html_writer::span(
                get_string(
                    'commerce_guest_crm_class_' . $candidate['classification'],
                    'local_subscriptions'
                ),
                'badge text-bg-' . $class
            ),
            'mb-2'
        );

        $out .= \html_writer::tag(
            'dl',
            \html_writer::tag('dt', get_string('commerce_guest_crm_source_session', 'local_subscriptions')) .
            \html_writer::tag('dd', '#' . (int)$candidate['source_session_id'] . ' · ' . s((string)$candidate['source_status'])) .
            \html_writer::tag('dt', get_string('commerce_guest_crm_resume_purchase', 'local_subscriptions')) .
            \html_writer::tag('dd', s((string)($candidate['purchase_reference'] ?: '—'))) .
            \html_writer::tag('dt', get_string('commerce_guest_crm_stuck_sessions', 'local_subscriptions')) .
            \html_writer::tag('dd', (string)(int)$candidate['stuck_sessions']),
            ['class' => 'mb-3']
        );

        $out .= \html_writer::link(
            $queueurl,
            get_string('commerce_guest_crm_open_case', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary btn-sm']
        );

        return $out;
    }
}
