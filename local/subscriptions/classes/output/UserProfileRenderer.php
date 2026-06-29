<?php

namespace local_subscriptions\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use html_table;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\support\SubsPresenter;
use local_subscriptions\support\DigitalPresenter;

final class UserProfileRenderer {

    public static function render(\stdClass $profile): string {
        $user = $profile->user;

        $out = '';

        $out .= self::header($user);
        $out .= self::quick_actions($user);
        $out .= self::notes($profile);
        $out .= self::stats($profile);
        $out .= self::subscriptions($profile->subscriptions);
        $out .= self::digital_purchases($profile->digitalpayments);
        $out .= self::admin_history($profile->adminlogs ?? []);

        return $out;
    }

    private static function header(\stdClass $user): string {
        return html_writer::div(
            html_writer::tag('h3', fullname($user), ['class' => 'mb-2']) .
            html_writer::div(
                html_writer::tag('strong', get_string('email') . ': ') . s($user->email) . '<br>' .
                html_writer::tag('strong', get_string('country') . ': ') . s($user->country ?: '-') . '<br>' .
                html_writer::tag('strong', get_string('timecreated') . ': ') .
                    (!empty($user->timecreated) ? userdate($user->timecreated, '%d/%m/%Y') : '-') . '<br>' .
                html_writer::tag('strong', get_string('lastaccess') . ': ') .
                    (!empty($user->lastaccess) ? userdate($user->lastaccess, '%d/%m/%Y %H:%M') : '-')
            ) .
            html_writer::div(
                html_writer::link(
                    new moodle_url('/user/profile.php', ['id' => $user->id]),
                    get_string('view_moodle_profile', 'local_subscriptions'),
                    ['class' => 'btn btn-outline-primary me-2 mt-3']
                ) .
                html_writer::link(
                    new moodle_url(subscription_config::add_manual_subscription_page(), ['userid' => $user->id]),
                    get_string('add_subscription', 'local_subscriptions'),
                    ['class' => 'btn btn-primary mt-3']
                ),
                'crm-user-actions'
            ),
            'crm-user-header card card-body mb-4'
        );
    }

