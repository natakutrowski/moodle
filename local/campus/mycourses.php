<?php
// /local/campus/mycourses.php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/campus/lib.php');
require_once($CFG->dirroot . '/local/subscriptions/lib.php');

use local_campus\mycourses\MyCoursesService;
use local_campus\output\mycourses\MyCoursesPage;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationCollection;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationService;
use local_subscriptions\support\Region;

require_login();
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(\local_subscriptions\url\UrlFactory::my_courses());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mycourses_title', 'local_campus'));
$PAGE->set_heading(get_string('mycourses_title', 'local_campus'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(
    get_string('commerce_customer_hub_title', 'local_subscriptions'),
    \local_subscriptions\url\UrlFactory::my_campus()
);
$PAGE->navbar->add(get_string('mycourses_title', 'local_campus'), $PAGE->url);
$PAGE->requires->css(new moodle_url('/local/campus/styles.css'));

if (get_user_preferences('local_campus_trial_welcome_pending', 0)) {
    \core\notification::add(
        get_string('trial_welcome_banner_html', 'local_campus'),
        \core\output\notification::NOTIFY_SUCCESS
    );
    unset_user_preference('local_campus_trial_welcome_pending');
}

$collection = (new MyCoursesService($DB))->get_for_current_user();
$recommendations = new CommerceCourseRecommendationCollection();

try {
    $recommendations = (new CommerceCourseRecommendationService($DB))->get_for_learner(
        (int)$USER->id,
        array_keys($collection->all()),
        array_keys($collection->trial_course_map()),
        current_language(),
        Region::default_currency_for(Region::detect_country())
    );
} catch (\Throwable $exception) {
    debugging('My courses recommendations are temporarily unavailable: ' . $exception->getMessage(), DEBUG_DEVELOPER);
}

$page = new MyCoursesPage($collection, $recommendations);

echo $OUTPUT->header();
echo $page->render($OUTPUT);
echo $OUTPUT->footer();
