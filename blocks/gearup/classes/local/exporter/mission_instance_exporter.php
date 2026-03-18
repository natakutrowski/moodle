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
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\exporter;

use block_gearup\di;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\utils\human_utils;
use block_gearup\local\utils\time_utils;
use core_user\fields;
use DateInterval;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_instance_exporter extends \core\external\exporter {

    /** @var mission_instance The instance. */
    protected $missioninst;

    /**
     * Constructor.
     *
     * @param mission_instance $missioninst The mission instance.
     * @param array $related The related objects.
     */
    public function __construct(mission_instance $missioninst, $related = []) {
        $this->missioninst = $missioninst;
        parent::__construct([], $related);
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'access_permissions_factory' => '\block_gearup\local\factory\access_permissions_factory',
            'exporter_factory' => 'block_gearup\local\factory\exporter_factory',
            'mission_helper' => 'block_gearup\local\mission\helper',
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
            'id' => [
                'type' => PARAM_INT,
            ],
            'mission' => [
                'type' => mission_exporter::read_properties_definition(),
            ],

            'subject' => [
                'type' => user_exporter::read_properties_definition(),
            ],

            'storylineexcerpt' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            'description' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'descriptionhtml' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'instructions' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'instructionshtml' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'feedback' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'feedbackhtml' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            'chat' => [
                'type' => mission_chat_exporter::read_properties_definition(),
                'optional' => true,
            ],

            // Deprecated in favour of chat.
            'dialogue' => [
                'type' => [
                    'readmessages' => [
                        'type' => [
                            'contenttype' => ['type' => PARAM_ALPHANUMEXT],
                            'content' => ['type' => PARAM_RAW, 'null' => NULL_ALLOWED],
                            'ismessage' => ['type' => PARAM_BOOL],
                            'isobjectives' => ['type' => PARAM_BOOL],
                            'isrewards' => ['type' => PARAM_BOOL],
                        ],
                        'multiple' => true,
                        'default' => [],
                    ],
                    'hasreadmessages' => [
                        'type' => PARAM_BOOL,
                    ],
                    'unreadmessages' => [
                        'type' => [
                            'contenttype' => ['type' => PARAM_ALPHANUMEXT],
                            'content' => ['type' => PARAM_RAW, 'null' => NULL_ALLOWED],
                            'ismessage' => ['type' => PARAM_BOOL],
                            'isobjectives' => ['type' => PARAM_BOOL],
                            'isrewards' => ['type' => PARAM_BOOL],
                        ],
                        'multiple' => true,
                        'default' => [],
                    ],
                    'hasunreadmessages' => [
                        'type' => PARAM_BOOL,
                    ],
                ],
            ],

            'hasstarted' => [
                'type' => PARAM_BOOL,
            ],
            'hascompleted' => [
                'type' => PARAM_BOOL,
            ],

            'isassigned' => [
                'type' => PARAM_BOOL,
            ],
            'isstarted' => [
                'type' => PARAM_BOOL,
            ],
            'iscompleted' => [
                'type' => PARAM_BOOL,
            ],
            'isended' => [
                'type' => PARAM_BOOL,
            ],
            'isincomplete' => [
                'type' => PARAM_BOOL,
            ],

            'counter' => [
                'type' => PARAM_INT,
            ],

            'counterunit' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
            ],

            'deadline' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'deadlinehtml' => [
                'type' => PARAM_RAW,
            ],
            'deadlinelefthtml' => [
                'type' => PARAM_RAW,
            ],
            'hasdeadline' => [
                'type' => PARAM_BOOL,
            ],

            'nextstartrelativeref' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            'needsattention' => [
                'type' => PARAM_BOOL,
            ],

            'timeassignedhtml' => [
                'type' => PARAM_RAW,
            ],
            'timestartedhtml' => [
                'null' => true,
                'type' => PARAM_RAW,
            ],
            'timecompletedhtml' => [
                'null' => true,
                'type' => PARAM_RAW,
            ],
            'timeendedhtml' => [
                'null' => true,
                'type' => PARAM_RAW,
            ],
            'timelosthtml' => [
                'null' => true,
                'type' => PARAM_RAW,
            ],

            'completionratiopc' => [
                'type' => PARAM_INT,
            ],
            'iscompletionrationonzero' => [
                'type' => PARAM_BOOL,
            ],
            'incompletionratiopc' => [
                'type' => PARAM_INT,
            ],
            'isincompletionrationonzero' => [
                'type' => PARAM_BOOL,
            ],

            'objinsts' => [
                'type' => objective_instance_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasobjinsts' => [
                'type' => PARAM_BOOL,
            ],

            'manageurl' => [
                'type' => PARAM_URL,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param \block_gearup\output\renderer $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $PAGE, $USER;

        $missioninst = $this->missioninst;
        $mission = $missioninst->get_mission();
        $mh = $this->related['mission_helper'];
        $accessperms = $this->related['access_permissions_factory']->get_permissions_for_context($mission->get_context());
        $canmanage = $accessperms->can_manage($USER->id);

        $manageurl = null;
        if ($canmanage) {
            $urlresolver = $this->related['url_resolver'] ?? di::get('url_resolver');
            $manageurl = $urlresolver->reverse('mission_instance', [
                'missionid' => $mission->get_id(),
                'missioninstid' => $missioninst->get_id(),
            ])->out(false);
        }

        $missionexporter = $this->related['exporter_factory']->get_mission_exporter($mission, $this->related + [
            'mission_instance' => $missioninst,
        ]);
        $objinsts = $missioninst->get_objective_instances();

        // TODO Do not sort these here.
        usort($objinsts, function ($obj1, $obj2) {
            return $obj1->get_objective()->get_id() - $obj2->get_objective()->get_id();
        });

        $completionratio = $missioninst->get_completion_ratio();

        // TODO We could improve how we resolve the user here.
        $user = $USER;
        if ($USER->id != $missioninst->get_subject_id()) {
            $namefields = 'id' . fields::for_name()
                ->with_userpic()
                ->with_identity($mission->get_context(), false)
                ->get_sql()->selects;
            $user = \core_user::get_user($missioninst->get_subject_id(), $namefields, MUST_EXIST);
        }

        $hasstarted = $mh->has_started($missioninst);
        $hascompleted = $mh->has_completed($missioninst);
        $deadline = $missioninst->get_deadline();

        $counter = $missioninst->get_counter();
        $counterunit = null;
        if ($mh->is_a_streak($missioninst)) {
            if ($mission->get_time_limit() < WEEKSECS) {
                $counterunit = get_string($counter === 1 ? 'day' : 'days', 'core');
            } else if ($mission->get_time_limit() === WEEKSECS) {
                $counterunit = get_string($counter === 1 ? 'week' : 'weeks', 'core');
            } else if ($mission->get_time_limit() === 30 * DAYSECS) {
                $counterunit = get_string($counter === 1 ? 'month' : 'months', 'core');
            } else {
                $counterunit = get_string($counter === 1 ? 'streakunit' : 'streakunits', 'block_gearup');
            }
        }

        $timelosthtml = null;
        if ($mh->is_a_streak($missioninst) && $mh->is_ended($missioninst) && $missioninst->get_deadline()) {
            $timelostts = min($missioninst->get_time_ended()->getTimestamp(), $missioninst->get_deadline()->getTimestamp());
            $timelost = new \DateTimeImmutable('@' . $timelostts);
            $timelosthtml = $this->html_date($timelost);
        }

        $nextstartrelativeref = null;
        if ($mh->is_a_streak($missioninst) && $mh->is_repeating($missioninst) && $missioninst->get_deadline()) {
            if ($mission->get_time_limit() === DAYSECS * 30) {
                $nextstartrelativeref = get_string('nextmonth', 'block_gearup');
            } else if ($mission->get_time_limit() === WEEKSECS * 2) {
                $weekn = di::get('clock')->now()->format('W');
                $nextperiodweekn = $missioninst->get_deadline()->format('W') + 1; // The deadline is always set just before.
                $diff = ($nextperiodweekn < $weekn ? (52 + $nextperiodweekn - $weekn) : $nextperiodweekn - $weekn);
                if ($diff <= 1) {
                    $nextstartrelativeref = get_string('nextweek', 'block_gearup');
                } else {
                    $nextstartrelativeref = get_string('intwoweeks', 'block_gearup');
                }
            } else if ($mission->get_time_limit() === WEEKSECS) {
                $nextstartrelativeref = get_string('nextweek', 'block_gearup');
            } else if ($mission->get_time_limit() === time_utils::DAILY_WEEKDAY && $missioninst->get_deadline()->format('N') >= 5) {
                $nextstartrelativeref = get_string('monday', 'core_calendar');
            } else if ($mission->get_time_limit() < WEEKSECS) {
                $nextstartrelativeref = get_string('tomorrow', 'calendar');
            }
        }

        $data = [
            'id' => $missioninst->get_id(),
            'mission' => $missionexporter->export($output),
            'subject' => (new user_exporter($user, ['context' => $mission->get_context()]))->export($output),

            'hasstarted' => $hasstarted,
            'hascompleted' => $hascompleted,
            'isassigned' => $mh->is_assigned($missioninst),
            'isstarted' => $mh->is_started($missioninst),
            'iscompleted' => $mh->is_completed($missioninst),
            'isended' => $mh->is_ended($missioninst),
            'isincomplete' => $mh->is_incomplete($missioninst),

            'counter' => $counter,
            'counterunit' => $counterunit,

            'deadline' => $deadline ? $deadline->getTimestamp() : null,
            'deadlinehtml' => $deadline ? $this->html_date($deadline) : '',
            'deadlinelefthtml' => $deadline ? $this->html_time_left($deadline) : '',
            'hasdeadline' => $deadline !== null,

            'nextstartrelativeref' => $nextstartrelativeref,

            'needsattention' => $missioninst->needs_attention(),

            'timeassignedhtml' => $this->html_date($missioninst->get_time_assigned()),
            'timestartedhtml' => $mh->has_started($missioninst) ? $this->html_date($missioninst->get_time_started()) : null,
            'timecompletedhtml' => $mh->has_completed($missioninst) ? $this->html_date($missioninst->get_time_completed()) : null,
            'timeendedhtml' => $mh->is_ended($missioninst) ? $this->html_date($missioninst->get_time_ended()) : null,
            'timelosthtml' => $timelosthtml,

            'completionratiopc' => human_utils::percentage($completionratio),
            'iscompletionrationonzero' => $completionratio > 0,
            'incompletionratiopc' => human_utils::percentage(1 - $completionratio),
            'isincompletionrationonzero' => $completionratio < 1,

            'objinsts' => array_map(function ($objinst) use ($mission, $output) {
                $oie = new objective_instance_exporter($objinst, [
                    'context' => $mission->get_context(),
                    'mission' => $mission,
                ]);
                return $oie->export($output);
            }, array_values($objinsts)),
            'hasobjinsts' => !empty($objinsts),

            'manageurl' => $manageurl,
        ];

        $data += $this->get_storyline_values($user);
        if ($mh->is_a_quest($mission)) {
            $data['chat'] = (new mission_chat_exporter($mission, [
                'mission_instance' => $missioninst,
                'mission_helper' => $mh,
                'user' => $user,
                'context' => $mission->get_context(),
            ]))->export($output);
        }

        return $data;
    }

    /**
     * Get the story line values.
     *
     * @param object $user The recruit.
     * @return array
     */
    protected function get_storyline_values($user) {
        $mh = $this->related['mission_helper'];

        $missioninst = $this->missioninst;
        $mission = $missioninst->get_mission();

        $hasstarted = $mh->has_started($missioninst);
        $hascompleted = $mh->has_completed($missioninst);

        $description = $this->apply_storyline_placeholders($user, $mission->get_description());
        $instructions = $this->apply_storyline_placeholders($user, $hasstarted ? $mission->get_instructions() : null);
        $feedback = $this->apply_storyline_placeholders($user, $hascompleted ? $mission->get_feedback() : null);
        $tomakeexcerptof = $feedback ?? $instructions ?? $description ?? '';
        $storylineexcerpt = shorten_text(str_replace(['[objectives]', '[rewards]'], '', $tomakeexcerptof), 120, false, '…');

        $readmessages = [];
        $unreadmessages = [];

        return [
            'description' => $description,
            'descriptionhtml' => $this->format_storyline($description),
            'dialogue' => [
                'readmessages' => $readmessages,
                'hasreadmessages' => !empty($readmessages),
                'unreadmessages' => $unreadmessages,
                'hasunreadmessages' => !empty($unreadmessages),
            ],
            'feedback' => $feedback,
            'feedbackhtml' => $this->format_storyline($feedback),
            'instructions' => $instructions,
            'instructionshtml' => $this->format_storyline($instructions),
            'storylineexcerpt' => $storylineexcerpt,
        ];
    }

    protected function convert_storyline_message_to_contenttype($message, bool $isunread) {
        if ($message === '[objectives]') {
            return ['contenttype' => 'objectives', 'isunread' => $isunread];
        } else if ($message === '[rewards]') {
            return ['contenttype' => 'rewards', 'isunread' => $isunread];
        }
        return ['contenttype' => 'message', 'content' => $message, 'isunread' => $isunread];
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
     * Convert a storyline to messages.
     *
     * @param string|null $text
     * @param array
     */
    protected function convert_storyline_to_messages(?string $text) {
        return array_values(array_filter(preg_split("/[\r\n]+/m", $text ?? ''), function ($t) {
            return !empty(trim($t));
        }));
    }

    /**
     * Apply storyline placeholders.
     *
     * @param \stdClass $user The user.
     * @param string|null $text The text.
     */
    protected function format_storyline(?string $text) {
        if (empty($text)) {
            return $text;
        }
        return '<p>' . implode('</p><p>', array_map('s', array_filter(preg_split("/[\r\n]+/m", $text), function ($t) {
            return !empty(trim($t)) && $t !== '[objectives]' && $t !== '[rewards]';
        }))) . '</p>';
    }

    /**
     * Shortcut to userdate_htmltime.
     *
     * @param \DateTimeImmutable $d The date.
     */
    protected function html_date(\DateTimeImmutable $d) {
        return userdate_htmltime($d->getTimestamp());
    }

    /**
     * Time left.
     *
     * @param \DateTimeImmutable $d The date.
     */
    protected function html_time_left(\DateTimeImmutable $d) {
        $secsleft = $d->getTimestamp() - time();
        if ($secsleft <= 0) {
            return get_string('ended', 'block_gearup');
        } else if ($secsleft <= DAYSECS * 2) {
            $hours = floor($secsleft / HOURSECS);
            $mins = floor(($secsleft - $hours * HOURSECS) / MINSECS);
            $secs = $secsleft - $hours * HOURSECS - $mins * MINSECS;
            return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }
        $days = floor($secsleft / DAYSECS);
        return get_string('numdays', 'core', $days);
    }

}
