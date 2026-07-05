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
        $out .= self::stats($profile);
        $out .= self::subscriptions($profile->subscriptions);
        $out .= self::digital_purchases($profile->digitalpayments);
        $out .= self::courses($profile->courses ?? []);
        $out .= self::timeline($profile->timeline ?? []);

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
                ),
                'crm-user-actions'
            ),
            'crm-user-header card card-body mb-4'
        );
    }

    private static function stats(\stdClass $profile): string {
        $stats = $profile->stats;

        $cards = [
            [
                'icon' => '🟢',
                'label' => get_string('crm_status', 'local_subscriptions'),
                'value' => self::crm_status_badge($stats),
                'muted' => get_string('crm_stats_status_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '📚',
                'label' => get_string('subscriptions', 'local_subscriptions'),
                'value' => $stats->subscriptions ?? 0,
                'muted' => get_string('crm_stats_subscriptions_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '📦',
                'label' => get_string('digital_purchases', 'local_subscriptions'),
                'value' => $stats->digitalpayments ?? 0,
                'muted' => get_string('crm_stats_digital_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '🎓',
                'label' => get_string('crm_accessible_courses', 'local_subscriptions'),
                'value' => $stats->accessiblecourses ?? 0,
                'muted' => get_string('crm_stats_courses_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '💳',
                'label' => get_string('crm_total_spent', 'local_subscriptions'),
                'value' => self::stats_total_spent($stats),
                'muted' => get_string('crm_stats_spent_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '🕒',
                'label' => get_string('crm_last_activity', 'local_subscriptions'),
                'value' => !empty($stats->lastactivity)
                    ? AdminFormatter::datetime((int)$stats->lastactivity)
                    : '-',
                'muted' => get_string('crm_stats_activity_hint', 'local_subscriptions'),
            ],
        ];

        $out = html_writer::tag('h3', get_string('crm_stats_title', 'local_subscriptions'), [
            'class' => 'mt-4 mb-3',
        ]);

        $out .= html_writer::start_div('row mb-4 crm-stats-grid');

        foreach ($cards as $card) {
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::div(
                        html_writer::span($card['icon'], 'crm-stat-icon') .
                        html_writer::tag('div', $card['value'], ['class' => 'crm-stat-number']) .
                        html_writer::tag('div', $card['label'], ['class' => 'crm-stat-label']) .
                        html_writer::tag('div', $card['muted'], ['class' => 'text-muted small mt-1']),
                        'card card-body crm-stat-card h-100'
                    )
                ),
                'col-md-4 col-lg mb-3'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function crm_status_badge(\stdClass $stats): string {
        $status = (string)($stats->crmstatus ?? 'unknown');

        $map = [
            'active_customer' => ['crm_status_active_customer', 'success'],
            'trial' => ['crm_status_trial', 'info'],
            'former_customer' => ['crm_status_former_customer', 'secondary'],
            'suspended' => ['crm_status_suspended', 'danger'],
            'lead' => ['crm_status_lead', 'warning'],
            'unknown' => ['crm_status_unknown', 'light text-dark border'],
        ];

        [$key, $class] = $map[$status] ?? $map['unknown'];

        return html_writer::span(
            get_string($key, 'local_subscriptions'),
            'badge bg-' . $class
        );
    }

    private static function stats_total_spent(\stdClass $stats): string {
        $parts = [];

        if (!empty($stats->spent_eur)) {
            $parts[] = AdminFormatter::price((float)$stats->spent_eur, 'EUR');
        }

        if (!empty($stats->spent_rub)) {
            $parts[] = AdminFormatter::price((float)$stats->spent_rub, 'RUB');
        }

        return $parts ? implode(' · ', $parts) : '-';
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
                self::subscription_actions($sub),
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

        $issuspended = !empty($user->suspended);

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_toggle_suspension_page(), [
                'id' => (int)$user->id,
                'sesskey' => sesskey(),
            ]),
            $issuspended
                ? '✅ ' . get_string('crm_activate_moodle_profile', 'local_subscriptions')
                : '⏸️ ' . get_string('crm_suspend_moodle_profile', 'local_subscriptions'),
            [
                'class' => $issuspended
                    ? 'btn btn-sm btn-outline-success'
                    : 'btn btn-sm btn-outline-warning',
            ]
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
    public static function timeline(array $items): string {
        if (!$items) {
            return html_writer::div(
                get_string('crm_timeline_empty', 'local_subscriptions'),
                'alert alert-info'
            );
        }

        $groups = self::group_timeline_items($items);

        $out = html_writer::start_div('crm-timeline');

        $out .= html_writer::tag('h3', get_string('crm_timeline_title', 'local_subscriptions'), [
            'class' => 'mt-5 mb-3',
        ]);

        $out .= html_writer::div(
            html_writer::span(get_string('show') . ' : ', 'me-2') .
            self::timeline_filter_button('all', get_string('all')) .
            self::timeline_filter_button('subscriptions', get_string('subscriptions', 'local_subscriptions')) .
            self::timeline_filter_button('purchases', get_string('crm_filter_purchases', 'local_subscriptions')) .
            self::timeline_filter_button('emails', get_string('crm_filter_emails', 'local_subscriptions')) .
            self::timeline_filter_button('other', get_string('crm_filter_other', 'local_subscriptions')),
            'crm-timeline-filters mb-3'
        );

        $out .= html_writer::div(
            html_writer::tag('button', '▾ ' . get_string('crm_timeline_expand_all', 'local_subscriptions'), [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary me-2',
                'onclick' => "
                    document.querySelectorAll('.crm-timeline-body').forEach(function(e){e.classList.remove('d-none');});
                    document.querySelectorAll('.crm-timeline-toggle').forEach(function(b){b.innerText='▴';});
                ",
            ]) .
            html_writer::tag('button', '▴ ' . get_string('crm_timeline_collapse_all', 'local_subscriptions'), [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary',
                'onclick' => "
                    document.querySelectorAll('.crm-timeline-body').forEach(function(e){e.classList.add('d-none');});
                    document.querySelectorAll('.crm-timeline-toggle').forEach(function(b){b.innerText='▾';});
                ",
            ]),
            'mb-3'
        );

        foreach ($groups as $groupkey => $group) {
            if (!$group['items']) {
                continue;
            }

            $collapseid = 'crm-timeline-group-' . $groupkey;

            $out .= html_writer::start_div('crm-timeline-group card mb-2');

            $out .= html_writer::tag('button',
                html_writer::span($group['icon'] . ' ' . $group['label'], 'fw-bold') .
                html_writer::span(' ' . count($group['items']), 'badge bg-primary ms-2') .
                html_writer::span('▾', 'float-end'),
                [
                    'type' => 'button',
                    'class' => 'btn btn-light text-start w-100 crm-timeline-group-toggle',
                    'onclick' => "
                        var el = document.getElementById('$collapseid');
                        el.classList.toggle('d-none');
                        this.querySelector('.float-end').innerText = el.classList.contains('d-none') ? '▸' : '▾';
                    ",
                ]
            );

            $hidden = $groupkey === 'recent' ? '' : ' d-none';
            $out .= html_writer::start_div('crm-timeline-group-body' . $hidden, ['id' => $collapseid]);

            foreach ($group['items'] as $item) {
                $out .= self::timeline_item($item);
            }

            $out .= html_writer::end_div();
            $out .= html_writer::end_div();
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_timeline_body(\stdClass $item): string {
        $details = $item->detailsraw ?? [];

        if (($item->action ?? '') === 'email.custom.sent') {
            $content = '';

            if (!empty($details['subject'])) {
                $content .= html_writer::div(
                    html_writer::tag('strong', get_string('subject', 'local_subscriptions') . ': ') .
                    s((string)$details['subject'])
                );
            }

            if (!empty($details['to'])) {
                $content .= html_writer::div(
                    html_writer::tag('strong', get_string('recipient', 'local_subscriptions') . ': ') .
                    s((string)$details['to']),
                    'small text-muted mt-1'
                );
            }

            if (!empty($details['body'])) {
                $content .= html_writer::div(
                    format_text((string)$details['body'], FORMAT_HTML),
                    'mt-2'
                );
            }

            if (!empty($item->logid)) {
                $content .= html_writer::div(
                    html_writer::link(
                        new moodle_url(subscription_config::admin_user_email_preview_page(), [
                            'logid' => (int)$item->logid,
                        ]),
                        '👁️ ' . get_string('crm_email_preview', 'local_subscriptions'),
                        [
                            'class' => 'btn btn-sm btn-outline-secondary mt-2',
                            'target' => '_blank',
                        ]
                    )
                );
            }

            return html_writer::div(
                $content,
                'crm-timeline-email-card crm-timeline-card'
            );
        }        

        if (($item->type ?? '') === 'admin_log' && str_starts_with((string)($item->action ?? ''), 'email.')) {
            return self::render_email_timeline_body($item, $details);
        }

        if (($item->objecttype ?? '') === 'subscription') {
            return self::render_subscription_timeline_body($details);
        }

        if (($item->type ?? '') === 'subscription_payment') {
            return self::render_subscription_payment_timeline_body($details);
        }

        if (($item->type ?? '') === 'admin_log' && ($item->objecttype ?? '') === 'digital_purchase') {
            return self::render_digital_action_timeline_body($details);
        }

        if (($item->type ?? '') === 'digital_purchase') {
            return self::render_digital_purchase_timeline_body($details);
        }

        if (!empty($details['subject'])) {
            $content = '';

            $content .= html_writer::div(
                html_writer::tag('strong', get_string('subject', 'local_subscriptions') . ': ') .
                s((string)$details['subject'])
            );

            if (!empty($details['body'])) {
                $content .= html_writer::div(
                    format_text((string)$details['body'], FORMAT_HTML),
                    'mt-2'
                );
            }

            return html_writer::div(
                $content,
                'crm-timeline-email-card crm-timeline-card'
            );
        }

        // On masque les détails purement techniques.
        if (array_key_exists('notifyuser', $details)) {
            return '';
        }

        $body = trim((string)($item->body ?? ''));

        return $body !== ''
            ? html_writer::div(format_text($body, FORMAT_PLAIN), 'crm-timeline-note-card crm-timeline-card')
            : '';
    }

    private static function render_subscription_timeline_body(array $details): string {
        if (!$details) {
            return '';
        }

        $main = [];

        $plan = s((string)($details['plan'] ?? '-'));
        $status = DigitalPresenter::render_status_badge((string)($details['status'] ?? ''));

        $main[] = html_writer::div(
            html_writer::tag('strong', $plan) . ' ' . $status
        );

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
            'crm-timeline-subscription-card crm-timeline-card'
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
            'crm-timeline-digital-card crm-timeline-card'
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
            'crm-timeline-digital-action-card crm-timeline-card'
        );
    }

    private static function subscription_actions(\stdClass $sub): string {
        $userid = (int)$sub->userid;
        $subscriptionid = (int)$sub->id;

        $baseparams = [
            'userid' => $userid,
            'subscriptionid' => $subscriptionid,
            'sesskey' => sesskey(),
        ];

        $items = [];

        $items[] = html_writer::link(
            new moodle_url(subscription_config::user_subscription_edit_page(), ['id' => $subscriptionid]),
            '✏️ ' . get_string('edit'),
            ['class' => 'btn btn-sm btn-outline-primary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::user_subscription_view_page(), ['id' => $subscriptionid]),
            '👁️ ' . get_string('view_details', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'welcome',
            ]),
            '👋 ' . get_string('crm_resend_welcome_email', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'access',
            ]),
            '🔐 ' . get_string('crm_resend_access_email', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'receipt',
            ]),
            '🧾 ' . get_string('crm_resend_receipt', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'extend',
                'days' => 30,
            ]),
            '➕30j',
            ['class' => 'btn btn-sm btn-outline-success me-1 mb-1']
        );

        return html_writer::div(implode('', $items), 'crm-subscription-actions');
    }

    private static function render_subscription_payment_timeline_body(array $details): string {
        if (!$details) {
            return '';
        }

        $plan = s((string)($details['plan'] ?? '-'));
        $status = DigitalPresenter::render_status_badge((string)($details['status'] ?? ''));

        $price = AdminFormatter::price(
            $details['price'] ?? 0,
            $details['currency'] ?? ''
        );

        $provider = trim((string)($details['provider'] ?? ''));
        $transactionid = trim((string)($details['transactionid'] ?? ''));
        $email = trim((string)($details['email'] ?? ''));

        $lines = [];

        $lines[] = html_writer::div(
            html_writer::tag('strong', $plan) . ' ' . $status
        );

        if ($price !== '-') {
            $lines[] = html_writer::div($price, 'small mt-1');
        }

        if ($provider !== '') {
            $lines[] = html_writer::div(
                \local_subscriptions\payment\Provider::label_with_icon($provider),
                'small mt-1'
            );
        }

        if ($transactionid !== '') {
            $lines[] = html_writer::div(
                get_string('transactionid', 'local_subscriptions') . ': ' . s($transactionid),
                'text-muted small mt-1'
            );
        }

        if ($email !== '') {
            $lines[] = html_writer::div(s($email), 'text-muted small mt-1');
        }

        return html_writer::div(
            implode('', $lines),
            'crm-timeline-subscription-payment-card crm-timeline-card'
        );
    }

    private static function render_email_timeline_body(\stdClass $item, array $details): string {
        $action = (string)($item->action ?? '');

        $type = match ($action) {
            'email.receipt.sent' => get_string('crm_email_type_receipt', 'local_subscriptions'),
            'email.subscription_access.sent' => get_string('crm_email_type_access', 'local_subscriptions'),
            'email.welcome.sent' => get_string('crm_email_type_welcome', 'local_subscriptions'),
            default => get_string('email', 'local_subscriptions'),
        };

        $lines = [];

        $lines[] = html_writer::div(
            html_writer::tag('strong', get_string('type', 'local_subscriptions') . ': ') . s($type)
        );

        if (!empty($details['plan'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('plan', 'local_subscriptions') . ': ') . s((string)$details['plan']),
                'small'
            );
        }

        if (!empty($details['subscriptionid'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('subscription', 'local_subscriptions') . ': ') . '#' . (int)$details['subscriptionid'],
                'small'
            );
        }

        if (!empty($details['paymentrequest'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('payment_request', 'local_subscriptions') . ': ') . s((string)$details['paymentrequest']),
                'small'
            );
        }

        if (!empty($details['provider'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('payment_provider', 'local_subscriptions') . ': ') .
                \local_subscriptions\payment\Provider::label_with_icon((string)$details['provider']),
                'small'
            );
        }

        if (!empty($details['transactionid']) && $details['transactionid'] !== '-') {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('transactionid', 'local_subscriptions') . ': ') .
                s((string)$details['transactionid']),
                'small text-muted'
            );
        }

        return html_writer::div(
            implode('', $lines),
            'crm-timeline-email-card crm-timeline-card'
        );
    }

    private static function timeline_filter_button(string $filter, string $label): string {
        return html_writer::tag('button', $label, [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-primary me-1',
            'onclick' => "
                document.querySelectorAll('.crm-timeline-item').forEach(function(item){
                    item.classList.toggle('d-none', '$filter' !== 'all' && item.dataset.category !== '$filter');
                });
            ",
        ]);
    }

    private static function group_timeline_items(array $items): array {
        $now = time();

        $groups = [
            'recent' => [
                'label' => get_string('crm_timeline_recent', 'local_subscriptions'),
                'icon' => '🗓️',
                'items' => [],
            ],
            'middle' => [
                'label' => get_string('crm_timeline_middle', 'local_subscriptions'),
                'icon' => '📦',
                'items' => [],
            ],
            'old' => [
                'label' => get_string('crm_timeline_old', 'local_subscriptions'),
                'icon' => '🗂️',
                'items' => [],
            ],
        ];

        foreach ($items as $item) {
            $age = $now - (int)$item->timecreated;

            if ($age <= 30 * DAYSECS) {
                $groups['recent']['items'][] = $item;
            } else if ($age <= 90 * DAYSECS) {
                $groups['middle']['items'][] = $item;
            } else {
                $groups['old']['items'][] = $item;
            }
        }

        return $groups;
    }

    private static function timeline_category(\stdClass $item): string {
        $type = (string)($item->type ?? '');
        $objecttype = (string)($item->objecttype ?? '');
        $action = (string)($item->action ?? '');

        if (str_starts_with($action, 'email.')) {
            return 'emails';
        }

        if (
            $type === 'subscription_payment' ||
            $type === 'digital_purchase' ||
            str_contains($objecttype, 'purchase') ||
            str_contains($objecttype, 'payment')
        ) {
            return 'purchases';
        }

        if (
            $type === 'subscription_snapshot' ||
            $objecttype === 'subscription' ||
            str_contains($action, 'subscription') ||
            str_contains($action, 'trial')
        ) {
            return 'subscriptions';
        }

        return 'other';
    }

    private static function timeline_item(\stdClass $item): string {
        $body = self::render_timeline_body($item);
        $category = self::timeline_category($item);

        $meta = AdminFormatter::datetime((int)$item->timecreated);
        $actor = self::timeline_actor_label($item);

        $technical = $meta;
        if ($actor !== '') {
            $technical .= ' · ' . $actor;
        }

        $out = html_writer::start_div('crm-timeline-item border-top p-2 w-100', [
            'data-category' => $category,
        ]);

        $hasbody = $body !== '';

        $out .= html_writer::start_div('d-flex align-items-center w-100 crm-timeline-line' . ($hasbody ? ' crm-timeline-line-clickable' : ''), [
            'onclick' => $hasbody ? "
                var item = this.closest('.crm-timeline-item');
                var body = item.querySelector('.crm-timeline-body');
                var toggle = item.querySelector('.crm-timeline-toggle');
                body.classList.toggle('d-none');
                toggle.innerText = body.classList.contains('d-none') ? '▾' : '▴';
            " : '',
        ]);

        $out .= html_writer::span($item->icon ?? '•', 'crm-timeline-icon flex-shrink-0');

        $out .= html_writer::start_div('flex-grow-1 w-100');

        $out .= html_writer::start_div('d-flex align-items-center w-100 crm-timeline-line');

        $out .= html_writer::div(
            html_writer::tag('strong', ' ' . s((string)$item->title)) .
            html_writer::span(' (' . s($technical) . ')', 'text-muted small ms-2'),
            'flex-grow-1'
        );

        if ($body !== '') {
            $out .= html_writer::tag('button', '▾', [
                'type' => 'button',
                'class' => 'btn btn-sm btn-link crm-timeline-toggle ms-auto',
                'title' => get_string('crm_timeline_view_details', 'local_subscriptions'),
                'onclick' => "event.stopPropagation();
                    var item = this.closest('.crm-timeline-item');
                    var body = item.querySelector('.crm-timeline-body');
                    body.classList.toggle('d-none');
                    this.innerText = body.classList.contains('d-none') ? '▾' : '▴';
                ",
            ]);
        }

        $out .= html_writer::end_div();

        if ($body !== '') {
            $out .= html_writer::div($body, 'crm-timeline-body d-none mt-2 w-100');
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    private static function timeline_actor_label(\stdClass $item): string {
        global $DB;

        $actorid = (int)($item->actorid ?? 0);

        if ($actorid <= 0) {
            return '';
        }

        $actor = $DB->get_record('user', ['id' => $actorid], 'id, firstname, lastname, email', IGNORE_MISSING);

        if (!$actor) {
            return get_string('crm_timeline_by_admin', 'local_subscriptions');
        }

        $name = trim(($actor->firstname ?? '') . ' ' . ($actor->lastname ?? ''));

        if ($name === '') {
            $name = $actor->email ?? ('#' . $actorid);
        }

        return get_string('crm_timeline_by_actor', 'local_subscriptions', $name);
    }

}