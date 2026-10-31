<?php
// GPL v3 or later.
namespace auth_campusremember\task;

defined('MOODLE_INTERNAL') || die();

class purge_expired_tokens extends \core\task\scheduled_task {
    public function get_name(): string {
        return 'CampusFR: delete expired persistent login tokens';
    }

    public function execute() {
        global $DB;
        $DB->delete_records_select('auth_campusremember_tokens', 'expires < ?', [time()]);
    }
}
