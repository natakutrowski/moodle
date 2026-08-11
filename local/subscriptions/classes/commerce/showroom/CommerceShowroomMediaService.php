<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

/** Resolves and persists product-specific Showroom media metadata. */
final class CommerceShowroomMediaService {
    public const SLOT = 'showroom';

    public function __construct(
        private readonly CommerceStorefrontContentFileService $files
    ) {
    }

    public static function create(): self {
        return new self(CommerceStorefrontContentFileService::create());
    }

    /** @param array<string,mixed> $metadata @return array<string,mixed> */
    public function definition(array $metadata, string $language): array {
        $configuration = is_array($metadata['showroom'] ?? null)
            ? $metadata['showroom']
            : [];
        $locales = is_array($configuration['locales'] ?? null)
            ? $configuration['locales']
            : [];
        $locale = is_array($locales[$language] ?? null)
            ? $locales[$language]
            : [];
        $itemid = max(0, (int)($configuration['mediaitemid'] ?? 0));
        $url = $this->files->get_slot_url($itemid, self::SLOT);

        return [
            'key' => $this->clean_key((string)($configuration['key'] ?? '')),
            'mediaitemid' => $itemid,
            'alt' => trim((string)($locale['alt'] ?? '')),
            'hasimage' => $url !== null,
            'imageurl' => $url?->out(false) ?? '',
            'diagnostic' => $this->files->slot_diagnostic($itemid, self::SLOT),
        ];
    }

    /** @param array<string,mixed> $metadata @return array<string,mixed> */
    public function merge(
        array $metadata,
        string $language,
        string $key,
        int $itemid,
        string $alt
    ): array {
        $configuration = is_array($metadata['showroom'] ?? null)
            ? $metadata['showroom']
            : [];
        $configuration['key'] = $this->clean_key($key);
        $configuration['mediaitemid'] = $this->files->ensure_item_id($itemid);
        $configuration['locales'] = is_array($configuration['locales'] ?? null)
            ? $configuration['locales']
            : [];
        $configuration['locales'][$language] = [
            'alt' => trim($alt),
        ];
        $metadata['showroom'] = $configuration;
        return $metadata;
    }

    public function ensure_item_id(int $itemid): int {
        return $this->files->ensure_item_id($itemid);
    }

    public function store_upload(int $itemid, string $field): void {
        $this->files->store_uploaded_slot(
            $itemid,
            self::SLOT,
            $field,
            ['png', 'jpg', 'jpeg', 'webp', 'gif']
        );
    }

    private function clean_key(string $key): string {
        $key = strtolower(trim($key));
        return preg_match('/^[a-z0-9_-]{1,80}$/', $key) === 1 ? $key : '';
    }
}
