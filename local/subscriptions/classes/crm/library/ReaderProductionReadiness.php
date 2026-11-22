<?php
// CampusFR D2.10 — evidence-only status for the production cockpit.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderProductionReadiness {
    /** No publication decisions: read-only facts about editorial files and text. */
    public static function summarize(array $pages, array $inventory): array {
        $count = count($pages);
        $validtext = $count > 0 && $count <= 100;
        $visualtotal = 0;
        $visualwithfile = 0;
        $totalparagraphs = 0;
        $paths = [];
        foreach (($inventory['pageimage'] ?? []) as $file) {
            if (is_array($file) && isset($file['filepath'], $file['filename'])) {
                $paths[(string)$file['filepath'] . (string)$file['filename']] = true;
            }
        }
        foreach (array_values($pages) as $position => $page) {
            if (!is_object($page) || (int)($page->pageindex ?? -1) !== $position ||
                trim((string)($page->title ?? '')) === '' ||
                !in_array((string)($page->kind ?? ''), ['cover', 'back', 'text', 'image', 'mixed'], true)) {
                $validtext = false;
                continue;
            }
            if (($position === 0 && (string)$page->kind !== 'cover') ||
                ($position > 0 && (string)$page->kind === 'cover')) { $validtext = false; }
            $paras = json_decode((string)($page->paragraphsjson ?? ''), true);
            if (!is_array($paras) || !array_is_list($paras) || count($paras) < 1 || count($paras) > 8) {
                $validtext = false;
            } else {
                foreach ($paras as $para) {
                    if (!is_string($para) || trim($para) === '') { $validtext = false; }
                    else { $totalparagraphs++; }
                }
            }
            // Cover, image and mixed pages can contain actual images; placeholders do not count.
            if (in_array((string)$page->kind, ['cover', 'image', 'mixed'], true)) {
                $visualtotal++;
                $name = (string)($page->imagefile ?? '');
                if ($name !== '' && isset($paths['/p' . $position . '/' . $name])) { $visualwithfile++; }
            }
        }
        $audio = count($inventory['narration'] ?? []);
        $pdf = count($inventory['pdf'] ?? []);
        return [
            'pages' => $count, 'paragraphs' => $totalparagraphs,
            'text' => $validtext && $totalparagraphs > 0,
            'illustrated' => $visualwithfile, 'visualpages' => $visualtotal,
            'visuals' => $visualtotal > 0 && $visualwithfile === $visualtotal,
            'audio' => $audio, 'pdf' => $pdf,
            'audio_unique' => $audio === 1, 'pdf_unique' => $pdf === 1,
        ];
    }
}
