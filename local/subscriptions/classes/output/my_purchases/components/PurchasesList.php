<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_purchases\components;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Mustache rendering boundary for purchase-card collections.
 */
final class PurchasesList implements renderable, templatable {
    public function __construct(
        private readonly renderer_base $output
    ) {
    }

    public function render_item(PurchaseCard $card): string {
        return $this->output->render_from_template(
            'local_subscriptions/my_purchases/components/purchase_card',
            $card->export_for_template($this->output)
        );
    }

    /**
     * @param PurchaseCard[] $cards
     */
    public function render(array $cards): string {
        return $this->output->render_from_template(
            'local_subscriptions/my_purchases/components/purchases_list',
            [
                'cards' => array_map(
                    fn(PurchaseCard $card): array => $card->export_for_template($this->output),
                    $cards
                ),
            ]
        );
    }

    public function export_for_template(renderer_base $output): array {
        return [];
    }
}
