<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\admin;

defined('MOODLE_INTERNAL') || die();

use core_text;

/** Generates immutable, readable Native product SKUs from an English technical name. */
final class CommerceProductSkuGenerator {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function generate(string $type, string $englishname): string {
        $prefix = strtoupper(trim($type));
        $name = core_text::strtolower(trim($englishname));
        if ($name === '') {
            throw new \coding_exception('An English technical product name is required to generate a SKU.');
        }
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $ascii = $ascii === false ? $name : $ascii;
        $slug = strtoupper(trim((string)preg_replace('/[^A-Za-z0-9]+/', '_', $ascii), '_'));
        $slug = substr($slug, 0, 80);
        if ($slug === '') {
            throw new \coding_exception('The English technical product name cannot generate a valid SKU.');
        }
        $base = $prefix . '.' . $slug;
        $candidate = $base;
        $suffix = 2;
        while ($this->db->record_exists('local_subs_commerce_product', ['sku' => $candidate])) {
            $candidate = substr($base, 0, 112) . '_' . $suffix;
            $suffix++;
        }
        return $candidate;
    }
}
