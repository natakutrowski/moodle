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
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller\system;

use cache_helper;
use context_system;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook extends base {

    protected function get_signing_secret() {
        return get_config('block_gearup', 'webhooksecret') ?: null;
    }

    protected function process($type, $payload) {
        if ($type === 'metadata.updated') {
            cache_helper::purge_by_definition('block_gearup', 'metadata');
            $fs = get_file_storage();
            $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');

        } else if ($type === 'system.deactivate') {
            unset_config('activationid', 'block_gearup');
            unset_config('activationtoken', 'block_gearup');
            unset_config('webhooksecret', 'block_gearup');

            cache_helper::purge_by_definition('block_gearup', 'metadata');
            $fs = get_file_storage();
            $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');

        } else if ($type === 'system.test') {
            echo json_encode($payload);
        }
    }

}
