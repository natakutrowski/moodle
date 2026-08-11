<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRenderTemplateRegistry;

/**
 * Resolves a public Showroom definition and overlays published CMS products.
 *
 * The PHP registry remains a safe fallback for legacy installs and incomplete
 * CMS records. Once valid productsjson is published, it becomes authoritative
 * for the configured roles.
 */
final class CommerceShowroomPublishedDefinitionResolver {
    /** @var string[] */
    private const PRODUCT_ROLES = ['course', 'pdf', 'bundle'];

    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public function require(string $showroomkey): CommerceShowroomDefinition {
        $fallback = CommerceShowroomRegistry::require($showroomkey);
        $repository = new CommerceShowroomCmsRepository($this->db);
        $record = $repository->get_by_key($showroomkey);

        if (
            $record === null
            || (string)$record->status !== CommerceShowroomStatus::PUBLISHED
        ) {
            return $fallback;
        }

        $products = $this->resolve_products(
            (string)$record->productsjson,
            $fallback->get_products()
        );

        $slugs = $this->resolve_slugs($record, $fallback->get_slugs());
        $template = $this->resolve_template(
            (string)$record->template,
            $fallback->get_template()
        );
        $seo = CommerceShowroomSeoConfig::from_settings_json(
            (string)$record->settingsjson
        );
        $offerconfig = CommerceShowroomOfferConfig::from_settings_json(
            (string)$record->settingsjson
        );

        return new CommerceShowroomDefinition(
            $fallback->get_key(),
            $slugs,
            $template,
            $products,
            (string)($record->titlekey ?: $fallback->get_title_key()),
            (string)($record->descriptionkey ?: $fallback->get_description_key()),
            $seo,
            $offerconfig
        );
    }

    /**
     * @param array<string,string> $fallback
     * @return array<string,string>
     */
    private function resolve_slugs(\stdClass $record, array $fallback): array {
        $resolved = $fallback;
        foreach (['fr', 'en', 'ru'] as $language) {
            $field = 'slug' . $language;
            $slug = trim((string)($record->{$field} ?? ''));
            if ($slug !== '') {
                $resolved[$language] = $slug;
            }
        }
        return $resolved;
    }

    private function resolve_template(string $template, string $fallback): string {
        $template = trim($template);
        if ($template === '') {
            return $fallback;
        }

        try {
            return CommerceShowroomRenderTemplateRegistry::normalise($template);
        } catch (\invalid_parameter_exception) {
            return $fallback;
        }
    }

    /**
     * @param array<string,string> $fallback
     * @return array<string,string>
     */
    private function resolve_products(
        string $productsjson,
        array $fallback
    ): array {
        $decoded = json_decode($productsjson, true);
        if (!is_array($decoded)) {
            return $fallback;
        }

        $resolved = $fallback;
        foreach (self::PRODUCT_ROLES as $role) {
            if (!array_key_exists($role, $decoded)) {
                continue;
            }

            $sku = strtoupper(trim((string)$decoded[$role]));
            if ($sku === '') {
                unset($resolved[$role]);
                continue;
            }

            $resolved[$role] = clean_param($sku, PARAM_RAW_TRIMMED);
        }

        return $resolved;
    }
}
