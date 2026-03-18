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

namespace block_gearup\local\setting;

use block_gearup\di;

/**
 * Setting.
 *
 * @package    block_gearup
 * @copyright  2025 Branch Up Pty Ltd
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activation_status extends static_setting {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup/activationstatus', get_string('licenceactivation', 'block_gearup'), '');
    }

    /**
     * Get HTML content.
     *
     * @return string
     */
    protected function get_html_content() {
        $lm = \block_gearup\di::get('lm');
        $context = [
            'isactive' => $lm->is_active(),
            'activationid' => $lm->get_activation_id(),
        ];
        return di::get('renderer')->render_from_template('block_gearup/admin/setting/activation_status', $context);
    }

}
