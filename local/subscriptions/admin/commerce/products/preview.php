<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\pricing\CommerceProductPromotionService;
use local_subscriptions\commerce\presentation\CommercePresentationContext;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);
$sku = required_param('sku', PARAM_RAW_TRIMMED);

$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();

if (!$product->is_bundle()) {
    throw new coding_exception(
        'Only Bundle products have a bundle preview.'
    );
}

$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/preview.php',
    ['sku' => $sku]
);
$pagetitle = get_string(
    'commerce_bundle_preview_title',
    'local_subscriptions',
    $displayname
);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-bundle-preview-page'
);

$preview = null;
$previewerror = null;
try {
    $preview = $factory
        ->bundle_preview_service()
        ->build($sku, true);
} catch (Throwable $exception) {
    $previewerror = $exception->getMessage();
}

$formatmoney = static function(
    int $minor,
    string $currency
): string {
    return format_float($minor / 100, 2)
        . ' '
        . s($currency);
};

$formatduration = static function($entitlement): string {
    if ($entitlement->is_lifetime()) {
        return get_string(
            'commerce_entitlement_lifetime',
            'local_subscriptions'
        );
    }

    return format_time(
        $entitlement->get_duration_seconds()
    );
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

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string(
        'commerce_product_step_preview',
        'local_subscriptions'
    )
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::PREVIEW
);
echo CommerceProductPageHeaderRenderer::render(
    $pagetitle,
    CommerceDesignSystemRenderer::page_intro(
        get_string(
            'commerce_bundle_preview_intro_n811',
            'local_subscriptions'
        )
    ),
    '',
    get_string(
        'commerce_bundle_preview_eyebrow',
        'local_subscriptions'
    )
);

