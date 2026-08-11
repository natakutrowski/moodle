<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * One-time, idempotent migration from the historical coupled block structure.
 */
final class CommerceShowroomBlockStructureMigrator {
    private const CANONICAL_ORDER = [
        'hero',
        'stats',
        'problem',
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
        'final_cta',
    ];

    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    /** @return array{changed:int,created:int,converted:int,reordered:bool} */
    public function migrate(
        string $showroomkey,
        int $userid,
        bool $dryrun = false
    ): array {
        $repository = new CommerceShowroomCmsRepository($this->db);
        $showroom = $repository->get_by_key($showroomkey);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom: ' . $showroomkey);
        }

        $blocks = $repository->blocks((int)$showroom->id);
        $bytype = [];
        foreach ($blocks as $block) {
            $bytype[(string)$block->blocktype] ??= $block;
        }

        $method = $bytype['method'] ?? null;
        $journey = $bytype['journey'] ?? null;
        $cta = $bytype['cta'] ?? null;
        $stats = $bytype['stats'] ?? null;
        $exercise = $bytype['exercise_explorer'] ?? null;

        $methodenabled = $method !== null && (int)$method->enabled === 1;
        $journeyenabled = $journey !== null && (int)$journey->enabled === 1;
        $ctaenabled = $cta !== null && (int)$cta->enabled === 1;
        $statsenabled = $stats !== null && (int)$stats->enabled === 1;

        $created = 0;
        $converted = 0;
        $changed = 0;

        if (!$dryrun) {
            if ($journey !== null && !isset($bytype['stage_method'])) {
                $config = $this->merge_defaults(
                    $showroomkey,
                    'stage_method',
                    $this->decode((string)$journey->configjson),
                    ['eyebrow', 'title', 'text']
                );
                $repository->save_block((int)$showroom->id, [
                    'id' => (int)$journey->id,
                    'blocktype' => 'stage_method',
                    'blockkey' => (string)$journey->blockkey,
                    'sortorder' => (int)$journey->sortorder,
                    // Historical stage-method section was unconditional.
                    'enabled' => true,
                    'configjson' => $this->json($config),
                ], $userid);
                $converted++;
                $changed++;
            }

            if ($method !== null && !isset($bytype['learning_method'])) {
                $source = $this->decode((string)$method->configjson);
                $config = $this->merge_defaults(
                    $showroomkey,
                    'learning_method',
                    $source,
                    ['title', 'text']
                );
                $repository->save_block((int)$showroom->id, [
                    'id' => (int)$method->id,
                    'blocktype' => 'learning_method',
                    'blockkey' => (string)$method->blockkey,
                    'sortorder' => (int)$method->sortorder,
                    'enabled' => $methodenabled && $journeyenabled,
                    'configjson' => $this->json($config),
                ], $userid);
                $converted++;
                $changed++;
            }

            if ($cta !== null && !isset($bytype['final_cta'])) {
                $config = $this->merge_defaults(
                    $showroomkey,
                    'final_cta',
                    $this->decode((string)$cta->configjson),
                    ['title', 'text', 'buttonlabel', 'buttontarget', 'style']
                );
                $repository->save_block((int)$showroom->id, [
                    'id' => (int)$cta->id,
                    'blocktype' => 'final_cta',
                    'blockkey' => (string)$cta->blockkey,
                    'sortorder' => (int)$cta->sortorder,
                    'enabled' => $ctaenabled,
                    'configjson' => $this->json($config),
                ], $userid);
                $converted++;
                $changed++;
            }

            // Historical exercise explorer was unconditional in the Mustache.
            if ($exercise !== null && (int)$exercise->enabled !== 1) {
                $repository->set_block_enabled(
                    (int)$showroom->id,
                    (int)$exercise->id,
                    true,
                    $userid
                );
                $changed++;
            }
        }

        $blocks = $dryrun ? $blocks : $repository->blocks((int)$showroom->id);
        $bytype = [];
        foreach ($blocks as $block) {
            $bytype[(string)$block->blocktype] ??= $block;
        }

