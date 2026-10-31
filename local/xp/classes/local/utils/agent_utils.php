<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\utils;

use DateTimeImmutable;
use local_xp\local\mcp\schema;

/**
 * Agent utils.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agent_utils {
    /**
     * Convert HTML to plain text.
     *
     * This is the closest we can get to Markdown from arbitrary HTML content. It is means to be
     * used to create readable plain text version of HTML for an agent to consume. It is not too
     * far from Markdown while not being Markdown itself.
     *
     * @param string $html HTML.
     * @return string Plain text.
     */
    public static function html_to_text(string $html): string {
        global $CFG;

        require_once($CFG->libdir . '/html2text/lib.php');
        $h2t = new \core_html2text($html, [
            'width' => 0,
            'do_links' => 'inline',
        ]);
        return trim($h2t->getText());
    }

    /**
     * Get the known ChatGPT CIMD domains.
     *
     * @return array
     */
    public static function get_chatgpt_cimd_domains(): array {
        return ['chatgpt.com'];
    }

    /**
     * Get the known Claude CIMD domains.
     *
     * @return array
     */
    public static function get_claude_cimd_domains(): array {
        return ['claude.ai'];
    }

    /**
     * Parse a date/time string.
     *
     * Values with trailing Z or a numeric offset (+HH:MM, +HHMM, etc.) use that zone.
     * Naive datetimes use {@see \core_date::get_user_timezone_object()}. Plain dates
     * are only accepted when $fallbacktime is provided, and use its time and timezone.
     *
     * @param string $raw Raw datetime.
     * @param DateTimeImmutable|null $fallbacktime Fallback time for plain dates.
     * @return DateTimeImmutable|null Parsed date, or null when the value cannot be parsed.
     */
    public static function parse_datetime(string $raw, ?DateTimeImmutable $fallbacktime): ?DateTimeImmutable {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $usertz = \core_date::get_user_timezone_object();
        if (
            preg_match('/Z$/i', $raw) || preg_match('/[+-]\d{2}:\d{2}$/', $raw)
                || preg_match('/[+-]\d{4}$/', $raw)
        ) {
            $dt = date_create_immutable($raw);
            return $dt instanceof DateTimeImmutable ? $dt : null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            if (!$fallbacktime) {
                return null;
            }
            $dt = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s.u',
                $raw . ' ' . $fallbacktime->format('H:i:s.u'),
                $fallbacktime->getTimezone()
            );
            return $dt instanceof DateTimeImmutable ? $dt : null;
        }

        $formats = [
            'Y-m-d\TH:i:s',
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s.u',
            'Y-m-d\TH:i',
            'Y-m-d H:i',
        ];
        foreach ($formats as $fmt) {
            $dt = DateTimeImmutable::createFromFormat($fmt, $raw, $usertz);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
        }

        $dt = date_create_immutable($raw, $usertz);
        return $dt instanceof DateTimeImmutable ? $dt : null;
    }

    /**
     * Paginated results schema.
     *
     * @param schema $schema
     * @return schema
     */
    public static function paginated_results_schema(schema $schema): schema {
        return schema::object([
            'current_page' => schema::integer('The current page number (1-based).'),
            'next_page' => schema::integer('The page number to request next when more results exist, or null.')->nullable(),
            'total_pages' => schema::integer('Total number of pages.')->minimum(1),
            'items' => schema::array_of($schema),
        ]);
    }

    /**
     * Soft-truncate text near a cap, breaking on the next line.
     *
     * When the input is within 5% of $cap it is returned as-is. Otherwise the text is cropped at
     * the next line break at or after $cap (falling back to a hard cut when no line break is found)
     * and a truncation marker is appended on a new line. An ellipsis is only appended when the last
     * retained line was truncated.
     *
     * @param string $text Plain text to (possibly) shorten.
     * @param int $cap Soft maximum length, in characters.
     * @return string
     */
    public static function shorten_text(string $text, int $cap = 5000): string {
        $len = \core_text::strlen($text);
        if ($len === 0 || $cap <= 0) {
            return $text;
        }

        // Stay as-is when only marginally over the cap to avoid noisy truncation markers.
        $tolerance = (int) ceil($cap * 1.05);
        if ($len <= $tolerance) {
            return $text;
        }

        $newlinepos = \core_text::strpos($text, "\n", $cap);
        $lastlineistruncated = true;
        if ($newlinepos !== false && $newlinepos <= $tolerance) {
            $cutat = $newlinepos;
            $lastlineistruncated = false;
        } else {
            $cutat = $cap;
        }

        $kept = \core_text::substr($text, 0, $cutat);
        $omitted = $len - $cutat;
        $ellipsis = $lastlineistruncated ? '…' : '';

        return rtrim($kept) . $ellipsis . "\n\n" . '[truncated; ' . $omitted . ' chars omitted]';
    }
}
