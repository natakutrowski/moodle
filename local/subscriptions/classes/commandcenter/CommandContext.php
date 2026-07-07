<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandContext {

    private CommandQuery $query;
    private array $tokens;
    private string $language;
    private int $userid;

    private function __construct(CommandQuery $query, array $tokens, string $language, int $userid) {
        $this->query = $query;
        $this->tokens = $tokens;
        $this->language = $language;
        $this->userid = $userid;
    }

    public static function from_command_query(CommandQuery $query): self {
        global $USER;

        return new self(
            $query,
            self::tokenize($query->text()),
            current_language(),
            (int)($USER->id ?? 0)
        );
    }

    public function query(): CommandQuery {
        return $this->query;
    }

    public function raw(): string {
        return $this->query->raw();
    }

    public function text(): string {
        return $this->query->text();
    }

    public function normalized_text(): string {
        return self::normalize($this->text());
    }

    public function tokens(): array {
        return $this->tokens;
    }

    public function language(): string {
        return $this->language;
    }

    public function userid(): int {
        return $this->userid;
    }

    public function is_action_mode(): bool {
        return $this->query->is_action_mode();
    }

    public function has_direct_entity(): bool {
        return $this->query->has_direct_entity();
    }

    public function is_direct_entity(string $entity): bool {
        return $this->query->is_direct_entity($entity);
    }

    public function direct_entity(): ?string {
        return $this->query->entity();
    }

    public function direct_id(): ?int {
        return $this->query->id();
    }

    public function has_token(string $token): bool {
        $token = \core_text::strtolower(trim($token));

        return in_array($token, $this->tokens, true);
    }

    private static function tokenize(string $text): array {
        $text = self::normalize($text);

        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\s+/', $text);

        if (!$parts) {
            return [];
        }

        return array_values(array_filter($parts, static function(string $part): bool {
            return $part !== '';
        }));
    }

    private static function normalize(string $text): string {
        $text = \core_text::strtolower(trim($text));
        $text = str_replace(['’', '\''], '', $text);

        if (class_exists('\Transliterator')) {
            $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator) {
                $text = $transliterator->transliterate($text);
            }
        }

        return $text;
    }

    public static function normalize_text(string $text): string {
        return self::normalize($text);
    }    
}