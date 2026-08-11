<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\translation;

defined('MOODLE_INTERNAL') || die();

/**
 * Extracts only human-language Storefront fields and safely reapplies translations.
 */
final class CommerceStorefrontTranslationFieldMapper {
    private const TRANSLATABLE_KEYS = [
        'title',
        'subtitle',
        'content',
        'caption',
        'quote',
        'role',
        'alt',
        'question',
        'answer',
        'label',
        'value',
        'description',
    ];

    private const HTML_KEYS = [
        'content',
        'caption',
        'quote',
        'answer',
    ];

    /**
     * @param array<string,mixed> $locale
     * @return array<int,array{id:string,text:string,html:bool,path:array<int|string>}>
     */
    public function extract_locale(array $locale): array {
        $entries = [];
        $this->walk($locale, [], $entries);
        return $entries;
    }

    /**
     * @param array<string,mixed> $showroomlocale
     * @return array<int,array{id:string,text:string,html:bool,path:array<int|string>}>
     */
    public function extract_showroom_locale(array $showroomlocale): array {
        $entries = [];
        if (trim((string)($showroomlocale['alt'] ?? '')) !== '') {
            $entries[] = [
                'id' => 'showroom.alt',
                'text' => (string)$showroomlocale['alt'],
                'html' => false,
                'path' => ['alt'],
            ];
        }
        return $entries;
    }

    /**
     * @param array<string,mixed> $locale
     * @param array<int,array{id:string,text:string,html:bool,path:array<int|string>}> $entries
     * @param array<string,string> $translations
     * @return array<string,mixed>
     */
    public function apply_locale(array $locale, array $entries, array $translations): array {
        foreach ($entries as $entry) {
            if (!array_key_exists($entry['id'], $translations)) {
                continue;
            }
            $locale = $this->set_path(
                $locale,
                $entry['path'],
                $this->clean_translation($translations[$entry['id']], $entry['html'])
            );
        }
        return $locale;
    }

    /**
     * @param array<string,mixed> $showroomlocale
     * @param array<int,array{id:string,text:string,html:bool,path:array<int|string>}> $entries
     * @param array<string,string> $translations
     * @return array<string,mixed>
     */
    public function apply_showroom_locale(array $showroomlocale, array $entries, array $translations): array {
        foreach ($entries as $entry) {
            if (!array_key_exists($entry['id'], $translations)) {
                continue;
            }
            $showroomlocale = $this->set_path(
                $showroomlocale,
                $entry['path'],
                $this->clean_translation($translations[$entry['id']], $entry['html'])
            );
        }
        return $showroomlocale;
    }

    /**
     * @param array<int|string> $path
     * @param array<int,array{id:string,text:string,html:bool,path:array<int|string>}> $entries
     */
    private function walk(array $value, array $path, array &$entries): void {
        foreach ($value as $key => $child) {
            $childpath = [...$path, $key];
            if (is_array($child)) {
                $this->walk($child, $childpath, $entries);
                continue;
            }
            if (!is_string($child) || trim($child) === '') {
                continue;
            }
            $keystring = (string)$key;
            if (!in_array($keystring, self::TRANSLATABLE_KEYS, true)) {
                continue;
            }
            // Testimonial authors and instructor names are identities, not copy.
            if ($keystring === 'label' && !$this->is_quick_fact_path($path)) {
                continue;
            }
            if ($keystring === 'value' && !$this->is_quick_fact_path($path)) {
                continue;
            }
            $entries[] = [
                'id' => $this->path_id($childpath),
                'text' => $child,
                'html' => in_array($keystring, self::HTML_KEYS, true),
                'path' => $childpath,
            ];
        }
    }

    /** @param array<int|string> $path */
    private function is_quick_fact_path(array $path): bool {
        return in_array('quickfacts', $path, true);
    }

    /** @param array<int|string> $path */
    private function path_id(array $path): string {
        return implode('.', array_map(static fn(int|string $part): string => (string)$part, $path));
    }

    /**
     * @param array<string,mixed> $root
     * @param array<int|string> $path
     * @return array<string,mixed>
     */
    private function set_path(array $root, array $path, string $value): array {
        $cursor =& $root;
        foreach ($path as $offset => $segment) {
            if ($offset === array_key_last($path)) {
                $cursor[$segment] = $value;
                break;
            }
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor =& $cursor[$segment];
        }
        unset($cursor);
        return $root;
    }

    private function clean_translation(string $value, bool $html): string {
        $value = trim($value);
        if ($html) {
            return clean_text($value, FORMAT_HTML);
        }
        return clean_param($value, PARAM_TEXT);
    }
}
