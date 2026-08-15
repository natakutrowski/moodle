<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\builder;

defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;

/**
 * Shared CRM editing components for all Mail Studio consumers.
 */
final class CommerceMailBuilderEditorRenderer {
    /**
     * @param string[] $variables
     * @param array<int,array{tag:string,label?:string,scope?:string}> $structuraltags
     */
    public static function tag_palette(array $variables, array $structuraltags): string {
        $variablechips = '';
        foreach (array_values(array_unique($variables)) as $variable) {
            $tag = '{{' . trim((string)$variable) . '}}';
            if ($tag === '{{}}') {
                continue;
            }
            $variablechips .= html_writer::tag(
                'button',
                s($tag),
                [
                    'type' => 'button',
                    'class' => 'btn btn-sm btn-light border commerce-mail-builder-tag',
                    'data-mail-builder-tag' => $tag,
                ]
            );
        }

        $structurechips = '';
        foreach ($structuraltags as $definition) {
            $tag = trim((string)($definition['tag'] ?? ''));
            if ($tag === '') {
                continue;
            }
            $structurechips .= html_writer::tag(
                'button',
                s($tag),
                [
                    'type' => 'button',
                    'class' => 'btn btn-sm btn-outline-secondary commerce-mail-builder-tag',
                    'data-mail-builder-tag' => $tag,
                    'title' => (string)($definition['label'] ?? $tag),
                ]
            );
        }

        $structuresection = $structurechips !== ''
            ? html_writer::div(
                html_writer::tag(
                    'strong',
                    get_string('commerce_mail_builder_blocks', 'local_subscriptions')
                )
                . html_writer::div(
                    $structurechips,
                    'commerce-mail-builder-tag-list'
                ),
                'commerce-mail-builder-palette-section'
            )
            : '';

        return html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'strong',
                    get_string('commerce_mail_builder_variables', 'local_subscriptions')
                )
                . html_writer::div(
                    $variablechips !== ''
                        ? $variablechips
                        : html_writer::span(
                            get_string('commerce_mail_builder_no_variables', 'local_subscriptions'),
                            'text-muted'
                        ),
                    'commerce-mail-builder-tag-list'
                ),
                'commerce-mail-builder-palette-section'
            )
            . $structuresection
            . html_writer::div(
                get_string('commerce_mail_builder_tag_help', 'local_subscriptions'),
                'commerce-mail-builder-palette-help'
            ),
            'commerce-mail-builder-palette'
        );
    }

    public static function rich_editor(
        string $id,
        string $name,
        string $value,
        context $context,
        bool $editable = true,
        int $rows = 12,
        string $classes = ''
    ): string {
        $attrs = [
            'id' => $id,
            'name' => $name,
            'rows' => max(4, $rows),
            'class' => trim('form-control commerce-mail-builder-editor ' . $classes),
        ];
        if (!$editable) {
            $attrs['disabled'] = 'disabled';
        }

        $html = html_writer::tag('textarea', $value, $attrs);
        if ($editable) {
            $editor = editors_get_preferred_editor((int)FORMAT_HTML);
            $editor->use_editor($id, [
                'context' => $context,
                'maxfiles' => 0,
                'maxbytes' => 0,
                'noclean' => false,
                'subdirs' => 0,
            ]);
        }
        return $html;
    }

    public static function require_copy_behaviour(\moodle_page $page): void {
        $page->requires->js_amd_inline(<<<'JS'
document.querySelectorAll('[data-mail-builder-tag]').forEach(function(button) {
    button.addEventListener('click', function() {
        var tag = button.dataset.mailBuilderTag || '';
        if (!tag) {
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(tag);
        }
        var original = button.innerHTML;
        button.innerHTML = '✓ ' + tag;
        setTimeout(function() {
            button.innerHTML = original;
        }, 850);
    });
});
JS);
    }

    private function __construct() {}
}
