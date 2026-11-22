<?php
// This file is part of Moodle - http://moodle.org/.
// Demo JSON repository only. Phase C will move paid content into protected Moodle files.
// @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
namespace local_campusreader\local;

defined('MOODLE_INTERNAL') || die();

final class demo_book_repository {
    private const MAX_BOOKS = 50;
    private const MAX_PAGES = 100;
    private const TYPES = ['cover', 'text', 'image', 'mixed', 'back'];

    private static function book_directory(): string {
        return dirname(__DIR__, 2) . '/demo/books';
    }

    private static function asset_directory(): string {
        return dirname(__DIR__, 2) . '/demo/assets';
    }

    public static function all(): array {
        $files = glob(self::book_directory() . '/*.json') ?: [];
        sort($files, SORT_STRING);
        if (count($files) > self::MAX_BOOKS) {
            throw new \RuntimeException('Trop de livres de démonstration.');
        }
        $result = [];
        foreach ($files as $file) {
            $id = basename($file, '.json');
            if (!self::valid_id($id) || is_link($file)) {
                throw new \RuntimeException('Nom de livre invalide.');
            }
            $result[$id] = self::load($id);
        }
        return $result;
    }

    public static function load(string $id): array {
        if (!self::valid_id($id)) {
            throw new \InvalidArgumentException('Identifiant de livre invalide.');
        }
        $file = self::book_directory() . '/' . $id . '.json';
        if (!is_file($file) || is_link($file)) {
            throw new \InvalidArgumentException('Livre introuvable.');
        }
        $raw = file_get_contents($file);
        if ($raw === false || strlen($raw) > 250000) {
            throw new \RuntimeException('Fichier de démonstration trop volumineux ou illisible.');
        }
        try {
            $book = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('JSON du livre invalide.', 0, $e);
        }
        if (!is_array($book) || ($book['schema'] ?? null) !== 1 || ($book['id'] ?? null) !== $id) {
            throw new \RuntimeException('Le nom du fichier et l’identifiant du livre ne correspondent pas.');
        }
        self::required_text($book, 'title', 140);
        self::required_text($book, 'subtitle', 240);
        $pages = $book['pages'] ?? null;
        if (!is_array($pages) || !array_is_list($pages) || count($pages) < 2 || count($pages) > self::MAX_PAGES) {
            throw new \RuntimeException('Nombre de pages invalide.');
        }
        if (($pages[0]['kind'] ?? '') !== 'cover' || ($pages[array_key_last($pages)]['kind'] ?? '') !== 'back') {
            throw new \RuntimeException('Le livre doit avoir une couverture et une quatrième de couverture.');
        }
        foreach ($pages as $index => $page) {
            if (!is_array($page) || !in_array($page['kind'] ?? null, self::TYPES, true)) {
                throw new \RuntimeException("Type de page invalide ($index).");
            }
            self::required_text($page, 'title', 140);
            $paragraphs = $page['paragraphs'] ?? null;
            if (!is_array($paragraphs) || !array_is_list($paragraphs) || count($paragraphs) < 1 || count($paragraphs) > 8) {
                throw new \RuntimeException("Paragraphes invalides ($index).");
            }
            foreach ($paragraphs as $paragraph) {
                if (!is_string($paragraph) || trim($paragraph) === '' || self::codepoint_length($paragraph) > 1200) {
                    throw new \RuntimeException("Paragraphe invalide ($index).");
                }
            }
            if (array_key_exists('image', $page)) {
                if (!is_string($page['image']) || !preg_match('/\A[a-z0-9][a-z0-9_-]*\.(?:svg|webp|png|jpg|jpeg)\z/D', $page['image'])
                    || !is_file(self::asset_directory() . '/' . $page['image'])
                    || is_link(self::asset_directory() . '/' . $page['image'])) {
                    throw new \RuntimeException("Illustration invalide ($index).");
                }
                self::required_text($page, 'image_alt', 180);
            }
        }
        return $book;
    }

    private static function valid_id(string $id): bool {
        return (bool) preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $id);
    }

    private static function codepoint_length(string $value): int {
        $count = preg_match_all('/./us', $value);
        if ($count === false) {
            throw new \RuntimeException('Chaîne UTF-8 invalide.');
        }
        return $count;
    }

    private static function required_text(array $data, string $key, int $max): void {
        if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === ''
                || self::codepoint_length($data[$key]) > $max) {
            throw new \RuntimeException("Champ invalide : $key.");
        }
    }
}
