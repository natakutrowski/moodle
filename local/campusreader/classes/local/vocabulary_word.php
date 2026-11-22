<?php
// CampusFR D2.8 — exact word authorization for frozen, grant-protected publications.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class vocabulary_word {
    public static function normalized(string $word): string {
        $word = trim(str_replace('’', "'", $word));
        if ($word === '' || mb_strlen($word, 'UTF-8') > 80 ||
                !mb_check_encoding($word, 'UTF-8') ||
                !preg_match("/\A\p{L}[\p{L}\p{M}]*(?:['-]\p{L}[\p{L}\p{M}]*)*\z/uD", $word)) {
            throw new \invalid_parameter_exception('Invalid word');
        }
        return mb_strtolower($word, 'UTF-8');
    }

    public static function page(string $slug, int $chapter, int $page, string $word): array {
        // Permission is evaluated BEFORE loading or exposing any frozen page content.
        $pub = publication_access::require_customer($slug, $chapter);
        $snapshot = publication_access::frozen($pub);
        if ($chapter < 1 || $chapter > count($snapshot['chapters'])) {
            throw new \invalid_parameter_exception('Unknown chapter');
        }
        $pages = $snapshot['chapters'][$chapter - 1]['pages'];
        if ($page < 0 || $page >= count($pages)) {
            throw new \invalid_parameter_exception('Unknown page');
        }
        $normalized = self::normalized($word);
        $paragraphs = $pages[$page]['paragraphs'];
        foreach ($paragraphs as $paragraph) {
            if (self::contains((string)$paragraph, $normalized)) {
                return [$normalized, $pub];
            }
        }
        throw new \invalid_parameter_exception('Word does not occur on accessible page');
    }

    /** Exact lexical matching; an oversized unrelated token must not reject a valid word. */
    private static function contains(string $paragraph, string $normalized): bool {
        preg_match_all("/\\p{L}[\\p{L}\\p{M}]*(?:['’\\-]\\p{L}[\\p{L}\\p{M}]*)*/u", $paragraph, $matches);
        foreach ($matches[0] as $token) {
            if (mb_strlen($token, 'UTF-8') <= 80 && self::normalized($token) === $normalized) {
                return true;
            }
        }
        return false;
    }

    /** Reuse server-owned frozen text, never accept arbitrary user-supplied context. */
    public static function context(array $paragraphs, string $word, int $index = -1): string {
        $normalized = self::normalized($word);
        if ($index >= 0) {
            if (!isset($paragraphs[$index]) || !self::contains((string)$paragraphs[$index], $normalized)) {
                throw new \invalid_parameter_exception('Word does not occur in selected paragraph');
            }
            $paragraph = (string)$paragraphs[$index];
        } else {
            $paragraph = '';
            foreach ($paragraphs as $candidate) {
                if (self::contains((string)$candidate, $normalized)) {
                    $paragraph = (string)$candidate;
                    break;
                }
            }
            if ($paragraph === '') { throw new \invalid_parameter_exception('Word not found'); }
        }
        // Keep the selected word in the 240-character context used by the provider.
        preg_match_all("/\\p{L}[\\p{L}\\p{M}]*(?:['’\\-]\\p{L}[\\p{L}\\p{M}]*)*/u", $paragraph, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$token, $offset]) {
            if (mb_strlen($token, 'UTF-8') > 80 || self::normalized($token) !== $normalized) { continue; }
            $position = mb_strlen(substr($paragraph, 0, $offset), 'UTF-8');
            $begin = max(0, $position - 75);
            return mb_substr($paragraph, $begin, 240, 'UTF-8');
        }
        throw new \invalid_parameter_exception('Word not found');
    }
}
