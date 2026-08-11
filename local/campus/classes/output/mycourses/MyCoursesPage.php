<?php

declare(strict_types=1);

namespace local_campus\output\mycourses;

defined('MOODLE_INTERNAL') || die();

use local_campus\mycourses\MyCourseImageService;
use local_campus\mycourses\MyCourseMobileCoverResolver;
use local_campus\mycourses\MyCoursePresentation;
use local_campus\mycourses\MyCoursesCollection;
use local_subscriptions\commerce\course\library\CommerceCourseAccessOrigin;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationCollection;

/** Native CampusFR presentation model for the My courses page. */
final class MyCoursesPage implements \templatable, \renderable {
    private readonly MyCourseImageService $images;
    private readonly MyCourseMobileCoverResolver $mobilecovers;

    public function __construct(
        private readonly MyCoursesCollection $courses,
        private readonly ?CommerceCourseRecommendationCollection $recommendations = null
    ) {
        $this->images = new MyCourseImageService();
        $this->mobilecovers = new MyCourseMobileCoverResolver();
    }

    public function export_for_template(\renderer_base $output): array {
        return [
            'hascourses' => count($this->courses) > 0,
            'hero' => $this->hero_context(),
            'categories' => $this->category_contexts(),
            'empty' => $this->empty_context(),
            'recommendations' => $this->recommendations_context(),
        ];
    }

    public function render(\renderer_base $output): string {
        return $output->render_from_template('local_campus/mycourses/page', $this->export_for_template($output));
    }

