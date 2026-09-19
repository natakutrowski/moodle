<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderOperationalStatus;
use local_subscriptions\payment\Provider;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentProviderOperationalRenderer {
    public static function badge(
        bool $ok,
        ?string $positive = null,
        ?string $negative = null
    ): string {
        return \html_writer::span(
            $ok
                ? ($positive ?? get_string(
                    'commerce_provider_ops_configured',
                    'local_subscriptions'
                ))
                : ($negative ?? get_string(
                    'commerce_provider_ops_missing',
                    'local_subscriptions'
                )),
            $ok
                ? 'badge text-bg-success'
                : 'badge text-bg-secondary'
        );
    }

    public static function icon(
        string $provider
    ): string {
        global $CFG;

        $provider = strtolower(trim($provider));
        $file = $provider . '.svg';
        $path =
            $CFG->dirroot
            . '/local/subscriptions/pix/providers/'
            . $file;

        if (is_file($path)) {
            return \html_writer::empty_tag(
                'img',
                [
                    'src' => (
                        new \moodle_url(
                            '/local/subscriptions/pix/providers/'
                            . $file
                        )
                    )->out(false),
                    'alt' => '',
                    'class' =>
                        'commerce-provider-ops-logo',
                ]
            );
        }

        return \html_writer::tag(
            'i',
            '',
            [
                'class' =>
                    'fa-solid fa-credit-card commerce-provider-ops-logo-fallback',
                'aria-hidden' => 'true',
            ]
        );
    }

    public static function label(
        string $provider
    ): string {
        return match (strtolower(trim($provider))) {
            Provider::STRIPE =>
                get_string('provider_stripe', 'local_subscriptions'),
            Provider::ALFA =>
                get_string('provider_alfa', 'local_subscriptions'),
            Provider::PAYPAL =>
                get_string('provider_paypal', 'local_subscriptions'),
            default => $provider,
        };
    }

    public static function overview_card(
        CommercePaymentProviderOperationalStatus $status,
        \moodle_url $url
    ): string {
        $state =
            $status->configured
                ? get_string(
                    'commerce_provider_ops_ready',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_provider_ops_incomplete',
                    'local_subscriptions'
                );

        $stateclass =
            $status->configured
                ? 'text-success'
                : 'text-warning';

        $html = \html_writer::start_div(
            'card h-100 commerce-provider-ops-card'
        );
        $html .= \html_writer::start_div('card-body');
        $html .= \html_writer::div(
            self::icon($status->provider)
            . \html_writer::tag(
                'h3',
                s(self::label($status->provider)),
                ['class' => 'h5 mb-0']
            ),
            'd-flex align-items-center gap-2 mb-3'
        );

        $html .= \html_writer::div(
            \html_writer::span(
                strtoupper($status->environment),
                'badge text-bg-light border me-2'
            )
            . \html_writer::span(
                s($state),
                $stateclass . ' fw-semibold'
            ),
            'mb-3'
        );

        $rows = [
            [
                get_string(
                    'commerce_provider_ops_credentials',
                    'local_subscriptions'
                ),
                self::badge($status->configured),
            ],
            [
                get_string(
                    'commerce_provider_ops_webhook',
                    'local_subscriptions'
                ),
                self::badge($status->webhookconfigured),
            ],
            [
                get_string(
                    'commerce_provider_ops_customer_allowed',
                    'local_subscriptions'
                ),
                self::badge(
                    $status->adminallowed,
                    get_string('yes'),
                    get_string('no')
                ),
            ],
        ];

        foreach ($rows as [$label, $value]) {
            $html .= \html_writer::div(
                \html_writer::span(
                    s($label),
                    'text-muted'
                )
                . $value,
                'd-flex justify-content-between align-items-center gap-3 mb-2'
            );
        }

        $html .= \html_writer::link(
            $url,
            get_string(
                'commerce_provider_ops_open',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-primary mt-2',
            ]
        );

        $html .= \html_writer::end_div();
        $html .= \html_writer::end_div();

        return $html;
    }

    public static function status_table(
        CommercePaymentProviderOperationalStatus $status
    ): string {
        $table = new \html_table();
        $table->attributes['class'] =
            'table table-sm align-middle mb-0';
        $table->head = [
            get_string(
                'commerce_provider_ops_check',
                'local_subscriptions'
            ),
            get_string(
                'commerce_provider_ops_status',
                'local_subscriptions'
            ),
        ];
        $table->data = [
            [
                get_string(
                    'commerce_provider_ops_environment',
                    'local_subscriptions'
                ),
                \html_writer::tag(
                    'strong',
                    s(strtoupper($status->environment))
                ),
            ],
            [
                get_string(
                    'commerce_provider_ops_credentials',
                    'local_subscriptions'
                ),
                self::badge($status->configured),
            ],
            [
                get_string(
                    'commerce_provider_ops_webhook',
                    'local_subscriptions'
                ),
                self::badge($status->webhookconfigured),
            ],
            [
                get_string(
                    'commerce_provider_ops_refunds',
                    'local_subscriptions'
                ),
                self::badge($status->refundconfigured),
            ],
            [
                get_string(
                    'commerce_provider_ops_customer_allowed',
                    'local_subscriptions'
                ),
                self::badge(
                    $status->adminallowed,
                    get_string('yes'),
                    get_string('no')
                ),
            ],
        ];

        return \html_writer::table($table);
    }
}
