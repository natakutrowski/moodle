<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\digital\library;

defined('MOODLE_INTERNAL') || die();

/**
 * Customer-facing representation of one owned digital product.
 */
final class CommerceDigitalResourcePresentation {
    private const ALLOWED_SOURCES = ['legacy', 'native'];

    /**
     * @param CommerceDigitalDownloadPresentation[] $downloads
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $source,
        public readonly ?string $producturl,
        public readonly ?string $orderurl,
        public readonly ?string $orderreference,
        public readonly int $purchasedat,
        public readonly array $downloads,
        public readonly bool $frombundle = false,
        public readonly array $metadata = [],
        public readonly ?string $coverurl = null,
        public readonly ?array $coverresponsive = null
    ) {
        if (trim($key) === '') {
            throw new \coding_exception('A digital resource key cannot be empty.');
        }
        if (trim($title) === '') {
            throw new \coding_exception('A digital resource title cannot be empty.');
        }
        if (!in_array($source, self::ALLOWED_SOURCES, true)) {
            throw new \coding_exception('Unsupported digital resource source: ' . $source);
        }
        foreach ($downloads as $download) {
            if (!$download instanceof CommerceDigitalDownloadPresentation) {
                throw new \coding_exception('Digital resource downloads must use CommerceDigitalDownloadPresentation.');
            }
        }
    }

    public function has_downloads(): bool {
        foreach ($this->downloads as $download) {
            if ($download->available) {
                return true;
            }
        }
        return false;
    }

    public function export(): array {
        $downloads = array_values(array_map(
            static fn(CommerceDigitalDownloadPresentation $download): array => $download->export(),
            array_filter(
                $this->downloads,
                static fn(CommerceDigitalDownloadPresentation $download): bool => $download->available
            )
        ));

        return [
            'key' => $this->key,
            'title' => trim($this->title),
            'producturl' => $this->producturl,
            'hasproducturl' => $this->producturl !== null,
            'coverurl' => $this->coverresponsive['src'] ?? $this->coverurl,
            'coversrcset' => $this->coverresponsive['srcset'] ?? '',
            'coverresponsive' => $this->coverresponsive !== null,
            'coverwidth' => $this->coverresponsive['width'] ?? 480,
            'coverheight' => $this->coverresponsive['height'] ?? 600,
            'hascover' => ($this->coverresponsive['src'] ?? $this->coverurl) !== null,
            'downloads' => $downloads,
            'downloadcount' => count($downloads),
            'hasdownloads' => $downloads !== [],
            'hasmultiplefiles' => count($downloads) > 1,
            'metadata' => $this->metadata,
        ];
    }
}
