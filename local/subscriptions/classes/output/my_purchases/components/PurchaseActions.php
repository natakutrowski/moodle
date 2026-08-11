<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_purchases\components;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Output model for the actions attached to a purchase card.
 */
final class PurchaseActions implements renderable, templatable {
    /**
     * @param string[] $actions Already-rendered and permission-checked actions.
     */
    public function __construct(
        private readonly array $actions,
        private readonly string $classes = 'mt-2'
    ) {
    }

    public function is_empty(): bool {
        return $this->actions === [];
    }

    public function export_for_template(renderer_base $output): array {
        return [
            'actions' => array_map(
                static fn(string $html): array => ['html' => $html],
                $this->actions
            ),
            'classes' => $this->classes,
            'hasactions' => !$this->is_empty(),
        ];
    }
}
