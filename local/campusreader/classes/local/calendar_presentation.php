<?php
// CampusFR D2.22.1 — Calendar-only localized editorial labels, never access control.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class calendar_presentation {
    public const LANGUAGES = ['fr', 'en', 'ru'];
    public const FIELDS = ['title' => 191, 'subtitle' => 255, 'skytitle' => 191, 'skycaption' => 191];

    public static function language(?string $code = null): string {
        $code = strtolower(str_replace('-', '_', $code ?? current_language()));
        $base = explode('_', $code, 2)[0];
        return in_array($base, self::LANGUAGES, true) ? $base : 'fr';
    }

    public static function defaults(array $snapshot, array $calendar, ?string $code = null): array {
        $first = (int)($calendar['slots'][0]['unlockat'] ?? 0);
        $year = $first > 0 ? (new \DateTimeImmutable('@' . $first))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y') : date('Y');
        $count = is_array($calendar['slots'] ?? null) ? count($calendar['slots']) : 0;
        $lang = self::language($code);
        // Moodle 5+ get_string() does not accept a language as its fourth argument.
        // The string manager supports explicit language selection (CRM FR/EN/RU preview).
        $stringmanager = get_string_manager();
        return [
            'title' => (string)($snapshot['anthology']['title'] ?? ''),
            'subtitle' => (string)($snapshot['anthology']['subtitle'] ?? ''),
            'skytitle' => $stringmanager->get_string('cfr_launch_cal_skytitle', 'local_campusreader', $year, $lang),
            'skycaption' => $stringmanager->get_string('cfr_launch_cal_sky', 'local_campusreader', $count, $lang),
        ];
    }

    public static function resolve(array $snapshot, array $calendar, ?string $code = null): array {
        $defaults = self::defaults($snapshot, $calendar, $code);
        $lang = self::language($code);
        $labels = $calendar['display'][$lang] ?? null;
        if (!is_array($labels)) { return $defaults; }
        foreach (self::FIELDS as $field => $limit) {
            if (array_key_exists($field, $labels) && is_string($labels[$field]) &&
                    \core_text::strlen($labels[$field]) <= $limit) {
                // Empty subtitle is intentional. Other empty labels use the defaults.
                if ($field === 'subtitle' || trim($labels[$field]) !== '') {
                    $defaults[$field] = $labels[$field];
                }
            }
        }
        return $defaults;
    }
}
