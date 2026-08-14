<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;

/**
 * Hydrates a Showroom definition from its CMS record.
 *
 * The PHP Registry is optional compatibility data only. A complete CMS row
 * can therefore be cloned/imported under a new showroom key and remain fully
 * previewable/publishable without adding PHP registry entries.
 */
final class CommerceShowroomCmsDefinitionFactory {
    /** @var string[] */
    private const PRODUCT_ROLES = ['course', 'pdf', 'bundle'];

    public function create(
        \stdClass $record,
        ?CommerceShowroomDefinition $fallback = null
    ): CommerceShowroomDefinition {
        $template = $this->resolve_template(
            (string)$record->template,
            $fallback?->get_template()
        );
        $templatedefinition =
            CommerceShowroomRenderTemplateRegistry::require_definition(
                $template
            );

        $products = $this->resolve_products(
            (string)$record->productsjson,
            $fallback?->get_products() ?? []
        );
        $slugs = $this->resolve_slugs(
            $record,
            $fallback?->get_slugs() ?? []
        );

        $seo = CommerceShowroomSeoConfig::from_settings_json(
            (string)$record->settingsjson
        );
        $offerconfig = CommerceShowroomOfferConfig::from_settings_json(
            (string)$record->settingsjson
        );
        $socialimage = '';
        if (!empty($record->id)) {
            $socialimageurl = (new CommerceShowroomSocialImageService(
                \context_system::instance()
            ))->get_url((int)$record->id);
            $socialimage = $socialimageurl?->out(false) ?? '';
        }

        $titlekey = trim((string)($record->titlekey ?? ''));
        if ($titlekey === '') {
            $titlekey = $fallback?->get_title_key()
                ?? $templatedefinition['titlekey'];
        }

        $descriptionkey = trim(
            (string)($record->descriptionkey ?? '')
        );
        if ($descriptionkey === '') {
            $descriptionkey = $fallback?->get_description_key()
                ?? $templatedefinition['descriptionkey'];
        }

        return new CommerceShowroomDefinition(
            (string)$record->showroomkey,
            $slugs,
            $template,
            $products,
            $titlekey,
            $descriptionkey,
            $seo,
            $offerconfig,
            $socialimage
        );
    }

    /**
     * @param array<string,string> $fallback
     * @return array<string,string>
     */
    private function resolve_slugs(
        \stdClass $record,
        array $fallback
    ): array {
        $resolved = $fallback;

        foreach (['fr', 'en', 'ru'] as $language) {
            $field = 'slug' . $language;
            $slug = trim((string)($record->{$field} ?? ''));

            if ($slug !== '') {
                $resolved[$language] = $slug;
            } else if (array_key_exists($language, $resolved)) {
                // Explicitly empty CMS slug must remain empty for autonomous
                // imports instead of silently exposing a Registry route.
                unset($resolved[$language]);
            }
        }

        return $resolved;
    }

    private function resolve_template(
        string $template,
        ?string $fallback
    ): string {
        $template = trim($template);
        if ($template !== '') {
            return CommerceShowroomRenderTemplateRegistry::normalise(
                $template
            );
        }

        if ($fallback !== null && trim($fallback) !== '') {
            return CommerceShowroomRenderTemplateRegistry::normalise(
                $fallback
            );
        }

        throw new \invalid_parameter_exception(
            'A CMS showroom requires a supported render template.'
        );
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

            $resolved[$role] = clean_param(
                $sku,
                PARAM_RAW_TRIMMED
            );
        }

        return $resolved;
    }
}
