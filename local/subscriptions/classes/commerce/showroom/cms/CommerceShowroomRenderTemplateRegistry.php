<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Explicit catalogue of public Moodle templates supported by Showroom.
 *
 * This is deliberately explicit rather than scanning the templates directory:
 * only templates implementing the Showroom public contract may be selected.
 */
final class CommerceShowroomRenderTemplateRegistry {
    /** @return array<string,array{label:string}> */
    public static function definitions(): array {
        return [
            'local_subscriptions/showroom/third_group_verbs' => [
                'label' => 'Showroom — Verbes du 3e groupe',
            ],
        ];
    }

    /** @return array<string,string> */
    public static function options(): array {
        $options = [];
        foreach (self::definitions() as $template => $definition) {
            $options[$template] = $definition['label'];
        }
        return $options;
    }

    public static function normalise(string $template): string {
        $template = trim($template);
        if (!array_key_exists($template, self::definitions())) {
            throw new \invalid_parameter_exception('Unsupported Showroom render template.');
        }
        return $template;
    }
}
