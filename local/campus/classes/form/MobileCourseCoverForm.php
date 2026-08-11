<?php

declare(strict_types=1);

namespace local_campus\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_campus\mycourses\MyCourseMobileCoverService;

/** Administration form for a course-specific mobile cover. */
final class MobileCourseCoverForm extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $courseid = (int)($this->_customdata['courseid'] ?? 0);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement(
            'filemanager',
            'mobilecover_filemanager',
            get_string('mobilecoverfield', 'local_campus'),
            null,
            MyCourseMobileCoverService::filemanager_options()
        );
        $mform->addHelpButton('mobilecover_filemanager', 'mobilecoverfield', 'local_campus');

        $this->add_action_buttons(false, get_string('savechanges'));
    }
}
