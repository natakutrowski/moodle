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
 * Exporter factory.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\factory;

use block_gearup\local\exporter\mission_exporter;
use block_gearup\local\exporter\mission_instance_exporter;
use block_gearup\local\exporter\tracker\tracker_exporter;
use block_gearup\local\mission\helper;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;

/**
 * Exporter factory.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exporter_factory {

    /** @var helper The mission helper. */
    protected $missionhelper;
    /** @var access_permissions_factory The perms factory. */
    protected $accesspermsfactory;

    public function __construct(access_permissions_factory $accesspermsfactory, helper $missionhelper) {
        $this->missionhelper = $missionhelper;
        $this->accesspermsfactory = $accesspermsfactory;
    }

    public function get_mission_instance_exporter(mission_instance $missioninst, array $related = []) {
        return new mission_instance_exporter($missioninst, $related + [
            'access_permissions_factory' => $this->accesspermsfactory,
            'exporter_factory' => $this,
            'mission_helper' => $this->missionhelper,
        ]);
    }

    public function get_mission_instance_exporter_class() {
        return mission_instance_exporter::class;
    }

    public function get_mission_exporter(mission $mission, array $related = []) {
        return new mission_exporter($mission, $related + [
            'access_permissions_factory' => $this->accesspermsfactory,
            'mission_helper' => $this->missionhelper,
        ]);
    }

    public function get_mission_exporter_class() {
        return mission_exporter::class;
    }

    public function get_tracker_exporter(int $userid, array $related = []) {
        return new tracker_exporter($userid, $related + []);
    }

    public function get_tracker_exporter_class() {
        return tracker_exporter::class;
    }
}