if ($previewerror !== null) {
    echo CommerceDesignSystemRenderer::empty_state(
        get_string(
            'commerce_bundle_preview_unavailable',
            'local_subscriptions'
        ),
        get_string(
            'commerce_bundle_preview_unavailable_help_n811',
            'local_subscriptions'
        ),
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/components.php',
            ['sku' => $sku]
        ),
        get_string(
            'commerce_bundle_fix_components',
            'local_subscriptions'
        )
    );
} else {
    echo CommerceDesignSystemRenderer::metrics([
        [
            'label' => get_string(
                'commerce_bundle_preview_products',
                'local_subscriptions'
            ),
            'value' => $preview->get_product_count(),
        ],
        [
            'label' => get_string(
                'commerce_bundle_preview_quantity',
                'local_subscriptions'
            ),
            'value' => $preview->get_total_quantity(),
        ],
        [
            'label' => get_string(
                'commerce_bundle_preview_entitlements',
                'local_subscriptions'
            ),
            'value' => $preview->get_entitlement_count(),
        ],
        [
            'label' => get_string(
                'commerce_bundle_preview_depth',
                'local_subscriptions'
            ),
            'value' => $preview->get_maximum_depth(),
        ],
    ]);

    echo html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_bundle_preview_contents_title_n811',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_bundle_preview_contents_help_n811',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'crm-bundle-preview-section-heading'
    );

    if ($preview->get_items() === []) {
        echo CommerceDesignSystemRenderer::empty_state(
            $pagetitle,
            get_string(
                'commerce_bundle_preview_empty',
                'local_subscriptions'
            ),
            new moodle_url(
                '/local/subscriptions/admin/commerce/products/components.php',
                ['sku' => $sku]
            ),
            get_string(
                'commerce_bundle_fix_components',
                'local_subscriptions'
            )
        );
    } else {
        echo html_writer::start_div(
            'crm-bundle-preview-items'
        );

        foreach ($preview->get_items() as $position => $item) {
            $leaf = $item->get_product();
            $leafname =
                CommerceCatalogProductNameResolver::resolve_native_id(
                    $DB,
                    (int)$leaf->get_id(),
                    $leaf->get_name()
                );

            $pricehtml = '';
            foreach ($item->get_prices() as $price) {
                $pricehtml .= html_writer::span(
                    $currencyflag($price->get_currency())
                    . ' '
                    . $formatmoney(
                        $price->get_amount_minor(),
                        $price->get_currency()
                    ),
                    'crm-bundle-preview-price-pill'
                );
            }
            if ($pricehtml === '') {
                $pricehtml = html_writer::span(
                    get_string(
                        'commerce_no_active_price',
                        'local_subscriptions'
                    ),
                    'crm-bundle-preview-muted'
                );
            }

            $entitlementrows = '';
            foreach ($item->get_entitlements() as $entitlement) {
                $entitlementrows .= html_writer::div(
                    html_writer::div(
                        html_writer::tag('i', '', [
                            'class' =>
                                'fa fa-check-circle',
                            'aria-hidden' => 'true',
                        ]),
                        'crm-bundle-preview-right-icon'
                    )
                    . html_writer::div(
                        CommerceProductPresentation::entitlement_html(
                            $entitlement->get_type(),
                            $entitlement->get_resource_key(),
                            $DB,
                            CommercePresentationContext::CRM
                        )
                        . html_writer::div(
                            $formatduration($entitlement)
                            . ' · ×'
                            . $entitlement->get_quantity(),
                            'crm-bundle-preview-right-meta'
                        ),
                        'crm-bundle-preview-right-copy'
                    ),
                    'crm-bundle-preview-right-row'
                );
            }
            if ($entitlementrows === '') {
                $entitlementrows = html_writer::div(
                    get_string(
                        'commerce_no_entitlement',
                        'local_subscriptions'
                    ),
                    'crm-bundle-preview-muted'
                );
            }

            $technicalpaths = '';
            foreach ($item->get_paths() as $path) {
                $technicalpaths .= html_writer::div(
                    s(implode(' → ', $path)),
                    'crm-bundle-preview-tech-line'
                );
            }

            $typebadge =
                CommerceProductPresentation::type_badge(
                    $leaf->get_type()
                );

            echo html_writer::start_div(
                'card crm-bundle-preview-item-card'
            );
            echo html_writer::start_div('card-body');

            echo html_writer::div(
                html_writer::div(
                    html_writer::span(
                        (string)($position + 1),
                        'crm-bundle-preview-order'
                    )
                    . html_writer::div(
                        html_writer::link(
                            new moodle_url(
                                '/local/subscriptions/admin/commerce/products/view.php',
                                [
                                    'id' => (int)$leaf->get_id(),
                                    'origin' => 'native',
                                ]
                            ),
                            html_writer::tag(
                                'h3',
                                format_string($leafname),
                                ['class' => 'h5 mb-1']
                            ),
                            ['class' => 'crm-bundle-preview-name-link']
                        )
                        . $typebadge,
                        'crm-bundle-preview-item-heading-copy'
                    ),
                    'crm-bundle-preview-item-heading'
                )
                . html_writer::span(
                    '×' . $item->get_quantity(),
                    'badge rounded-pill text-bg-primary crm-bundle-preview-quantity'
                ),
                'crm-bundle-preview-item-header'
            );

            echo html_writer::div(
                html_writer::div(
                    html_writer::tag(
                        'h4',
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-credit-card me-2',
                            'aria-hidden' => 'true',
                        ])
                        . get_string(
                            'commerce_bundle_preview_prices',
                            'local_subscriptions'
                        ),
                        ['class' => 'h6 mb-2']
                    )
                    . html_writer::div(
                        $pricehtml,
                        'crm-bundle-preview-price-list'
                    ),
                    'crm-bundle-preview-business-panel'
                )
                . html_writer::div(
                    html_writer::tag(
                        'h4',
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-key me-2',
                            'aria-hidden' => 'true',
                        ])
                        . get_string(
                            'commerce_bundle_preview_rights',
                            'local_subscriptions'
                        ),
                        ['class' => 'h6 mb-2']
                    )
                    . $entitlementrows,
                    'crm-bundle-preview-business-panel'
                ),
                'crm-bundle-preview-business-grid'
            );

            if ($technicalpaths !== '') {
                echo html_writer::tag(
                    'details',
                    html_writer::tag(
                        'summary',
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-code me-2',
                            'aria-hidden' => 'true',
                        ])
                        . get_string(
                            'commerce_bundle_preview_technical_details_n811',
                            'local_subscriptions'
                        ),
                        ['class' => 'crm-bundle-preview-tech-summary']
                    )
                    . html_writer::div(
                        $technicalpaths,
                        'crm-bundle-preview-tech-body'
                    ),
                    ['class' => 'crm-bundle-preview-tech-details']
                );
            }

            echo html_writer::end_div();
            echo html_writer::end_div();
        }

        echo html_writer::end_div();
    }
}

