<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use moodle_url;

/** Shared breadcrumb and persistent step navigation for Commerce product editing. */
final class CommerceProductEditorNavigationRenderer {
    public const INFORMATION = 'information';
    public const COMPONENTS = 'components';
    public const PRICING = 'pricing';
    public const PREVIEW = 'preview';

    public static function breadcrumb(string $productname, string $currentlabel): string {
        return CrmBreadcrumbRenderer::render([
            ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
            ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
            ['label' => $productname, 'url' => null],
            ['label' => $currentlabel, 'url' => null],
        ]);
    }

    public static function render(string $sku, string $active): string {
        $steps = [
            self::INFORMATION => ['label' => get_string('commerce_product_step_information', 'local_subscriptions'), 'url' => 'edit.php'],
            self::COMPONENTS => ['label' => get_string('commerce_product_step_components', 'local_subscriptions'), 'url' => 'components.php'],
            self::PRICING => ['label' => get_string('commerce_product_step_pricing', 'local_subscriptions'), 'url' => 'pricing.php'],
            self::PREVIEW => ['label' => get_string('commerce_product_step_preview', 'local_subscriptions'), 'url' => 'preview.php'],
        ];

        $items = [];
        $number = 1;
        foreach ($steps as $key => $step) {
            $isactive = $key === $active;
            $content = html_writer::span((string) $number, 'crm-commerce-step-number') .
                html_writer::span($step['label'], 'crm-commerce-step-label');
            $items[] = html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/products/' . $step['url'], ['sku' => $sku]),
                $content,
                ['class' => 'crm-commerce-step' . ($isactive ? ' is-active' : ''), 'aria-current' => $isactive ? 'step' : null]
            );
            $number++;
        }

        return html_writer::tag('nav', implode('', $items), [
            'class' => 'crm-commerce-steps mb-4',
            'aria-label' => get_string('commerce_product_edit_steps', 'local_subscriptions'),
        ]);
    }
}
