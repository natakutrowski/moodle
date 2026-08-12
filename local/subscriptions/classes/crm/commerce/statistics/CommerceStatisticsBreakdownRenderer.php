<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/**
 * Shared compact breakdowns used by both global and product Commerce statistics.
 */
final class CommerceStatisticsBreakdownRenderer {
    /**
     * Render acquisition origins and collected payments by provider.
     *
     * @param array<string,mixed> $snapshot
     * @param callable(int,string):string $money
     */
    public static function render(array $snapshot, callable $money): string {
        $html = html_writer::start_div('commerce-stat-breakdowns');

        $html .= self::acquisitions($snapshot['acquisitions'] ?? []);

        if (!empty($snapshot['providers'])) {
            $html .= self::providers($snapshot['providers'], $money);
        }

        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * @param array<string,int> $acquisitions
     */
    private static function acquisitions(array $acquisitions): string {
        $definitions = [
            'standard' => ['commerce_m52_acq_standard', 'fa-solid fa-cart-shopping'],
            'promotion' => ['commerce_m52_acq_promotion', 'fa-solid fa-tags'],
            'personaloffer' => ['commerce_m52_acq_personaloffer', 'fa-solid fa-envelope-open-text'],
            'free' => ['commerce_m52_acq_free', 'fa-solid fa-ticket'],
            'manual' => ['commerce_m52_acq_manual', 'fa-solid fa-gift'],
        ];

        $items = '';
        foreach ($definitions as $key => [$stringkey, $icon]) {
            $items .= html_writer::div(
                html_writer::tag('i', '', [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ]) .
                html_writer::div(
                    html_writer::tag(
                        'strong',
                        s((string)(int)($acquisitions[$key] ?? 0))
                    ) .
                    html_writer::tag(
                        'span',
                        s(get_string($stringkey, 'local_subscriptions'))
                    ),
                    'commerce-stat-breakdown-item-copy'
                ),
                'commerce-stat-breakdown-item'
            );
        }

        return html_writer::tag(
            'section',
            html_writer::tag(
                'h3',
                s(get_string('commerce_m52_acquisition_origin', 'local_subscriptions')),
                ['class' => 'commerce-stat-breakdown-title']
            ) .
            html_writer::div($items, 'commerce-stat-acquisition-row'),
            ['class' => 'commerce-stat-breakdown-card']
        );
    }

    /**
     * @param array<string,array<string,array<string,int>>> $providers
     * @param callable(int,string):string $money
     */
    private static function providers(array $providers, callable $money): string {
        $items = '';

        foreach ($providers as $currency => $providerrows) {
            foreach ($providerrows as $provider => $row) {
                $items .= html_writer::div(
                    html_writer::div(
                        html_writer::tag(
                            'strong',
                            s(self::provider_label((string)$provider))
                        ) .
                        html_writer::tag(
                            'span',
                            self::currency_flag((string)$currency) . ' ' . s((string)$currency)
                        ),
                        'commerce-stat-provider-name'
                    ) .
                    html_writer::div(
                        html_writer::tag(
                            'strong',
                            s($money((int)($row['revenueminor'] ?? 0), (string)$currency))
                        ) .
                        html_writer::tag(
                            'span',
                            s(get_string(
                                'commerce_m52_provider_orders',
                                'local_subscriptions',
                                (int)($row['orders'] ?? 0)
                            ))
                        ),
                        'commerce-stat-provider-value'
                    ),
                    'commerce-stat-provider-item'
                );
            }
        }

        return html_writer::tag(
            'section',
            html_writer::tag(
                'h3',
                s(get_string('commerce_m52_provider_distribution', 'local_subscriptions')),
                ['class' => 'commerce-stat-breakdown-title']
            ) .
            html_writer::div($items, 'commerce-stat-provider-row'),
            ['class' => 'commerce-stat-breakdown-card']
        );
    }

    private static function provider_label(string $provider): string {
        return match (strtolower($provider)) {
            'stripe' => 'Stripe',
            'alfa' => 'Alfa',
            'manual' => 'Manual',
            'trial' => 'Trial',
            'paypal' => 'PayPal',
            default => ucfirst($provider),
        };
    }

    private static function currency_flag(string $currency): string {
        return match (strtoupper($currency)) {
            'EUR' => '🇪🇺',
            'RUB' => '🇷🇺',
            default => '🌐',
        };
    }
}
