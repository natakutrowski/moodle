<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Fail-closed integrity checks executed before public publication.
 */
final class CommerceShowroomPublicationIntegrityValidator {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceShowroomCmsRepository $repository,
        private readonly \context_system $context
    ) {
    }

    public function validate(int $showroomid): void {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }

        CommerceShowroomRenderTemplateRegistry::normalise(
            (string)$showroom->template
        );

        $products = [];
        foreach (['productsjson', 'settingsjson'] as $field) {
            $decoded = json_decode(
                (string)$showroom->{$field},
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (!is_array($decoded)) {
                $this->fail('invalid ' . $field);
            }
            if ($field === 'productsjson') {
                $products = CommerceShowroomProductConfig::from_json(
                    (string)$showroom->productsjson
                );
            }
        }

        $blocks = $this->repository->blocks($showroomid);
        $hasenabled = false;
        $keys = [];

        foreach ($blocks as $block) {
            $blockid = (int)$block->id;
            $blockkey = (string)$block->blockkey;
            $blocktype = (string)$block->blocktype;

            if (
                $blockkey === ''
                || isset($keys[$blockkey])
                || !CommerceShowroomBlockTypeRegistry::exists($blocktype)
            ) {
                $this->fail('invalid or duplicate block key/type');
            }
            $keys[$blockkey] = true;

            $config = json_decode(
                (string)$block->configjson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (!is_array($config)) {
                $this->fail('invalid config for block ' . $blockkey);
            }

            if ((int)$block->enabled !== 1) {
                continue;
            }

            $hasenabled = true;
            $this->validate_required_fields(
                $blocktype,
                $blockkey,
                $config
            );
            if ($blocktype === 'offers') {
                $this->validate_offer_products($blockkey, $products);
            }
            $this->validate_internal_media_urls(
                $blockid,
                $blockkey,
                $config
            );
        }

        if (!$hasenabled) {
            $this->fail('no enabled block');
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    private function validate_required_fields(
        string $blocktype,
        string $blockkey,
        array $config
    ): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema($blocktype);
        $presentation = trim((string)($config['presentation'] ?? ''));

        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (empty($field['required'])) {
                continue;
            }

            $name = (string)($field['name'] ?? '');
            if ($name === '') {
                continue;
            }

            // The four-column PPT variant is intentionally non-interactive.
            // Its content lives in col1..col4, so the legacy interactive
            // choices/correct-answer/consequences fields are irrelevant and
            // must not block publication.
            if (
                $blocktype === 'problem_interactive'
                && $presentation === 'four_columns_ppt'
                && in_array($name, ['choices', 'correctanswer', 'consequences'], true)
            ) {
                continue;
            }

            // These A1 variants render from their dedicated collections, not
            // from the legacy standard block-level `items` textarea.
            if (
                $name === 'items'
                && (
                    ($blocktype === 'journey_map' && $presentation === 'interactive_a1')
                    || ($blocktype === 'faq' && $presentation === 'a1_travel')
                )
            ) {
                continue;
            }

            $this->validate_required_value(
                $blockkey,
                $name,
                self::required_field_value($config, $field)
            );
        }

        // Keep publication fail-closed for the custom four-column variant:
        // require the three content collections that actually feed its UI.
        if ($blocktype === 'problem_interactive' && $presentation === 'four_columns_ppt') {
            foreach (['col1items', 'col3items', 'col4items'] as $name) {
                $this->validate_required_value($blockkey, $name, $config[$name] ?? null);
            }
        }

        if ($blocktype === 'journey_map' && $presentation === 'interactive_a1') {
            // The interactive map is data-driven; legacy legend `items` is
            // intentionally absent, but positions and captions must exist.
            foreach (['captions', 'geopoints'] as $name) {
                $this->validate_required_value(
                    $blockkey,
                    $name,
                    self::required_field_value($config, [
                        'name' => $name,
                        'translatable' => $name === 'captions',
                    ])
                );
            }
        }

        if ($blocktype === 'faq' && $presentation === 'a1_travel') {
            // The A1 FAQ uses structured question/answer entries instead of
            // the historical pipe-separated `items` format.
            $entriesjson = self::required_field_value($config, [
                'name' => 'entriesjson',
                'translatable' => true,
            ]);
            $this->validate_required_value($blockkey, 'entriesjson', $entriesjson);
            $entries = json_decode((string)$entriesjson, true);
            if (!is_array($entries) || !array_is_list($entries)) {
                $this->fail('invalid entriesjson in block "' . $blockkey . '"');
            }
            $hasvalidentry = false;
            foreach ($entries as $entry) {
                if (
                    is_array($entry)
                    && trim((string)($entry['question'] ?? '')) !== ''
                    && trim((string)($entry['answer'] ?? '')) !== ''
                ) {
                    $hasvalidentry = true;
                    break;
                }
            }
            if (!$hasvalidentry) {
                $this->fail('no valid entriesjson entry in block "' . $blockkey . '"');
            }
        }
    }

    /**
     * Returns a translated value when a portable import stores editorial
     * content only under `translations`, without the legacy FR root copy.
     * A required field stays required: the value must exist in at least one
     * actual locale or at the root.
     *
     * @param array<string,mixed> $config
     * @param array<string,mixed> $field
     */
    private static function required_field_value(array $config, array $field): mixed {
        $name = (string)($field['name'] ?? '');
        $value = $config[$name] ?? null;
        if (empty($field['translatable']) || !self::missing_required_value($value)) {
            return $value;
        }

        $translations = $config['translations'] ?? null;
        if (!is_array($translations)) {
            return $value;
        }
        foreach (['fr', 'en', 'ru'] as $language) {
            $translated = is_array($translations[$language] ?? null)
                ? ($translations[$language][$name] ?? null)
                : null;
            if (!self::missing_required_value($translated)) {
                return $translated;
            }
        }
        return $value;
    }

    private static function missing_required_value(mixed $value): bool {
        return $value === null
            || $value === []
            || (is_string($value) && trim($value) === '');
    }

    /**
     * The Offers block gets its products from showroom.productsjson.
     * The historical block-level `skus` textarea is compatibility-only and
     * must not gate publication anymore.
     *
     * @param array<string,string> $products
     */
    private function validate_offer_products(string $blockkey, array $products): void {
        if ($products === []) {
            $this->fail(
                'no product is configured for offers block "' . $blockkey . '"'
            );
        }
    }

    private function validate_required_value(
        string $blockkey,
        string $name,
        mixed $value
    ): void {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '' || $value === []) {
            $this->fail(
                'required field "' . $name
                . '" is empty in block "' . $blockkey . '"'
            );
        }
    }

    /**
     * Validate only internal Moodle File API references.
     *
     * @param array<string,mixed> $config
     */
    private function validate_internal_media_urls(
        int $blockid,
        string $blockkey,
        array $config
    ): void {
        $urls = [];
        array_walk_recursive(
            $config,
            static function (mixed $value) use (&$urls): void {
                if (
                    is_string($value)
                    && str_contains(
                        $value,
                        '/local_subscriptions/showroom_block_media/'
                    )
                ) {
                    $urls[] = $value;
                }
            }
        );

        foreach (array_unique($urls) as $url) {
            if (
                !preg_match(
                    '#/local_subscriptions/showroom_block_media/'
                    . '(\d+)/(.+)$#',
                    $url,
                    $matches
                )
            ) {
                $this->fail(
                    'malformed internal media URL in block "' . $blockkey . '"'
                );
            }

            $itemid = (int)$matches[1];
            if ($itemid !== $blockid) {
                $this->fail(
                    'stale media itemid in block "' . $blockkey . '"'
                );
            }

            $relative = rawurldecode((string)$matches[2]);
            $lastslash = strrpos($relative, '/');
            if ($lastslash === false) {
                $this->fail(
                    'malformed media path in block "' . $blockkey . '"'
                );
            }

            $filepath = '/' . substr($relative, 0, $lastslash + 1);
            $filename = substr($relative, $lastslash + 1);

            $file = get_file_storage()->get_file(
                $this->context->id,
                CommerceShowroomBlockMediaManager::COMPONENT,
                CommerceShowroomBlockMediaManager::FILEAREA,
                $blockid,
                $filepath,
                $filename
            );

            if ($file === false || $file === null || $file->is_directory()) {
                $this->fail(
                    'missing media file in block "' . $blockkey . '"'
                );
            }
        }
    }

    private function fail(string $detail): never {
        throw new \moodle_exception(
            'commerce_showroom_publish_integrity_failed',
            'local_subscriptions',
            '',
            $detail
        );
    }
}
