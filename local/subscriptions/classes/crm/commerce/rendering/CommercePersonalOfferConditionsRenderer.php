<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignValidityService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

/**
 * Shared offer-condition fields for individual offers and offer campaigns.
 */
final class CommercePersonalOfferConditionsRenderer {
    /**
     * @param string[] $currencies
     */
    public static function pricing(array $currencies): string {
        $html = html_writer::start_div('crm-personal-offer-conditions-pricing');
        $html .= html_writer::start_div('mb-4');
        $html .= html_writer::tag(
            'label',
            get_string('commerce_personal_offer_pricing', 'local_subscriptions'),
            ['for' => 'strategy', 'class' => 'form-label fw-semibold']
        );
        $html .= html_writer::select([
            CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE =>
                get_string('commerce_personal_offer_strategy_fixed_price', 'local_subscriptions'),
            CommercePersonalOfferTerms::STRATEGY_FIXED_DISCOUNT =>
                get_string('commerce_personal_offer_strategy_fixed_discount', 'local_subscriptions'),
            CommercePersonalOfferTerms::STRATEGY_PERCENTAGE_DISCOUNT =>
                get_string('commerce_personal_offer_strategy_percentage_discount', 'local_subscriptions'),
        ], 'strategy', CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE, false, [
            'id' => 'strategy',
            'class' => 'form-select',
        ]);
        $html .= html_writer::div(
            get_string('commerce_personal_offer_pricing_help', 'local_subscriptions'),
            'form-text'
        );
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('row g-3 mb-4');
        foreach ($currencies as $currency) {
            $code = strtoupper((string)$currency);
            $symbol = match ($code) {
                'EUR' => ' (€)',
                'RUB' => ' (₽)',
                'USD' => ' ($)',
                'GBP' => ' (£)',
                'CAD' => ' (C$)',
                default => '',
            };
            $placeholder = match ($code) {
                'EUR' => '30.00',
                'RUB' => '2990.00',
                default => '0.00',
            };
            $html .= html_writer::start_div('col-12 col-md-4');
            $html .= html_writer::tag(
                'label',
                $code . $symbol,
                [
                    'for' => 'amount-' . strtolower($code),
                    'class' => 'form-label',
                ]
            );
            $html .= html_writer::empty_tag('input', [
                'id' => 'amount-' . strtolower($code),
                'name' => 'amount_' . strtolower($code),
                'type' => 'number',
                'min' => '0',
                'step' => '0.01',
                'class' => 'form-control crm-offers-access-currency-input',
                'placeholder' => $placeholder,
            ]);
            $html .= html_writer::end_div();
        }
        $html .= html_writer::start_div('col-12 col-md-4');
        $html .= html_writer::tag(
            'label',
            get_string('commerce_personal_offer_percent', 'local_subscriptions'),
            ['for' => 'percent', 'class' => 'form-label']
        );
        $html .= html_writer::empty_tag('input', [
            'id' => 'percent',
            'name' => 'percent',
            'type' => 'number',
            'min' => '1',
            'max' => '100',
            'value' => '20',
            'class' => 'form-control',
        ]);
        $html .= html_writer::end_div();
        $html .= html_writer::div(
            get_string('commerce_personal_offer_amounts_display_help', 'local_subscriptions'),
            'col-12 form-text'
        );
        $html .= html_writer::end_div();

        return $html . html_writer::end_div();
    }

