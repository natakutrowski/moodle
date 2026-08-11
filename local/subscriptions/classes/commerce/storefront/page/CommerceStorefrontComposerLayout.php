<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\page;

defined('MOODLE_INTERNAL') || die();

/** Controlled layout contract for the visual Storefront composer. */
final class CommerceStorefrontComposerLayout {
    public const WIDTHS = ['contained', 'wide', 'full'];
    public const COLUMNS = [1, 2, 3];
    public const RATIOS = [
        '100',
        '50_50',
        '40_60',
        '60_40',
        '33_67',
        '67_33',
        '33_33_33',
    ];
    public const BACKGROUNDS = ['default', 'soft', 'accent', 'contrast', 'transparent'];
    public const SPACINGS = ['none', 'small', 'medium', 'large'];
    public const ALIGNMENTS = ['start', 'center', 'end', 'stretch'];

    /** @return array<string,mixed> */
    public static function normalise(array $section, int $position): array {
        $layout = is_array($section['layout'] ?? null) ? $section['layout'] : [];
        $columns = self::choice_int($layout['columns'] ?? 1, self::COLUMNS, 1);
        $ratio = self::choice((string)($layout['ratio'] ?? self::default_ratio($columns)), self::RATIOS, self::default_ratio($columns));

        if (($columns === 1 && $ratio !== '100')
            || ($columns === 2 && !in_array($ratio, ['50_50', '40_60', '60_40', '33_67', '67_33'], true))
            || ($columns === 3 && $ratio !== '33_33_33')) {
            $ratio = self::default_ratio($columns);
        }

        $rowid = trim((string)($layout['rowid'] ?? ''));
        if ($rowid === '' || preg_match('/^[a-z0-9_-]{1,64}$/', $rowid) !== 1) {
            $rowid = 'row-' . ($position + 1);
        }

        return [
            'rowid' => $rowid,
            'column' => max(1, min($columns, (int)($layout['column'] ?? 1))),
            'columns' => $columns,
            'ratio' => $ratio,
            'width' => self::choice((string)($layout['width'] ?? 'contained'), self::WIDTHS, 'contained'),
            'background' => self::choice((string)($layout['background'] ?? 'default'), self::BACKGROUNDS, 'default'),
            'spacing' => self::choice((string)($layout['spacing'] ?? 'medium'), self::SPACINGS, 'medium'),
            'alignment' => self::choice((string)($layout['alignment'] ?? 'stretch'), self::ALIGNMENTS, 'stretch'),
        ];
    }

    public static function default_ratio(int $columns): string {
        return match ($columns) {
            2 => '50_50',
            3 => '33_33_33',
            default => '100',
        };
    }

    /** @param string[] $allowed */
    private static function choice(string $value, array $allowed, string $default): string {
        $value = strtolower(trim($value));
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** @param int[] $allowed */
    private static function choice_int(mixed $value, array $allowed, int $default): int {
        $value = (int)$value;
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
