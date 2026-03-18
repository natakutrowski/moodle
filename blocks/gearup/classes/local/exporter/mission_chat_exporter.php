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

namespace block_gearup\local\exporter;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\narrator\narrator_with_speaking_frames;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\objective_with_supporting_url;
use block_gearup\local\outcome\type\user_facing_type;
use block_gearup\local\utils\collection_utils;
use moodle_url;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_chat_exporter extends \core\external\exporter {

    /** @var mission The mission. */
    protected $mission;
    /** @var array The objectives. */
    protected ?array $objectives = null;
    /** @var array The objective instances. */
    protected ?array $objinsts = null;
    /** @var array The rewards. */
    protected ?array $rewards = null;

    /**
     * Constructor.
     *
     * @param mission $mission The mission.
     * @param array $related The related objects.
     */
    public function __construct(mission $mission, $related = []) {
        $this->mission = $mission;

        // If we're receiving a mission instance, populate the state and objectives.
        if (!empty($related['mission_instance'])) {
            $mh = $related['mission_helper'];
            $mi = $related['mission_instance'];
            $related += ['state' => (object) [
                'hasstarted' => $mh->has_started($mi),
                'hascompleted' => $mh->has_completed($mi),
                'isended' => $mh->is_ended($mi),
                'needsattention' => $mi->needs_attention(),
            ]];
            $related += ['objective_instances' => $mi->get_objective_instances()];
        }

        // Set default state value.
        $related['state'] = (object) (((array) $related['state'] ?? []) + [
            'hasstarted' => false,
            'hascompleted' => false,
            'isended' => false,
            'needsattention' => false,
        ]);

        parent::__construct([], $related + [
            'context' => $mission->get_context(),
        ]);
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'context' => '\context',
            'mission_helper' => '\block_gearup\local\mission\helper',
            'objective_instances' => '\block_gearup\local\objective\objective_instance[]?',
            'state' => '\stdClass',
            'user' => '\stdClass',
        ];
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'items' => [
                'type' => [
                    'type' => ['type' => PARAM_ALPHANUMEXT],
                    'timestamp' => ['type' => PARAM_INT],

                    'message' => [
                        'type' => [
                            'author' => ['type' => PARAM_ALPHANUMEXT],
                            'content' => ['type' => PARAM_RAW],
                            'audio' => [
                                'type' => PARAM_URL,
                                'null' => NULL_ALLOWED,
                                'default' => null,
                            ],
                        ],
                        'optional' => true,
                    ],

                    'objectives' => [
                        'type' => [
                            'objectives' => [
                                'type' => [
                                    'label' => ['type' => PARAM_RAW],
                                    'completed' => ['type' => PARAM_BOOL],
                                    'counter' => ['type' => PARAM_INT],
                                    'count_needed' => ['type' => PARAM_INT],
                                    'supporting_url' => [
                                        'type' => PARAM_URL,
                                        'null' => NULL_ALLOWED,
                                        'default' => null,
                                    ],
                                ],
                                'multiple' => true,
                            ],
                        ],
                        'optional' => true,
                    ],

                    'rewards' => [
                        'type' => [
                            'rewards' => [
                                'type' => [
                                    'label' => ['type' => PARAM_RAW],
                                ],
                                'multiple' => true,
                            ],
                        ],
                        'optional' => true,
                    ],

                ],
                'multiple' => true,
            ],

            'participants' => [
                'type' => [
                    'id' => ['type' => PARAM_ALPHANUMEXT],
                    'picture_url' => [
                        'type' => PARAM_URL,
                        'null' => NULL_ALLOWED,
                        'default' => null,
                    ],
                    'speaking_frames' => [
                        'type' => PARAM_URL,
                        'multiple' => true,
                        'default' => [],
                    ],
                ],
                'multiple' => true,
            ],

            'readuntil' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
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
        $mission = $this->mission;
        $missionhelper = $this->related['mission_helper'];

        if (!$missionhelper->is_a_quest($mission)) {
            throw new \moodle_exception('Invalid mission type');
        }

        $introductionunread = $this->needs_attention() && !$this->has_started();
        $introductionitems = $this->convert_storyline_to_messages($mission->get_description());
        $instructionsunread = $this->needs_attention() && !$this->has_completed();
        $instructionsitems = $this->has_started() ? $this->convert_storyline_to_messages($mission->get_instructions()) : [];
        $feedbackunread = $this->needs_attention() && !$this->is_ended();
        $feedbackitems = $this->has_completed() ? $this->convert_storyline_to_messages($mission->get_feedback()) : [];

        $items = [];
        $timestamp = 1000;
        $readuntil = null;
        foreach ($introductionitems as $idx => $item) {
            $messageid = $timestamp;
            $readuntil = $introductionunread ? $readuntil : $timestamp;
            $items[] = $this->convert_item($item, $idx, $messageid, 'description');

            $timestamp += 1;
        }

        foreach ($instructionsitems as $idx => $item) {
            $messageid = $timestamp;
            $readuntil = $instructionsunread ? $readuntil : $timestamp;
            $items[] = $this->convert_item($item, $idx, $messageid, 'instructions');

            $timestamp += 1;
        }

        $dialoguecontainsobjectives = count(array_filter($items, function ($item) {
            return $item !== null && $item['type'] === 'objectives';
        })) > 0;
        if ($instructionsitems && !$dialoguecontainsobjectives) {
            $items[] = $this->convert_item('[objectives]', 99, $timestamp, 'instructions');
            $readuntil = $instructionsunread ? $readuntil : $timestamp;

            $timestamp += 1;
        }

        foreach ($feedbackitems as $idx => $item) {
            $messageid = $timestamp;
            $readuntil = $feedbackunread ? $readuntil : $timestamp;
            $items[] = $this->convert_item($item, $idx, $messageid, 'feedback');

            $timestamp += 1;
        }

        $dialoguecontainsrewards = count(array_filter($items, function ($item) {
            return $item !== null && $item['type'] === 'rewards';
        })) > 0;
        if ($feedbackitems && !$dialoguecontainsrewards) {
            $readuntil = $feedbackunread ? $readuntil : $timestamp;
            $items[] = $this->convert_item('[rewards]', 99, $timestamp, 'feedback');

            $timestamp += 1;
        }

        $visual = $mission->get_visual();
        $speakingframes = [];
        if ($visual && $visual instanceof narrator_with_speaking_frames) {
            $speakingframes = array_values(array_map(function ($url) {
                return $url->out(false);
            }, $visual->get_speaking_frames()));
        }

        $data = [
            'items' => array_values(array_filter($items)),
            'participants' => [
                [
                    'id' => 'narrator',
                    'picture_url' => $visual ? $visual->get_url()->out(false) : null,
                    'speaking_frames' => $speakingframes,
                ],
                [
                    'id' => 'user',
                    'picture_url' => $output->get_user_picture($this->related['user'])->out(false),
                    'speaking_frames' => [],
                ],
            ],
            'readuntil' => $readuntil,
        ];

        return $data;
    }

    /**
     * Apply storyline placeholders.
     *
     * @param \stdClass $user The user.
     * @param string|null $text The text.
     */
    protected function apply_storyline_placeholders(\stdClass $user, ?string $text) {
        if (empty($text)) {
            return $text;
        }
        $placeholders = [
            '[firstname]' => $user->firstname,
            '[fullname]' => fullname($user),
        ];
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    /**
     * Remove storyline placeholders.
     *
     * @param \stdClass $user The user.
     * @param string|null $text The text.
     */
    protected function remove_storyline_placeholders(?string $text) {
        if (empty($text)) {
            return $text;
        }
        $placeholders = [
            '[firstname]' => '',
            '[fullname]' => '',
        ];
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    protected function convert_item(string $text, int $idx, int $messageid, string $section) {
        $data = [
            'timestamp' => $messageid,
        ];

        if ($text === '[rewards]') {
            $rewards = $this->get_rewards();
            if (empty($rewards)) {
                return null;
            }
            return $data + [
                'type' => 'rewards',
                'rewards' => [
                    'rewards' => array_values(array_map(function ($reward) {
                        return [
                            'label' => $reward->get_label(),
                        ];
                    }, $this->get_rewards())),
                ],
            ];

        } else if ($text === '[objectives]') {
            $objectives = $this->get_objectives();
            if (empty($objectives)) {
                return null;
            }
            $objinsts = $this->related['objective_instances'] ?? [];
            return $data + [
                'type' => 'objectives',
                'objectives' => [
                    'objectives' => array_values(array_map(function (objective $objective) use ($objinsts) {
                        $objinst = collection_utils::find($objinsts, function (objective_instance $oi) use ($objective) {
                            return $oi->get_objective()->get_id() === $objective->get_id();
                        });
                        $url = $objective instanceof objective_with_supporting_url ? $objective->get_supporting_url() : null;
                        return [
                            'label' => $objective->get_label(),
                            'completed' => $objinst ? $objinst->is_completed() : false,
                            'counter' => $objinst ? $objinst->get_counter() : 0,
                            'count_needed' => $objective->get_count_needed(),
                            'supporting_url' => $url ? $url->out(false) : null,
                        ];
                    }, $objectives)),
                ],
            ];
        }

        $plaincontent = $this->remove_storyline_placeholders($text);
        $hasaudio = $this->has_speech() && !empty(trim($plaincontent));
        return $data + [
            'type' => 'message',
            'message' => [
                'content' => $this->apply_storyline_placeholders($this->related['user'], $text),
                'author' => 'narrator',
                'audio' => $hasaudio ? moodle_url::make_pluginfile_url(
                    $this->mission->get_context()->id,
                    'block_gearup',
                    'speech',
                    $this->mission->get_id(),
                    '/' . implode('/', [$section, $this->mission->get_time_modified()]) . '/',
                    $idx . '.mp3'
                )->out(false) : null,
            ],
        ];
    }

    /**
     * Convert a storyline to messages.
     *
     * @param string|null $text
     * @param array
     */
    protected function convert_storyline_to_messages(?string $text) {
        // This method should be predictable, and always return the same number of messages.
        return array_values(array_filter(preg_split("/[\r\n]+/m", $text ?? ''), function ($t) {
            return !empty(trim($t));
        }));
    }

    protected function get_objectives(): array {
        if ($this->objectives === null) {
            $this->objectives = array_values($this->mission->get_objectives());
        }
        return $this->objectives;
    }

    protected function get_objective_instances(): array {
        if ($this->objinsts === null) {
            $this->objinsts = [];
        }
        return $this->objinsts;
    }

    protected function get_rewards(): array {
        if ($this->rewards === null) {
            $alloutcomes = array_values(di::get('repository')->get_outcomes($this->mission->get_id()));
            $this->rewards = array_values(array_filter($alloutcomes, function ($outcome) {
                return $outcome->get_type() instanceof user_facing_type;
            }));
            unset($alloutcomes);
        }
        return $this->rewards;
    }

    protected function has_completed(): bool {
        return $this->related['state']->hascompleted;
    }

    protected function is_ended(): bool {
        return $this->related['state']->isended;
    }

    protected function has_speech(): bool {
        return (bool) $this->mission->get_voice_id() && di::get('lm')->use_speech();
    }

    protected function has_started(): bool {
        return $this->related['state']->hasstarted;
    }

    protected function needs_attention(): bool {
        return $this->related['state']->needsattention;
    }

}
