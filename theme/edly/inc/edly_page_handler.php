<?php
/*
@edlyRef: @
*/

defined('MOODLE_INTERNAL') || die();
include_once($CFG->dirroot . '/course/lib.php');

class edlyPageHandler {
  public function edlyGetPageTitle() {
    global $PAGE, $COURSE, $DB, $CFG;

    $edlyReturn = $PAGE->heading;

    if(
      $DB->record_exists('course', array('id' => $COURSE->id))
      && $COURSE->format == 'site'
      && $PAGE->cm
      && $PAGE->cm->name !== NULL
    ){
      $edlyReturn = $PAGE->cm->name;
    } elseif($PAGE->pagetype == 'blog-index') {
      $edlyReturn = get_string("blog", "blog");
    }

    return $edlyReturn;
  }
}
