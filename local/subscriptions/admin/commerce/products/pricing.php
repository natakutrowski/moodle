<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingConfiguration;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingStrategy;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\pricing\CommerceProductPromotionService;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);

$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();

if (!$product->is_bundle()) {
    throw new coding_exception(
        'Only Bundle products have Bundle pricing.'
    );
}

$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);
$pricing = $factory->bundle_pricing_service();
$configuration = $pricing->get_configuration($sku);
$promotionservice = new CommerceProductPromotionService();

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/pricing.php',
    ['sku' => $sku]
);
$pagetitle = get_string(
    'commerce_bundle_pricing_title',
    'local_subscriptions',
    $displayname
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-bundle-pricing-page'
);

$timezone = \core_date::get_user_timezone_object();
$offsetseconds = $timezone->getOffset(new \DateTimeImmutable('now'));
$offsetsign = $offsetseconds >= 0 ? '+' : '-';
$offsetabsolute = abs($offsetseconds);
$timezonelabel = $timezone->getName()
    . ' (GMT '
    . $offsetsign
    . intdiv($offsetabsolute, HOURSECS)
    . ':'
    . str_pad(
        (string)intdiv(
            $offsetabsolute % HOURSECS,
            MINSECS
        ),
        2,
        '0',
        STR_PAD_LEFT
    )
    . ')';

$parsedatetime = static function(
    string $value,
    \DateTimeZone $timezone
): ?int {
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $datetime = \DateTimeImmutable::createFromFormat(
        'Y-m-d\TH:i',
        $value,
        $timezone
    );
    if (!$datetime instanceof \DateTimeImmutable) {
        throw new moodle_exception(
            'commerce_product_promotion_invalid_datetime',
            'local_subscriptions'
        );
    }

    return $datetime->getTimestamp();
};

$datetimevalue = static function(
    ?int $timestamp,
    \DateTimeZone $timezone
): string {
    if ($timestamp === null || $timestamp <= 0) {
        return '';
    }

    return (new \DateTimeImmutable('@' . $timestamp))
        ->setTimezone($timezone)
        ->format('Y-m-d\TH:i');
};

$currencyflag = static function(string $currency): string {
    return match (strtoupper($currency)) {
        'EUR' => '🇪🇺',
        'RUB' => '🇷🇺',
        'USD' => '🇺🇸',
        'GBP' => '🇬🇧',
        'CAD' => '🇨🇦',
        'CHF' => '🇨🇭',
        default => '🌐',
    };
};

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if (
    $action === 'deleteprice'
    && data_submitted()
    && confirm_sesskey()
) {
    $priceid = required_param('priceid', PARAM_INT);
    $currency = strtoupper(
        required_param('currency', PARAM_ALPHA)
    );

    $manager->delete_price($sku, $priceid);

    $freshproduct = $manager
        ->get_editor_data($sku)
        ->get_product();
    $metadata = $promotionservice->without_promotion(
        $freshproduct->get_metadata(),
        $currency
    );
    $manager->save_metadata($sku, $metadata);

    redirect(
        $pageurl,
        get_string(
            'commerce_price_currency_deleted',
            'local_subscriptions',
            $currency
        )
    );
}