    private static function stats(\stdClass $profile): string {
        $cards = [
            [get_string('subscriptions', 'local_subscriptions'), $profile->stats->subscriptions],
            [get_string('digital_purchases', 'local_subscriptions'), $profile->stats->digitalpayments],
        ];

        $out = html_writer::start_div('row mb-4');

        foreach ($cards as [$label, $value]) {
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::tag('div', $value, ['class' => 'crm-stat-number']) .
                    html_writer::tag('div', $label, ['class' => 'text-muted']),
                    'card card-body'
                ),
                'col-md-4 mb-3'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function subscriptions(array $subscriptions): string {
        global $OUTPUT;

        $out = html_writer::tag('h4', get_string('subscriptions', 'local_subscriptions'), ['class' => 'mb-3']);

        if (!$subscriptions) {
            return $out . $OUTPUT->notification(get_string('crm_no_subscriptions', 'local_subscriptions'), 'info');
        }

        $table = new html_table();
        $table->head = [
            get_string('plan', 'local_subscriptions'),
            get_string('subscription_period', 'local_subscriptions'),
            get_string('price', 'local_subscriptions'),
            get_string('status', 'local_subscriptions'),
            get_string('actions', 'local_subscriptions'),
        ];

        foreach ($subscriptions as $sub) {
            $start = !empty($sub->start_date) ? userdate($sub->start_date, '%d/%m/%y') : '-';

            if (empty($sub->end_date) || (int)$sub->end_date > strtotime('2100-01-01')) {
                $period = $start . '<br><span class="badge bg-light text-dark border">♾️ ' .
                    get_string('unlimited', 'local_subscriptions') . '</span>';
            } else {
                $period = $start . '<br><span class="text-muted">→ ' . userdate($sub->end_date, '%d/%m/%y') . '</span>';
            }

            $price = ((float)($sub->pricepaid ?? 0) > 0)
                ? format_float((float)$sub->pricepaid, 2) . ' ' . strtoupper($sub->currency ?? '')
                : '-';

            $table->data[] = [
                format_string($sub->planname ?: get_string('unknown_plan', 'local_subscriptions')),
                $period,
                $price,
                SubsPresenter::render_status_badge($sub->status),
                html_writer::link(
                    new moodle_url(subscription_config::user_subscription_edit_page(), ['id' => $sub->id]),
                    get_string('edit'),
                    ['class' => 'btn btn-sm btn-outline-primary']
                ),
            ];
        }

        return $out . html_writer::table($table);
    }

    private static function digital_purchases(array $digitalpayments): string {
        global $OUTPUT;

        $out = html_writer::tag('h4', get_string('digital_purchases', 'local_subscriptions'), ['class' => 'mt-5 mb-3']);

        if (!$digitalpayments) {
            return $out . $OUTPUT->notification(get_string('crm_no_digital_purchases', 'local_subscriptions'), 'info');
        }

        $table = new html_table();
        $table->head = [
            get_string('product', 'local_subscriptions'),
            get_string('email'),
            get_string('price', 'local_subscriptions'),
            get_string('status', 'local_subscriptions'),
            get_string('creation_date', 'local_subscriptions'),
        ];

        foreach ($digitalpayments as $payment) {
            $price = ((float)($payment->price ?? 0) > 0)
                ? format_float((float)$payment->price, 2) . ' ' . strtoupper($payment->currency ?? '')
                : '-';

            $table->data[] = [
                format_string($payment->productname ?: '-'),
                s($payment->email),
                $price,
                DigitalPresenter::render_status_badge($payment->status),
                !empty($payment->creation_date) ? userdate($payment->creation_date, '%d/%m/%y') : '-',
            ];
        }

        return $out . html_writer::table($table);
    }

    private static function quick_actions(\stdClass $user): string {
        $items = [];

        $items[] = html_writer::link(
            new moodle_url(subscription_config::add_manual_subscription_page(), ['userid' => $user->id]),
            '➕ ' . get_string('add_subscription', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-primary']
        );

        $items[] = html_writer::link(
            new moodle_url('/local/subscriptions/admin/users/email.php', ['id' => $user->id]),
            '✉️ ' . get_string('crm_send_email', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );

        $items[] = html_writer::link(
            new moodle_url('/local/subscriptions/admin/users/reset_password.php', ['id' => $user->id]),
            '🔑 ' . get_string('crm_reset_password', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );

        return html_writer::div(
            html_writer::tag('h4', get_string('crm_quick_actions', 'local_subscriptions'), ['class' => 'h5 mb-3']) .
            html_writer::div(implode(' ', $items), 'crm-quick-actions-buttons'),
            'crm-quick-actions card card-body mb-4'
        );
    }

    private static function admin_history(array $logs): string {
        global $DB, $OUTPUT;

        $out = html_writer::tag('h4', get_string('crm_admin_history', 'local_subscriptions'), [
            'class' => 'mt-5 mb-3',
        ]);

        if (!$logs) {
            return $out . $OUTPUT->notification(get_string('crm_no_admin_history', 'local_subscriptions'), 'info');
        }

        $actorids = array_values(array_unique(array_filter(array_map(function($log) {
            return (int)$log->actorid;
        }, $logs))));

        $actors = [];
        if ($actorids) {
            [$insql, $params] = $DB->get_in_or_equal($actorids, SQL_PARAMS_NAMED);
            $actors = $DB->get_records_select('user', "id $insql", $params);
        }

        $table = new html_table();
        $table->head = [
            get_string('date', 'local_subscriptions'),
            get_string('admin_action', 'local_subscriptions'),
            get_string('admin_actor', 'local_subscriptions'),
            get_string('details', 'local_subscriptions'),
        ];

        foreach ($logs as $log) {
            $details = '-';

            if (!empty($log->details)) {
                $decoded = json_decode($log->details, true);
                if (is_array($decoded) && $decoded) {
                    $parts = [];
                    foreach ($decoded as $key => $value) {
                        if (is_bool($value)) {
                            $value = $value ? 'true' : 'false';
                        } else if (is_array($value)) {
                            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }

                        $parts[] = s($key) . ': ' . s((string)$value);
                    }
                    $details = implode('<br>', $parts);
                }
            }

            $actor = $actors[$log->actorid] ?? null;

            $table->data[] = [
                !empty($log->timecreated) ? userdate($log->timecreated, '%d/%m/%y %H:%M') : '-',
                self::format_admin_action($log->action),
                $actor ? fullname($actor) : '-',
                $details,
            ];
        }

        return $out . html_writer::table($table);
    }

    private static function format_admin_action(string $action): string {
        $key = 'adminlog_' . str_replace('.', '_', $action);

        if (get_string_manager()->string_exists($key, 'local_subscriptions')) {
            return get_string($key, 'local_subscriptions');
        }

        return s($action);
    }

    private static function notes(\stdClass $profile): string {
        global $DB;

        $user = $profile->user;
        $notes = $profile->notes ?? [];

        $out = html_writer::start_div('crm-notes card card-body mb-4');
        $out .= html_writer::tag('h4', get_string('crm_internal_notes', 'local_subscriptions'), ['class' => 'h5 mb-3']);

        $out .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new moodle_url('/local/subscriptions/admin/users/add_note.php'),
            'class' => 'mb-4',
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $user->id,
        ]);

        $out .= html_writer::tag('textarea', '', [
            'name' => 'note',
            'class' => 'form-control mb-2',
            'rows' => 3,
            'placeholder' => get_string('crm_note_placeholder', 'local_subscriptions'),
            'required' => 'required',
        ]);

        $out .= html_writer::tag('button', get_string('crm_add_note', 'local_subscriptions'), [
            'type' => 'submit',
            'class' => 'btn btn-sm btn-primary',
        ]);

        $out .= html_writer::end_tag('form');

        if (!$notes) {
            $out .= html_writer::div(get_string('crm_no_notes', 'local_subscriptions'), 'text-muted');
            $out .= html_writer::end_div();
            return $out;
        }

        $authorids = array_values(array_unique(array_map(fn($note) => (int)$note->authorid, $notes)));
        [$insql, $params] = $DB->get_in_or_equal($authorids, SQL_PARAMS_NAMED);
        $authors = $DB->get_records_select('user', "id $insql", $params);

        foreach ($notes as $note) {
            $author = $authors[$note->authorid] ?? null;

            $out .= html_writer::div(
                html_writer::div(
                    ($author ? fullname($author) : '-') .
                    ' · ' .
                    userdate($note->timecreated, '%d/%m/%y %H:%M'),
                    'small text-muted mb-1'
                ) .
                html_writer::div(format_text($note->note, FORMAT_PLAIN)),
                'crm-note-item border rounded p-3 mb-2'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

}