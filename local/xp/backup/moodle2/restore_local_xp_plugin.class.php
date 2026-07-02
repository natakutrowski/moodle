<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local XP backup.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_xp_plugin extends restore_local_plugin {

    /** @var bool Preprocessed. */
    private $xppreprocessed = false;

    /**
     * Define structure.
     */
    protected function define_course_plugin_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('users');

        // Define each path. Note that, this structure may not always be defined. If the backup is merging
        // into an existing course, and the setting 'Overwrite course configuration' is set to 'No' (the default),
        // then the restore_course_structure_step is not included, and thus this structure is not defined. This
        // can lead to data loss if users are not aware of this. This also means that delete & merge backups
        // without the overwrite setting enabled, will not have their data deleted.
        $paths[] = new restore_path_element($this->get_namefor('config'), $this->get_pathfor('/xp_config'));
        $paths[] = new restore_path_element($this->get_namefor('drop'), $this->get_pathfor('/xp_drops/xp_drop'));
        $paths[] = new restore_path_element($this->get_namefor('rule'), $this->get_pathfor('/xp_rules/xp_rule'));

        // This path is a legacy one to ensure that older backups still work. We had to change the name of the
        // key where the config was stored because Moodle requires keys to be unique across plugins, hence the
        // new name 'xp_config'. The previous key structure is identical, and as such we should always send it
        // to the same restore method. Though because we can't have two restore path with the same name, we
        // had to declare another method. Note that when the config isn't found, nothing is done. See MDL-45441.
        $paths[] = new restore_path_element($this->get_namefor('config_legacy_key'), $this->get_pathfor('/config'));

        return $paths;
    }

    /**
     * Pre-processing.
     *
     * This method must be called from the first restore path element as we cannot simulate the presence
     * of another path if there is no associated data in the backup about it.
     *
     * @return void
     */
    private function pre_process_local_xp() {
        global $DB;

        // Prevent multiple accidental calls.
        if ($this->xppreprocessed) {
            return;
        }
        $this->xppreprocessed = true;

        $target = $this->task->get_target();
        $courseid = $this->task->get_courseid();

        // The backup target expects that all content is first being removed. Since deleting the block
        // instance does not delete the data itself, we must manually delete everything.
        if ($target == backup::TARGET_CURRENT_DELETING || $target == backup::TARGET_EXISTING_DELETING) {
            $this->task->log('local_xp: deleting all data in target course', backup::LOG_DEBUG);

            // Removing associated data.
            $conditions = ['courseid' => $courseid];
            $DB->delete_records('local_xp_config', $conditions);
            $DB->delete_records('local_xp_drops', $conditions);
            $DB->delete_records('local_xp_log', ['contextid' => $this->task->get_contextid()]);
            $DB->execute(
                "DELETE FROM {local_xp_rule}
                  WHERE ruleid IN (SELECT id FROM {block_xp_rule} WHERE contextid = :ctxid)",
                ['ctxid' => $this->task->get_contextid()]
            );
        }
    }

    /**
     * Process the legacy key of config.
     *
     * @param array $data Data.
     * @return void
     */
    public function process_local_xp_config_legacy_key($data) {
        $this->process_local_xp_config($data);
    }

    /**
     * Process config.
     *
     * @param array $data Data.
     * @return void
     */
    public function process_local_xp_config($data) {
        global $DB;

        // Call the pre-process.
        $this->pre_process_local_xp();

        $data['courseid'] = $this->task->get_courseid();
        if ($DB->record_exists('local_xp_config', ['courseid' => $data['courseid']])) {
            $this->task->log('local_xp: config not restored, existing config was found', backup::LOG_DEBUG);
            return;
        }
        $DB->insert_record('local_xp_config', $data);
    }

    /**
     * Process drop.
     *
     * @param array $data Data.
     * @return void
     */
    public function process_local_xp_drop($data) {
        global $DB;

        $oldid = $data['id'];
        $data['courseid'] = $this->task->get_courseid();

        // When the secret is already found, we have to generate a new secret. It usually means that
        // the secret is being restored in the same site as the original, either by duplicating the course
        // or by merge. Technically we should not care if drops share secrets, but as the shortcode used
        // to only include the secret, we should try and keep it as is for restores on other sites. For
        // restores on the same site, the secret will be regenerated which will cause the legacy xpdrop
        // shortcode to point to the wrong shortcode, but that's the best we can do. For those using
        // the new shortcodes including the ID, this won't have any impact.
        while ($DB->record_exists('local_xp_drops', ['secret' => $data['secret']])) {
            $data['secret'] = substr(bin2hex(random_bytes(128)), 0, 7);
        }

        $newid = $DB->insert_record('local_xp_drops', $data);
        $this->set_mapping('local_xp_drop', $oldid, $newid);
    }

    /**
     * Process rule.
     *
     * @param array $data Data.
     * @return void
     */
    public function process_local_xp_rule($data) {
        // Save the rule to process it after the restore as we don't have the final rule ID, yet.
        $restoreid = $this->get_restoreid();
        $itemid = (int) $data['id'];
        restore_dbops::set_backup_ids_record($restoreid, 'local_xp_rule_record', $itemid, 0, null, $data);
    }

    /**
     * After execute.
     *
     * @return void
     */
    public function after_execute_course() {
        $this->add_related_files('local_xp', 'currency', null, $this->task->get_old_contextid());
    }

    /**
     * After restore.
     *
     * @return void
     */
    public function after_restore_course() {
        $this->after_restore_local_xp_rule();
    }

    /**
     * After restore for rule.
     *
     * @return void
     */
    protected function after_restore_local_xp_rule() {
        global $DB;
        $restoreid = $this->get_restoreid();

        // Retrieve all the rules that we stored during the restore process, we have to wait to restore them
        // because we need the block to restore first in order to retrieve the rule ID from it.
        $conditions = ['backupid' => $restoreid, 'itemname' => 'local_xp_rule_record'];
        $recordset = $DB->get_recordset('backup_ids_temp', $conditions, '', 'id, itemid, info');
        foreach ($recordset as $record) {
            $data = backup_controller_dbops::decode_backup_temp_info($record->info);
            $oldruleid = $data['ruleid'];
            $newruleid = $this->step->get_mappingid('block_xp_rule', $oldruleid, false);
            if (!$newruleid) {
                $this->task->log('local_xp: rule not restored, block rule not found', backup::LOG_DEBUG);
                continue;
            } else if ($DB->record_exists('local_xp_rule', ['ruleid' => $newruleid])) {
                $this->task->log('local_xp: rule not restored, already exists', backup::LOG_DEBUG);
                continue;
            }
            unset($data['id']);
            $data['ruleid'] = $newruleid;
            $DB->insert_record('local_xp_rule', $data);
        }
        $recordset->close();
    }

    /**
     * Define decode contents.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [];
    }
}
