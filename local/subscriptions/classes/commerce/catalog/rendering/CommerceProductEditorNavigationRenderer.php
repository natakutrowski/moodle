<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\editing\CommerceProductEditorCapabilities;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use moodle_url;

/** Shared breadcrumb and capability-driven navigation for Commerce product editing. */
final class CommerceProductEditorNavigationRenderer {
    public const INFORMATION = 'information';
    public const PRICES = 'prices';
    public const FULFILLMENTS = 'fulfillments';
    public const ACCESS_SCOPE = 'access_scope';
    public const COMPONENTS = 'components';
    public const PRICING = 'pricing';
    public const PREVIEW = 'preview';
    public const ASSETS = 'assets';
    public const STOREFRONT = 'storefront';

    public static function breadcrumb(string $productname, string $currentlabel): string {
        return CrmBreadcrumbRenderer::render([
            ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
            ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
            ['label' => $productname, 'url' => null],
            ['label' => $currentlabel, 'url' => null],
        ]);
    }

    public static function render(CommerceProduct $product, string $active): string {
        $capabilities = CommerceProductEditorCapabilities::for_product($product);
        $steps = [
            self::INFORMATION => ['label' => get_string('commerce_product_step_information', 'local_subscriptions'), 'url' => 'edit.php'],
        ];

        $steps[self::ASSETS] = ['label' => get_string('commerce_product_step_assets', 'local_subscriptions'), 'url' => 'assets.php'];

        if ($capabilities->can_edit_prices()) {
            $steps[self::PRICES] = ['label' => get_string('commerce_product_step_prices', 'local_subscriptions'), 'url' => 'prices.php'];
        }
        if ($capabilities->can_manage_access_scope()) {
            $steps[self::ACCESS_SCOPE] = ['label' => get_string('commerce_product_step_access_scope', 'local_subscriptions'), 'url' => 'access_scope.php'];
        }
        if ($capabilities->can_edit_components()) {
            $steps[self::COMPONENTS] = ['label' => get_string('commerce_product_step_components', 'local_subscriptions'), 'url' => 'components.php'];
            $steps[self::PRICING] = ['label' => get_string('commerce_product_step_prices', 'local_subscriptions'), 'url' => 'pricing.php'];
        }
        if ($capabilities->can_preview_bundle()) {
            $steps[self::PREVIEW] = ['label' => get_string('commerce_product_step_preview', 'local_subscriptions'), 'url' => 'preview.php'];
        }

        // Page Boutique is deliberately last: editorial composition comes after identity, pricing, access and assets.
        $steps[self::STOREFRONT] = ['label' => get_string('commerce_product_step_storefront', 'local_subscriptions'), 'url' => 'storefront.php'];

        $items = [];
        $items[] = html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/products/view.php', ['sku' => $product->get_sku()]),
            html_writer::span('←', 'me-1', ['aria-hidden' => 'true']) .
                html_writer::span(get_string('commerce_product_back_to_view', 'local_subscriptions')),
            ['class' => 'crm-commerce-step crm-commerce-step-return']
        );
        $number = 1;
        foreach ($steps as $key => $step) {
            $isactive = $key === $active;
            $content = html_writer::span((string)$number, 'crm-commerce-step-number') .
                html_writer::span($step['label'], 'crm-commerce-step-label');
            $attributes = ['class' => 'crm-commerce-step' . ($isactive ? ' is-active' : '')];
            if ($isactive) {
                $attributes['aria-current'] = 'step';
            }
            $items[] = html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/products/' . $step['url'], ['sku' => $product->get_sku()]),
                $content,
                $attributes
            );
            $number++;
        }

        return html_writer::tag('nav', implode('', $items), [
            'class' => 'crm-commerce-steps mb-4',
            'aria-label' => get_string('commerce_product_edit_steps', 'local_subscriptions'),
        ]);
    }
}
