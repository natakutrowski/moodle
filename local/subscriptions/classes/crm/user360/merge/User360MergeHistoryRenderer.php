<?php
declare(strict_types=1);

namespace local_subscriptions\crm\user360\merge;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeHistoryService;
use moodle_url;

/**
 * Renders account merge history inside User360.
 */
final class User360MergeHistoryRenderer {
    public static function render(int $userid): string {
        global $DB;

        $history = (new CommerceCustomerMergeHistoryService($DB))->for_user($userid);
        if (!$history) {
            return '';
        }

        $out = '';
        foreach ($history as $merge) {
            $out .= self::merge_card($merge);
        }

        return html_writer::div($out, 'crm-user360-merge-history');
    }

    private static function merge_card(array $merge): string {
        $status = $merge['certified']
            ? get_string('user360_merge_certified', 'local_subscriptions')
            : get_string('user360_merge_completed', 'local_subscriptions');

        $badgeclass = $merge['certified'] ? 'badge bg-success' : 'badge bg-secondary';
        $out = html_writer::start_div('border rounded p-3 mb-3');
        $out .= html_writer::div(
            html_writer::span(s($status), $badgeclass)
                . html_writer::span(
                    userdate((int)$merge['timecreated'], get_string('strftimedatetimeshort', 'langconfig')),
                    'text-muted small ms-2'
                ),
            'd-flex align-items-center flex-wrap mb-2'
        );

        if ($merge['isretained']) {
            $out .= html_writer::tag(
                'strong',
                get_string('user360_merge_retained_account', 'local_subscriptions'),
                ['class' => 'd-block mb-2']
            );
            $sourceparts = [];
            foreach ($merge['sources'] as $source) {
                $label = trim((string)$source['name']);
                if ($label === '') {
                    $label = '#' . (int)$source['userid'];
                } else {
                    $label .= ' #' . (int)$source['userid'];
                }
                if ($source['email'] !== '') {
                    $label .= ' · ' . $source['email'];
                }
                $sourceparts[] = html_writer::link(
                    self::user_url((int)$source['userid']),
                    s($label)
                );
            }
            $out .= html_writer::div(
                get_string('user360_merge_absorbed_accounts', 'local_subscriptions')
                    . ' ' . implode(', ', $sourceparts),
                'mb-2'
            );
        } else {
            $targetlabel = trim((string)$merge['targetname']);
            $targetlabel = ($targetlabel !== '' ? $targetlabel . ' ' : '')
                . '#' . (int)$merge['targetuserid'];
            if ($merge['targetemail'] !== '') {
                $targetlabel .= ' · ' . $merge['targetemail'];
            }
            $out .= html_writer::div(
                get_string('user360_merge_absorbed_notice', 'local_subscriptions'),
                'fw-semibold mb-2'
            );
            $out .= html_writer::link(
                self::user_url((int)$merge['targetuserid']),
                get_string('user360_merge_open_retained', 'local_subscriptions') . ' — ' . s($targetlabel),
                ['class' => 'btn btn-sm btn-outline-primary mb-3']
            );
        }

        $transfercount = array_sum(array_map('intval', $merge['transfers']));
        $out .= html_writer::div(
            get_string(
                'user360_merge_summary',
                'local_subscriptions',
                (object)[
                    'transfers' => $transfercount,
                    'decisions' => (int)$merge['manualdecisions'],
                    'checks' => (int)$merge['certificationchecks'],
                ]
            ),
            'small mb-2'
        );

        if ($merge['actorname'] !== '') {
            $out .= html_writer::div(
                get_string('user360_merge_performed_by', 'local_subscriptions', s($merge['actorname'])),
                'small text-muted'
            );
        }
        $out .= html_writer::div(
            get_string('user360_merge_audit_reference', 'local_subscriptions', s($merge['mergeuuid'])),
            'small text-muted'
        );

        if (!empty($merge['identitytransfer']) && is_array($merge['identitytransfer'])) {
            $identity = $merge['identitytransfer'];
            $out .= html_writer::div(
                get_string(
                    'user360_merge_identity_transfer',
                    'local_subscriptions',
                    (object)[
                        'oldemail' => (string)($identity['target_before_email'] ?? ''),
                        'newemail' => (string)($identity['target_after_email'] ?? ''),
                        'sourceuserid' => (int)($identity['sourceuserid'] ?? 0),
                    ]
                ),
                'alert alert-info py-2 small mt-3'
            );
        }

        if ($merge['transfers']) {
            $details = '';
            foreach ($merge['transfers'] as $key => $count) {
                if ((int)$count === 0) {
                    continue;
                }
                $details .= html_writer::tag(
                    'li',
                    s(self::transfer_label((string)$key)) . ' : ' . (int)$count
                );
            }
            if ($details !== '') {
                $out .= html_writer::tag(
                    'details',
                    html_writer::tag(
                        'summary',
                        get_string('user360_merge_view_details', 'local_subscriptions'),
                        ['class' => 'mt-3']
                    ) . html_writer::tag('ul', $details, ['class' => 'mt-2 mb-0']),
                    ['class' => 'small']
                );
            }
        }

        $out .= html_writer::end_div();
        return $out;
    }

    private static function user_url(int $userid): moodle_url {
        return new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $userid]);
    }

    private static function transfer_label(string $key): string {
        $known = [
            'suspendedaccounts' => get_string('user360_merge_transfer_accounts', 'local_subscriptions'),
            'notes' => get_string('user360_merge_transfer_notes', 'local_subscriptions'),
            'crmscores' => get_string('user360_merge_transfer_scores', 'local_subscriptions'),
            'inboxcontacts' => get_string('user360_merge_transfer_inbox', 'local_subscriptions'),
            'tags' => get_string('user360_merge_transfer_tags', 'local_subscriptions'),
            'tagsdeduplicated' => get_string('user360_merge_transfer_tags_deduplicated', 'local_subscriptions'),
        ];
        if (isset($known[$key])) {
            return $known[$key];
        }
        if (str_starts_with($key, 'learning_')) {
            return get_string('user360_merge_transfer_learning', 'local_subscriptions')
                . ' — ' . str_replace('_', ' ', substr($key, 9));
        }
        if (str_starts_with($key, 'legacy')) {
            return get_string('user360_merge_transfer_legacy', 'local_subscriptions')
                . ' — ' . str_replace('_', ' ', $key);
        }
        if (str_starts_with($key, 'native') || str_contains($key, 'commerce')) {
            return get_string('user360_merge_transfer_commerce', 'local_subscriptions')
                . ' — ' . str_replace('_', ' ', $key);
        }
        return str_replace('_', ' ', $key);
    }
}
