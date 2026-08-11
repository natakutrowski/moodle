<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

/** Removes Storefront configuration and all files owned by its sections. */
final class CommerceStorefrontResetService {
    public function __construct(
        private readonly CommerceStorefrontContentFileService $files
    ) {
    }

    /**
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function reset(array $metadata): array {
        $storefront = $metadata['storefront'] ?? [];
        if (is_string($storefront)) {
            $decoded = json_decode($storefront, true);
            $storefront = is_array($decoded) ? $decoded : [];
        }
        if (is_array($storefront)) {
            foreach ($this->collect_item_ids($storefront) as $itemid) {
                $this->files->delete_item_area($itemid);
            }
        }

        unset(
            $metadata['storefront'],
            $metadata['storefront_template'],
            $metadata['storefront_theme'],
            $metadata['storefront_sections']
        );

        return $metadata;
    }

    /**
     * @param array<string,mixed> $storefront
     * @return int[]
     */
    private function collect_item_ids(array $storefront): array {
        $ids = [];
        $collect = static function (mixed $value) use (&$collect, &$ids): void {
            if (!is_array($value)) {
                return;
            }
            if (isset($value['mediaitemid'])) {
                $itemid = (int)$value['mediaitemid'];
                if ($itemid > 0) {
                    $ids[$itemid] = $itemid;
                }
            }
            foreach ($value as $child) {
                if (is_array($child)) {
                    $collect($child);
                }
            }
        };
        $collect($storefront);
        return array_values($ids);
    }
}
