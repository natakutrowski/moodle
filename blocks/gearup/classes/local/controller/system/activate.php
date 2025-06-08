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

use block_gearup\di;
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
class activate extends base {

    protected function get_signing_secret() {
        $secret = null;
        $actsecret = get_config('block_gearup', 'activationsecret');
        if ($actsecret && strpos($actsecret, ':') > 0) {
            list($acttime, $candidate) = explode(':', $actsecret, 2);
            $acttime = (int) $acttime;
            if ($acttime > time() - 60 && $acttime < time() + 60) {
                $secret = $candidate;
            }
        }
        return $secret;
    }

    protected function process($type, $payload) {
        $lm = di::get('lm');
        if ($lm->is_activated()) {
            header('HTTP/1.1 412 Precondition Failed');
            return;
        }

        if ($type === 'licence.activate') {
            $lm->process_payload($payload);

            cache_helper::purge_by_definition('block_gearup', 'metadata');
            $fs = get_file_storage();
            $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');
        }
        unset_config('activationsecret', 'block_gearup');
    }

}
