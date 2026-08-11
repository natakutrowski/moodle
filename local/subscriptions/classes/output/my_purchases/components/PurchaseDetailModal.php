<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_purchases\components;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Output model for the purchase-detail modal.
 */
final class PurchaseDetailModal implements renderable, templatable {
    /**
     * @param array<int, array<int|string, mixed>> $rows
     */
    public function __construct(
        private readonly string $modalid,
        private readonly string $title,
        private readonly array $rows
    ) {
    }

    public function render(renderer_base $output): string {
        return $output->render_from_template(
            'local_subscriptions/my_purchases/components/purchase_detail_modal',
            $this->export_for_template($output)
        );
    }

    public function export_for_template(renderer_base $output): array {
        $rows = [];

        foreach ($this->rows as $row) {
            if (!empty($row['section'])) {
                $rows[] = [
                    'issect' => true,
                    'section' => (string)$row['section'],
                    'icon' => (string)($row['icon'] ?? 'fa-solid fa-circle-info'),
                ];
                continue;
            }

            $rows[] = [
                'isrow' => true,
                'label' => (string)($row[0] ?? ''),
                'value' => (string)($row[1] ?? ''),
            ];
        }

        return [
            'modalid' => $this->modalid,
            'title' => $this->title,
            'rows' => $rows,
            'closelabel' => get_string('close', 'local_subscriptions'),
        ];
    }
}
