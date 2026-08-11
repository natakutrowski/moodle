<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_campus\form\MobileCourseCoverForm;
use local_campus\mycourses\MyCourseMobileCoverService;

admin_externalpage_setup('local_campus_mobile_covers');
require_capability('moodle/site:config', \context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$courses = $DB->get_records_select('course', 'id > :siteid', ['siteid' => SITEID], 'fullname ASC', 'id,fullname,shortname');
if (!$courseid && $courses) {
    $courseid = (int)array_key_first($courses);
}
if (!$courseid || !isset($courses[$courseid])) {
    throw new moodle_exception('invalidcourseid');
}

$course = $courses[$courseid];
$context = \context_course::instance($courseid);
$covers = new MyCourseMobileCoverService();

if ($action === 'delete') {
    require_sesskey();
    $covers->delete($courseid);
    redirect(
        new moodle_url('/local/campus/mobile_course_covers.php', ['courseid' => $courseid]),
        get_string('mobilecoverdeleted', 'local_campus'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$draftitemid = file_get_submitted_draft_itemid('mobilecover_filemanager');
$covers->prepare_draft($courseid, $draftitemid);

$form = new MobileCourseCoverForm(null, ['courseid' => $courseid]);
$form->set_data((object)[
    'courseid' => $courseid,
    'mobilecover_filemanager' => $draftitemid,
]);

if ($data = $form->get_data()) {
    $covers->save_draft((int)$data->courseid, (int)$data->mobilecover_filemanager);
    redirect(
        new moodle_url('/local/campus/mobile_course_covers.php', ['courseid' => $courseid]),
        get_string('mobilecoversaved', 'local_campus'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_url('/local/campus/mobile_course_covers.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('mobilecoverspage', 'local_campus'));
$PAGE->set_heading(get_string('mobilecoverspage', 'local_campus'));

$courseoptions = [];
foreach ($courses as $candidate) {
    $courseoptions[(int)$candidate->id] = format_string($candidate->fullname) . ' [' . $candidate->id . ']';
}

$currenturl = $covers->get_url($courseid);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mobilecoverspage', 'local_campus'));
echo html_writer::tag('p', get_string('mobilecoversintro', 'local_campus'), ['class' => 'text-muted']);

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => (new moodle_url('/local/campus/mobile_course_covers.php'))->out(false),
    'class' => 'mb-4',
]);
echo html_writer::label(get_string('mobilecovercourse', 'local_campus'), 'local-campus-mobile-cover-course', false, ['class' => 'form-label fw-semibold']);
echo html_writer::select($courseoptions, 'courseid', $courseid, false, [
    'id' => 'local-campus-mobile-cover-course',
    'class' => 'form-select',
    'onchange' => 'this.form.submit()',
]);
echo html_writer::end_tag('form');

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', format_string($course->fullname), ['class' => 'h5']);
if ($currenturl !== null) {
    echo html_writer::div(
        html_writer::empty_tag('img', [
            'src' => $currenturl,
            'alt' => get_string('mobilecoverpreviewalt', 'local_campus', format_string($course->fullname)),
            'style' => 'display:block;width:min(100%,520px);aspect-ratio:4/3;object-fit:cover;border-radius:16px;',
        ]),
        'mb-3'
    );
    echo html_writer::link(
        new moodle_url('/local/campus/mobile_course_covers.php', [
            'courseid' => $courseid,
            'action' => 'delete',
            'sesskey' => sesskey(),
        ]),
        get_string('mobilecoverdelete', 'local_campus'),
        ['class' => 'btn btn-outline-danger btn-sm']
    );
} else {
    echo html_writer::div(get_string('mobilecovernone', 'local_campus'), 'alert alert-light border');
}
echo html_writer::end_div();
echo html_writer::end_div();

$form->display();
echo $OUTPUT->footer();