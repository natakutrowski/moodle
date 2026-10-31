<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\form\admin;

use block_xp\di;
use context;
use context_system;
use core_form\dynamic_form;
use local_xp\local\model\oauth_client as oauth_client_model;
use moodle_url;

/**
 * OAuth client form.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_client extends dynamic_form {
    /** @var ?oauth_client_model */
    protected $client;

    /**
     * Get the client.
     *
     * @return oauth_client_model
     */
    protected function get_client(): oauth_client_model {
        if (!$this->client) {
            $id = $this->optional_param('id', 0, PARAM_INT);
            $client = null;
            if ($id) {
                $client = oauth_client_model::get_record(['id' => $id, 'type' => oauth_client_model::TYPE_MANUAL], MUST_EXIST);
            }
            $this->client = $client ?? new oauth_client_model(0, (object) ['type' => oauth_client_model::TYPE_MANUAL]);
        }
        return $this->client;
    }

    /**
     * Get logo file manager options.
     *
     * @return array
     */
    protected function get_logo_filemanager_options(): array {
        return [
            'subdirs' => 0,
            'accepted_types' => ['image'],
            'maxfiles' => 1,
        ];
    }

    /**
     * Get the form context.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Check access.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/site:config', $this->get_context_for_dynamic_submission());
        $this->get_client();
    }

    /**
     * Get the page URL.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return di::get('url_resolver')->reverse('admin/oauthclients')->get_compatible_url();
    }

    /**
     * Save the OAuth client.
     *
     * @return void
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $client = $this->get_client();

        $client->set('displayname', trim($data->displayname));
        $client->set('redirecturis', implode(',', explode("\n", $data->redirecturis)));
        $client->set('isvalid', true);
        $client->set('invalidhint', null);
        if (!$client->get('id')) {
            $client->set('isenabled', true);
        }
        $client->save();

        $context = $this->get_context_for_dynamic_submission();
        file_save_draft_area_files(
            $data->logo,
            $context->id,
            'local_xp',
            'oauthclient',
            $client->get('id'),
            $this->get_logo_filemanager_options()
        );

        $files = get_file_storage()->get_area_files(
            $context->id,
            'local_xp',
            'oauthclient',
            $client->get('id'),
            'sortorder, id',
            false
        );
        $file = reset($files);
        $client->set('logofilename', $file ? $file->get_filename() : null);
        $client->save();
    }

    /**
     * Set the OAuth client form data.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $client = $this->get_client();
        $context = $this->get_context_for_dynamic_submission();
        $draftitemid = file_get_submitted_draft_itemid('logo');
        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'local_xp',
            'oauthclient',
            $client->get('id'),
            $this->get_logo_filemanager_options()
        );

        $this->set_data([
            'id' => $client->get('id'),
            'displayname' => $client->get('displayname'),
            'redirecturis' => implode("\n", $client->get_redirect_urls()),
            'logo' => $draftitemid,
        ]);
    }

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'displayname', get_string('name', 'core'), ['size' => '64']);
        $mform->setType('displayname', PARAM_RAW);
        $mform->addRule('displayname', null, 'required', null, 'client');
        $mform->addRule('displayname', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'redirecturis', get_string('redirecturls', 'block_xp'), ['rows' => 4]);
        $mform->setType('redirecturis', PARAM_RAW);

        $mform->addElement(
            'filemanager',
            'logo',
            get_string('logo', 'core_admin'),
            null,
            $this->get_logo_filemanager_options()
        );
    }

    /**
     * Validation.
     *
     * @param array $data The data.
     * @param array $files The files.
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