if (
    data_submitted()
    && confirm_sesskey()
    && $action !== 'deleteprice'
) {
    $strategy = required_param(
        'strategy',
        PARAM_ALPHANUMEXT
    );
    $discount = optional_param(
        'discountpercent',
        '0',
        PARAM_RAW_TRIMMED
    );

    if (
        !preg_match(
            '/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/',
            $discount
        )
    ) {
        throw new coding_exception(
            'Invalid Bundle discount percentage.'
        );
    }

    $discountbps = (int)round(((float)$discount) * 100);
    if (
        $strategy
        !== CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT
    ) {
        $discountbps = 0;
    }

    $existingcurrencies = $factory
        ->currency_service()
        ->get_product_currencies($sku, true, true);
    if ($existingcurrencies === []) {
        $existingcurrencies = ['EUR'];
    }

    $fixedprices = [];
    foreach ($existingcurrencies as $currency) {
        $raw = optional_param(
            'price_' . strtolower($currency),
            '',
            PARAM_RAW_TRIMMED
        );

        if ($raw === '') {
            continue;
        }
        if (!preg_match('/^\d+(?:[.,]\d{1,2})?$/', $raw)) {
            throw new coding_exception(
                'Invalid fixed price for ' . $currency . '.'
            );
        }

        $fixedprices[$currency] = (int)round(
            ((float)str_replace(',', '.', $raw)) * 100
        );
    }

    $newcurrency = strtoupper(
        optional_param(
            'newcurrency',
            '',
            PARAM_ALPHANUMEXT
        )
    );
    $newprice = optional_param(
        'newprice',
        '',
        PARAM_RAW_TRIMMED
    );

    if ($newcurrency !== '' || $newprice !== '') {
        if (!preg_match('/^[A-Z]{3}$/', $newcurrency)) {
            throw new coding_exception(
                'A currency code must contain exactly three letters.'
            );
        }
        if (
            !preg_match(
                '/^\d+(?:[.,]\d{1,2})?$/',
                $newprice
            )
        ) {
            throw new coding_exception(
                'Invalid price for ' . $newcurrency . '.'
            );
        }

        $fixedprices[$newcurrency] = (int)round(
            (
                (float)str_replace(',', '.', $newprice)
            ) * 100
        );
    }

    $pricing->configure(
        $sku,
        new CommerceBundlePricingConfiguration(
            $strategy,
            $discountbps
        ),
        $fixedprices
    );

    // configure() rebuilds the authoritative Bundle prices. Work on the
    // freshly persisted metadata so the pricing strategy and promotions
    // coexist safely.
    $freshproduct = $manager
        ->get_editor_data($sku)
        ->get_product();
    $metadata = $freshproduct->get_metadata();

    $postcurrencies = $factory
        ->currency_service()
        ->get_product_currencies($sku, true, true);

    foreach ($postcurrencies as $currency) {
        $key = strtolower($currency);
        $enabled = optional_param(
            'promotion_' . $key . '_enabled',
            0,
            PARAM_BOOL
        ) === 1;

        if (!$enabled) {
            $metadata = $promotionservice->without_promotion(
                $metadata,
                $currency
            );
            continue;
        }

        $rawpromo = str_replace(
            ',',
            '.',
            optional_param(
                'promotion_' . $key . '_amount',
                '',
                PARAM_RAW_TRIMMED
            )
        );

        if ($rawpromo === '' || !is_numeric($rawpromo)) {
            throw new moodle_exception(
                'commerce_product_promotion_invalid_price',
                'local_subscriptions'
            );
        }

        $promotionminor = (int)round(
            ((float)$rawpromo) * 100
        );

        $regularprice = null;
        foreach (
            $manager->get_editor_data($sku)->get_prices()
            as $candidate
        ) {
            if (
                $candidate->get_provider() === null
                && $candidate->is_active()
                && $candidate->get_currency() === $currency
            ) {
                $regularprice = $candidate;
                break;
            }
        }

        if (
            $regularprice === null
            || $promotionminor <= 0
            || $promotionminor
                >= $regularprice->get_amount_minor()
        ) {
            throw new moodle_exception(
                'commerce_product_promotion_must_be_lower',
                'local_subscriptions'
            );
        }

        $start = $parsedatetime(
            optional_param(
                'promotion_' . $key . '_start',
                '',
                PARAM_RAW_TRIMMED
            ),
            $timezone
        );
        $end = $parsedatetime(
            optional_param(
                'promotion_' . $key . '_end',
                '',
                PARAM_RAW_TRIMMED
            ),
            $timezone
        );

        if (
            $start !== null
            && $end !== null
            && $end <= $start
        ) {
            throw new moodle_exception(
                'commerce_product_promotion_invalid_period',
                'local_subscriptions'
            );
        }

        $metadata = $promotionservice->with_promotion(
            $metadata,
            $currency,
            $promotionminor,
            $start,
            $end
        );
    }

    $manager->save_metadata($sku, $metadata);
    redirect($pageurl, get_string('changessaved'));
}

$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$configuration = $pricing->get_configuration($sku);
$currencies = $factory
    ->currency_service()
    ->get_product_currencies($sku, true, true);
