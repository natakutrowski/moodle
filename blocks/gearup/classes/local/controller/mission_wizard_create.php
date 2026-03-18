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

use block_gearup\form\wizard\achievement_create_form;
use block_gearup\form\wizard\challenge_create_form;
use block_gearup\form\wizard\quest_create_form;
use block_gearup\form\wizard\streak_create_form;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\model\mission;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_wizard_create extends route_base {
    use utils\mission_wizard_trait;

    protected $currentstep = 'create';

    protected $form;

    protected function get_form() {
        if (!$this->form) {
            $type = $this->get_param('type');
            if ($type === 'quest') {
                $this->form = new quest_create_form($this->pageurl->out(false), [
                    'persistent' => null,
                    'context' => $this->context,
                    'type' => mission::TYPE_QUEST,
                    'placeholdertitle' => get_string('sampleartofconnecting', 'block_gearup'),
                ]);
            } else if ($type === 'achievement') {
                $this->form = new achievement_create_form($this->pageurl->out(false), [
                    'persistent' => null,
                    'context' => $this->context,
                    'type' => mission::TYPE_ACHIEVEMENT,
                    'placeholdertitle' => get_string('samplecollaborationchampion', 'block_gearup'),
                ]);
            } else if ($type === 'challenge' && $this->lm->use_challenges()) {
                $this->form = new challenge_create_form($this->pageurl->out(false), [
                    'persistent' => null,
                    'context' => $this->context,
                    'type' => mission::TYPE_CHALLENGE,
                ]);
            } else if ($type === 'streak' && $this->lm->use_streaks()) {
                $this->form = new streak_create_form($this->pageurl->out(false), [
                    'persistent' => null,
                    'context' => $this->context,
                    'type' => mission::TYPE_STREAK,
                    'placeholdertitle' => get_string('samplemyfirststreak', 'block_gearup'),
                ]);
                $this->form->set_data(['title' => get_string('samplemyfirststreak', 'block_gearup')]);

            } else {
                throw new \coding_exception('Invalid or unknown mission type.');
            }
        }
        return $this->form;
    }

    protected function get_page_html_head_title() {
        if ($this->get_param('type') === 'achievement') {
            return get_string('newachievement', 'block_gearup');
        } else if ($this->get_param('type') === 'challenge') {
            return get_string('newchallenge', 'block_gearup');
        } else if ($this->get_param('type') === 'streak') {
            return get_string('newstreak', 'block_gearup');
        } else if ($this->get_param('type') === 'quest') {
            return get_string('newquest', 'block_gearup');
        }
        throw new \coding_exception('Unknown mission type.');
    }

    protected function get_wizard_title() {
        if ($this->get_param('type') === 'achievement') {
            return get_string('whatisidentityachievement', 'block_gearup');
        } else if ($this->get_param('type') === 'challenge') {
            return get_string('whatisidentitychallenge', 'block_gearup');
        } else if ($this->get_param('type') === 'streak') {
            return get_string('whatisidentitystreak', 'block_gearup');
        } else if ($this->get_param('type') === 'quest') {
            return get_string('whatisidentityquest', 'block_gearup');
        }
        throw new \coding_exception('Unknown mission type.');
    }

    protected function pre_wizard_content() {
        $form = $this->get_form();
        if ($data = $form->get_data()) {
            $mission = new mission(0, $data);
            $mission->create();
            $this->redirect($this->get_wizard_next_url($mission));

        } else if ($form->is_cancelled()) {
            $returnurl = $this->urlresolver->reverse('missions');
            if ($this->get_param('type') === 'streak') {
                $this->redirect($this->urlresolver->reverse('streaks'));
            }
            $this->redirect($returnurl);
        }
    }

    protected function wizard_content() {
        $form = $this->get_form();
        $form->set_display_vertical();
        $form->display();
    }

}
