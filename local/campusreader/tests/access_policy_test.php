<?php
/**
 * CampusFR Avent — C2.2b.4.
 * Access policy integration tests using Moodle's ISOLATED PHPUnit database.
 * These tests do not write to the operational DEV database.
 * GPL-3.0-or-later.
 */
declare(strict_types=1);

namespace local_campusreader;

defined('MOODLE_INTERNAL') || die();

use local_campusreader\local\collection_access;

final class access_policy_test extends \advanced_testcase {
    private \stdClass $course;
    private \stdClass $student;
    private \stdClass $collection;
    private \stdClass $book;
    private \stdClass $slot;
    private \stdClass $manual;
    private int $now;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->now = time();
        $this->course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $this->student = $this->getDataGenerator()->create_user();
        $this->manual = \enrol_get_plugin('manual')
            ? $this->find_manual_instance()
            : throw new \RuntimeException('Enrolment manual absent du Moodle de test.');

        $DB = $this->getDB();
        $collectionid = (int)$DB->insert_record('local_cr_collection', (object)[
            'slug' => 'cfrqa-' . $this->course->id,
            'title' => 'QA Advent — PHPUnit only',
            'courseid' => $this->course->id,
            'timezone' => 'Europe/Paris',
            'published' => 1,
            'timecreated' => $this->now,
            'timemodified' => $this->now,
            'presentationmode' => 'auto',
            'libraryafter' => $this->now + 86400,
        ]);
        $bookid = (int)$DB->insert_record('local_cr_book', (object)[
            'collectionid' => $collectionid,
            'slug' => 'cfrqa-story',
            'title' => 'QA Story',
            'subtitle' => 'For tests only',
            'published' => 1,
            'timecreated' => $this->now,
            'timemodified' => $this->now,
        ]);
        $slotid = (int)$DB->insert_record('local_cr_slot', (object)[
            'collectionid' => $collectionid,
            'position' => 1,
            'unlockat' => $this->now - 10,
            'bookid' => $bookid,
            'teaser' => 'QA',
            'timecreated' => $this->now,
            'timemodified' => $this->now,
            'layoutsize' => 'normal',
        ]);
        $this->collection = $DB->get_record('local_cr_collection', ['id' => $collectionid], '*', MUST_EXIST);
        $this->book = $DB->get_record('local_cr_book', ['id' => $bookid], '*', MUST_EXIST);
        $this->slot = $DB->get_record('local_cr_slot', ['id' => $slotid], '*', MUST_EXIST);
    }

    private function getDB(): \moodle_database {
        global $DB;
        return $DB;
    }

    private function find_manual_instance(): \stdClass {
        $DB = $this->getDB();
        $instance = $DB->get_record('enrol', [
            'courseid' => $this->course->id,
            'enrol' => 'manual',
        ]);
        if (!$instance) {
            $plugin = \enrol_get_plugin('manual');
            $plugin->add_default_instance($this->course->id);
            $instance = $DB->get_record('enrol', [
                'courseid' => $this->course->id,
                'enrol' => 'manual',
            ], '*', MUST_EXIST);
        }
        if ((int)$instance->status !== ENROL_INSTANCE_ENABLED) {
            $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['id' => $instance->id]);
            $instance->status = ENROL_INSTANCE_ENABLED;
        }
        return $instance;
    }

    private function enrol_student(int $status = ENROL_USER_ACTIVE, int $start = 0, int $end = 0): void {
        $role = $this->getDB()->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);
        \enrol_get_plugin('manual')->enrol_user(
            $this->manual, (int)$this->student->id, (int)$role->id,
            $start, $end, $status
        );
    }

    private function as_student(): void {
        $this->setUser($this->student);
    }

    private function allow(): bool {
        return collection_access::can_read_book($this->book, $this->collection, $this->slot, $this->now);
    }

    /**
     * Diagnostics only: never changes a role, an enrolment or the access policy.
     * IDs here belong exclusively to Moodle's isolated PHPUnit database.
     */
    private function positive_gate_diagnostic(): string {
        global $USER;
        $DB = $this->getDB();
        $context = \context_course::instance((int)$this->course->id);
        $course = $DB->get_record('course', ['id' => $this->course->id], 'id,visible');
        $enrolment = $DB->get_record('user_enrolments', [
            'enrolid' => $this->manual->id,
            'userid' => $this->student->id,
        ]);
        $roleassignments = (int)$DB->count_records('role_assignments', [
            'userid' => $this->student->id,
            'contextid' => $context->id,
        ]);
        $enroled = \is_enrolled($context, (int)$this->student->id, '', true);
        $cap = \has_capability('moodle/course:view', $context, (int)$this->student->id);
        $isguest = \isguestuser((int)$this->student->id);
        $enrolend = \enrol_get_enrolment_end((int)$this->course->id, (int)$this->student->id);
        return sprintf(
            'GATES course_visible=%d collection_published=%d book_published=%d ' .
            'student_current=%d guest=%d manual_instance_status=%d ' .
            'user_enrolment_exists=%d user_enrolment_status=%s ' .
            'user_enrolment_start=%s user_enrolment_end=%s ' .
            'is_enrolled_active=%d enrolment_end_result=%s ' .
            'has_course_view=%d course_role_assignments=%d slot_unlockat=%d now=%d',
            $course ? (int)$course->visible : -1,
            (int)$this->collection->published,
            (int)$this->book->published,
            (int)((int)($USER->id ?? 0) === (int)$this->student->id),
            (int)$isguest,
            (int)$this->manual->status,
            (int)(bool)$enrolment,
            $enrolment ? (string)$enrolment->status : 'missing',
            $enrolment ? (string)$enrolment->timestart : 'missing',
            $enrolment ? (string)$enrolment->timeend : 'missing',
            (int)$enroled,
            $enrolend === false ? 'false' : (string)$enrolend,
            (int)$cap,
            $roleassignments,
            (int)$this->slot->unlockat,
            $this->now
        );
    }

    public function test_active_enrolment_opens_published_unlocked_book(): void {
        $this->enrol_student();
        $this->as_student();
        $this->assertTrue(
            collection_access::can_view_collection($this->collection),
            $this->positive_gate_diagnostic()
        );
        $this->assertTrue($this->allow());
    }

    /**
     * A standard student may be actively enrolled without moodle/course:view:
     * that capability is for unenrolled viewing, not a student entitlement.
     */
    public function test_active_student_without_course_view_capability_is_allowed(): void {
        $this->enrol_student();
        $context = \context_course::instance((int)$this->course->id);
        $studentrole = $this->getDB()->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);
        \assign_capability('moodle/course:view', CAP_PREVENT, (int)$studentrole->id, $context, true);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->as_student();
        $this->assertTrue(\is_enrolled($context, (int)$this->student->id, '', true));
        $this->assertFalse(\has_capability('moodle/course:view', $context, (int)$this->student->id));
        $this->assertTrue(collection_access::can_view_collection($this->collection));
        $this->assertTrue($this->allow());
    }

    public function test_non_enrolled_user_is_denied(): void {
        $this->as_student();
        $this->assertFalse(collection_access::can_view_collection($this->collection));
        $this->assertFalse($this->allow());
    }

    public function test_suspended_enrolment_is_denied(): void {
        $this->enrol_student(ENROL_USER_SUSPENDED);
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_expired_enrolment_is_denied(): void {
        $this->enrol_student(ENROL_USER_ACTIVE, $this->now - 86400, $this->now - 30);
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_future_enrolment_is_denied(): void {
        $this->enrol_student(ENROL_USER_ACTIVE, $this->now + 3600, $this->now + 86400);
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_hidden_course_blocks_even_active_enrolment(): void {
        $this->enrol_student();
        $this->getDB()->set_field('course', 'visible', 0, ['id' => $this->course->id]);
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_unpublished_collection_blocks_student(): void {
        $this->enrol_student();
        $this->collection->published = 0;
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_unpublished_book_blocks_student(): void {
        $this->enrol_student();
        $this->book->published = 0;
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_locked_date_blocks_even_published_book(): void {
        $this->enrol_student();
        $this->slot->unlockat = $this->now + 1;
        $this->as_student();
        $this->assertFalse($this->allow());
        $this->assertTrue(collection_access::can_read_book(
            $this->book, $this->collection, $this->slot, $this->now + 1
        ), $this->positive_gate_diagnostic());
    }

    public function test_mismatched_book_slot_is_denied(): void {
        $this->enrol_student();
        $this->slot->bookid = $this->book->id + 100;
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_mismatched_collection_is_denied(): void {
        $this->enrol_student();
        $this->slot->collectionid = $this->collection->id + 100;
        $this->as_student();
        $this->assertFalse($this->allow());
    }

    public function test_guest_cannot_view_published_collection(): void {
        $this->setGuestUser();
        $this->assertFalse(collection_access::can_view_collection($this->collection));
        $this->assertFalse($this->allow());
    }

    public function test_admin_can_preview_draft_locked_book(): void {
        $this->collection->published = 0;
        $this->book->published = 0;
        $this->slot->unlockat = $this->now + 86400;
        $this->setAdminUser();
        $this->assertTrue(collection_access::can_view_collection($this->collection));
        $this->assertTrue($this->allow());
    }
    public function test_private_pageimage_denied_to_non_enrolled_user(): void {
        // A genuine stored Moodle File API record exists; the gate must reject before delivery.
        $DB = $this->getDB();
        $filename = 'private-qa.png';
        $DB->insert_record('local_cr_page', (object)[
            'bookid' => $this->book->id,
            'pageindex' => 2,
            'kind' => 'image',
            'title' => 'Private QA page',
            'paragraphsjson' => '["Une image privée."]',
            'imagefile' => $filename,
            'imagealt' => 'QA',
            'timecreated' => $this->now,
            'timemodified' => $this->now,
        ]);
        $fs = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'local_campusreader',
            'filearea' => 'pageimage',
            'itemid' => $this->book->id,
            'filepath' => '/p2/',
            'filename' => $filename,
            'mimetype' => 'image/png',
            'userid' => 0,
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9aYmO08AAAAASUVORK5CYII='));
        $this->assertNotFalse($file);
        $this->as_student(); // No course enrolment.
        require_once(dirname(__DIR__) . '/lib.php');
        $this->assertFalse(\local_campusreader_pluginfile(
            null, null, \context_system::instance(),
            'pageimage', [(string)$this->book->id, 'p2', $filename], false
        ));
    }

    public function test_private_file_invalid_path_rejected_before_loading(): void {
        $this->enrol_student();
        $this->as_student();
        require_once(dirname(__DIR__) . '/lib.php');
        $this->assertFalse(\local_campusreader_pluginfile(
            null, null, \context_system::instance(),
            'pageimage', [(string)$this->book->id, 'p../../', 'private-qa.png'], false
        ));
    }

}
