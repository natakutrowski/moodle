<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_purchases\components;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Output model for one purchase card.
 */
final class PurchaseCard implements renderable, templatable {
    public function __construct(
        private readonly string $header,
        private readonly string $body,
        private readonly PurchaseActions $actions,
        private readonly string $classes = 'card shadow-sm mb-3',
        private readonly string $headerclasses = 'card-header bg-white',
        private readonly string $variant = 'default',
        private readonly string $icon = 'fa-solid fa-bag-shopping'
    ) {
    }

    public function export_for_template(renderer_base $output): array {
        return [
            'header' => $this->header,
            'body' => $this->body,
            'actions' => $this->actions->export_for_template($output),
            'classes' => $this->classes,
            'headerclasses' => $this->headerclasses,
            'variant' => $this->variant,
            'icon' => $this->icon,
        ];
    }
}
