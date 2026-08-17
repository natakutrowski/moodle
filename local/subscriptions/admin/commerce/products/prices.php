<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\pricing\CommerceProductPromotionService;
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
$registry = new CommerceCurrencyRegistry();
$promotionservice = new CommerceProductPromotionService();
$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/prices.php',
    ['sku' => $sku]
);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    get_string('commerce_product_prices_title', 'local_subscriptions'),
    'local-subscriptions-commerce-product-prices-page'
);

$timezone = \core_date::get_user_timezone_object();
$timezoneid = $timezone->getName();
$offsetseconds = $timezone->getOffset(new \DateTimeImmutable('now'));
$offsetsign = $offsetseconds >= 0 ? '+' : '-';
$offsetabsolute = abs($offsetseconds);
$timezoneoffset = sprintf(
    'GMT %s%d:%02d',
    $offsetsign,
    intdiv($offsetabsolute, HOURSECS),
    intdiv($offsetabsolute % HOURSECS, MINSECS)
);
$timezonelabel = $timezoneid . ' (' . $timezoneoffset . ')';

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

$action = optional_param('action', '', PARAM_ALPHA);
if ($action !== '' && confirm_sesskey()) {
    if ($action === 'delete') {
        $priceid = required_param('priceid', PARAM_INT);
        $deletedcurrency = '';
        foreach ($editor->get_prices() as $candidate) {
            if ($candidate->get_id() === $priceid) {
                $deletedcurrency = $candidate->get_currency();
                break;
            }
        }

        $manager->delete_price($sku, $priceid);
        if ($deletedcurrency !== '') {
            $metadata = $promotionservice->without_promotion(
                $product->get_metadata(),
                $deletedcurrency
            );
            $manager->save_metadata($sku, $metadata);
        }

        redirect(
            $pageurl,
            get_string('commerce_price_deleted', 'local_subscriptions')
        );
    }

    $priceid = optional_param('priceid', 0, PARAM_INT);
    $existingprice = null;
    if ($priceid > 0) {
        foreach ($editor->get_prices() as $candidate) {
            if ($candidate->get_id() === $priceid) {
                $existingprice = $candidate;
                break;
            }
        }
        if ($existingprice === null) {
            throw new moodle_exception('invalidrecord', 'error');
        }
    }

    $currency = $registry->require_enabled(
        strtoupper(required_param('currency', PARAM_ALPHA))
    );
    $amount = str_replace(
        ',',
        '.',
        required_param('amount', PARAM_RAW_TRIMMED)
    );
    if (!is_numeric($amount) || (float)$amount < 0) {
        throw new moodle_exception(
            'commerce_invalid_price',
            'local_subscriptions'
        );
    }
    $regularminor = (int)round(((float)$amount) * 100);

    if (
        $manager->price_currency_exists(
            $sku,
            $currency,
            $priceid ?: null
        )
    ) {
        throw new moodle_exception(
            'commerce_price_currency_duplicate',
            'local_subscriptions'
        );
    }

    $price = new CommerceProductPrice(
        $sku,
        CommerceMoney::from_minor($regularminor, $currency),
        optional_param('active', 0, PARAM_BOOL) === 1,
        $existingprice?->get_provider(),
        $existingprice?->get_provider_price_id(),
        [],
        $priceid ?: null
    );

    if ($priceid > 0) {
        $manager->update_price($price);
    } else {
        $manager->save_price($price);
    }

    $metadata = $product->get_metadata();
    $oldcurrency = $existingprice?->get_currency();
    if ($oldcurrency !== null && $oldcurrency !== $currency) {
        $metadata = $promotionservice->without_promotion(
            $metadata,
            $oldcurrency
        );
    }

    $promotionenabled = optional_param(
        'promotion_enabled',
        0,
        PARAM_BOOL
    ) === 1;

    if ($promotionenabled) {
        $promotionamount = str_replace(
            ',',
            '.',
            optional_param(
                'promotion_amount',
                '',
                PARAM_RAW_TRIMMED
            )
        );
        if (
            $promotionamount === ''
            || !is_numeric($promotionamount)
        ) {
            throw new moodle_exception(
                'commerce_product_promotion_invalid_price',
                'local_subscriptions'
            );
        }

        $promotionminor = (int)round(
            ((float)$promotionamount) * 100
        );
        if (
            $promotionminor <= 0
            || $promotionminor >= $regularminor
        ) {
            throw new moodle_exception(
                'commerce_product_promotion_must_be_lower',
                'local_subscriptions'
            );
        }

        $promotionstart = $parsedatetime(
            optional_param(
                'promotion_start',
                '',
                PARAM_RAW_TRIMMED
            ),
            $timezone
        );
        $promotionend = $parsedatetime(
            optional_param(
                'promotion_end',
                '',
                PARAM_RAW_TRIMMED
            ),
            $timezone
        );

        if (
            $promotionstart !== null
            && $promotionend !== null
            && $promotionend <= $promotionstart
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
            $promotionstart,
            $promotionend
        );
    } else {
        $metadata = $promotionservice->without_promotion(
            $metadata,
            $currency
        );
    }

    $manager->save_metadata($sku, $metadata);
    redirect($pageurl, get_string('changessaved'));
}

