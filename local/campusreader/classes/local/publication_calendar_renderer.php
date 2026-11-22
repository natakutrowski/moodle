<?php
// CampusFR D2.4.3 — frozen schedule rendered with the Phase B Advent components.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class publication_calendar_renderer {
    public static function render(\stdClass $publication, array $snapshot, array $calendar): string {
        global $USER;
        $now = time();
        $switchat = (int)($calendar['libraryafter'] ?? 0);
        $mode = $switchat > 0 && $now >= $switchat ? 'library' : 'advent';
        $slots = $calendar['slots'];
        if (!is_array($slots) || !array_is_list($slots) || count($slots) < 1 || count($slots) > 31) {
            throw new \invalid_parameter_exception('Invalid frozen calendar');
        }
        $cards = [];
        $tz = new \DateTimeZone('Europe/Paris');
        $labels = calendar_presentation::resolve($snapshot, $calendar);
        foreach ($slots as $slot) {
            $day = (int)($slot['position'] ?? 0);
            $chapter = (int)($slot['chapter'] ?? 0);
            if ($day < 1 || $chapter < 1 || !isset($snapshot['chapters'][$chapter - 1])) {
                throw new \invalid_parameter_exception('Invalid frozen calendar chapter');
            }
            $open = publication_access::chapter_allowed($publication, $chapter, $now, $snapshot);
            $datepassed = (int)$slot['unlockat'] <= $now;
            $pending = $datepassed && !$open;
            $date = (new \DateTimeImmutable('@' . (int)$slot['unlockat']))->setTimezone($tz)->format('d/m');
            $cards[] = [
                'day' => $day, 'chapter' => $chapter, 'date' => $date, 'open' => $open, 'pending' => $pending,
                'title' => $open ? (string)$snapshot['chapters'][$chapter - 1]['title'] : '',
                'size' => in_array((string)($slot['layoutsize'] ?? ''),
                    ['feature', 'wide', 'tall'], true) ? (string)$slot['layoutsize'] : 'normal',
                'url' => $open ? (new \moodle_url('/local/campusreader/publication.php',
                    ['slug' => $publication->slug, 'chapter' => $chapter]))->out(false) : null,
            ];
        }
        $nextunlock = 0;
        foreach ($slots as $slot) {
            if ((int)$slot['unlockat'] > $now && (!$nextunlock || (int)$slot['unlockat'] < $nextunlock)) {
                $nextunlock = (int)$slot['unlockat'];
            }
        }
        $out = '<section class="cfr-library cfr-library--' . s($mode) . '" data-cfr-library-mode="' . s($mode) .
            '" data-cfr-session-key="cfr.publication.advent.' . s((string)$publication->slug) . '.' . (int)$USER->id .
            '" data-cfr-next-unlock="' . $nextunlock . '"' .
            ' data-cfr-server-now="' . $now . '"' .
            ' data-cfr-opened="' . s(get_string('cfr_launch_cal_opened_js', 'local_campusreader')) . '"' .
            ' data-cfr-closed="' . s(get_string('cfr_launch_cal_closed_js', 'local_campusreader')) . '"' .
            ' data-cfr-replaywait="' . s(get_string('cfr_launch_cal_replaywait', 'local_campusreader')) . '"' .
            ' data-cfr-replaydone="' . s(get_string('cfr_launch_cal_replaydone', 'local_campusreader')) . '">';
        // The Moodle page heading + editorial subtitle already supply the main title.
        // No secondary eyebrow/intro/count: one clear hierarchy above the 24 doors.
        if ($mode === 'advent') {
            $curated24 = [11,3,7,18,1,14,5,22,9,12,2,16,24,8,20,4,15,10,6,23,13,17,21,19];
            $rank = array_flip($curated24);
            if (count($cards) === 24) {
                usort($cards, static fn($a,$b):int => $rank[$a['day']] <=> $rank[$b['day']]);
            }
            $out .= '<section class="cfr-advent" aria-label="' . s(get_string('cfr_launch_cal_label', 'local_campusreader')) . '">';
            $out .= '<div class="cfr-advent__sky">';
            if ($nextunlock > 0) {
                $firstdate = (new \DateTimeImmutable('@' . $nextunlock))->setTimezone($tz);
                $displaydate = $firstdate->format('d/m/Y H:i');
                $counttext = get_string($now < (int)$slots[0]['unlockat'] ?
                    'cfr_launch_cal_firstopens_short' : 'cfr_launch_cal_nextopens_short',
                    'local_campusreader', $displaydate);
                $out .= '<div class="cfr-advent__countdown" role="timer" aria-live="off" aria-label="' .
                    s(get_string('cfr_launch_cal_countdown', 'local_campusreader')) . '">' .
                    '<div class="cfr-advent__countdown-label">✦ ' . s($counttext) . ' ✦</div>' .
                    '<div class="cfr-advent__countdown-units" data-cfr-countdown>';
                foreach (['days', 'hours', 'minutes', 'seconds'] as $unit) {
                    $out .= '<span class="cfr-advent__countdown-unit"><strong data-cfr-countdown-' . $unit . '>—</strong>' .
                        '<small>' . s(get_string('cfr_launch_cal_unit_' . $unit, 'local_campusreader')) . '</small></span>';
                }
                $out .= '</div></div>';
            }
            $out .= '<span class="cfr-advent__sky-sparks" aria-hidden="true">✧ ✦ ✧</span>' .
                '<span class="cfr-advent__sky-month">' . s($labels['skytitle']) . '</span>' .
                '<span class="cfr-advent__sky-caption">' . s($labels['skycaption']) . '</span></div>' .
                '<ol class="cfr-advent__grid">';
            foreach ($cards as $card) {
                $day = $card['day'];
                $size = $card['size'];
                if ($size === 'normal') {
                    $size = in_array($day, [1,24], true) ? 'feature' :
                        (in_array($day, [7,12,17,23], true) ? 'tall' :
                        (in_array($day, [3,8,11,15,20], true) ? 'wide' : 'normal'));
                }
                $link = $card['url'];
                $revealid = 'cfr-advent-reveal-' . $day;
                $out .= '<li class="cfr-advent__slot cfr-advent__slot--' . s($size) .
                    ($link ? ' is-available' : ' is-unavailable') . '" data-cfr-day="' . $day . '"><div class="cfr-advent__case">';
                $front = '<span class="cfr-advent__door-front"><span class="cfr-advent__door-crest" aria-hidden="true">✦</span>' .
                    '<span class="cfr-advent__number">' . $day . '</span><span class="cfr-advent__decoration" aria-hidden="true">✧</span>' .
                    '<span class="cfr-advent__date">' . s($card['date']) . '</span><span class="cfr-advent__invitation">' .
                    s(get_string($link ? 'cfr_launch_cal_invite' : 'cfr_launch_cal_soon', 'local_campusreader')) . '</span><span class="cfr-advent__pull-tab" aria-hidden="true">⌄</span></span>' .
                    '<span class="cfr-advent__door-back" aria-hidden="true"><span>✧</span><small>' . s(get_string('cfr_launch_cal_surprise', 'local_campusreader')) . '</small></span>';
                if ($link) {
                    $out .= '<a class="cfr-advent__door" data-cfr-door href="' . s($link) . '" aria-controls="' .
                        $revealid . '" aria-expanded="false" aria-label="' . s(get_string('cfr_launch_cal_openaria', 'local_campusreader', $day)) . '">' . $front . '</a>' .
                        '<div class="cfr-advent__reveal" id="' . $revealid . '" hidden>' .
                        '<span class="cfr-advent__reveal-rays" aria-hidden="true"></span>' .
                        '<span class="cfr-advent__reveal-symbol" aria-hidden="true">✦</span>' .
                        '<span class="cfr-advent__reveal-kicker">' . s(get_string('cfr_launch_cal_opened', 'local_campusreader', $day)) . '</span>' .
                        '<strong>' . s($card['title']) . '</strong><button type="button" class="cfr-advent__discover" data-cfr-discover>✧ ' . s(get_string('cfr_launch_cal_discover', 'local_campusreader')) . '</button>' .
                        '<a class="cfr-advent__read-link" href="' . s($link) . '">' . s(get_string('cfr_launch_cal_read', 'local_campusreader')) . '</a>' .
                        '<button type="button" class="cfr-advent__close" data-cfr-door-close aria-label="' . s(get_string('cfr_launch_cal_closearia', 'local_campusreader')) . '">×</button></div>';
                } else {
                    $out .= '<div class="cfr-advent__door cfr-advent__door--locked" aria-label="' . s(get_string('cfr_launch_cal_lockedaria', 'local_campusreader',
                            (object)['day' => $day, 'date' => $card['date']])) . '">' . $front . '</div>';
                }
                $out .= '</div><span class="cfr-advent__status">' . ($link ? s(get_string('cfr_launch_cal_to_discover', 'local_campusreader')) :
                    ($card['pending'] ? s(get_string('cfr_launch_cal_preparing', 'local_campusreader')) :
                        s(get_string('cfr_launch_cal_soon', 'local_campusreader')))) . '</span></li>';
            }
            $out .= '</ol><dialog class="cfr-advent-magic" data-cfr-magic-dialog aria-labelledby="cfr-advent-magic-title">' .
                '<div class="cfr-advent-magic__inner"><button type="button" class="cfr-advent-magic__close" data-cfr-magic-close aria-label="' . s(get_string('cfr_launch_cal_modal_close', 'local_campusreader')) . '">×</button>' .
                '<div class="cfr-advent-magic__art" aria-hidden="true"><span class="cfr-advent-magic__moon">✧</span></div>' .
                '<div class="cfr-advent-magic__content"><span class="cfr-advent-magic__eyebrow">✦ ' . s(get_string('cfr_launch_cal_modal_gift', 'local_campusreader')) . ' ✦</span>' .
                '<span class="cfr-advent-magic__date" data-cfr-magic-date></span><h3 id="cfr-advent-magic-title" data-cfr-magic-title></h3>' .
                '<p>' . s(get_string('cfr_launch_cal_modal_intro', 'local_campusreader')) . '</p>' .
                '<a class="cfr-advent-magic__read" data-cfr-magic-link href="#">' . s(get_string('cfr_launch_cal_modal_book', 'local_campusreader')) . '</a>' .
                '<button type="button" class="cfr-advent-magic__fold" data-cfr-magic-fold>↶ ' . s(get_string('cfr_launch_cal_modal_fold', 'local_campusreader')) . '</button></div></div></dialog>' .
                '</section>';
        } else {
            // Bookshelf phase B, with link access still checked per chapter on every request.
            $out .= '<section class="cfr-shelves" aria-label="' . s(get_string('cfr_launch_cal_shelves', 'local_campusreader')) . '">';
            foreach (array_chunk($cards, 6) as $idx => $row) {
                $out .= '<div class="cfr-shelves__scroll" tabindex="0"><div class="cfr-shelves__row" role="list">';
                foreach ($row as $card) {
                    if ($card['url']) {
                        $out .= '<a class="cfr-pub-door" href="' . s($card['url']) . '"><strong>' .
                            $card['day'] . '</strong><span>' . s($card['title']) . '</span></a>';
                    } else {
                        $out .= '<span class="cfr-pub-door cfr-pub-door--locked" aria-disabled="true"><strong>' .
                            $card['day'] . '</strong><span>' . s(get_string('cfr_launch_cal_preparing', 'local_campusreader')) . '</span></span>';
                    }
                }
                $out .= '</div></div>';
            }
            $out .= '</section>';
        }
        return $out . '</section>';
    }
}
