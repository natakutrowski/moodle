<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use local_subscriptions\output\UserProfileRenderer;
use moodle_url;

/**
 * Renders a read-only CRM profile for a deleted Moodle user.
 */
final class HistoricalUserProfileRenderer {

    /**
     * Renders the historical profile.
     */
    public static function render(
        HistoricalUserProfileViewModel $profile
    ): string {
        $out = html_writer::start_div(
            'crm-user-history',
            [
                'data-userid' => $profile->userid,
            ]
        );

        $out .= self::render_warning(
            $profile
        );

        $out .= self::render_summary(
            $profile
        );

        $out .= self::render_navigation(
            $profile
        );

        $out .= html_writer::start_div(
            'crm-user-history-grid'
        );

        $out .= self::render_subscriptions(
            $profile
        );

        $out .= self::render_digital_payments(
            $profile
        );

        $out .= self::render_notes(
            $profile
        );

        $out .= self::render_tags(
            $profile
        );

        $out .= UserProfileRenderer::
            render_historical_timeline_panel(
                (object)[
                    'user' => (object)[
                        'id' =>
                            $profile->userid,
                    ],

                    'timeline' =>
                        $profile->timeline,

                    'timelinehasmore' =>
                        $profile->timelinehasmore,

                    'timelinenextoffset' =>
                        $profile->timelinenextoffset,
                ]
            );        

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Renders the deleted-account warning.
     */
    private static function render_warning(
        HistoricalUserProfileViewModel $profile
    ): string {
        $content = html_writer::tag(
            'strong',
            get_string(
                'crm_user_history_readonly',
                'local_subscriptions'
            )
        );

        $content .= html_writer::tag(
            'p',
            get_string(
                'crm_user_history_readonly_description',
                'local_subscriptions',
                $profile->userid
            ),
            [
                'class' => 'mb-0 mt-1',
            ]
        );

        return html_writer::div(
            $content,
            'alert alert-warning crm-user-history-warning',
            [
                'role' => 'status',
            ]
        );
    }

    /**
     * Renders historical CRM indicators.
     */
    private static function render_summary(
        HistoricalUserProfileViewModel $profile
    ): string {
        $items = [];

        $items[] = self::render_stat(
            get_string(
                'crm_user_history_userid',
                'local_subscriptions'
            ),
            (string)$profile->userid
        );

        $items[] = self::render_stat(
            get_string(
                'crm_user_history_subscriptions',
                'local_subscriptions'
            ),
            (string)count(
                $profile->subscriptions
            )
        );

        $items[] = self::render_stat(
            get_string(
                'crm_user_history_digital_purchases',
                'local_subscriptions'
            ),
            (string)count(
                $profile->digitalpayments
            )
        );

        $items[] = self::render_stat(
            get_string(
                'crm_user_history_courses',
                'local_subscriptions'
            ),
            (string)$profile->historicalcoursecount
        );

        $items[] = self::render_stat(
            get_string(
                'crm_user_history_last_activity',
                'local_subscriptions'
            ),
            $profile->lastactivity > 0
                ? userdate(
                    $profile->lastactivity,
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                )
                : get_string(
                    'never'
                )
        );

        $items[] = self::render_stat(
            get_string(
                'crm_user_history_revenue',
                'local_subscriptions'
            ),
            self::format_revenue(
                $profile->revenuebycurrency
            )
        );

        return html_writer::div(
            implode('', $items),
            'crm-user-history-summary',
            [
                'aria-label' => get_string(
                    'crm_user_history_summary',
                    'local_subscriptions'
                ),
            ]
        );
    }

    /**
     * Renders one historical indicator.
     */
    private static function render_stat(
        string $label,
        string $value
    ): string {
        $content = html_writer::div(
            s($label),
            'crm-user-history-stat-label'
        );

        $content .= html_writer::div(
            s($value),
            'crm-user-history-stat-value'
        );

        return html_writer::div(
            $content,
            'crm-user-history-stat'
        );
    }

    /**
     * Renders links to existing CRM historical data.
     */
    private static function render_navigation(
        HistoricalUserProfileViewModel $profile
    ): string {
        $links = [];

        $links[] = html_writer::link(
            new moodle_url(
                subscription_config::admin_users_page(),
                [
                    'userid' => $profile->userid,
                ]
            ),
            get_string(
                'crm_user_history_open_users',
                'local_subscriptions'
            ),
            [
                'class' => 'btn btn-outline-secondary',
            ]
        );

        if (
            method_exists(
                subscription_config::class,
                'admin_inbox_page'
            )
        ) {
            $links[] = html_writer::link(
                new moodle_url(
                    subscription_config::admin_inbox_page(),
                    [
                        'userid' => $profile->userid,
                    ]
                ),
                get_string(
                    'crm_user_history_open_inbox',
                    'local_subscriptions'
                ),
                [
                    'class' => 'btn btn-outline-secondary',
                ]
            );
        }

        if (
            method_exists(
                subscription_config::class,
                'admin_work_page'
            )
        ) {
            $links[] = html_writer::link(
                new moodle_url(
                    subscription_config::admin_work_page(),
                    [
                        'targetuserid' => $profile->userid,
                    ]
                ),
                get_string(
                    'crm_user_history_open_work',
                    'local_subscriptions'
                ),
                [
                    'class' => 'btn btn-outline-secondary',
                ]
            );
        }

        return html_writer::div(
            implode('', $links),
            'crm-user-history-navigation d-flex flex-wrap gap-2 mb-4'
        );
    }

    /**
     * Renders historical subscriptions.
     */
    private static function render_subscriptions(
        HistoricalUserProfileViewModel $profile
    ): string {
        if ($profile->subscriptions === []) {
            return self::render_empty_section(
                get_string(
                    'crm_user_history_subscriptions',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user_history_no_subscriptions',
                    'local_subscriptions'
                )
            );
        }

        $rows = '';

        foreach ($profile->subscriptions as $subscription) {
            $planname = trim(
                (string)($subscription->planname ?? '')
            );

            if ($planname === '') {
                $planname = get_string(
                    'crm_user_history_unknown_plan',
                    'local_subscriptions'
                );
            }

            $status = trim(
                (string)($subscription->status ?? '')
            );

            $amount = self::format_amount(
                isset($subscription->pricepaid)
                    ? (float)$subscription->pricepaid
                    : null,
                (string)($subscription->currency ?? '')
            );

            $date = !empty($subscription->start_date)
                ? userdate(
                    (int)$subscription->start_date,
                    get_string(
                        'strftimedateshort',
                        'langconfig'
                    )
                )
                : '—';

            $rows .= html_writer::tag(
                'tr',
                html_writer::tag(
                    'td',
                    s($planname)
                )
                . html_writer::tag(
                    'td',
                    s($status !== '' ? $status : '—')
                )
                . html_writer::tag(
                    'td',
                    s($amount)
                )
                . html_writer::tag(
                    'td',
                    s($date)
                )
            );
        }

        $head = html_writer::tag(
            'tr',
            html_writer::tag(
                'th',
                get_string(
                    'crm_user_history_plan',
                    'local_subscriptions'
                ),
                ['scope' => 'col']
            )
            . html_writer::tag(
                'th',
                get_string(
                    'status'
                ),
                ['scope' => 'col']
            )
            . html_writer::tag(
                'th',
                get_string(
                    'crm_user_history_amount',
                    'local_subscriptions'
                ),
                ['scope' => 'col']
            )
            . html_writer::tag(
                'th',
                get_string(
                    'date'
                ),
                ['scope' => 'col']
            )
        );

        $table = html_writer::tag(
            'table',
            html_writer::tag(
                'thead',
                $head
            )
            . html_writer::tag(
                'tbody',
                $rows
            ),
            [
                'class' =>
                    'generaltable crm-user-history-table',
            ]
        );

        return self::render_section(
            get_string(
                'crm_user_history_subscriptions',
                'local_subscriptions'
            ),
            $table
        );
    }

    /**
     * Renders historical digital payments.
     */
    private static function render_digital_payments(
        HistoricalUserProfileViewModel $profile
    ): string {
        if ($profile->digitalpayments === []) {
            return self::render_empty_section(
                get_string(
                    'crm_user_history_digital_purchases',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user_history_no_digital_purchases',
                    'local_subscriptions'
                )
            );
        }

        $items = '';

        foreach ($profile->digitalpayments as $payment) {
            $name = trim(
                (string)($payment->productname ?? '')
            );

            if ($name === '') {
                $name = get_string(
                    'crm_user_history_unknown_product',
                    'local_subscriptions'
                );
            }

            $amount = self::format_amount(
                isset($payment->price)
                    ? (float)$payment->price
                    : null,
                (string)($payment->currency ?? '')
            );

            $date = !empty($payment->creation_date)
                ? userdate(
                    (int)$payment->creation_date,
                    get_string(
                        'strftimedateshort',
                        'langconfig'
                    )
                )
                : '—';

            $meta = implode(
                ' · ',
                array_filter(
                    [
                        $amount,
                        $date,
                        trim(
                            (string)($payment->status ?? '')
                        ),
                    ],
                    static fn(string $value): bool =>
                        $value !== ''
                )
            );

            $content = html_writer::div(
                s($name),
                'crm-user-history-item-title'
            );

            $content .= html_writer::div(
                s($meta),
                'crm-user-history-item-meta'
            );

            $items .= html_writer::tag(
                'li',
                $content,
                [
                    'class' =>
                        'crm-user-history-item',
                ]
            );
        }

        return self::render_section(
            get_string(
                'crm_user_history_digital_purchases',
                'local_subscriptions'
            ),
            html_writer::tag(
                'ul',
                $items,
                [
                    'class' =>
                        'crm-user-history-list',
                ]
            )
        );
    }

    /**
     * Renders historical notes.
     */
    private static function render_notes(
        HistoricalUserProfileViewModel $profile
    ): string {
        if ($profile->notes === []) {
            return self::render_empty_section(
                get_string(
                    'crm_notes',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user_history_no_notes',
                    'local_subscriptions'
                )
            );
        }

        $items = '';

        foreach ($profile->notes as $note) {
            $text = trim(
                (string)($note->note ?? '')
            );

            if ($text === '') {
                continue;
            }

            $items .= html_writer::tag(
                'li',
                format_text(
                    $text,
                    FORMAT_PLAIN
                ),
                [
                    'class' =>
                        'crm-user-history-note',
                ]
            );
        }

        if ($items === '') {
            return self::render_empty_section(
                get_string(
                    'crm_notes',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user_history_no_notes',
                    'local_subscriptions'
                )
            );
        }

        return self::render_section(
            get_string(
                'crm_notes',
                'local_subscriptions'
            ),
            html_writer::tag(
                'ul',
                $items,
                [
                    'class' =>
                        'crm-user-history-list',
                ]
            )
        );
    }

    /**
     * Renders historical tags.
     */
    private static function render_tags(
        HistoricalUserProfileViewModel $profile
    ): string {
        if ($profile->tags === []) {
            return self::render_empty_section(
                get_string(
                    'crm_tags',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user_history_no_tags',
                    'local_subscriptions'
                )
            );
        }

        $tags = '';

        foreach ($profile->tags as $tag) {
            $value = trim(
                (string)($tag->tag ?? '')
            );

            if ($value === '') {
                continue;
            }

            $tags .= html_writer::tag(
                'span',
                s($value),
                [
                    'class' =>
                        'badge text-bg-secondary me-1 mb-1',
                ]
            );
        }

        if ($tags === '') {
            $tags = get_string(
                'crm_user_history_no_tags',
                'local_subscriptions'
            );
        }

        return self::render_section(
            get_string(
                'crm_tags',
                'local_subscriptions'
            ),
            $tags
        );
    }

    /**
     * Renders a card-like historical section.
     */
    private static function render_section(
        string $title,
        string $content
    ): string {
        $body = html_writer::tag(
            'h2',
            s($title),
            [
                'class' => 'crm-user-history-section-title',
            ]
        );

        $body .= html_writer::div(
            $content,
            'crm-user-history-section-content'
        );

        return html_writer::tag(
            'section',
            $body,
            [
                'class' => 'crm-user-history-section',
            ]
        );
    }

    /**
     * Renders a section without historical data.
     */
    private static function render_empty_section(
        string $title,
        string $message
    ): string {
        return self::render_section(
            $title,
            html_writer::tag(
                'p',
                s($message),
                [
                    'class' =>
                        'text-muted mb-0',
                ]
            )
        );
    }

    /**
     * Formats a revenue map.
     *
     * @param array<string, float> $totals
     */
    private static function format_revenue(
        array $totals
    ): string {
        if ($totals === []) {
            return '—';
        }

        $parts = [];

        foreach ($totals as $currency => $amount) {
            $parts[] = self::format_amount(
                (float)$amount,
                (string)$currency
            );
        }

        return implode(
            ' · ',
            $parts
        );
    }

    /**
     * Formats an amount without assuming a fixed currency.
     */
    private static function format_amount(
        ?float $amount,
        string $currency
    ): string {
        if ($amount === null) {
            return '—';
        }

        $currency = strtoupper(
            trim($currency)
        );

        $formatted = format_float(
            $amount,
            2
        );

        return $currency !== ''
            ? $formatted . ' ' . $currency
            : $formatted;
    }
}