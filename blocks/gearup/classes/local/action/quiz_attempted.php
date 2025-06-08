<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * Action.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\action;

use context_module;
use mod_quiz\quiz_attempt;

/**
 * Action.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_attempted extends static_action {

    /** @var \stdClass|false|null */
    protected $quizrecord;
    /** @var quiz_attempt|false|null */
    protected $quizattempt;
    /** @var \stdClass|false|null */
    protected $quizattemptrecord;

    public function __construct($userid, context_module $context, $attemptid) {
        parent::__construct($userid, $context, $attemptid);
    }

    public function get_attempt_id(): int {
        return $this->get_object_id();
    }

    public function get_cm_id(): int {
        return (int) $this->context->instanceid;
    }

    public function get_course_context(): \context_course {
        return $this->context->get_course_context();
    }

    public function get_course_id(): int {
        return (int) $this->get_course_context()->instanceid;
    }

    /**
     * Get the quiz attempt.
     *
     * @return quiz_attempt|null|false
     */
    public function get_quiz_attempt() {
        if ($this->quizattempt === null) {
            $attemptrecord = $this->get_quiz_attempt_record();
            $quizrecord = $this->get_quiz_record();
            $quizattempt = false;
            if ($attemptrecord && $quizrecord) {
                try {
                    [$course, $cm] = get_course_and_cm_from_instance($quizrecord->id, 'quiz');
                    $quizattemptclass = static::resolve_quiz_attempt_class();
                    $quizattempt = new $quizattemptclass($attemptrecord, $quizrecord, $cm, $course);
                } catch (\moodle_exception $e) {
                }
            }
            $this->quizattempt = $quizattempt;
        }
        return $this->quizattempt;
    }

    /**
     * Get the quiz attempt record.
     *
     * @return \stdClass|false
     */
    public function get_quiz_attempt_record() {
        global $DB;
        if ($this->quizattemptrecord === null) {
            $this->quizattemptrecord = $DB->get_record('quiz_attempts', ['id' => $this->get_attempt_id()]);
        }
        return $this->quizattemptrecord;
    }

    /**
     * Get the quiz attempt record.
     *
     * @return \stdClass|false
     */
    public function get_quiz_record() {
        global $DB;
        if ($this->quizrecord === null) {
            $quiz = false;
            $qa = $this->get_quiz_attempt_record();
            if ($qa) {
                $quiz = $DB->get_record('quiz', ['id' => $qa->quiz]);
            }
            $this->quizrecord = $quiz;
        }
        return $this->quizrecord;
    }

    /**
     * Set the quiz record.
     *
     * @param \stdClass|false|null $quizrecord The grade record, or falsey.
     */
    public function set_quiz_record($quizrecord) {
        $this->quizrecord = $quizrecord ?: false;
    }

    /**
     * Set the quiz attempt record.
     *
     * @param \stdClass|false|null $quizattemptrecord The grade record, or falsey.
     */
    public function set_quiz_attempt_record($quizattemptrecord) {
        $this->quizattemptrecord = $quizattemptrecord ?: false;
    }

    /**
     * Resolve the quiz attempt class name.
     *
     * @return string
     */
    protected static function resolve_quiz_attempt_class() {
        global $CFG;

        // The quiz_attempt class was renamed and moved in 4.2.
        if ($CFG->branch >= 402) {
            return '\\mod_quiz\\quiz_attempt';
        }

        require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
        return '\\quiz_attempt';
    }
}
