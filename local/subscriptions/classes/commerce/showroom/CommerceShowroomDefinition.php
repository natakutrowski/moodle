<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** Immutable configuration for one custom Commerce showroom. */
final class CommerceShowroomDefinition {
    /** @param array<string,string> $slugs @param array<string,string> $products */
    public function __construct(
        private readonly string $key,
        private readonly array $slugs,
        private readonly string $template,
        private readonly array $products,
        private readonly string $titlekey,
        private readonly string $descriptionkey,
        private readonly array $seo = [],
        private readonly array $offerconfig = []
    ) {
    }

    public function get_key(): string {
        return $this->key;
    }

    public function get_slug(?string $language = null): string {
        $language = self::normalise_language($language);
        return (string)($this->slugs[$language] ?? $this->slugs['fr'] ?? '');
    }

    /** @return array<string,string> */
    public function get_slugs(): array {
        return $this->slugs;
    }

    public function get_template(): string {
        return $this->template;
    }

    /** @return array<string,string> */
    public function get_products(): array {
        return $this->products;
    }

    public function get_title_key(): string {
        return $this->titlekey;
    }

    public function get_description_key(): string {
        return $this->descriptionkey;
    }

    /** @return array<string,string> */
    /** @return array<string,mixed> */
    public function get_offer_config(string $role): array {
        $config = $this->offerconfig[$role] ?? [];
        return is_array($config) ? $config : [];
    }

    public function is_offer_details_enabled(string $role): bool {
        $config = $this->get_offer_config($role);
        return !array_key_exists('detailsenabled', $config) || !empty($config['detailsenabled']);
    }

    public function get_seo(?string $language = null): array {
        $language = self::normalise_language($language);
        $row = $this->seo[$language] ?? [];
        return is_array($row) ? $row : [];
    }

    private static function normalise_language(?string $language): string {
        $language = strtolower(trim((string)($language ?: current_language())));
        $language = explode('_', str_replace('-', '_', $language))[0];
        return in_array($language, ['fr', 'en', 'ru'], true) ? $language : 'fr';
    }
}
