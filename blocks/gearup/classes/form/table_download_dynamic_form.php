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
 * Form.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

use block_gearup\di;
use context;
use context_system;
use moodle_url;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_download_dynamic_form extends improved_dynamic_form {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'filename', get_string('filename', 'repository'), ['size' => '50']);
        $mform->setType('filename', PARAM_NOTAGS);

        $formats = \core_plugin_manager::instance()->get_plugins_of_type('dataformat');
        $options = [];
        foreach ($formats as $format) {
            if ($format->is_enabled()) {
                $options[$format->name] = get_string('dataformat', $format->component);
            }
        }
        $mform->addElement('select', 'dataformat', get_string('downloadformat', 'block_gearup'), $options);

        $mform->addElement('hidden', 'guctxid');
        $mform->setType('guctxid', PARAM_INT);

        $mform->addElement('hidden', 'pageurl');
        $mform->setType('pageurl', PARAM_LOCALURL);
    }

    protected function get_default_data() {
        $pageurl = new moodle_url($this->optional_param('pageurl', '', PARAM_LOCALURL));
        return (object) [
            'guctxid' => $this->optional_param('guctxid', 0, PARAM_INT),
            'filename' => $this->optional_param('filename', 'file', PARAM_NOTAGS),
            'dataformat' => get_user_preferences('block_gearup_dataformat', ''),
            'pageurl' => $pageurl->out(false),
        ];
    }

    protected function initialise_for_dynamic_submission(): void {
        $contextid = $this->optional_param('guctxid', 0, PARAM_INT);

        // Normalize the context.
        $candidatecontext = $contextid ? context::instance_by_id($contextid) : context_system::instance();
        $context = di::get('context_manager')->normalise_context($candidatecontext);

        $this->_dynamicdata['context'] = $context;
    }

    protected function process_dynamic_save() {
        if ($data = $this->get_data()) {
            set_user_preference('block_gearup_dataformat', $data->dataformat);
            $url = new moodle_url($data->pageurl);
            $url->param('download', $data->dataformat);
            if (!empty($data->filename)) {
                $url->param('downloadfilename', $data->filename);
            }
            return ['redirecturl' => $url->out(false)];
        }
    }

    protected function check_access_for_dynamic_submission(): void {
        $accessperms = di::get('access_permissions_factory')->get_permissions_for_context($this->_dynamicdata['context']);
        $accessperms->require_manage();
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return $this->get_url_resolver()->reverse('missions');
    }

    protected function extra_validation($data, $files, array &$errors) {
        if (empty($data->dataformat)) {
            $errors['dataformat'] = get_string('required');
        }
    }
}
