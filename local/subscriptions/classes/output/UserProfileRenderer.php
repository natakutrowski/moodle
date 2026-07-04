<?php

namespace local_subscriptions\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use html_table;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\support\SubsPresenter;
use local_subscriptions\support\DigitalPresenter;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;

final class UserProfileRenderer {

    public static function render(\stdClass $profile): string {
        $user = $profile->user;

        $out = '';

        $out .= self::header($user);
        $out .= self::quick_actions($user);
        $out .= self::timeline($profile);
        $out .= self::stats($profile);
        $out .= self::courses($profile->courses ?? []);
        $out .= self::subscriptions($profile->subscriptions);
        $out .= self::digital_purchases($profile->digitalpayments);

        return $out;
    }

    private static function header(\stdClass $user): string {
        return html_writer::div(
            html_writer::tag('h3', fullname($user), ['class' => 'mb-2']) .
            html_writer::div(
                html_writer::tag('strong', get_string('email') . ': ') . s($user->email) . '<br>' .
                html_writer::tag('strong', get_string('country') . ': ') . s($user->country ?: '-') . '<br>' .
                html_writer::tag('strong', get_string('timecreated') . ': ') .
                    (!empty($user->timecreated) ? AdminFormatter::date((int)$user->timecreated) : '-') . '<br>' .
                html_writer::tag('strong', get_string('lastaccess') . ': ') .
                    (!empty($user->lastaccess) ? AdminFormatter::datetime((int)$user->lastaccess) : '-')
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

        $out = html_writer::tag('h4', get_string('subscriptions', 'local_subscriptions'), ['class' => 'mt-5 mb-3']);

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
            $start = !empty($sub->start_date) ? AdminFormatter::date((int)$sub->start_date) : '-';

            if (empty($sub->end_date) || (int)$sub->end_date > strtotime('2100-01-01')) {
                $period = $start . '<br><span class="badge bg-light text-dark border">♾️ ' .
                    get_string('unlimited', 'local_subscriptions') . '</span>';
            } else {
                $period = $start . '<br><span class="text-muted">→ ' . AdminFormatter::date((int)$sub->end_date) . '</span>';
            }

            $price = ((float)($sub->pricepaid ?? 0) > 0)
                ? AdminFormatter::price($sub->pricepaid ?? 0, $sub->currency ?? '')
                : '-';

            $table->data[] = [
                format_string($sub->planname ?: get_string('unknown_plan', 'local_subscriptions')),
                $period,
                $price,
                SubsPresenter::render_status_badge($sub->status),
                AdminEntityLinks::subscription(
                    (int)$sub->id,
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
                ? AdminFormatter::price($payment->price ?? 0, $payment->currency ?? '')
                : '-';

            $table->data[] = [
                format_string($payment->productname ?: '-'),
                s($payment->email),
                $price,
                DigitalPresenter::render_status_badge($payment->status),
                !empty($payment->creation_date) ? AdminFormatter::date((int)$payment->creation_date) : '-',
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
            new moodle_url(subscription_config::admin_user_email_page(), ['id' => $user->id]),
            '✉️ ' . get_string('crm_send_email', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_reset_password_page(), ['id' => $user->id]),
            '🔑 ' . get_string('crm_reset_password', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );

        $noteform = html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new moodle_url(subscription_config::admin_user_add_note_page()),
            'class' => 'crm-quick-note-form mt-3',
        ]);

        $noteform .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $noteform .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $user->id,
        ]);

        $noteform .= html_writer::tag('textarea', '', [
            'name' => 'note',
            'class' => 'form-control mb-2',
            'rows' => 2,
            'placeholder' => get_string('crm_note_placeholder', 'local_subscriptions'),
            'required' => 'required',
        ]);

        $noteform .= html_writer::tag('button', '📝 ' . get_string('crm_add_note', 'local_subscriptions'), [
            'type' => 'submit',
            'class' => 'btn btn-sm btn-outline-primary',
        ]);

        $noteform .= html_writer::end_tag('form');

        return html_writer::div(
            html_writer::tag('h4', get_string('crm_quick_actions', 'local_subscriptions'), ['class' => 'h5 mb-3']) .
            html_writer::div(implode(' ', $items), 'crm-quick-actions-buttons') .
            $noteform,
            'crm-quick-actions card card-body mb-4'
        );

    }


    private static function timeline(\stdClass $profile): string {
        global $DB, $OUTPUT;

        $items = $profile->timeline ?? [];

        $out = html_writer::start_div('crm-timeline card card-body mb-4');
        $out .= html_writer::tag('h4', get_string('crm_timeline', 'local_subscriptions'), [
            'class' => 'h5 mb-3',
        ]);

        if (!$items) {
            $out .= html_writer::div(get_string('crm_timeline_empty', 'local_subscriptions'), 'text-muted');
            $out .= html_writer::end_div();
            return $out;
        }

        $actorids = array_values(array_unique(array_filter(array_map(function($item) {
            return (int)$item->actorid;
        }, $items))));

        $actors = [];
        if ($actorids) {
            [$insql, $params] = $DB->get_in_or_equal($actorids, SQL_PARAMS_NAMED);
            $actors = $DB->get_records_select('user', "id $insql", $params);
        }

        $out .= html_writer::start_div('crm-timeline-list');

        foreach ($items as $item) {
            $actor = $actors[$item->actorid] ?? null;

            $meta = AdminFormatter::datetime((int)$item->timecreated);

            if ($actor) {
                $meta .= ' · ' . fullname($actor);
            }

            $body = self::render_timeline_body($item);

            $out .= html_writer::div(
                html_writer::div($item->icon, 'crm-timeline-icon') .
                html_writer::div(
                    html_writer::div(
                        html_writer::tag('strong', s($item->title)) .
                        html_writer::span($meta, 'crm-timeline-meta'),
                        'crm-timeline-head'
                    ) .
                    ($body !== '' ? html_writer::div($body, 'crm-timeline-body') : ''),
                    'crm-timeline-content'
                ),
                'crm-timeline-item'
            );
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_timeline_body(\stdClass $item): string {
        $details = $item->detailsraw ?? [];
        $title = (string)($item->title ?? '');

        if (($item->objecttype ?? '') === 'subscription') {
            return self::render_subscription_timeline_body($details);
        }

        if (($item->type ?? '') === 'admin_log' && ($item->objecttype ?? '') === 'digital_purchase') {
            return self::render_digital_action_timeline_body($details);
        }

        if (($item->type ?? '') === 'digital_purchase') {
            return self::render_digital_purchase_timeline_body($details);
        }

        if (!empty($details['subject'])) {
            return html_writer::div(
                html_writer::tag('span', get_string('subject', 'local_subscriptions') . ': ', ['class' => 'text-muted']) .
                html_writer::tag('strong', s((string)$details['subject'])),
                'crm-timeline-email-card'
            );
        }

        // On masque les détails purement techniques.
        if (array_key_exists('notifyuser', $details)) {
            return '';
        }

        $body = trim((string)($item->body ?? ''));

        return $body !== '' ? format_text($body, FORMAT_PLAIN) : '';
    }

    private static function render_subscription_timeline_body(array $details): string {
        if (!$details) {
            return '';
        }

        $main = [];

        if (!empty($details['plan'])) {
            $main[] = html_writer::tag('strong', s((string)$details['plan']));
        }

        if (!empty($details['status'])) {
            $main[] = html_writer::span(
                s((string)$details['status']),
                'badge bg-light text-dark border ms-2'
            );
        }

        $period = '';

        if (!empty($details['start']) || !empty($details['end'])) {
            $period = html_writer::div(
                s(
                    self::normalize_crm_date((string)($details['start'] ?? '-')) .
                    ' → ' .
                    self::normalize_crm_date((string)($details['end'] ?? '-'))
                ),
                'text-muted small mt-1'
            );
        }

        $price = '';

        if (!empty($details['price']) && $details['price'] !== '-') {
            $price = html_writer::div(
                s((string)$details['price']),
                'small mt-1'
            );
        }

        $changes = '';

        if (!empty($details['changes']) && is_array($details['changes'])) {
            $changes .= html_writer::start_div('crm-timeline-changes mt-2');

            foreach ($details['changes'] as $field => $change) {
                if (!is_array($change)) {
                    continue;
                }

                $changes .= html_writer::div(
                    html_writer::tag('span', s($field), ['class' => 'text-muted me-1']) .
                    html_writer::tag('span', s(self::normalize_crm_date((string)($change['from'] ?? '-'))), ['class' => 'crm-change-old']) .
                    html_writer::tag('span', ' → ', ['class' => 'text-muted mx-1']) .
                    html_writer::tag('span', s(self::normalize_crm_date((string)($change['to'] ?? '-'))), ['class' => 'crm-change-new']),
                    'small'
                );
            }

            $changes .= html_writer::end_div();
        }

        return html_writer::div(
            html_writer::div(implode('', $main)) .
            $period .
            $price .
            $changes,
            'crm-timeline-subscription-card'
        );
    }

    private static function normalize_crm_date(string $value): string {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $value, $m)) {
            return sprintf('%02d/%02d/%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }

        return $value;
    }

    private static function courses(array $courses): string {
        global $OUTPUT;

        $out = html_writer::start_div('crm-section crm-courses-section mt-5');
        $out .= html_writer::tag('h4', get_string('crm_accessible_courses', 'local_subscriptions'), [
            'class' => 'mt-5 mb-3',
        ]);

        if (!$courses) {
            $out .= $OUTPUT->notification(get_string('crm_no_accessible_courses', 'local_subscriptions'), 'info');
            $out .= html_writer::end_div();

            return $out;
        }

        $table = new html_table();
        $table->head = [
            get_string('course'),
            get_string('shortnamecourse'),
            get_string('access', 'local_subscriptions'),
        ];

        foreach ($courses as $course) {
            $access = get_string('active', 'local_subscriptions');

            if (!empty($course->timeend)) {
                $access .= ' · ' . get_string('until', 'local_subscriptions') . ' ' .
                    AdminFormatter::date((int)$course->timeend);
            }

            $table->data[] = [
                AdminEntityLinks::course(
                    (int)$course->id,
                    format_string($course->fullname)
                ),
                s($course->shortname),
                $access,
            ];
        }

        $out .= html_writer::table($table);
        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_digital_purchase_timeline_body(array $details): string {
        if (!$details) {
            return '';
        }

        $product = !empty($details['productid'])
            ? AdminEntityLinks::digital_product(
                (int)$details['productid'],
                s((string)($details['product'] ?? '-'))
            )
            : s((string)($details['product'] ?? '-'));

        $status = DigitalPresenter::render_status_badge((string)($details['status'] ?? ''));

        $price = AdminFormatter::price(
            $details['price'] ?? 0,
            $details['currency'] ?? ''
        );

        return html_writer::div(
            html_writer::div(
                html_writer::tag('strong', $product) . ' ' . $status
            ) .
            html_writer::div($price, 'small mt-1') .
            html_writer::div(s((string)($details['email'] ?? '')), 'text-muted small mt-1'),
            'crm-timeline-digital-card'
        );
    }

    private static function render_digital_action_timeline_body(array $details): string {
        $email = s((string)($details['email'] ?? ''));
        $purchaseid = (int)($details['purchaseid'] ?? 0);
        $productid = (int)($details['productid'] ?? 0);

        $purchase = $purchaseid > 0
            ? html_writer::link(
                new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => $purchaseid]),
                get_string('digital_purchase', 'local_subscriptions') . ' #' . $purchaseid,
                ['class' => 'crm-entity-link']
            )
            : '';

        $product = $productid > 0
            ? AdminEntityLinks::digital_product(
                $productid,
                get_string('product', 'local_subscriptions') . ' #' . $productid
            )
            : '';

        $expires = !empty($details['expires'])
            ? html_writer::div(
                get_string('digital_purchase_link_expires', 'local_subscriptions') . ': ' . s((string)$details['expires']),
                'small text-muted mt-1'
            )
            : '';

        $oldtoken = !empty($details['oldtoken'])
            ? html_writer::div(
                get_string('digital_purchase_old_token', 'local_subscriptions') . ': ' . s((string)$details['oldtoken']),
                'small text-muted mt-1'
            )
            : '';

        $lines = array_filter([$purchase, $product]);

        if ($email !== '') {
            $lines[] = html_writer::span($email, 'text-muted small');
        }

        return html_writer::div(
            implode(html_writer::empty_tag('br'), $lines) .
            $expires .
            $oldtoken,
            'crm-timeline-digital-action-card'
        );
    }

}