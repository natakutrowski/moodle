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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\local\controller\utils\mission_route_base;
use block_gearup\local\mission\mission;
use html_writer;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_wizard_end extends mission_route_base {

    use utils\mission_wizard_trait;
    protected $currentstep = 'end';

    protected function get_subpage_html_head_title() {
        return get_string('wizardend', 'block_gearup');
    }

    protected function get_wizard_title() {
        return get_string('youredone', 'block_gearup');
    }

    protected function pre_wizard_content() {
        $persistent = $this->mission->get_persistent();
        $persistent->set('state', mission::STATE_ACTIVE);
        $persistent->update();
    }

    protected function wizard_content() {
        $type = 'quest';
        $missionstrkey = 'viewquest';
        $listurl = $this->urlresolver->reverse('missions');

        if ($this->is_challenge()) {
            $type = 'challenge';
            $missionstrkey = 'viewchallenge';
        } else if ($this->is_achievement()) {
            $type = 'achievement';
            $missionstrkey = 'viewachievement';
        } else if ($this->is_streak()) {
            $type = 'streak';
            $missionstrkey = 'viewstreak';
            $listurl = $this->urlresolver->reverse('streaks');
        }

        $createurl = $this->urlresolver->reverse('mission_create', ['type' => $type]);
        $missionurl = $this->get_mission_url();
        $assignurl = $this->get_mission_url('assign');

        $assignstr = get_string('recruitusers', 'block_gearup');
        $createstr = get_string('createanother', 'block_gearup');
        $missionsstr = get_string('backtolist', 'block_gearup');
        $missionstr = get_string($missionstrkey, 'block_gearup');

        echo html_writer::tag('p', get_string('wizardendnote', 'block_gearup'));
        echo html_writer::tag('p', get_string('whatlikedonext', 'block_gearup'));
        echo html_writer::start_tag('nav');
        echo html_writer::start_tag('ul', ['class' => 'gu-m-0 gu-space-y-2']);
        if (!$this->is_streak()) {
            echo html_writer::tag('li', html_writer::link($createurl, $createstr));
        }
        echo html_writer::tag('li', html_writer::link($assignurl, $assignstr));
        echo html_writer::tag('li', html_writer::link($missionurl, $missionstr));
        echo html_writer::tag('li', html_writer::link($listurl, $missionsstr));
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('nav');
    }

}