        $specs = [
            'problem' => [
                'enabled' => $methodenabled && $exercise !== null,
                'source' => $method,
                'fields' => ['eyebrow'],
            ],
            'content_highlights' => [
                'enabled' => $statsenabled,
                'source' => $stats,
                'fields' => $this->layout_fields(),
            ],
            'ascent' => [
                'enabled' => $journeyenabled,
                'source' => $journey,
                'fields' => $this->layout_fields(),
            ],
            'memory_method' => [
                'enabled' => $methodenabled,
                'source' => $method,
                'fields' => ['eyebrow', 'title', 'text'],
            ],
            'trust' => [
                'enabled' => $methodenabled,
                'source' => $method,
                'fields' => $this->layout_fields(),
            ],
            'bonus' => [
                'enabled' => $ctaenabled,
                'source' => $cta,
                'fields' => $this->layout_fields(),
            ],
        ];

        foreach ($specs as $type => $spec) {
            if (isset($bytype[$type])) {
                continue;
            }
            $created++;
            $changed++;
            if ($dryrun) {
                continue;
            }

            $sourceconfig = $spec['source'] instanceof \stdClass
                ? $this->decode((string)$spec['source']->configjson)
                : [];
            $config = $this->merge_defaults(
                $showroomkey,
                $type,
                $sourceconfig,
                $spec['fields']
            );
            $repository->save_block((int)$showroom->id, [
                'blocktype' => $type,
                'enabled' => (bool)$spec['enabled'],
                'configjson' => $this->json($config),
            ], $userid);
        }

        // Testimonials are a genuine optional section; add it disabled if absent.
        if (!isset($bytype['testimonials'])) {
            $created++;
            $changed++;
            if (!$dryrun) {
                $repository->save_block((int)$showroom->id, [
                    'blocktype' => 'testimonials',
                    'enabled' => false,
                    'configjson' => $this->json(
                        CommerceShowroomBlockDefaultsCatalog::for_block(
                            $showroomkey,
                            'testimonials'
                        )
                    ),
                ], $userid);
            }
        }

        $reordered = false;
        if (!$dryrun) {
            $current = $repository->blocks((int)$showroom->id);
            $rank = array_flip(self::CANONICAL_ORDER);
            usort($current, static function(\stdClass $a, \stdClass $b) use ($rank): int {
                $ra = $rank[(string)$a->blocktype] ?? 999;
                $rb = $rank[(string)$b->blocktype] ?? 999;
                return $ra <=> $rb ?: ((int)$a->sortorder <=> (int)$b->sortorder);
            });
            $ids = array_map(static fn(\stdClass $block): int => (int)$block->id, $current);
            $repository->reorder_blocks((int)$showroom->id, $ids, $userid);
            $reordered = true;
        }

        return compact('changed', 'created', 'converted', 'reordered');
    }

    private function merge_defaults(
        string $showroomkey,
        string $targettype,
        array $source,
        array $fields
    ): array {
        $target = CommerceShowroomBlockDefaultsCatalog::for_block(
            $showroomkey,
            $targettype
        );
        foreach ($fields as $field) {
            if (array_key_exists($field, $source)) {
                $target[$field] = $source[$field];
            }
        }

        if (isset($source['translations']) && is_array($source['translations'])) {
            foreach ($source['translations'] as $lang => $translated) {
                if (!is_array($translated)) {
                    continue;
                }
                foreach ($fields as $field) {
                    if (array_key_exists($field, $translated)) {
                        $target['translations'][$lang][$field] = $translated[$field];
                    }
                }
            }
        }
        return $target;
    }

    private function layout_fields(): array {
        return [
            'sectionwidth',
            'sectionbackground',
            'sectionbackgroundcolor',
            'sectionbackgroundimageurl',
            'sectionbackgroundopacity',
            'sectionbackgroundblur',
            'sectionspacing',
            'sectionanimation',
        ];
    }

    private function decode(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function json(array $config): string {
        return json_encode(
            $config,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }
}