    public static function validity(
        bool $allowduration = true,
        bool $allownoexpiration = true
    ): string {
        $html = html_writer::start_div('crm-personal-offer-conditions-validity');
        $html .= html_writer::tag(
            'h3',
            get_string('commerce_personal_offer_validity_title', 'local_subscriptions'),
            ['class' => 'h6 mb-1']
        );
        $html .= html_writer::div(
            get_string('commerce_personal_offer_validity_help', 'local_subscriptions'),
            'form-text mb-3'
        );
        if ($allowduration) {
            $html .= html_writer::start_div('mb-3');
            $html .= html_writer::tag(
                'label',
                get_string('commerce_personal_offer_validity_mode', 'local_subscriptions'),
                ['for' => 'validitymode', 'class' => 'form-label fw-semibold']
            );
            $html .= html_writer::select([
                CommercePersonalOfferCampaignValidityService::MODE_FIXED =>
                    get_string('commerce_personal_offer_validity_fixed', 'local_subscriptions'),
                CommercePersonalOfferCampaignValidityService::MODE_DURATION =>
                    get_string('commerce_personal_offer_validity_duration', 'local_subscriptions'),
            ], 'validitymode', CommercePersonalOfferCampaignValidityService::MODE_FIXED, false, [
                'id' => 'validitymode',
                'class' => 'form-select',
            ]);
            $html .= html_writer::div(
                get_string('commerce_personal_offer_validity_mode_help', 'local_subscriptions'),
                'form-text'
            );
            $html .= html_writer::end_div();
        } else {
            $html .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'id' => 'validitymode',
                'name' => 'validitymode',
                'value' => CommercePersonalOfferCampaignValidityService::MODE_FIXED,
            ]);
        }

        $html .= html_writer::start_div('row g-3', ['id' => 'validity-fixed']);
        foreach ([
            ['validfrom', 'commerce_personal_offer_valid_from', 'commerce_personal_offer_valid_from_help'],
            ['expiresat', 'commerce_personal_offer_expires_at', 'commerce_personal_offer_expires_at_help'],
        ] as [$name, $labelkey, $helpkey]) {
            $html .= html_writer::start_div('col-12 col-md-6');
            $html .= html_writer::tag(
                'label',
                get_string($labelkey, 'local_subscriptions'),
                ['for' => $name, 'class' => 'form-label fw-semibold']
            );
            $attrs = [
                'id' => $name,
                'name' => $name,
                'type' => 'datetime-local',
                'class' => 'form-control',
            ];
            if ($name === 'expiresat') {
                $attrs['data-validity-required'] = '1';
            }
            $html .= html_writer::empty_tag('input', $attrs);
            $html .= html_writer::div(
                get_string($helpkey, 'local_subscriptions'),
                'form-text'
            );
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        if ($allownoexpiration) {
            $html .= html_writer::div(
                html_writer::div(
                    html_writer::empty_tag('input', [
                        'type' => 'checkbox',
                        'name' => 'noexpiration',
                        'value' => 1,
                        'id' => 'noexpiration',
                        'class' => 'form-check-input',
                    ])
                    . html_writer::tag(
                        'label',
                        get_string(
                            'commerce_personal_offer_no_expiration',
                            'local_subscriptions'
                        ),
                        [
                            'for' => 'noexpiration',
                            'class' => 'form-check-label',
                        ]
                    ),
                    'form-check crm-offers-access-no-expiration-check'
                )
                . html_writer::div(
                    get_string(
                        'commerce_personal_offer_no_expiration_help',
                        'local_subscriptions'
                    ),
                    'form-text'
                ),
                'crm-offers-access-no-expiration mt-3'
            );
        }

        if ($allowduration) {
            $html .= html_writer::start_div(
                'row g-3 d-none mt-1',
                ['id' => 'validity-duration']
            );
            $html .= html_writer::start_div('col-7 col-md-4');
            $html .= html_writer::tag(
                'label',
                get_string(
                    'commerce_personal_offer_validity_duration_value',
                    'local_subscriptions'
                ),
                [
                    'for' => 'validitydurationvalue',
                    'class' => 'form-label fw-semibold',
                ]
            );
            $html .= html_writer::empty_tag('input', [
                'id' => 'validitydurationvalue',
                'name' => 'validitydurationvalue',
                'type' => 'number',
                'min' => '1',
                'max' => '8760',
                'value' => '48',
                'class' => 'form-control',
            ]);
            $html .= html_writer::end_div();
            $html .= html_writer::start_div('col-5 col-md-4');
            $html .= html_writer::tag(
                'label',
                get_string(
                    'commerce_personal_offer_validity_duration_unit',
                    'local_subscriptions'
                ),
                [
                    'for' => 'validitydurationunit',
                    'class' => 'form-label fw-semibold',
                ]
            );
            $html .= html_writer::select([
                'hours' => get_string(
                    'commerce_personal_offer_validity_hours',
                    'local_subscriptions'
                ),
                'days' => get_string(
                    'commerce_personal_offer_validity_days',
                    'local_subscriptions'
                ),
            ], 'validitydurationunit', 'hours', false, [
                'id' => 'validitydurationunit',
                'class' => 'form-select',
            ]);
            $html .= html_writer::end_div();
            $html .= html_writer::div(
                get_string(
                    'commerce_personal_offer_validity_duration_help',
                    'local_subscriptions'
                ),
                'col-12 form-text'
            );
            $html .= html_writer::end_div();
        }

        $timezoneoptions = [];
        $nowutc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        foreach (\DateTimeZone::listIdentifiers() as $timezoneid) {
            try {
                $zone = new \DateTimeZone($timezoneid);
                $offsetseconds = $zone->getOffset($nowutc);
                $sign = $offsetseconds >= 0 ? '+' : '-';
                $absolute = abs($offsetseconds);
                $hours = intdiv($absolute, HOURSECS);
                $minutes = intdiv($absolute % HOURSECS, MINSECS);
                $offsetlabel = sprintf(
                    'GMT %s%d:%02d',
                    $sign,
                    $hours,
                    $minutes
                );
                $timezoneoptions[$timezoneid] =
                    $timezoneid . ' (' . $offsetlabel . ')';
            } catch (\Throwable) {
                $timezoneoptions[$timezoneid] = $timezoneid;
            }
        }
        $timezoneoptions['UTC'] = 'UTC (GMT +0:00)';
        ksort($timezoneoptions);

        $html .= html_writer::start_div('mt-3');
        $html .= html_writer::tag(
            'label',
            get_string(
                'commerce_personal_offer_validity_timezone',
                'local_subscriptions'
            ),
            ['for' => 'validitytimezone', 'class' => 'form-label fw-semibold']
        );
        $html .= html_writer::select(
            $timezoneoptions,
            'validitytimezone',
            CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE,
            false,
            [
                'id' => 'validitytimezone',
                'class' => 'form-select',
            ]
        );
        $html .= html_writer::div(
            get_string(
                'commerce_personal_offer_validity_timezone_help',
                'local_subscriptions'
            ),
            'form-text'
        );
        $html .= html_writer::end_div();

        return $html . html_writer::end_div();
    }

    private function __construct() {}
}