    /** @return array<string, mixed> */
    private function hero_context(): array {
        $total = count($this->courses);
        $completed = count($this->courses->completed_course_ids());
        $started = 0;
        $trial = 0;

        foreach ($this->courses as $item) {
            if ($item->progress !== null && $item->progress > 0 && !$item->completed) {
                $started++;
            }
            if ($this->effective_origin($item) === CommerceCourseAccessOrigin::TRIAL) {
                $trial++;
            }
        }

        $stats = [
            $this->stat_context('fa-solid fa-book-open', get_string('mycourses_stat_total', 'local_campus'), $total),
            $this->stat_context('fa-solid fa-chart-line', get_string('mycourses_stat_inprogress', 'local_campus'), $started),
            $this->stat_context('fa-solid fa-circle-check', get_string('mycourses_stat_completed', 'local_campus'), $completed),
        ];
        if ($trial > 0) {
            $stats[] = $this->stat_context(
                'fa-solid fa-hourglass-half',
                get_string('mycourses_stat_trial', 'local_campus'),
                $trial
            );
        }

        return [
            'title' => get_string('mycourses_title', 'local_campus'),
            'text' => get_string('mycourses_enriched_intro', 'local_campus'),
            'hasstats' => $total > 0,
            'stats' => $stats,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function category_contexts(): array {
        global $DB;

        $groups = $this->courses->grouped_by_category();
        if ($groups === []) {
            return [];
        }

        $categoryids = array_keys($groups);
        [$insql, $params] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
        $records = $DB->get_records_select('course_categories', "id {$insql}", $params, '', 'id,name');

        $result = [];
        foreach ($groups as $categoryid => $items) {
            $title = '';
            if (isset($records[$categoryid])) {
                $title = format_string($records[$categoryid]->name, true, [
                    'context' => \context_coursecat::instance((int)$categoryid),
                ]);
            }

            $result[] = [
                'hastitle' => $title !== '',
                'title' => $title,
                'courses' => array_map(fn(MyCoursePresentation $item): array => $this->course_context($item), $items),
            ];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function course_context(MyCoursePresentation $item): array {
        $course = $item->course;
        $courseid = $item->courseid();
        $context = \context_course::instance($courseid);
        $title = format_string((string)$course->fullname, true, ['context' => $context]);
        $summary = $this->summary_text($course, $context);
        $imageurl = $this->images->get_image_url($course);
        $mobileimageurl = $this->mobilecovers->resolve($courseid);
        $progress = $this->progress_context($item);
        $access = $this->access_context($item);

        return [
            'courseid' => $courseid,
            'title' => $title,
            'summary' => $summary,
            'hassummary' => $summary !== '',
            'courseurl' => \local_subscriptions\url\UrlFactory::course($courseid)->out(false),
            'hasimage' => $imageurl !== null,
            'imageurl' => $imageurl ?? '',
            'hasmobileimage' => $mobileimageurl !== null,
            'mobileimageurl' => $mobileimageurl ?? '',
            'imagealt' => get_string('mycourses_course_image_alt', 'local_campus', $title),
            'completed' => $item->completed,
            'trial' => $access['trial'],
            'triallabel' => get_string('trial_badge', 'local_campus'),
            'hasaccesslabel' => $access['haslabel'],
            'accesslabel' => $access['label'],
            'accessstate' => $access['state'],
            'progress' => $progress,
            'ctlabel' => $this->cta_label($item),
        ];
    }

    private function summary_text(\stdClass $course, \context_course $context): string {
        $summary = format_text(
            (string)($course->summary ?? ''),
            (int)($course->summaryformat ?? FORMAT_HTML),
            ['context' => $context, 'filter' => true]
        );
        $summary = html_to_text($summary, 0, false);
        $summary = trim((string)preg_replace('/\s+/u', ' ', $summary));

        return $summary === '' ? '' : shorten_text($summary, 220, true);
    }

    /** @return array<string, mixed> */
    private function progress_context(MyCoursePresentation $item): array {
        $raw = $item->progress;
        $percentage = $raw === null ? 0.0 : max(0.0, min(100.0, $raw));
        $display = $percentage > 0 && $percentage < 100
            ? format_float(max(0.1, min(99.9, round($percentage, 1))), 1)
            : format_float($percentage, 0);

        $hascounts = $item->completedactivities !== null
            && $item->totalactivities !== null
            && $item->totalactivities > 0;
        $ratio = '';
        if ($hascounts) {
            $ratio = get_string('course_progress_ratio', 'local_campus', (object)[
                'done' => $item->completedactivities,
                'total' => $item->totalactivities,
            ]);
        }

        return [
            'percentage' => $percentage,
            'display' => $display,
            'label' => $this->progress_message($item),
            'hascounts' => $hascounts,
            'ratio' => $ratio,
            'completed' => $item->completed,
        ];
    }

    private function progress_message(MyCoursePresentation $item): string {
        if ($item->completed || ($item->progress !== null && $item->progress >= 100)) {
            return get_string('mycourses_progress_completed', 'local_campus');
        }
        if ($item->progress === null || $item->progress <= 0) {
            return get_string('mycourses_progress_not_started', 'local_campus');
        }
        if ($item->progress < 35) {
            return get_string('mycourses_progress_started', 'local_campus');
        }
        if ($item->progress < 75) {
            return get_string('mycourses_progress_halfway', 'local_campus');
        }
        return get_string('mycourses_progress_final_stretch', 'local_campus');
    }

    private function cta_label(MyCoursePresentation $item): string {
        if ($item->completed) {
            return get_string('mycourses_cta_review', 'local_campus');
        }
        if ($item->progress === null || $item->progress <= 0) {
            return get_string('cta_connected_start', 'local_campus');
        }
        return get_string('cta_connected_resume', 'local_campus');
    }

    /** @return array{trial:bool,haslabel:bool,label:string,state:string} */
    private function access_context(MyCoursePresentation $item): array {
        $origin = $this->effective_origin($item);
        $period = $item->commerceaccess->period;
        $now = time();
        $label = '';
        $state = 'neutral';

        if ($period->lifetime) {
            $label = get_string('course_access_lifetime', 'local_campus');
            $state = 'lifetime';
        } else if ($period->validfrom !== null && $period->validfrom > $now) {
            $label = get_string('course_access_from', 'local_campus', userdate(
                $period->validfrom,
                get_string('strftimedate', 'langconfig')
            ));
            $state = 'future';
        } else if ($period->validuntil !== null) {
            $date = userdate($period->validuntil, get_string('strftimedate', 'langconfig'));
            if ($period->validuntil < $now) {
                $label = get_string('course_access_expired', 'local_campus', $date);
                $state = 'expired';
            } else {
                $label = get_string('course_access_until', 'local_campus', $date);
                $state = 'dated';
            }
        }

        return [
            'trial' => $origin === CommerceCourseAccessOrigin::TRIAL,
            'haslabel' => $label !== '',
            'label' => $label,
            'state' => $state,
        ];
    }

    private function effective_origin(MyCoursePresentation $item): string {
        $origin = $item->commerceaccess->origin;
        if ($item->trial && in_array($origin, [CommerceCourseAccessOrigin::UNKNOWN, CommerceCourseAccessOrigin::ADMIN], true)) {
            return CommerceCourseAccessOrigin::TRIAL;
        }
        return $origin;
    }

    /** @return array<string, mixed> */
    private function recommendations_context(): array {
        $items = $this->recommendations?->all() ?? [];
        return [
            'hasitems' => $items !== [],
            'title' => get_string('mycourses_recommendations_title', 'local_campus'),
            'text' => get_string('mycourses_recommendations_text', 'local_campus'),
            'items' => array_map(function($item): array {
                $context = $item->to_array();
                $recommendationsku = trim((string)($context['sku'] ?? $context['productsku'] ?? ''));
                if ($recommendationsku !== '') {
                    $context['producturl'] = \local_subscriptions\url\CommerceCustomerPublicUrlResolver::product($recommendationsku)->out(false);
                }

                // Trial discovery and a genuine paid upgrade are mutually
                // exclusive presentation modes. This also prevents A1 Full
                // from inheriting the small Upgrade panel while in Trial.
                $context['upgrade'] = !empty($context['upgrade'])
                    && empty($context['trialoffer'])
                    && ($context['type'] ?? '') !== 'bundle';
                $context['hasupgradeprice'] = $context['upgrade']
                    && !empty($context['upgradepriceformatted']);
                $context['hasupgradepath'] = $context['upgrade']
                    && !empty($context['upgradefromlabel'])
                    && !empty($context['upgradetolabel']);
                $context['hasupgradecompareprice'] = $context['upgrade']
                    && !empty($context['upgradecomparepriceformatted']);
                $context['hasupgradediscount'] = $context['upgrade']
                    && !empty($context['upgradediscountpercentage']);
                $context['hasupgradesaving'] = $context['upgrade']
                    && !empty($context['upgradesavingformatted']);

                $context['upgradelabel'] = get_string(
                    'mycourses_recommendation_upgrade',
                    'local_campus'
                );
                $context['upgradeheading'] = get_string('mycourses_recommendation_upgrade_heading', 'local_campus');
                $context['upgradetext'] = get_string('mycourses_recommendation_upgrade_text', 'local_campus');
                $context['upgradepricelabel'] = get_string(
                    'mycourses_recommendation_upgrade_price',
                    'local_campus'
                );
                $context['discoverypricelabel'] = get_string(
                    'mycourses_recommendation_discovery_price',
                    'local_campus'
                );
                $context['standardpricelabel'] = get_string(
                    'mycourses_recommendation_standard_price',
                    'local_campus'
                );
                $context['upgradesavinglabel'] = get_string(
                    'mycourses_recommendation_upgrade_saving',
                    'local_campus',
                    $context['upgradesavingformatted'] ?? ''
                );
                $context['upgradesavingtext'] = get_string(
                    'mycourses_recommendation_upgrade_saving_text',
                    'local_campus',
                    $context['upgradefromlabel'] ?? ''
                );
                $context['ctlabel'] = get_string(
                    'mycourses_recommendation_discover',
                    'local_campus'
                );
                $context['imagealt'] = get_string('mycourses_recommendation_image_alt', 'local_campus', $item->title);
                return $context;
            }, $items),
        ];
    }

    /** @return array<string, mixed> */
    private function empty_context(): array {
        return [
            'title' => get_string('mycourses_empty_title', 'local_campus'),
            'text' => get_string('mycourses_empty_text', 'local_campus'),
            'storefronturl' => (new \moodle_url('/boutique'))->out(false),
            'storefrontlabel' => get_string('mycourses_empty_storefront', 'local_campus'),
            'trialurl' => (new \moodle_url('/local/campus/courses.php', ['segment' => 'trial']))->out(false),
            'triallabel' => get_string('mycourses_empty_trial', 'local_campus'),
        ];
    }

    /** @return array{icon:string,label:string,value:int} */
    private function stat_context(string $icon, string $label, int $value): array {
        return ['icon' => $icon, 'label' => $label, 'value' => $value];
    }
}