$pricing = $factory->bundle_pricing_service();
$promotionservice = new CommerceProductPromotionService();

echo html_writer::div(
    html_writer::tag(
        'h2',
        get_string(
            'commerce_bundle_preview_pricing',
            'local_subscriptions'
        ),
        ['class' => 'h5 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_bundle_preview_pricing_help_n811',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    ),
    'crm-bundle-preview-section-heading'
);

echo html_writer::start_div(
    'crm-bundle-preview-pricing-grid'
);

foreach (
    $factory
        ->currency_service()
        ->get_product_currencies($sku, true, true)
    as $currency
) {
    try {
        $quote = $pricing->quote(
            $sku,
            $currency,
            true
        );
        $regularminor = $quote
            ->get_final_price()
            ->get_amount_minor();

        $promotion = $promotionservice->resolve(
            $product->get_metadata(),
            $currency,
            $regularminor
        );
        $effectiveminor = (int)(
            $promotion['amountminor']
            ?? $regularminor
        );
        $compareminor = isset(
            $promotion['compareamountminor']
        )
            ? (int)$promotion['compareamountminor']
            : null;

        $pricevalue = html_writer::tag(
            'strong',
            $currencyflag($currency)
            . ' '
            . $formatmoney(
                $effectiveminor,
                $currency
            ),
            ['class' => 'crm-bundle-preview-final-price']
        );

        if (
            $compareminor !== null
            && $compareminor > $effectiveminor
        ) {
            $pricevalue .= html_writer::span(
                $formatmoney(
                    $compareminor,
                    $currency
                ),
                'crm-bundle-preview-compare-price'
            );
        }

        $comparisonhtml = '';
        if ($quote->has_component_comparison()) {
            $comparisonhtml .= html_writer::div(
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
                ),
                'crm-bundle-preview-pricing-meta'
            );
            $comparisonhtml .= html_writer::div(
                get_string(
                    'commerce_bundle_savings',
                    'local_subscriptions'
                )
                . ': '
                . $formatmoney(
                    $quote->get_savings_minor(),
                    $currency
                ),
                'crm-bundle-preview-pricing-meta'
            );
        }

        echo html_writer::div(
            $pricevalue
            . html_writer::div(
                get_string(
                    'commerce_bundle_final_price',
                    'local_subscriptions'
                ),
                'crm-bundle-preview-pricing-label'
            )
            . $comparisonhtml,
            'card card-body crm-bundle-preview-pricing-card'
        );
    } catch (Throwable $exception) {
        echo html_writer::div(
            html_writer::tag(
                'strong',
                $currencyflag($currency)
                . ' '
                . s($currency)
            )
            . html_writer::div(
                get_string(
                    'commerce_bundle_pricing_incomplete',
                    'local_subscriptions'
                ),
                'crm-bundle-preview-muted'
            ),
            'card card-body crm-bundle-preview-pricing-card'
        );
    }
}

echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-info-circle',
        'aria-hidden' => 'true',
    ])
    . html_writer::div(
        html_writer::tag(
            'strong',
            get_string(
                'commerce_bundle_preview_pricing_note_title_n811',
                'local_subscriptions'
            )
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_bundle_preview_pricing_note_n811',
                'local_subscriptions'
            ),
            ['class' => 'mb-0']
        ),
        'crm-bundle-preview-note-copy'
    ),
    'crm-bundle-preview-pricing-note'
);

echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
