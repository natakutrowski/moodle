<?php
// CampusFR D2.11.2 - pure page planning/imposition; no DB, HTTP, files or writes.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderBookletImposition {
    /**
     * Retains every source page exactly once, adds an owner page, and moves the
     * back cover to the final physical page. Every booklet has 4n pages.
     * @return array{items:array, sheets:array, source_count:int, blanks:int}
     */
    public static function plan(array $pages): array {
        $pages = array_values($pages);
        $n = count($pages);
        if ($n < 1 || $n > 60) { throw new \InvalidArgumentException('1 à 60 pages attendues'); }
        $hascover = (string)$pages[0]->kind === 'cover';
        $hasback = $n > 1 && (string)$pages[$n-1]->kind === 'back';
        $items = [];
        $items[] = $hascover ? ['kind' => 'source', 'index' => 0] : ['kind' => 'generated_cover'];
        $items[] = ['kind' => 'owner'];
        for ($i = $hascover ? 1 : 0; $i < ($hasback ? $n - 1 : $n); $i++) {
            $items[] = ['kind' => 'source', 'index' => $i];
        }
        $back = $hasback ? ['kind' => 'source', 'index' => $n-1] : ['kind' => 'generated_back'];
        $blanks = 0;
        while ((count($items) + 1) % 4 !== 0) {
            $items[] = ['kind' => 'blank'];
            $blanks++;
        }
        $items[] = $back;
        $total = count($items);
        if ($total % 4 !== 0 || $total < 4 || $total > 64) {
            throw new \RuntimeException('Nombre de pages physique invalide');
        }
        $sheets = [];
        for ($i = 0; $i < $total / 4; $i++) {
            $sheets[] = ['front' => [$total - 2*$i, 2*$i+1], 'back' => [2*$i+2, $total - 2*$i - 1]];
        }
        $seen = [];
        foreach ($sheets as $sheet) {
            foreach (['front', 'back'] as $side) {
                foreach ($sheet[$side] as $num) {
                    if ($num < 1 || $num > $total || isset($seen[$num])) {
                        throw new \RuntimeException('Imposition incohérente');
                    }
                    $seen[$num] = true;
                }
            }
        }
        if (count($seen) !== $total) { throw new \RuntimeException('Imposition incomplète'); }
        return ['items' => $items, 'sheets' => $sheets, 'source_count' => $n, 'blanks' => $blanks];
    }
}