$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$prices = $editor->get_prices();
$currencyoptions = $registry->options();

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

$promotionstatus = static function(
    ?array $configured,
    int $regularminor
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

    $sale = (int)($configured['saleamountminor'] ?? 0);
    if ($sale <= 0 || $sale >= $regularminor) {
        return [
            'label' => get_string(
                'commerce_product_promotion_invalid',
                'local_subscriptions'
            ),
            'class' => 'text-bg-danger',
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

$renderprice = static function(
    ?CommerceProductPrice $price = null
) use (
    $sku,
    $currencyoptions,
    $product,
    $promotionservice,
    $datetimevalue,
    $timezone,
    $timezoneid,
    $timezonelabel,
    $currencyflag,
    $promotionstatus
): string {
    $id = $price?->get_id() ?? 0;
    $currency = $price?->get_currency() ?? '';
    $amount = $price
        ? number_format(
            $price->get_amount_minor() / 100,
            2,
            '.',
            ''
        )
        : '';

    $configured = $currency !== ''
        ? $promotionservice->configured(
            $product->get_metadata(),
            $currency
        )
        : null;
    $legacy = $currency !== '' && $configured === null
        ? $promotionservice->legacy_configured(
            $product->get_metadata(),
            $currency
        )
        : null;

    // The upgrade normally migrates this automatically. Keep a clear visual
    // fallback if an old record could not be converted.
    if ($configured === null && $legacy !== null && $price !== null) {
        $configured = [
            'saleamountminor' => $price->get_amount_minor(),
            'start' => $legacy['start'],
            'end' => $legacy['end'],
        ];
    }

    $promotionenabled = $configured !== null;
    $promotionamount = $promotionenabled
        ? number_format(
            ((int)$configured['saleamountminor']) / 100,
            2,
            '.',
            ''
        )
        : '';
    $promotionstart = $promotionenabled
        ? $datetimevalue($configured['start'], $timezone)
        : '';
    $promotionend = $promotionenabled
        ? $datetimevalue($configured['end'], $timezone)
        : '';

    $status = $price !== null
        ? $promotionstatus(
            $configured,
            $price->get_amount_minor()
        )
        : null;

    $formid = 'commerce-price-form-' . ($id ?: 'new');

    $html = html_writer::start_tag('form', [
        'id' => $formid,
        'method' => 'post',
        'class' => 'crm-product-pricing-card',
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sku',
        'value' => $sku,
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'priceid',
        'value' => $id,
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => $id ? 'update' : 'create',
    ]);

    $headercurrency = $currency !== ''
        ? $currencyflag($currency) . ' ' . $currency
        : get_string(
            'commerce_product_pricing_new_currency',
            'local_subscriptions'
        );

    $headerbadges = '';
    if ($price !== null) {
        $headerbadges .= html_writer::span(
            get_string(
                $price->is_active()
                    ? 'commerce_product_pricing_enabled'
                    : 'commerce_product_pricing_disabled',
                'local_subscriptions'
            ),
            'badge rounded-pill '
            . ($price->is_active()
                ? 'text-bg-success'
                : 'text-bg-secondary')
        );
    }
    if ($status !== null && $promotionenabled) {
        $headerbadges .= html_writer::span(
            $status['label'],
            'badge rounded-pill ' . $status['class']
        );
    }

    $html .= html_writer::div(
        html_writer::div(
            html_writer::tag(
                'strong',
                $headercurrency,
                ['class' => 'crm-product-pricing-currency-title']
            )
            . html_writer::span(
                get_string(
                    'commerce_product_pricing_regular_price_help',
                    'local_subscriptions'
                ),
                'crm-product-pricing-currency-help'
            ),
            'crm-product-pricing-card-title'
        )
        . html_writer::div(
            $headerbadges,
            'crm-product-pricing-card-badges'
        ),
        'crm-product-pricing-card-header'
    );

    $html .= html_writer::start_div(
        'crm-product-pricing-base-grid'
    );
    $html .= html_writer::div(
        html_writer::tag(
            'label',
            get_string('currency'),
            [
                'for' => 'currency-' . ($id ?: 'new'),
                'class' => 'form-label',
            ]
        )
        . html_writer::select(
            $currencyoptions,
            'currency',
            $currency,
            false,
            [
                'id' => 'currency-' . ($id ?: 'new'),
                'class' => 'form-select',
            ]
        ),
        'crm-product-pricing-field'
    );

    $html .= html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_pricing_regular_price',
                'local_subscriptions'
            ),
            [
                'for' => 'amount-' . ($id ?: 'new'),
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'id' => 'amount-' . ($id ?: 'new'),
            'name' => 'amount',
            'value' => $amount,
            'class' => 'form-control',
            'inputmode' => 'decimal',
            'required' => true,
            'placeholder' => '54.00',
        ]),
        'crm-product-pricing-field'
    );

    $activeid = 'commerce-price-active-' . ($id ?: 'new');
    $activecheckbox = html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'active',
        'id' => $activeid,
        'value' => 1,
        'class' => 'form-check-input',
    ] + (($price?->is_active() ?? true)
        ? ['checked' => 'checked']
        : []));
    $html .= html_writer::div(
        html_writer::div(
            $activecheckbox
            . html_writer::tag(
                'label',
                get_string(
                    'commerce_product_pricing_available_for_sale',
                    'local_subscriptions'
                ),
                [
                    'for' => $activeid,
                    'class' => 'form-check-label',
                ]
            ),
            'form-check crm-product-pricing-active-check'
        ),
        'crm-product-pricing-field is-toggle'
    );
    $html .= html_writer::end_div();

    $promotionid = 'promotion-enabled-' . ($id ?: 'new');
    $promotioncheckbox = html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'promotion_enabled',
        'id' => $promotionid,
        'value' => 1,
        'class' => 'form-check-input',
        'data-promotion-toggle' => (string)($id ?: 'new'),
    ] + ($promotionenabled ? ['checked' => 'checked'] : []));

    $html .= html_writer::start_div(
        'crm-product-promotion-panel'
        . ($promotionenabled ? ' is-enabled' : '')
    );
    $html .= html_writer::div(
        html_writer::div(
            $promotioncheckbox
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
            'crm-product-promotion-auto-help'
        ),
        'crm-product-promotion-panel-header'
    );

    $html .= html_writer::start_div(
        'crm-product-promotion-fields',
        [
            'data-promotion-fields' => (string)($id ?: 'new'),
        ]
    );

    $html .= html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_promotion_price',
                'local_subscriptions'
            ),
            [
                'for' => 'promotion-amount-' . ($id ?: 'new'),
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'id' => 'promotion-amount-' . ($id ?: 'new'),
            'name' => 'promotion_amount',
            'value' => $promotionamount,
            'class' => 'form-control',
            'inputmode' => 'decimal',
            'placeholder' => '39.00',
        ]),
        'crm-product-pricing-field'
    );

    $html .= html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_promotion_start',
                'local_subscriptions'
            ),
            [
                'for' => 'promotion-start-' . ($id ?: 'new'),
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'type' => 'datetime-local',
            'id' => 'promotion-start-' . ($id ?: 'new'),
            'name' => 'promotion_start',
            'value' => $promotionstart,
            'class' => 'form-control',
        ]),
        'crm-product-pricing-field'
    );

    $html .= html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_promotion_end',
                'local_subscriptions'
            ),
            [
                'for' => 'promotion-end-' . ($id ?: 'new'),
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'type' => 'datetime-local',
            'id' => 'promotion-end-' . ($id ?: 'new'),
            'name' => 'promotion_end',
            'value' => $promotionend,
            'class' => 'form-control',
        ]),
        'crm-product-pricing-field'
    );

    $html .= html_writer::end_div();

    $html .= html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-clock-o me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_product_promotion_timezone',
            'local_subscriptions',
            $timezonelabel
        ),
        'crm-product-promotion-timezone'
    );

    $html .= html_writer::end_div();

    $buttons = html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-save me-1',
            'aria-hidden' => 'true',
        ])
        . get_string($id ? 'savechanges' : 'add'),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    );

    if ($id) {
        $deleteurl = new moodle_url(
            '/local/subscriptions/admin/commerce/products/prices.php',
            [
                'sku' => $sku,
                'action' => 'delete',
                'priceid' => $id,
                'sesskey' => sesskey(),
            ]
        );
        $buttons .= html_writer::link(
            $deleteurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-trash-o me-1',
                'aria-hidden' => 'true',
            ])
            . get_string('delete'),
            [
                'class' => 'btn btn-outline-danger',
                'data-confirmation' => 'modal',
                'data-confirmation-title-str' =>
                    json_encode(['delete']),
                'data-confirmation-question-str' =>
                    json_encode(['areyousure']),
            ]
        );
    }

    $html .= html_writer::div(
        $buttons,
        'crm-product-pricing-card-actions'
    );
    $html .= html_writer::end_tag('form');

    return $html;
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string(
        'commerce_product_step_prices',
        'local_subscriptions'
    )
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::PRICES
);

