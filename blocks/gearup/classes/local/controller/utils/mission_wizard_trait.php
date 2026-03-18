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

namespace block_gearup\local\controller\utils;

use block_gearup\di;
use block_gearup\local\mission\mission;
use core\persistent;
use html_writer;

/**
 * Wizard trait.
 *
 * Controllers using this must define:
 *
 * - Property $currentstep
 * - Method get_wizard_title
 * - Method wizard_content
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait mission_wizard_trait {

    /** @var mission|null Defined by the parent class, maybe. */
    protected $mission;
    /** @var array|null The steps. */
    protected $navsteps;

    /**
     * Whether it is an achievement.
     *
     * @return bool
     */
    protected function is_wizard_achievement() {
        $isachievement = false;
        if (empty($this->mission)) {
            $isachievement = $this->get_param('type') === 'achievement';
        } else {
            $isachievement = $this->is_achievement();
        }
        return $isachievement;
    }

    /**
     * Whether it is a challenge.
     *
     * @return bool
     */
    protected function is_wizard_challenge() {
        $ischallenge = false;
        if (empty($this->mission)) {
            $ischallenge = $this->get_param('type') === 'challenge';
        } else {
            $ischallenge = $this->is_challenge();
        }
        return $ischallenge;
    }

    /**
     * Whether it is an achievement.
     *
     * @return bool
     */
    protected function is_wizard_streak() {
        $isachievement = false;
        if (empty($this->mission)) {
            $isachievement = $this->get_param('type') === 'streak';
        } else {
            $isachievement = $this->is_streak();
        }
        return $isachievement;
    }

    /**
     * Get current step.
     */
    protected function get_wizard_current_step() {
        if (empty($this->currentstep)) {
            throw new \coding_exception('Wizard class must define its current step');
        }
        return $this->currentstep;
    }

    /**
     * Get the steps.
     *
     * @param mission|null $mission The mission.
     * @return array
     */
    protected function get_wizard_steps($mission = null) {
        if ($this->navsteps) {
            return $this->navsteps;
        }

        $mission = $mission ?? $this->mission ?? null;
        $missionid = null;
        if ($mission) {
            $missionid = $mission instanceof persistent ? $mission->get('id') : $mission->get_id();
        }
        $hasurl = $missionid && $this->get_wizard_current_step() !== 'end';
        $urlresolver = $this->urlresolver;
        $params = $mission ? ['missionid' => $missionid] : null;

        $steps = [
            [
                'id' => 'create',
                'url' => $hasurl ? $urlresolver->reverse('mission_wizard_identity', $params) : null,
                'name' => get_string('missionidentity', 'block_gearup'),
            ],
            [
                'id' => 'objectives',
                'url' => $hasurl ? $urlresolver->reverse('mission_wizard_objectives', $params) : null,
                'name' => get_string('objectives', 'block_gearup'),
            ],
            [
                'id' => 'outcomes',
                'url' => $hasurl ? $urlresolver->reverse('mission_wizard_outcomes', $params) : null,
                'name' => get_string('outcomes', 'block_gearup'),
            ],
            [
                'id' => 'assignbehaviour',
                'url' => $hasurl ? $urlresolver->reverse('mission_wizard_assignbehaviour', $params) : null,
                'name' => get_string('assignmentbehaviour', 'block_gearup'),
            ],
            [
                'id' => 'timing',
                'url' => $hasurl ? $urlresolver->reverse('mission_wizard_timing', $params) : null,
                'name' => get_string('timing', 'block_gearup'),
            ],
            [
                'id' => 'storyline',
                'url' => $hasurl ? $urlresolver->reverse('mission_wizard_storyline', $params) : null,
                'name' => [
                    'achievement' => get_string('achievementinstructions', 'block_gearup'),
                    'quest' => get_string('storyline', 'block_gearup'),
                    'streak' => get_string('streakinstructions', 'block_gearup'),
                ],
            ],
            [
                'id' => 'end',
                'url' => $hasurl ? $urlresolver->reverse('mission_wizard_end', $params) : null,
                'name' => get_string('wizardend', 'block_gearup'),
            ],
        ];

        $type = 'quest';
        if ($this->is_wizard_achievement()) {
            $type = 'achievement';
        } else if ($this->is_wizard_challenge()) {
            $type = 'challenge';
        } else if ($this->is_wizard_streak()) {
            $type = 'streak';
        }

        foreach ($steps as &$step) {
            if (is_array($step['name'])) {
                $step['name'] = $step['name'][$type] ?? null;
            }
        }

        if ($this->is_wizard_achievement()) {
            return array_filter($steps, function ($step) {
                return in_array($step['id'], ['create', 'objectives', 'storyline', 'end']);
            });
        } else if ($this->is_wizard_challenge()) {
            return array_filter($steps, function ($step) {
                return in_array($step['id'], ['create', 'objectives', 'outcomes', 'timing', 'end']);
            });
        } else if ($this->is_wizard_streak()) {
            return array_filter($steps, function ($step) {
                return in_array($step['id'], ['create', 'objectives', 'timing', 'storyline', 'end']);
            });
        }

        return array_filter($steps, function ($step) {
            return !in_array($step['id'], ['timing']);
        });
    }

    protected function get_wizard_next_url($mission) {
        $nexturl = null;
        $foundstep = false;
        $currentstep = $this->get_wizard_current_step();
        foreach ($this->get_wizard_steps($mission) as $candidate) {
            if ($foundstep) {
                $nexturl = $candidate['url'];
                break;
            } else if ($candidate['id'] === $currentstep) {
                $foundstep = true;
            }
        }
        if (!$nexturl) {
            throw new \coding_exception('Could not identify next step URL.');
        }
        return $nexturl;
    }

    final protected function pre_content() {
        parent::pre_content();

        // Check whether the wizard was previously completed.
        $mh = di::get('mission_helper');
        if ($this->mission && !$mh->is_in_wizard($this->mission)) {
            $this->redirect($this->urlresolver->reverse('mission', ['missionid' => $this->mission->get_id()]));
        }

        // If we're hitting a step that is not meant for this mission, redirect elsewhere.
        $currentstep = $this->get_wizard_current_step();
        $stepids = array_map(function ($step) {
            return $step['id'];
        }, $this->get_wizard_steps());
        if (!in_array($currentstep, $stepids)) {
            $this->redirect($this->urlresolver->reverse('missions'));
        }

        // Call pre content if any.
        if (method_exists($this, 'pre_wizard_content')) {
            $this->pre_wizard_content();
        }
    }

    final protected function content() {
        $output = $this->get_renderer();
        $backurl = $this->urlresolver->reverse('missions');
        if ($this->is_wizard_streak()) {
            $backurl = $this->urlresolver->reverse('streaks');
        }

        echo html_writer::start_div('gu-grid gu-grid-cols-4 gu-gap-4');

        echo html_writer::start_div('');
        echo html_writer::start_div();
        echo html_writer::link($backurl, get_string('back', 'core'));
        echo html_writer::end_div();
        echo html_writer::start_div('gu-my-6');
        echo $output->wizard_steps($this->get_wizard_steps(), $this->get_wizard_current_step());
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('gu-col-span-3');

        echo html_writer::start_div('gu-mb-8');
        echo $output->heading($this->get_wizard_title(), 4);
        echo html_writer::end_div();

        echo html_writer::start_div();
        $this->wizard_content();
        echo html_writer::end_div();

        echo html_writer::end_div();

        echo html_writer::end_div();
    }

    /**
     * Get the wizard title.
     *
     * @return string
     */
    abstract protected function get_wizard_title();

    /**
     * Outputs the wizard content.
     *
     * @return void
     */
    abstract protected function wizard_content();

}
