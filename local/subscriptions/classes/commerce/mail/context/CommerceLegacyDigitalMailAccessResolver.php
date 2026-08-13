<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\context;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;
use local_subscriptions\url\UrlFactory;
use moodle_database;

/**
 * Reconstructs customer download actions for projected Legacy digital purchases.
 *
 * Native projected purchases intentionally preserve the historical Commerce snapshot,
 * but they do not always have Native grant/digital-access rows. Transactional access
 * mail therefore needs a read-only fallback to the authoritative Legacy download token.
 */
final class CommerceLegacyDigitalMailAccessResolver {
    public function __construct(private readonly moodle_database $database) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    /**
     * @return array{title:string,accesses:array<int,array<string,mixed>>}|null
     */
    public function resolve(int $legacyid, string $language): ?array {
        if ($legacyid <= 0) {
            return null;
        }

        $purchase = $this->database->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => $legacyid],
            '*',
            IGNORE_MISSING
        );
        if (!$purchase) {
            return null;
        }

        $status = strtolower(trim((string)($purchase->status ?? '')));
        if (!in_array($status, ['paid', 'completed'], true)) {
            return null;
        }

        $token = trim((string)($purchase->download_token ?? ''));
        if ($token === '') {
            return null;
        }

        $productid = (int)($purchase->productid ?? 0);
        if ($productid <= 0) {
            return null;
        }

        // The product may have been disabled since the historical purchase.
        // Access to an already-paid resource must still be recoverable.
        $product = product_manager::get_product_by_id($productid, false);
        if (!$product) {
            return null;
        }

        $language = strtolower(substr(trim($language), 0, 2));
        if (!in_array($language, ['fr', 'en', 'ru'], true)) {
            $language = 'ru';
        }

        $translation = product_manager::get_product_translation($productid, $language);
        if (!$translation && $language !== 'fr') {
            $translation = product_manager::get_product_translation($productid, 'fr');
        }

        $title = trim((string)($translation->title ?? $product->name ?? ''));
        $accesses = [[
            'kind' => 'download',
            'variant' => 'desktop',
            'label' => '',
            'title' => $title,
            'productsku' => '',
            'coverurl' => '',
            'url' => UrlFactory::digital_download(['token' => $token])->out(false),
            'filename' => trim((string)($product->filename ?? '')),
            'filetype' => 'PDF',
            'filesize' => '',
        ]];

        if (trim((string)($product->mobile_filename ?? '')) !== '') {
            $accesses[] = [
                'kind' => 'download',
                'variant' => 'mobile',
                'label' => '',
                'title' => $title,
                'productsku' => '',
                'coverurl' => '',
                'url' => UrlFactory::digital_download([
                    'token' => $token,
                    'version' => 'mobile',
                ])->out(false),
                'filename' => trim((string)$product->mobile_filename),
                'filetype' => 'PDF',
                'filesize' => '',
            ];
        }

        return [
            'title' => $title,
            'accesses' => $accesses,
        ];
    }
}