echo html_writer::div(
    html_writer::tag(
        'h1',
        get_string(
            'commerce_product_prices_title',
            'local_subscriptions'
        ),
        ['class' => 'h2 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_product_pricing_business_intro',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    ),
    'crm-product-pricing-page-header'
);

if ($prices !== []) {
    echo html_writer::start_div(
        'crm-product-pricing-list'
    );
    foreach ($prices as $price) {
        echo $renderprice($price);
    }
    echo html_writer::end_div();
}

echo html_writer::tag(
    'h2',
    get_string(
        'commerce_product_pricing_add_currency',
        'local_subscriptions'
    ),
    ['class' => 'h5 mt-4 mb-2']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_product_pricing_add_currency_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted small']
);
echo $renderprice();

$PAGE->requires->js_init_code(<<<JS
(function() {
    function sync(toggle) {
        var key = toggle.getAttribute('data-promotion-toggle');
        var fields = document.querySelector(
            '[data-promotion-fields="' + key + '"]'
        );
        if (!fields) {
            return;
        }
        fields.classList.toggle('is-disabled', !toggle.checked);
        fields.querySelectorAll('input').forEach(function(input) {
            input.disabled = !toggle.checked;
        });
    }

    document.querySelectorAll('[data-promotion-toggle]').forEach(
        function(toggle) {
            sync(toggle);
            toggle.addEventListener('change', function() {
                sync(toggle);
            });
        }
    );
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
