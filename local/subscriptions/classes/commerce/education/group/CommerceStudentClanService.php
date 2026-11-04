<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\group;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommercePedagogicalStudentLeaderboardService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;

/**
 * Read-only learner view of the *current* clan. A learner can never select
 * another group's identifier; the accessible promotion list is built from the
 * same guarded source as the Mon Campus learner leaderboard.
 */
final class CommerceStudentClanService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalStudentLeaderboardService $studentleaderboard,
        private readonly CommercePedagogicalGroupRepository $groups,
        private readonly CommercePedagogicalXpRepository $xp
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self(
            $db,
            CommercePedagogicalStudentLeaderboardService::create($db),
            CommercePedagogicalGroupRepository::create($db),
            CommercePedagogicalXpRepository::create($db)
        );
    }

    /**
     * @return array<string,mixed>|null Null for anyone outside a current, active clan.
     */
    public function get_for_user(int $userid, int $requestedpromotionid, \moodle_page $page, ?int $now = null): ?array {
        if ($userid <= 1 || $requestedpromotionid < 0) {
            return null;
        }
        $now ??= time();
        $available = $this->studentleaderboard->get_for_user($userid, $now);
        if (!$available) {
            return null;
        }

        $chosen = null;
        foreach ($available as $candidate) {
            if ($requestedpromotionid === 0 || (int)$candidate['promotionid'] === $requestedpromotionid) {
                $chosen = $candidate;
                break;
            }
        }
        if (!$chosen) {
            return null;
        }

        $promotionid = (int)$chosen['promotionid'];
        $groupid = (int)$chosen['groupid'];
        $group = $this->groups->get_group($groupid);
        if (!$group || !$group->is_active() || $group->get_promotion_id() !== $promotionid) {
            return null;
        }
        // Recheck both relations in case membership changed between the snapshot and this read.
        if (!$this->db->record_exists('local_subs_commerce_ped_gmem', [
            'groupid' => $groupid, 'userid' => $userid, 'active' => 1,
        ]) || !$this->db->record_exists('local_subs_commerce_ped_join', [
            'promotionid' => $promotionid, 'userid' => $userid, 'state' => 'active',
        ])) {
            return null;
        }

        $promotion = $this->db->get_record('local_subs_commerce_ped_promo',
            ['id' => $promotionid], 'id,name,courseid,startsat,endsat,xpstartsat,leaderboardhidehours', IGNORE_MISSING);
        if (!$promotion) {
            return null;
        }
        $countrylabels = get_string_manager()->get_list_of_countries();
        $hidden = !empty($chosen['isleaderboardhidden']);
        $pointsbyuser = $hidden ? [] : $this->xp->points_by_user($promotionid);

        // Only active members of the same group with a current promotion participation.
        // Other groups, former members, suspended/deleted accounts never enter the view.
        $members = $this->db->get_records_sql(
            "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                    u.middlename, u.alternatename, u.picture, u.imagealt, u.email, u.country
               FROM {local_subs_commerce_ped_gmem} gm
               JOIN {user} u ON u.id = gm.userid
              WHERE gm.groupid = :groupid
                AND gm.active = 1
                AND u.deleted = 0 AND u.suspended = 0
                AND EXISTS (SELECT 1 FROM {local_subs_commerce_ped_join} j
                             WHERE j.userid = u.id AND j.promotionid = :joinpromotion
                               AND j.state = :joinstate)
                AND EXISTS (SELECT 1 FROM {local_subs_commerce_ped_access} a
                             WHERE a.userid = u.id AND a.promotionid = :accesspromotion
                               AND a.courseid = :accesscourse)
           ORDER BY u.firstname ASC, u.id ASC",
            [
                'groupid' => $groupid,
                'joinpromotion' => $promotionid,
                'joinstate' => 'active',
                'accesspromotion' => $promotionid,
                'accesscourse' => (int)$promotion->courseid,
            ]
        );

        $cards = [];
        foreach ($members as $member) {
            $countrycode = strtoupper(trim((string)$member->country));
            $hascountry = preg_match('/^[A-Z]{2}$/D', $countrycode)
                && isset($countrylabels[$countrycode]);
            $hasavatar = (int)$member->picture > 0;
            $avatarurl = '';
            if ($hasavatar) {
                $picture = new \user_picture($member);
                $picture->size = 160;
                $avatarurl = $picture->get_url($page)->out(false);
            }
            $card = [
                'firstname' => format_string((string)$member->firstname),
                'ismine' => (int)$member->id === $userid,
                'hasavatar' => $hasavatar,
                'avatarurl' => $avatarurl,
                // Moodle's country is a profile country; it is NOT proof of citizenship.
                'hascountry' => (bool)$hascountry,
                'country' => $hascountry ? $countrylabels[$countrycode] : '',
                'flag' => $hascountry ? self::flag_for_country($countrycode) : '',
                'hasscore' => !$hidden,
            ];
            if (!$hidden) {
                $card['points'] = (int)($pointsbyuser[(int)$member->id] ?? 0);
            }
            $cards[] = $card;
        }
        if (!$hidden) {
            usort($cards, static function(array $a, array $b): int {
                return ($b['points'] <=> $a['points']) ?: strnatcasecmp($a['firstname'], $b['firstname']);
            });
        }

        $tutorname = trim((string)($group->get_tutor_name() ?? ''));
        if ($tutorname === '' && $group->get_tutor_id()) {
            $tutor = $this->db->get_record('user', ['id' => $group->get_tutor_id(), 'deleted' => 0],
                'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename', IGNORE_MISSING);
            if ($tutor) {
                $tutorname = fullname($tutor);
            }
        }
        $lang = trim((string)($group->get_support_language() ?? ''));
        $translatedlang = match (strtolower($lang)) {
            'fr', 'français', 'francais', 'french' => get_string('commerce_my_clan_lang_fr', 'local_subscriptions'),
            'ru', 'russe', 'russian' => get_string('commerce_my_clan_lang_ru', 'local_subscriptions'),
            'en', 'anglais', 'english' => get_string('commerce_my_clan_lang_en', 'local_subscriptions'),
            default => $lang,
        };
        $telegramurl = self::valid_telegram_url((string)($group->get_telegram_reference() ?? ''));

        $choices = [];
        foreach ($available as $candidate) {
            $choices[] = [
                'name' => format_string((string)$candidate['promotionname']),
                'url' => (new \moodle_url('/local/subscriptions/my_clan.php',
                    ['promotionid' => (int)$candidate['promotionid']]))->out(false),
                'isactive' => (int)$candidate['promotionid'] === $promotionid,
            ];
        }
        $dateformat = get_string('strftimedateshort', 'langconfig');
        $lessondateformat = get_string('strftimedatetimeshort', 'langconfig');
        $calendar = $this->lesson_milestones($promotionid, (int)$promotion->courseid, $now);
        $photourl = CommerceClanPhotoService::create($this->db)
            ->url($groupid, (int)$promotion->courseid);
        $data = [
            'promotionid' => $promotionid,
            'promotionname' => format_string((string)$promotion->name),
            'groupname' => format_string($group->get_display_name()),
            'members' => $cards,
            'membercount' => count($cards),
            'membercountlabel' => self::localized_count(count($cards), 'members'),
            'hasclanphoto' => $photourl !== '',
            'clanphotourl' => $photourl,
            'grouptotal' => count($cards),
            'groupcount' => $this->db->count_records('local_subs_commerce_ped_group',
                ['promotionid' => $promotionid, 'active' => 1]),
            'participantcount' => (int)$this->db->count_records_sql(
                'SELECT COUNT(DISTINCT j.userid)
                   FROM {local_subs_commerce_ped_join} j
                   JOIN {user} u ON u.id = j.userid
                  WHERE j.promotionid = :pid AND j.state = :state AND u.deleted = 0',
                ['pid' => $promotionid, 'state' => 'active']),
            // Only claim the exact pedagogical period when its dates are configured.
            // The calendar is the authority for lesson release dates, not for the period end.
            'startdate' => $promotion->startsat !== null
                ? userdate((int)$promotion->startsat, $dateformat)
                : ($calendar['firstunlockat'] !== null
                    ? userdate($calendar['firstunlockat'], $dateformat)
                    : get_string('commerce_my_clan_not_set', 'local_subscriptions')),
            'startlabel' => $promotion->startsat !== null
                ? get_string('commerce_my_clan_start', 'local_subscriptions')
                : get_string('commerce_my_clan_first_release', 'local_subscriptions'),
            'enddate' => $promotion->endsat !== null
                ? userdate((int)$promotion->endsat, $dateformat)
                : get_string('commerce_my_clan_not_set', 'local_subscriptions'),
            'haslastlesson' => $calendar['last'] !== null,
            'lastlesson' => $calendar['last']['name'] ?? '',
            'lastlessondate' => $calendar['last'] !== null
                ? userdate($calendar['last']['unlocksat'], $lessondateformat) : '',
            'lastlessonurl' => $calendar['last']['url'] ?? '',
            'hasnextlesson' => $calendar['next'] !== null,
            'nextlesson' => $calendar['next']['name'] ?? '',
            'nextlessondate' => $calendar['next'] !== null
                ? userdate($calendar['next']['unlocksat'], $lessondateformat) : '',
            'nextlessonurl' => $calendar['next']['url'] ?? '',
            'hascalendar' => $calendar['count'] > 0,
            'hastutor' => $tutorname !== '',
            'tutorname' => format_string($tutorname),
            'haslanguage' => $translatedlang !== '',
            'supportlanguage' => format_string($translatedlang),
            'hastelegram' => $telegramurl !== '',
            'telegramurl' => $telegramurl,
            'ishidden' => $hidden,
            'hasscore' => !$hidden,
            'revealdate' => $hidden ? (string)($chosen['revealdate'] ?? '') : '',
            'hasmultipleclans' => count($choices) > 1,
            'clans' => $choices,
            'backurl' => (new \moodle_url('/local/subscriptions/mon_campus.php'))->out(false),
            'promoiconurl' => (new \moodle_url('/local/subscriptions/pix/pain_au_chocolat.png'))->out(false),
        ];
        $data['groupcountlabel'] = self::localized_count((int)$data['groupcount'], 'groups');
        $data['participantcountlabel'] = self::localized_count((int)$data['participantcount'], 'participants');
        if (!$hidden) {
            $data['grouppoints'] = (int)($chosen['grouppoints'] ?? 0);
            $data['grouprank'] = (int)($chosen['grouprank'] ?? 0);
            $data['hasrank'] = $data['grouppoints'] > 0 && $data['grouprank'] > 0;
            $data['rankgold'] = $data['grouprank'] === 1 && $data['grouppoints'] > 0;
            $data['ranksilver'] = $data['grouprank'] === 2 && $data['grouppoints'] > 0;
            $data['rankbronze'] = $data['grouprank'] === 3 && $data['grouppoints'] > 0;
        }
        return $data;
    }

    /**
     * Exact lesson unlocks for this promotion; never derive dates from course completion.
     * Skip stale calendar entries pointing at deleted/different-course sections.
     * @return array{last:?array,next:?array,firstunlockat:?int,count:int}
     */
    private function lesson_milestones(int $promotionid, int $courseid, int $now): array {
        $last = null;
        $next = null;
        $firstunlockat = null;
        $count = 0;
        foreach (CommercePedagogicalCalendarRepository::create($this->db)->for_promotion($promotionid) as $item) {
            if ($item->get_item_type() !== CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION) {
                continue;
            }
            $section = $this->db->get_record('course_sections',
                ['id' => $item->get_item_id(), 'course' => $courseid], 'id,section,name', IGNORE_MISSING);
            if (!$section || (int)$section->section < 1) {
                continue;
            }
            $timestamp = $item->get_unlocks_at();
            $firstunlockat = $firstunlockat === null ? $timestamp : min($firstunlockat, $timestamp);
            $name = trim((string)($section->name ?? ''));
            if ($name === '') {
                $name = get_string('commerce_my_clan_lesson_number', 'local_subscriptions', (int)$section->section);
            } else {
                $name = format_string($name);
            }
            $lesson = [
                'name' => $name,
                'unlocksat' => $timestamp,
                'url' => (new \moodle_url('/course/view.php',
                    ['id' => $courseid, 'section' => (int)$section->section]))->out(false),
            ];
            $count++;
            if ($timestamp <= $now) {
                if ($last === null || $timestamp > $last['unlocksat']) {
                    $last = $lesson;
                }
            } else if ($next === null || $timestamp < $next['unlocksat']) {
                $next = $lesson;
            }
        }
        return compact('last', 'next', 'firstunlockat', 'count');
    }

    /** Three declensions in Russian, singular/plural in French and English. */
    public static function localized_count(int $count, string $unit, ?string $language = null): string {
        if (!in_array($unit, ['members', 'groups', 'participants'], true)) {
            throw new \coding_exception('Unsupported clan count unit.');
        }
        $language ??= current_language();
        $form = self::count_form($count, $language);
        return get_string('commerce_my_clan_count_' . $unit . '_' . $form, 'local_subscriptions', $count);
    }

    public static function count_form(int $count, string $language): string {
        if (str_starts_with(strtolower($language), 'ru')) {
            $mod100 = $count % 100;
            $mod10 = $count % 10;
            $form = ($mod100 >= 11 && $mod100 <= 14) ? 'many'
                : ($mod10 === 1 ? 'one' : (($mod10 >= 2 && $mod10 <= 4) ? 'few' : 'many'));
        } else {
            $form = $count === 1 ? 'one' : 'many';
        }
        return $form;
    }

    /** Only valid, public HTTPS Telegram host URLs are output as links. */
    private static function valid_telegram_url(string $value): string {
        $value = trim($value);
        if ($value === '' || preg_match('/[\x00-\x20]/', $value)) {
            return '';
        }
        $parts = parse_url($value);
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' ||
                !in_array(strtolower((string)($parts['host'] ?? '')), ['t.me', 'telegram.me'], true) ||
                !empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }
        return $value;
    }

    private static function flag_for_country(string $code): string {
        return mb_chr(0x1F1E6 + ord($code[0]) - 65, 'UTF-8') .
            mb_chr(0x1F1E6 + ord($code[1]) - 65, 'UTF-8');
    }
}
