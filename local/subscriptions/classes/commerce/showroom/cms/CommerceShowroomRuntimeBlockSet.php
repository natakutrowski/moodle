<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Published Showroom block state exposed to the public renderer.
 *
 * J16S1: missing, unpublished or empty CMS state is unavailable. load() never
 * selects the historical legacy full-page fallback automatically.
 */
final class CommerceShowroomRuntimeBlockSet {
    /** @var string[] */
    private const TYPES = [
        'hero',
        'stats',
        'problem',
        'problem_interactive',
        'learning_method',
        'video',
        'content_highlights',
        'ascent',
        'stage_method',
        'exercise_explorer',
        'offers',
        'comparison',
        'memory_method',
        'trust',
        'testimonials',
        'bonus',
        'faq',
        'support',
        'verbs_cards',
        'final_cta',
        'html',

        // Pre-J16G.2 aliases.
        'journey',
        'method',
        'cta',
    ];

    private const FLOW_TYPES = [
        'hero',
        'problem',
        'problem_interactive',
        'learning_method',
        'video',
        'content_highlights',
        'ascent',
        'stage_method',
        'exercise_explorer',
        'offers',
        'comparison',
        'memory_method',
        'trust',
        'testimonials',
        'bonus',
        'faq',
        'support',
        'verbs_cards',
        'final_cta',
    ];

    /**
     * @param array<string,bool> $enabled
     * @param array<string,array<string,mixed>> $configs
     * @param string[] $sequence
     * @param array<string,int> $blockids
     */
    private function __construct(
        private readonly bool $managed,
        private readonly array $enabled,
        private readonly array $configs,
        private readonly array $sequence,
        private readonly array $blockids
    ) {
    }

    public static function load(
        \moodle_database $db,
        string $showroomkey
    ): self {
        $repository = new CommerceShowroomCmsRepository($db);
        $showroom = $repository->get_by_key($showroomkey);

        if ($showroom === null || (string)$showroom->status !== CommerceShowroomStatus::PUBLISHED) {
            return self::unavailable();
        }

        $blocks = $repository->blocks((int)$showroom->id);
        return self::from_blocks($blocks);
    }

    public static function load_preview(
        \moodle_database $db,
        int $showroomid
    ): self {
        $repository = new CommerceShowroomCmsRepository($db);
        $showroom = $repository->get($showroomid);
        if ($showroom === null) {
            return self::unavailable();
        }

        return self::from_blocks($repository->blocks($showroomid));
    }

    /**
     * @param array<int,\stdClass> $blocks
     */
    private static function from_blocks(array $blocks): self {
        if ($blocks === []) {
            return self::unavailable();
        }

        $enabled = array_fill_keys(self::TYPES, false);
        $configs = [];
        $sequence = [];
        $blockids = [];

        foreach ($blocks as $block) {
            $type = clean_param((string)$block->blocktype, PARAM_ALPHANUMEXT);
            if (!array_key_exists($type, $enabled)) {
                continue;
            }

            $isenabled = (int)$block->enabled === 1;
            $enabled[$type] = $enabled[$type] || $isenabled;

            if (!$isenabled) {
                continue;
            }

            $sequence[] = $type;
            if (!isset($configs[$type])) {
                $configs[$type] = self::decode_config((string)$block->configjson);
                $blockids[$type] = (int)$block->id;
            }
        }

        self::apply_legacy_aliases($enabled, $configs, $sequence);

        return new self(true, $enabled, $configs, $sequence, $blockids);
    }

    public static function unavailable(): self {
        return new self(
            true,
            array_fill_keys(self::TYPES, false),
            [],
            [],
            []
        );
    }

    public static function legacy(): self {
        return new self(
            false,
            array_fill_keys(self::TYPES, true),
            [],
            self::TYPES,
            []
        );
    }

    public function is_managed(): bool {
        return $this->managed;
    }

    public function is_enabled(string $type): bool {
        return !empty($this->enabled[$type]);
    }

    /** @return array<string,mixed> */
    public function config(string $type): array {
        return $this->configs[$type] ?? [];
    }

    /** @return string[] */
    public function sequence(): array {
        return $this->sequence;
    }

    public function block_id(string $type): ?int {
        $blockid = (int)($this->blockids[$type] ?? 0);
        return $blockid > 0 ? $blockid : null;
    }

