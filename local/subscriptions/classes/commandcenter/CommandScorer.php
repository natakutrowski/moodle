<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandScorer {

    public static function exact_or_prefix(
        string $query,
        string $value,
        int $exact = 100,
        int $prefix = 90,
        int $contains = 70
    ): int {
        $q = self::normalize($query);
        $v = self::normalize($value);

        if ($q === '' || $v === '') {
            return 0;
        }

        if ($v === $q) {
            return $exact;
        }

        if (strpos($v, $q) === 0) {
            return $prefix;
        }

        if (strpos($v, $q) !== false) {
            return $contains;
        }

        return 0;
    }

    public static function keywords(string $query, string $keywords, int $score = 85): int {
        return self::exact_or_prefix($query, $keywords, 0, 0, $score);
    }

    public static function id(string $query, int $id, int $score = 98): int {
        return trim($query) === (string)$id ? $score : 0;
    }

    public static function email(string $query, string $email): int {
        return self::exact_or_prefix($query, $email, 100, 90, 75);
    }

    public static function username(string $query, string $username): int {
        return self::exact_or_prefix($query, $username, 100, 90, 75);
    }

    public static function fullname(string $query, string $fullname): int {
        return self::exact_or_prefix($query, $fullname, 95, 85, 60);
    }

    public static function slug(string $query, string $slug): int {
        return self::exact_or_prefix($query, $slug, 100, 90, 80);
    }

    public static function title(string $query, string $title): int {
        return self::exact_or_prefix($query, $title, 95, 90, 75);
    }

    public static function filename(string $query, string $filename): int {
        return self::exact_or_prefix($query, $filename, 85, 80, 70);
    }

    public static function transaction(string $query, string $transactionid): int {
        return self::exact_or_prefix($query, $transactionid, 100, 95, 85);
    }

    public static function plan(string $query, string $plan): int {
        return self::exact_or_prefix($query, $plan, 90, 80, 70);
    }

    public static function status(string $query, string $status): int {
        return self::exact_or_prefix($query, $status, 80, 70, 60);
    }

    public static function best(int ...$scores): int {
        return max($scores ?: [0]);
    }

    public static function normalize(string $value): string {
        return \core_text::strtolower(trim($value));
    }
}