if ($currencies === []) {
    $currencies = ['EUR'];
}

$existingprices = [];
foreach ($editor->get_prices() as $price) {
    if (
        $price->get_provider() === null
        && $price->is_active()
    ) {
        $existingprices[$price->get_currency()] = $price;
    }
}

$quotes = [];
foreach ($currencies as $currency) {
    try {
        $quotes[$currency] = $pricing->quote(
            $sku,
            $currency,
            true
        );
    } catch (Throwable $exception) {
        $quotes[$currency] = $exception->getMessage();
    }
}

$strategylabels = [
    CommerceBundlePricingStrategy::FIXED =>
        get_string(
            'commerce_bundle_pricing_fixed',
            'local_subscriptions'
        ),
    CommerceBundlePricingStrategy::COMPONENT_SUM =>
        get_string(
            'commerce_bundle_pricing_sum',
            'local_subscriptions'
        ),
    CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT =>
        get_string(
            'commerce_bundle_pricing_discount',
            'local_subscriptions'
        ),
];

$strategyhelp = [
    CommerceBundlePricingStrategy::FIXED =>
        get_string(
            'commerce_bundle_pricing_strategy_fixed_help_n810',
            'local_subscriptions'
        ),
    CommerceBundlePricingStrategy::COMPONENT_SUM =>
        get_string(
            'commerce_bundle_pricing_strategy_sum_help_n810',
            'local_subscriptions'
        ),
    CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT =>
        get_string(
            'commerce_bundle_pricing_strategy_discount_help_n810',
            'local_subscriptions'
        ),
];

$formatmoney = static function(
    int $minor,
    string $currency
): string {
    return format_float($minor / 100, 2)
        . ' '
        . $currency;
};

$promotionstatus = static function(
    ?array $configured
): array {
    if ($configured === null) {
        return [
            'label' => get_string(
                'commerce_product_promotion_none',
                'local_subscriptions'
            ),
            'class' => 'text-bg-secondary',
        ];
    }

    $now = time();
    $start = $configured['start'] ?? null;
    $end = $configured['end'] ?? null;

    if ($start !== null && $now < $start) {
        return [
            'label' => get_string(
                'commerce_product_promotion_scheduled',
                'local_subscriptions'
            ),
            'class' => 'text-bg-info',
        ];
    }
    if ($end !== null && $now > $end) {
        return [
            'label' => get_string(
                'commerce_product_promotion_expired',
                'local_subscriptions'
            ),
            'class' => 'text-bg-secondary',
        ];
    }

    return [
        'label' => get_string(
            'commerce_product_promotion_active',
            'local_subscriptions'
        ),
        'class' => 'text-bg-success',
    ];
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string(
        'commerce_product_step_pricing',
        'local_subscriptions'
    )
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::PRICING
);

echo CommerceProductPageHeaderRenderer::render(
    $pagetitle,
    html_writer::tag(
        'p',
        get_string(
            'commerce_bundle_pricing_intro_n810',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    ),
    '',
    get_string(
        'commerce_bundle_pricing_eyebrow',
        'local_subscriptions'
    )
);

foreach ($existingprices as $currency => $price) {
    if ($price->get_id() === null) {
        continue;
    }

    $deleteformid =
        'commerce-bundle-delete-price-'
        . strtolower($currency);

    echo html_writer::start_tag('form', [
        'id' => $deleteformid,
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'd-none',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sku',
        'value' => $sku,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'deleteprice',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'priceid',
        'value' => $price->get_id(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'currency',
        'value' => $currency,
    ]);
    echo html_writer::end_tag('form');
}

echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'crm-bundle-pricing-form',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => 'save',
]);

echo html_writer::start_div(
    'card card-body crm-bundle-pricing-strategy-card'
);
echo html_writer::div(
    html_writer::tag(
        'i',
        '',
        [
            'class' => 'fa fa-calculator',
            'aria-hidden' => 'true',
        ]
    )
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_bundle_pricing_method',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_bundle_pricing_method_help_n810',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'crm-bundle-pricing-section-copy'
    ),
    'crm-bundle-pricing-section-header'
);