    /** @return array<string,mixed> */
    public function to_template_data(): array {
        $data = [
            'showroomcmsmanaged' => $this->managed,
            'showroomblocksequence' => implode(',', $this->sequence),
            'showroomorderedblocks' => $this->ordered_template_blocks(),
        ];

        foreach (self::TYPES as $type) {
            $templatekey = str_replace('_', '', $type);
            $data['showblock' . $templatekey] = $this->is_enabled($type);
            $data['blockconfig' . $templatekey] = $this->config($type);
        }

        return $data;
    }

    private static function apply_legacy_aliases(
        array &$enabled,
        array &$configs,
        array &$sequence
    ): void {
        $hasnormalised = !empty($enabled['problem'])
            || !empty($enabled['learning_method'])
            || !empty($enabled['content_highlights'])
            || !empty($enabled['ascent'])
            || !empty($enabled['stage_method'])
            || !empty($enabled['memory_method'])
            || !empty($enabled['bonus'])
            || !empty($enabled['final_cta']);

        if ($hasnormalised) {
            return;
        }

        $methodenabled = !empty($enabled['method']);
        $journeyenabled = !empty($enabled['journey']);
        $ctaenabled = !empty($enabled['cta']);
        $exerciseenabled = array_key_exists('exercise_explorer', $enabled);

        $method = $configs['method'] ?? [];
        $journey = $configs['journey'] ?? [];
        $cta = $configs['cta'] ?? [];

        $enabled['problem'] = $methodenabled && $exerciseenabled;
        $configs['problem'] = self::only_fields($method, ['eyebrow']);

        $enabled['learning_method'] = $methodenabled && $journeyenabled;
        $configs['learning_method'] = self::only_fields($method, ['title', 'text']);

        $enabled['content_highlights'] = !empty($enabled['stats']);
        $configs['content_highlights'] = [];

        $enabled['ascent'] = $journeyenabled;
        $configs['ascent'] = self::only_fields(
            $journey,
            ['sectionwidth', 'sectionbackground', 'sectionbackgroundcolor', 'sectionbackgroundimageurl', 'sectionbackgroundopacity', 'sectionbackgroundblur', 'sectionspacing', 'sectionanimation']
        );

        // These two sections were historically unconditional in the template.
        $enabled['stage_method'] = true;
        $configs['stage_method'] = $journey;
        $enabled['exercise_explorer'] = true;

        $enabled['memory_method'] = $methodenabled;
        $configs['memory_method'] = $method;
        $enabled['trust'] = $methodenabled;
        $configs['trust'] = [];

        $enabled['bonus'] = $ctaenabled;
        $configs['bonus'] = [];
        $enabled['final_cta'] = $ctaenabled;
        $configs['final_cta'] = $cta;

        $sequence = array_values(array_filter(
            self::TYPES,
            static fn(string $type): bool => !in_array($type, ['journey', 'method', 'cta'], true)
                && !empty($enabled[$type])
        ));
    }

    private static function only_fields(array $config, array $fields): array {
        $result = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $config)) {
                $result[$field] = $config[$field];
            }
        }

        if (isset($config['translations']) && is_array($config['translations'])) {
            foreach ($config['translations'] as $lang => $translated) {
                if (!is_array($translated)) {
                    continue;
                }
                foreach ($fields as $field) {
                    if (array_key_exists($field, $translated)) {
                        $result['translations'][$lang][$field] = $translated[$field];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Ordered top-level sections for the public Mustache.
     *
     * Stats intentionally stay inside Hero and are therefore not a top-level
     * flow section. Legacy aliases and generic HTML are excluded here too.
     *
     * @return array<int,array<string,bool|string>>
     */
    private function ordered_template_blocks(): array {
        $blocks = [];
        $seen = [];

        foreach ($this->sequence as $type) {
            if (isset($seen[$type])
                    || !in_array($type, self::FLOW_TYPES, true)
                    || !$this->is_enabled($type)) {
                continue;
            }

            $seen[$type] = true;
            $templatekey = str_replace('_', '', $type);
            $blocks[] = [
                'type' => $type,
                'is' . $templatekey => true,
            ];
        }

        // Legacy mode uses the canonical type list. The sequence already has
        // that order, so this is only a safety fallback for malformed data.
        if ($blocks === [] && !$this->managed) {
            foreach (self::FLOW_TYPES as $type) {
                if (!$this->is_enabled($type)) {
                    continue;
                }
                $templatekey = str_replace('_', '', $type);
                $blocks[] = [
                    'type' => $type,
                    'is' . $templatekey => true,
                ];
            }
        }

        return $blocks;
    }

    /** @return array<string,mixed> */
    private static function decode_config(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
