<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the public Exercise Explorer from its catalogue, CMS copy and File API media.
 */
final class CommerceShowroomExerciseExplorerPresenter {
    public function __construct(
        private readonly \context_system $context
    ) {
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public function apply(
        array $data,
        array $config,
        ?int $blockid,
        ?string $language = null
    ): array {
        $language = CommerceShowroomExerciseCatalog::normalise_language(
            $language ?? current_language()
        );
        $mediamanager = new CommerceShowroomExercisePreviewMediaManager($this->context);
        $exercises = [];

        foreach (CommerceShowroomExerciseCatalog::all($language) as $index => $definition) {
            $position = (int)$definition['position'];
            $prefix = 'exercise' . str_pad((string)$position, 2, '0', STR_PAD_LEFT);
            $title = $this->text(
                $config,
                $prefix . 'title',
                $language,
                (string)$definition['title']
            );
            $text = $this->text(
                $config,
                $prefix . 'text',
                $language,
                (string)$definition['text']
            );

            $media = $blockid !== null
                ? $mediamanager->resolve($blockid, (string)$definition['key'], $language)
                : null;
            $previewurl = $media !== null ? $media['url']->out(false) : '';

            $exercises[] = [
                'key' => (string)$definition['key'],
                'position' => $position,
                'icon' => (string)$definition['icon'],
                'title' => $title,
                'text' => $text,
                'active' => $index === 0,
                'haspreview' => $previewurl !== '',
                'previewurl' => $previewurl,
                'previewlanguage' => (string)($media['language'] ?? ''),
                'previewalt' => $title,
            ];
        }

        $data['exercises'] = $exercises;
        $initial = $exercises[0] ?? null;
        $data['exerciseinitialhaspreview'] = !empty($initial['haspreview']);
        $data['exerciseinitialpreviewurl'] = (string)($initial['previewurl'] ?? '');
        $data['exerciseinitialpreviewalt'] = (string)($initial['previewalt'] ?? '');
        $data['exercisepreviewunavailable'] = get_string(
            'commerce_showroom_exercise_preview_unavailable',
            'local_subscriptions'
        );
        $data['exercisemobileprevious'] = get_string(
            'commerce_showroom_exercise_mobile_previous',
            'local_subscriptions'
        );
        $data['exercisemobilenext'] = get_string(
            'commerce_showroom_exercise_mobile_next',
            'local_subscriptions'
        );
        $data['exercisemobilecounterlabel'] = get_string(
            'commerce_showroom_exercise_mobile_counter',
            'local_subscriptions'
        );
        $data['exercisenavigationhint'] = get_string(
            'commerce_showroom_exercise_navigation_hint',
            'local_subscriptions'
        );
        $data['exercisenavigationlabel'] = get_string(
            'commerce_showroom_exercise_navigation_label',
            'local_subscriptions'
        );
        $data['exercisedesktophint'] = get_string(
            'commerce_showroom_exercise_desktop_hint',
            'local_subscriptions'
        );
        $data['exercisecount'] = count($exercises);

        return $data;
    }

    /**
     * @param array<string,mixed> $config
     */
    private function text(
        array $config,
        string $key,
        string $language,
        string $fallback
    ): string {
        $translations = is_array($config['translations'] ?? null)
            ? $config['translations']
            : [];

        foreach ([
            $translations[$language][$key] ?? null,
            $translations['fr'][$key] ?? null,
            $config[$key] ?? null,
        ] as $candidate) {
            if ($candidate === null) {
                continue;
            }

            $value = trim((string)$candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return $fallback;
    }
}
