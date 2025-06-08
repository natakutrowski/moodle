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

namespace block_gearup\local\exporter\tracker;

use block_gearup\di;
use block_gearup\local\exporter\context_exporter;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\quest;
use block_gearup\local\mission\streak;
use block_gearup\output\tracker;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracker_exporter extends \core\external\exporter {

    /** @var object */
    protected $lm;
    /** @var int The user ID. */
    protected $userid;

    /**
     * Constructor.
     *
     * @param int $userid The user ID.
     * @param array $related The related objects.
     */
    public function __construct(int $userid, $related = []) {
        $this->userid = $userid;
        $this->lm = di::get('lm');
        parent::__construct([], $related);
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'context' => 'context',
            'pageurl' => 'moodle_url?',
            'url_resolver' => 'block_gearup\local\routing\url_resolver?',
        ];
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'canedit' => ['type' => PARAM_BOOL],
            'canview' => ['type' => PARAM_BOOL],
            'context' => [
                'type' => context_exporter::read_properties_definition(),
            ],
            'hasany' => ['type' => PARAM_BOOL],
            'hassections' => ['type' => PARAM_BOOL],
            'sections' => [
                'type' => [
                    'isachievements' => ['type' => PARAM_BOOL],
                    'isquests' => ['type' => PARAM_BOOL],
                    'ischallenges' => ['type' => PARAM_BOOL],
                    'isstreaks' => ['type' => PARAM_BOOL],
                    'achievementscontext' => [
                        'type' => achievements_tracker_exporter::read_properties_definition(),
                        'optional' => true,
                    ],
                    'questscontext' => [
                        'type' => quests_tracker_exporter::read_properties_definition(),
                        'optional' => true,
                    ],
                    'challengescontext' => [
                        'type' => challenges_tracker_exporter::read_properties_definition(),
                        'optional' => true,
                    ],
                    'streakscontext' => [
                        'type' => streaks_tracker_exporter::read_properties_definition(),
                        'optional' => true,
                    ],
                ],
                'multiple' => true,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $context = $this->related['context'];
        $repo = di::get('repository');
        $accessperms = di::get('access_permissions_factory')->get_permissions_for_context($context);

        $canview = $accessperms->can_access();
        $canedit = $accessperms->can_manage();

        $types = $repo->get_visible_instance_types_in($this->userid, $context);
        $hasany = !empty($types);

        $sections = [];
        if ($hasany) {
            $sectionsorder = tracker::get_missions_order();
            foreach ($sectionsorder as $key) {
                if (!in_array($key, $types)) {
                    continue;
                }

                if ($key === achievement::class) {
                    $ctxkey = 'achievementscontext';
                    $ctxdata = (new achievements_tracker_exporter($this->userid, $this->related))->export($output);

                } else if ($key === challenge::class && $this->lm->use_challenges()) {
                    $ctxkey = 'challengescontext';
                    $ctxdata = (new challenges_tracker_exporter($this->userid, $this->related))->export($output);

                } else if ($key === quest::class) {
                    $ctxkey = 'questscontext';
                    $ctxdata = (new quests_tracker_exporter($this->userid, $this->related))->export($output);

                } else if ($key === streak::class && $this->lm->use_streaks()) {
                    $ctxkey = 'streakscontext';
                    $ctxdata = (new streaks_tracker_exporter($this->userid, $this->related))->export($output);
                } else {
                    continue;
                }

                $sections[] = [
                    $ctxkey => $ctxdata,
                    'isachievements' => $key === achievement::class,
                    'ischallenges' => $key === challenge::class,
                    'isquests' => $key === quest::class,
                    'isstreaks' => $key === streak::class,
                ];
            }
        }

        return [
            'canview' => $canview,
            'canedit' => $canedit,
            'context' => (new context_exporter($context))->export($output),
            'hasany' => $hasany,
            'sections' => $sections,
            'hassections' => !empty($sections),
        ];
    }

}
