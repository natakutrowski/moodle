<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\hub;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\digital\library\CommerceDigitalLibraryService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalStudentLeaderboardService;
use local_subscriptions\commerce\customer\course\CommerceCustomerCourseLearningStatusService;
use local_subscriptions\crm\success\repositories\EnrolledCourseProvider;
use local_subscriptions\crm\success\repositories\LevelUpXpRepository;
use local_subscriptions\url\UrlFactory;

/**
 * Builds the customer-facing Mon Campus hub.
 */
final class CommerceCustomerHubService {
    public static function create(): self {
        global $DB;

        return new self(
            new EnrolledCourseProvider(),
            new CommercePurchaseReadRepository($DB),
            CommerceDigitalLibraryService::create(),
            new LevelUpXpRepository(),
            CommerceCustomerCourseLearningStatusService::create($DB)
        );
    }

    public function __construct(
        private readonly EnrolledCourseProvider $courses,
        private readonly CommercePurchaseReadRepository $purchases,
        private readonly CommerceDigitalLibraryService $library,
        private readonly LevelUpXpRepository $levelxp,
        private readonly CommerceCustomerCourseLearningStatusService $learningstatus
    ) {
    }

    /** @return array<string,mixed> */
    public function build(\stdClass $user, \moodle_page $page): array {
        $userid = (int)$user->id;
        $email = trim((string)$user->email);
        $courses = $this->course_cards($userid);
        $purchasecount = count(
            $this->purchases->find_details_for_customer($userid, $email)
        );
        $resourcecount = count(
            $this->library->get_for_customer($userid, $email)->get_resources()
        );
        $xp = $this->xp_summary($userid);
        $teamchallenges = $this->team_challenges($userid);

        $picture = new \user_picture($user);
        // The default Moodle picture size is intended for compact navigation
        // elements. Request a larger source so the 96px hero avatar stays sharp
        // on standard and high-density displays.
        $picture->size = 200;

        return [
            'firstname' => format_string((string)$user->firstname),
            'fullname' => fullname($user),
            'avatarurl' => $picture->get_url($page)->out(false),
            'courses' => $courses,
            'hascourses' => $courses !== [],
            'coursecount' => count($courses),
            'purchasecount' => $purchasecount,
            'resourcecount' => $resourcecount,
            'xpavailable' => $xp['available'],
            'totalxp' => $xp['totalxp'],
            'highestlevel' => $xp['highestlevel'],
            'xpprogress' => $xp['progress'],
            'xpprogressstyle' => 'width: ' . $xp['progress'] . '%;',
            'xp30d' => $xp['xp30d'],
            'xprank' => $xp['rank'],
            'xpparticipants' => $xp['participants'],
            'hasxprank' => $xp['rank'] > 0,
            'lastreward' => $xp['lastreward'],
            'xpbadgeurl' => $xp['badgeurl'],
            'hasxpbadge' => $xp['badgeurl'] !== '',
            'teamchallenges' => $teamchallenges,
            'hasteamchallenges' => $teamchallenges !== [],
            'mycoursesurl' => UrlFactory::my_courses()->out(false),
            'myresourcesurl' => UrlFactory::my_digital_products()->out(false),
            'mypurchasesurl' => UrlFactory::my_purchases()->out(false),
            'profileurl' => UrlFactory::my_profile(['id' => $userid])->out(false),
            'supporturl' => UrlFactory::support()->out(false),
            'catalogurl' => UrlFactory::digital_catalog()->out(false),
            'boutiqueurl' => UrlFactory::digital_catalog()->out(false),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function course_cards(int $userid): array {
        $result = [];
        $now = time();
        foreach ($this->courses->get_courses($userid) as $course) {
            $courseid = (int)$course->id;
            try {
                $status = $this->learningstatus->resolve($userid, $courseid, $now);
            } catch (\Throwable) {
                $status = [];
            }
            $progress = isset($status['progress']) && $status['progress'] !== null
                ? (int)round((float)$status['progress'])
                : 0;
            $imageurl = $this->course_image_url($course);
            $learningmeta = $this->course_learning_meta($status);
            $result[] = [
                'id' => $courseid,
                'name' => format_string((string)$course->fullname),
                'url' => UrlFactory::course($courseid)->out(false),
                'hasimage' => $imageurl !== null,
                'imageurl' => $imageurl ?? '',
                'initial' => \core_text::strtoupper(
                    \core_text::substr(trim((string)$course->fullname), 0, 1)
                ),
                'progress' => max(0, min(100, $progress)),
                'progressstyle' => 'width: ' . max(0, min(100, $progress)) . '%;',
                'haslearningmeta' => $learningmeta !== [],
                'learningmeta' => $learningmeta,
            ];
        }
        return $result;
    }

    /** @return array<int,array{icon:string,label:string}> */
    private function course_learning_meta(array $status): array {
        if (empty($status['haspromotion'])) {
            return [];
        }

        $items = [];
        $promotionname = trim((string)($status['promotionname'] ?? ''));
        if ($promotionname !== '') {
            $items[] = [
                'icon' => 'fa-user-group',
                'label' => get_string('commerce_customer_hub_course_promotion', 'local_subscriptions',
                    format_string($promotionname)),
            ];
        }

        $items[] = [
            'icon' => !empty($status['hasfullaccess']) ? 'fa-unlock' : 'fa-calendar-check',
            'label' => get_string(
                !empty($status['hasfullaccess'])
                    ? 'commerce_customer_hub_course_access_full'
                    : 'commerce_customer_hub_course_access_progressive',
                'local_subscriptions'
            ),
        ];

        $startsat = isset($status['promotionstartsat']) && $status['promotionstartsat'] !== null
            ? (int)$status['promotionstartsat']
            : 0;
        if ($startsat > 0) {
            $items[] = [
                'icon' => 'fa-calendar-day',
                'label' => get_string('commerce_customer_hub_course_starts', 'local_subscriptions',
                    userdate($startsat, get_string('strftimedatetimeshort', 'langconfig'))),
            ];
        }

        if (!empty($status['hasnextlesson']) && !empty($status['nextunlocksat'])) {
            $lesson = trim((string)($status['nextsectionname'] ?? ''));
            if ($lesson === '') {
                $sectionnumber = (int)($status['nextsectionnumber'] ?? 0);
                $lesson = get_string('commerce_customer_hub_course_section_fallback', 'local_subscriptions', $sectionnumber);
            }
            $a = (object)[
                'lesson' => format_string($lesson),
                'date' => userdate((int)$status['nextunlocksat'], get_string('strftimedatetimeshort', 'langconfig')),
            ];
            $items[] = [
                'icon' => 'fa-forward-step',
                'label' => get_string(
                    !empty($status['hasfullaccess'])
                        ? 'commerce_customer_hub_course_next_promo_step'
                        : 'commerce_customer_hub_course_next_lesson',
                    'local_subscriptions',
                    $a
                ),
            ];
        }

        return $items;
    }

    /** @return array{available:bool,totalxp:int,highestlevel:int,progress:int,xp30d:int,rank:int,participants:int,lastreward:string,badgeurl:string} */
    private function xp_summary(int $userid): array {
        try {
            $available = $this->levelxp->is_available();
            $statistics = $this->levelxp->get_statistics($userid, time());
        } catch (\Throwable) {
            $available = false;
            $statistics = [];
        }

        $lastrewardat = (int)($statistics['last_reward_at'] ?? 0);
        $progress = (int)round((float)(
            $statistics['current_levels_progress_percentage'] ?? 0
        ));

        return [
            'available' => $available,
            'totalxp' => (int)($statistics['total_xp'] ?? 0),
            'highestlevel' => (int)($statistics['highest_level'] ?? 0),
            'progress' => max(0, min(100, $progress)),
            'xp30d' => (int)($statistics['xp_30d'] ?? 0),
            'rank' => (int)($statistics['leaderboard_rank'] ?? 0),
            'participants' => (int)($statistics['leaderboard_count'] ?? 0),
            'lastreward' => $lastrewardat > 0
                ? userdate($lastrewardat, get_string('strftimedateshort', 'langconfig'))
                : get_string('commerce_customer_hub_xp_no_activity', 'local_subscriptions'),
            'badgeurl' => $this->level_badge_url($userid, (int)($statistics['highest_level'] ?? 0)),
        ];
    }


    /** @return array<int,array<string,mixed>> */
    private function team_challenges(int $userid): array {
        try {
            return CommercePedagogicalStudentLeaderboardService::create()
                ->get_for_user($userid);
        } catch (\Throwable) {
            // Mon Campus must stay available even if team scoring is temporarily
            // unavailable or the pedagogical relation is incomplete.
            return [];
        }
    }


    private function course_image_url(\stdClass $course): ?string {
        // Reuse the exact same resolver as the Mes cours page whenever
        // local_campus is available. This keeps both customer surfaces aligned
        // with Moodle course overview files and avoids a second implementation.
        if (class_exists('\\local_campus\\mycourses\\MyCourseImageService')) {
            try {
                return (new \local_campus\mycourses\MyCourseImageService())
                    ->get_image_url($course);
            } catch (\Throwable) {
                // Fall through to the Moodle-native fallback below.
            }
        }

        try {
            $courseinlist = new \core_course_list_element($course);
            foreach ($courseinlist->get_course_overviewfiles() as $file) {
                if (!$file->is_valid_image()) {
                    continue;
                }

                return \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                )->out(false);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function level_badge_url(int $userid, int $highestlevel): string {
        if ($highestlevel <= 0 || !class_exists('\\block_xp\\di')) {
            return '';
        }
        try {
            $factory = \block_xp\di::get('course_world_factory');
            foreach ($this->courses->get_courses($userid) as $course) {
                $state = $factory->get_world((int)$course->id)->get_store()->get_state($userid);
                $level = $state->get_level();
                $number = is_numeric($level)
                    ? (int)$level
                    : (
                        is_object($level) && method_exists($level, 'get_level')
                            ? (int)$level->get_level()
                            : (
                                is_object($level) && method_exists($level, 'get_number')
                                    ? (int)$level->get_number()
                                    : 0
                            )
                    );
                if ($number !== $highestlevel || !is_object($level) || !method_exists($level, 'get_badge_url')) {
                    continue;
                }
                $url = $level->get_badge_url();
                return $url instanceof \moodle_url ? $url->out(false) : (string)$url;
            }
        } catch (\Throwable) {
            return '';
        }
        return '';
    }

}