echo html_writer::start_div(
    'crm-bundle-pricing-strategy-options'
);
foreach ($strategylabels as $value => $label) {
    $id = 'bundle-strategy-' . $value;
    echo html_writer::tag(
        'label',
        html_writer::empty_tag('input', [
            'type' => 'radio',
            'name' => 'strategy',
            'id' => $id,
            'value' => $value,
            'class' => 'form-check-input',
        ] + (
            $configuration->get_strategy() === $value
                ? ['checked' => 'checked']
                : []
        ))
        . html_writer::div(
            html_writer::tag(
                'strong',
                $label,
                ['class' => 'crm-bundle-pricing-strategy-name']
            )
            . html_writer::span(
                $strategyhelp[$value],
                'crm-bundle-pricing-strategy-help'
            ),
            'crm-bundle-pricing-strategy-copy'
        ),
        [
            'class' =>
                'crm-bundle-pricing-strategy-option',
            'for' => $id,
            'data-strategy-option' => $value,
        ]
    );
}
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_bundle_discount_percent',
            'local_subscriptions'
        ),
        [
            'for' => 'discountpercent',
            'class' => 'form-label',
        ]
    )
    . html_writer::empty_tag('input', [
        'id' => 'discountpercent',
        'name' => 'discountpercent',
        'value' => $configuration->get_discount_percent(),
        'class' => 'form-control',
        'inputmode' => 'decimal',
    ])
    . html_writer::div(
        get_string(
            'commerce_bundle_discount_percent_help_n810',
            'local_subscriptions'
        ),
        'form-text'
    ),
    'crm-bundle-pricing-discount-field',
    [
        'data-discount-field' => '1',
    ]
);
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag(
        'h2',
        get_string(
            'commerce_bundle_pricing_currency_section',
            'local_subscriptions'
        ),
        ['class' => 'h5 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_bundle_pricing_currency_section_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    ),
    'crm-bundle-pricing-currency-heading'
);

echo html_writer::start_div(
    'crm-bundle-pricing-currency-list'
);

foreach ($currencies as $currency) {
    $price = $existingprices[$currency] ?? null;
    $quote = $quotes[$currency] ?? null;
    $configuredpromo = $promotionservice->configured(
        $product->get_metadata(),
        $currency
    );
    $promostatus = $promotionstatus($configuredpromo);
    $key = strtolower($currency);
    $regularminor = $price?->get_amount_minor();

    echo html_writer::start_div(
        'card card-body crm-bundle-pricing-currency-card'
    );

    echo html_writer::div(
        html_writer::div(
            html_writer::tag(
                'strong',
                $currencyflag($currency)
                . ' '
                . $currency,
                ['class' => 'crm-bundle-pricing-currency-title']
            )
            . (
                $regularminor !== null
                    ? html_writer::span(
                        $formatmoney(
                            $regularminor,
                            $currency
                        ),
                        'crm-bundle-pricing-current-price'
                    )
                    : ''
            ),
            'crm-bundle-pricing-currency-identity'
        )
        . (
            $configuredpromo !== null
                ? html_writer::span(
                    $promostatus['label'],
                    'badge rounded-pill '
                    . $promostatus['class']
                )
                : ''
        ),
        'crm-bundle-pricing-currency-card-header'
    );

    if (
        $configuration->get_strategy()
        === CommerceBundlePricingStrategy::FIXED
    ) {
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_product_pricing_regular_price',
                    'local_subscriptions'
                ),
                [
                    'for' => 'price_' . $key,
                    'class' => 'form-label',
                ]
            )
            . html_writer::empty_tag('input', [
                'id' => 'price_' . $key,
                'name' => 'price_' . $key,
                'value' => $regularminor !== null
                    ? format_float(
                        $regularminor / 100,
                        2
                    )
                    : '',
                'class' => 'form-control',
                'inputmode' => 'decimal',
            ]),
            'crm-bundle-pricing-regular-field'
        );
    } else {
        $calculated = $quote !== null
            && !is_string($quote)
            ? $quote->get_final_price()->get_amount_minor()
            : $regularminor;

        echo html_writer::div(
            html_writer::span(
                get_string(
                    'commerce_bundle_pricing_calculated_price',
                    'local_subscriptions'
                ),
                'crm-bundle-pricing-calculated-label'
            )
            . html_writer::tag(
                'strong',
                $calculated !== null
                    ? $formatmoney(
                        $calculated,
                        $currency
                    )
                    : '—',
                ['class' => 'crm-bundle-pricing-calculated-value']
            ),
            'crm-bundle-pricing-calculated-price'
        );

        if ($regularminor !== null) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'price_' . $key,
                'value' => format_float(
                    $regularminor / 100,
                    2
                ),
            ]);
        }
    }

    if (
        $quote !== null
        && !is_string($quote)
        && $quote->has_component_comparison()
    ) {
        echo html_writer::div(
            html_writer::span(
                get_string(
                    'commerce_bundle_component_total',
                    'local_subscriptions'
                )
                . ': '
                . $formatmoney(
                    $quote
                        ->get_component_total()
                        ->get_amount_minor(),
                    $currency
                )
            )
            . html_writer::span(
                get_string(
                    'commerce_bundle_savings',
                    'local_subscriptions'
                )
                . ': '
                . $formatmoney(
                    $quote->get_savings_minor(),
                    $currency
                )
            ),
            'crm-bundle-pricing-comparison'
        );
    }

    $promotionid = 'bundle-promo-' . $key;
    echo html_writer::start_div(
        'crm-bundle-promotion-panel'
        . (
            $configuredpromo !== null
                ? ' is-enabled'
                : ''
        )
    );
    echo html_writer::div(
        html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'name' =>
                    'promotion_' . $key . '_enabled',
                'id' => $promotionid,
                'value' => 1,
                'class' => 'form-check-input',
                'data-bundle-promotion-toggle' => $key,
            ] + (
                $configuredpromo !== null
                    ? ['checked' => 'checked']
                    : []
            ))
            . html_writer::tag(
                'label',
                get_string(
                    'commerce_product_promotion_enable',
                    'local_subscriptions'
                ),
                [
                    'for' => $promotionid,
                    'class' => 'form-check-label fw-semibold',
                ]
            ),
            'form-check'
        )
        . html_writer::span(
            get_string(
                'commerce_product_promotion_auto_restore',
                'local_subscriptions'
            ),
            'crm-bundle-promotion-auto-help'
        ),
        'crm-bundle-promotion-panel-header'
    );

    echo html_writer::start_div(
        'crm-bundle-promotion-fields',
        [
            'data-bundle-promotion-fields' => $key,
        ]
    );

    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_promotion_price',
                'local_subscriptions'
            ),
            [
                'for' => 'promotion-' . $key . '-amount',
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'id' => 'promotion-' . $key . '-amount',
            'name' => 'promotion_' . $key . '_amount',
            'value' => $configuredpromo !== null
                ? format_float(
                    (
                        (int)$configuredpromo[
                            'saleamountminor'
                        ]
                    ) / 100,
                    2
                )
                : '',
            'class' => 'form-control',
            'inputmode' => 'decimal',
            'placeholder' => '49.00',
        ]),
        'crm-bundle-promotion-field'
    );

    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_promotion_start',
                'local_subscriptions'
            ),
            [
                'for' => 'promotion-' . $key . '-start',
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'type' => 'datetime-local',
            'id' => 'promotion-' . $key . '-start',
            'name' => 'promotion_' . $key . '_start',
            'value' => $configuredpromo !== null
                ? $datetimevalue(
                    $configuredpromo['start'],
                    $timezone
                )
                : '',
            'class' => 'form-control',
        ]),
        'crm-bundle-promotion-field'
    );

    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_promotion_end',
                'local_subscriptions'
            ),
            [
                'for' => 'promotion-' . $key . '-end',
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'type' => 'datetime-local',
            'id' => 'promotion-' . $key . '-end',
            'name' => 'promotion_' . $key . '_end',
            'value' => $configuredpromo !== null
                ? $datetimevalue(
                    $configuredpromo['end'],
                    $timezone
                )
                : '',
            'class' => 'form-control',
        ]),
        'crm-bundle-promotion-field'
    );

    echo html_writer::end_div();
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-clock-o me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_product_promotion_timezone',
            'local_subscriptions',
            $timezonelabel
        ),
        'crm-bundle-promotion-timezone'
    );
    echo html_writer::end_div();

    if (
        $price !== null
        && $price->get_id() !== null
    ) {
        $deleteformid =
            'commerce-bundle-delete-price-'
            . strtolower($currency);

        echo html_writer::div(
            html_writer::tag(
                'button',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-trash-o me-1',
                    'aria-hidden' => 'true',
                ])
                . get_string('delete'),
                [
                    'type' => 'submit',
                    'class' => 'btn btn-sm btn-outline-danger',
                    'form' => $deleteformid,
                    'data-confirmation' => 'modal',
                    'data-confirmation-title-str' =>
                        json_encode([
                            'commerce_price_currency_delete_title',
                            'local_subscriptions',
                        ]),
                    'data-confirmation-question-str' =>
                        json_encode([
                            'commerce_price_currency_delete_confirm',
                            'local_subscriptions',
                            $currency,
                        ]),
                ]
            ),
            'crm-bundle-pricing-delete'
        );
    }

    echo html_writer::end_div();
}

echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body crm-bundle-pricing-add-currency'
);
echo html_writer::tag(
    'h2',
    html_writer::tag('i', '', [
        'class' => 'fa fa-plus-circle me-2',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_bundle_add_currency',
        'local_subscriptions'
    ),
    ['class' => 'h5 mb-1']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_bundle_add_currency_help_n810',
        'local_subscriptions'
    ),
    ['class' => 'text-muted mb-3']
);
echo html_writer::start_div(
    'crm-bundle-pricing-add-currency-grid'
);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string('currency'),
        [
            'for' => 'newcurrency',
            'class' => 'form-label',
        ]
    )
    . html_writer::empty_tag('input', [
        'id' => 'newcurrency',
        'name' => 'newcurrency',
        'class' => 'form-control',
        'maxlength' => 3,
        'placeholder' => 'USD',
    ]),
    'crm-bundle-pricing-add-field'
);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_product_pricing_regular_price',
            'local_subscriptions'
        ),
        [
            'for' => 'newprice',
            'class' => 'form-label',
        ]
    )
    . html_writer::empty_tag('input', [
        'id' => 'newprice',
        'name' => 'newprice',
        'class' => 'form-control',
        'inputmode' => 'decimal',
        'placeholder' => '99.00',
    ]),
    'crm-bundle-pricing-add-field'
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-save me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('savechanges'),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    ),
    'crm-bundle-pricing-save-actions'
);
echo html_writer::end_tag('form');

