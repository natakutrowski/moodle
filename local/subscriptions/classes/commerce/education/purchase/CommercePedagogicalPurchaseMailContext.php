<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;

/**
 * Read-only, purchase-pinned pedagogical welcome details for Native access mail.
 *
 * Never resolves a promotion from the current public sale window: a stable
 * product can already be on sale for the NEXT promotion when a mail is retried.
 */
final class CommercePedagogicalPurchaseMailContext {
    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * @param array<int,object> $purchaseitems Native order presentation items.
     * @return array{hasentries:bool,alreadyowned:bool,hasa1course:bool,entries:array<int,array<string,mixed>>}
     */
    public function for_purchase(string $reference, int $userid, array $purchaseitems): array {
        $result = ['hasentries' => false, 'alreadyowned' => false, 'hasa1course' => false, 'entries' => []];
        $reference = trim($reference);
        if ($reference === '' || $userid <= 0) {
            return $result;
        }

        // This operation is only issued to an existing course owner buying
        // access to the pedagogy/tutor, not the Moodle course a second time.
        foreach ($purchaseitems as $item) {
            $metadata = is_array($item->metadata ?? null) ? $item->metadata : [];
            $operation = strtolower(trim((string)(
                $metadata['operation'] ?? $metadata['commerceoperation'] ?? ''
            )));
            if ($operation === 'promotion_join') {
                $result['alreadyowned'] = true;
                break;
            }
        }

        $groups = CommercePedagogicalGroupRepository::create($this->db);
        $participations = CommercePedagogicalParticipationRepository::create($this->db)
            ->active_for_purchase($reference);

        foreach ($participations as $participation) {
            if ((int)$participation->userid !== $userid) {
                continue;
            }

            $promotion = $this->db->get_record(
                'local_subs_commerce_ped_promo',
                ['id' => (int)$participation->promotionid],
                'id,name,courseid', IGNORE_MISSING
            );
            if (!$promotion || (int)$promotion->courseid !== (int)$participation->courseid) {
                continue;
            }

            // Identify the purchased course, not the current public sale window.
            // Other language levels must not receive the A1 Telegram invitation.
            if (self::is_a1_course($this->db, (int)$promotion->courseid)) {
                $result['hasa1course'] = true;
            }

            $group = $groups->group_for_user((int)$promotion->id, $userid);
            if ($group !== null && $group->get_product_id() !== (int)$participation->productid) {
                // Do not leak a group from a different commercial offer.
                $group = null;
            }
            $tutorname = $group !== null ? trim((string)$group->get_tutor_name()) : '';
            if ($tutorname === '' && $group?->get_tutor_id()) {
                $tutor = $this->db->get_record('user', ['id' => $group->get_tutor_id()],
                    'id,firstname,lastname', IGNORE_MISSING);
                if ($tutor) {
                    $tutorname = trim($tutor->firstname . ' ' . $tutor->lastname);
                }
            }

            $result['entries'][] = [
                'promotionname' => trim((string)$promotion->name),
                'groupname' => $group?->get_display_name() ?? '',
                'hasgroup' => $group !== null,
                'tutorname' => $tutorname,
                'hastutor' => $tutorname !== '',
                'supportlang' => trim((string)($group?->get_support_language() ?? '')),
                'hassupportlang' => trim((string)($group?->get_support_language() ?? '')) !== '',
                'telegramurl' => self::telegram_url($group?->get_telegram_reference()),
            ];
        }

        $result['hasentries'] = $result['entries'] !== [];
        // Never claim a tutoring-only purchase without a pedagogical match.
        if (!$result['hasentries']) {
            $result['alreadyowned'] = false;
        }
        return $result;
    }

    /**
     * A1 is a course level, not a product SKU: the same course can be sold under
     * different products and cohorts. Support both Latin A1 and Cyrillic А1.
     */
    private static function is_a1_course(\moodle_database $db, int $courseid): bool {
        $course = $db->get_record('course', ['id' => $courseid], 'id,fullname,shortname', IGNORE_MISSING);
        if (!$course) {
            return false;
        }
        $names = trim((string)$course->fullname . ' ' . (string)$course->shortname);
        return preg_match('/(?<![\p{L}\p{N}])[AaАа]1(?![\p{L}\p{N}])/u', $names) === 1;
    }

    /** Never render user-entered URLs to unknown domains in transactional mail. */
    private static function telegram_url(?string $reference): string {
        $reference = trim((string)$reference);
        if ($reference === '') {
            return '';
        }
        if (preg_match('/^@([a-zA-Z0-9_]{5,32})$/', $reference, $match)) {
            return 'https://t.me/' . $match[1];
        }
        if (preg_match('~^https://(?:t\.me|telegram\.me)/[a-zA-Z0-9_+/-]+$~i', $reference)) {
            return $reference;
        }
        return '';
    }
}