$PAGE->requires->js_init_code(<<<JS
(function() {
    function currentStrategy() {
        var checked = document.querySelector(
            'input[name="strategy"]:checked'
        );
        return checked ? checked.value : 'fixed';
    }

    function syncStrategy() {
        var strategy = currentStrategy();
        var discount = document.querySelector(
            '[data-discount-field]'
        );
        if (discount) {
            discount.classList.toggle(
                'is-hidden',
                strategy !== 'percentage_discount'
            );
        }

        document.querySelectorAll(
            '[data-strategy-option]'
        ).forEach(function(option) {
            option.classList.toggle(
                'is-selected',
                option.getAttribute('data-strategy-option')
                    === strategy
            );
        });
    }

    document.querySelectorAll(
        'input[name="strategy"]'
    ).forEach(function(input) {
        input.addEventListener('change', syncStrategy);
    });

    function syncPromotion(toggle) {
        var key = toggle.getAttribute(
            'data-bundle-promotion-toggle'
        );
        var fields = document.querySelector(
            '[data-bundle-promotion-fields="' + key + '"]'
        );
        if (!fields) {
            return;
        }
        fields.classList.toggle(
            'is-disabled',
            !toggle.checked
        );
        fields.querySelectorAll('input').forEach(
            function(input) {
                input.disabled = !toggle.checked;
            }
        );
    }

    document.querySelectorAll(
        '[data-bundle-promotion-toggle]'
    ).forEach(function(toggle) {
        syncPromotion(toggle);
        toggle.addEventListener('change', function() {
            syncPromotion(toggle);
        });
    });

    syncStrategy();
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
