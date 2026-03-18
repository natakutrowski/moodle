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
 * Renderer.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\output;

use action_menu;
use block_gearup\di;
use block_gearup\local\availability\static_info;
use block_gearup\local\exporter\tracker\achievements_tracker_exporter;
use block_gearup\local\exporter\tracker\challenges_tracker_exporter;
use block_gearup\local\exporter\tracker\quests_tracker_exporter;
use block_gearup\local\exporter\visual_exporter;
use block_gearup\local\mission\mission;
use block_gearup\local\permission\access_permissions;
use block_gearup\local\routing\url_resolver;
use block_gearup\local\utils\human_utils;
use block_gearup\local\visual\visual;
use html_writer;
use moodle_url;
use plugin_renderer_base;
use single_button;
use user_picture;

/**
 * Renderer.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /** @var object */
    protected $lm;

    /**
     * Advanced heading.
     *
     * @param string|null $heading The heading.
     * @param array $options The options.
     */
    public function advanced_heading($heading, $options = []) {
        $options = array_merge(['level' => 3, 'actions' => [], 'intro' => null, 'help' => null, 'visible' => null,
            'menu' => []], $options);
        $level = (int) $options['level'];
        $actions = (array) $options['actions'];
        $menu = new menu((array) $options['menu']);
        $intro = !empty($options['intro']) ? $options['intro'] : null;
        $help = $options['help'] instanceof \help_icon ? $options['help'] : null;

        return $this->render_from_template('block_gearup/advanced-heading', [
            'title' => $heading,
            'level' => $level,
            'islevel2' => $level === 2,

            'hasintro' => !empty($intro),
            'intro' => $intro,
            'helpicon' => $help ? $help->export_for_template($this) : null,

            'hasactions' => !empty($actions),
            'actions' => array_map([$this, 'render'], $actions),
        ] + $menu->export_for_template($this));
    }

    public function checkbox_group(array $checkboxes, $name, array $values) {
        return $this->render_from_template('block_gearup/checkbox_group', [
            'checkboxes' => array_map(function ($checkbox) use ($values, $name) {
                $checkbox = (object) $checkbox;
                $availability = $checkbox->availability ?? new static_info(true);
                return [
                    'name' => $name . '[' . $checkbox->value . ']',
                    'value' => $availability->is_available() ? '1' : '',
                    'label' => $checkbox->label,
                    'description' => $checkbox->description ?? '',
                    'isselected' => !empty($values) && in_array($checkbox->value, $values),
                    'isavailable' => $availability->is_available(),
                    'unavailablereasons' => $availability->get_reasons(),
                ];
            }, $checkboxes),
        ]);
    }

    public function code_instructions($html) {
        return html_writer::div($html, '[&_pre]:gu-p-2 [&_pre]:gu-bg-gray-100');
    }

    /**
     * Confirm step.
     *
     * This supercedes the default confirm renderer to accomodate for variation between versions,
     * and to set our own sensible defaults. For instance, the confirm button is red by default.
     *
     * Note that the parameters are not the same as the {@see \core_renderer::confirm}.
     *
     * @param string $title The title.
     * @param string $message The message.
     * @param \moodle_url $confirmurl The confirm URL.
     * @param \moodle_url $cancelurl The cancel URL.
     * @param array $options Some options.
     * @return string
     */
    public function confirm_step($title, $message, \moodle_url $confirmurl, \moodle_url $cancelurl, $options = []) {
        global $CFG;
        if ($CFG->branch < 400) {
            return parent::confirm($message, $confirmurl, $cancelurl);
        }
        return parent::confirm($message, $confirmurl, $cancelurl, [
            'confirmtitle' => $title,
            'continuestr' => $options['confirmlabel'] ?? null,
            'cancelstr' => $options['cancellabel'] ?? null,
            'type' => defined('single_button::BUTTON_DANGER') ? single_button::BUTTON_DANGER : null,
        ]);
    }

    /**
     * Render a control menu.
     *
     * @param action_menu_link $actions
     */
    public function control_menu($actions) {
        $menu = new action_menu();

        // Without this, the control menu can wrap on the next line when placed next to another item.
        $menu->attributes['class'] .= ' gu-inline-block';

        // Styles copied from core_courseformat\output\local\content\cm::get_action_menu() in 4.1dev.
        // From Moodle 4.2 we should be using `set_kebab_trigger`.
        $icon = $this->pix_icon('i/menu', get_string('menu', 'core'));
        $menu->set_menu_trigger($icon, 'btn btn-icon d-flex align-items-center justify-content-center after:gu-hidden');

        foreach ($actions as $action) {
            $action->primary = false;
            $menu->add_secondary_action($action);
        }

        return $this->render($menu);
    }

    /**
     * Get a user's picture.
     *
     * @param object $user The user.
     * @return moodle_url The URL to the picture.
     */
    public function get_user_picture($user): \moodle_url {
        $pic = new user_picture($user);
        $pic->size = 1;
        return $pic->get_url($this->page);
    }

    public function image_group($sections, $name, $value) {

        // Handle compatibility with previously accepted format of just options.
        $firstoption = reset($sections);
        if ($firstoption instanceof visual || is_array($firstoption)) {
            $sections = [
                (object) [
                    'label' => null,
                    'options' => $sections,
                ],
            ];
        }

        // Remove empty sections. In the future we may want to display them empty.
        $sections = array_filter($sections, function ($section) {
            return !empty($section->options);
        });

        // Count how many images in total.
        $nimages = array_sum(array_map(function ($section) {
            return count($section->options);
        }, $sections));

        $fn = function ($image) use ($value) {
            if ($image instanceof visual) {
                $data = [
                    'value' => $image->get_id(),
                    'src' => $image->get_url()->out(false),
                    'label' => $image->get_alt(),
                ];
            } else {
                $data = [
                    'value' => $image->value,
                    'src' => $image->src,
                    'label' => $image->label ?? '',
                ];
            }
            $data['isselected'] = $value !== null && $value !== '' && $value == $data['value'];
            return $data;
        };

        return $this->render_from_template('block_gearup/image_group', [
            'hasfew' => $nimages <= 12,
            'hasmany' => $nimages > 12,
            'isscrollable' => true,
            'name' => $name,
            'showsectionslabel' => count($sections) > 1,
            'sections' => array_map(function ($section) use ($fn) {
                if (empty($section->options)) {
                    return [];
                }
                return [
                    'label' => $section->label,
                    'images' => array_map($fn, $section->options),
                ];
            }, $sections),
        ]);
    }

    /**
     * Output a JSON script.
     *
     * @param mixed $data The data.
     * @param string $id The HTML ID to use.
     * @return string
     */
    public function json_script($data, $id) {
        $jsondata = json_encode($data, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        return html_writer::tag('script', $jsondata, ['id' => $id, 'type' => 'application/json']);
    }

    public function mission_header(url_resolver $urlresolver, mission $mission) {
        $mh = di::get('mission_helper');
        $visual = $mission->get_visual();

        $type = null;
        $listurl = $urlresolver->reverse('missions');
        if ($mh->is_a_quest($mission)) {
            $type = get_string('quest', 'block_gearup');
        } else if ($mh->is_an_achievement($mission)) {
            $type = get_string('achievement', 'block_gearup');
        } else if ($mh->is_a_challenge($mission)) {
            $type = get_string('challenge', 'block_gearup');
        } else if ($mh->is_a_streak($mission)) {
            $type = get_string('streak', 'block_gearup');
            $listurl = $urlresolver->reverse('streaks');
        }

        return $this->render_from_template('block_gearup/mission_header', [
            'listurl' => $listurl->out(false),
            'name' => $mission->get_title(),
            'visual' => $visual ? (new visual_exporter($visual))->export($this) : null,
            'isarchived' => $mh->is_archived($mission),
            'isachievement' => $mh->is_an_achievement($mission),
            'ischallenge' => $mh->is_a_challenge($mission),
            'isquest' => $mh->is_a_quest($mission),
            'isstreak' => $mh->is_a_streak($mission),
            'type' => $type,
        ]);
    }

    public function navigation_for_management(url_resolver $urlresolver, $currentpage = null) {
        $tabs = array_map(
            function ($link) {
                return new \tabobject($link['id'], $link['url'], $link['text'], clean_param($link['text'], PARAM_NOTAGS));
            },
            array_filter([
                [
                    'id' => 'missions',
                    'url' => $urlresolver->reverse('missions'),
                    'text' => get_string('missions', 'block_gearup'),
                ],
                [
                    'id' => 'streaks',
                    'url' => $urlresolver->reverse('streaks'),
                    'text' => get_string('streaks', 'block_gearup'),
                ],
                [
                    'id' => 'insights',
                    'url' => $urlresolver->reverse('insights'),
                    'text' => get_string('navinsights', 'block_gearup'),
                    'has' => $this->lm->use_insights(),
                ],
                [
                    'id' => 'users',
                    'url' => $urlresolver->reverse('users'),
                    'text' => get_string('navrecruits', 'block_gearup'),
                ],
                [
                    'id' => 'library',
                    'url' => $urlresolver->reverse('library'),
                    'text' => get_string('library', 'block_gearup'),
                ],
            ], function ($item) {
                return !array_key_exists('has', $item) || !empty($item['has']);
            })
        );
        return html_writer::div($this->tabtree($tabs, $currentpage));
    }

    public function navigation_for_mission_management(url_resolver $urlresolver, mission $mission, $currentpage = null) {
        $tabs = array_map(
            function ($link) {
                return new \tabobject($link['id'], $link['url'], $link['text'], clean_param($link['text'], PARAM_NOTAGS));
            },
            array_filter([
                [
                    'id' => 'mission',
                    'url' => $urlresolver->reverse('mission', ['missionid' => $mission->get_id()]),
                    'text' => get_string('navoverview', 'block_gearup'),
                ],
                [
                    'id' => 'mission_insights',
                    'url' => $urlresolver->reverse('mission_insights', ['missionid' => $mission->get_id()]),
                    'text' => get_string('navinsights', 'block_gearup'),
                    'has' => $this->lm->use_insights(),
                ],
                [
                    'id' => 'mission_users',
                    'url' => $urlresolver->reverse('mission_users', ['missionid' => $mission->get_id()]),
                    'text' => get_string('navrecruits', 'block_gearup'),
                ],
                [
                    'id' => 'mission_assign',
                    'url' => $urlresolver->reverse('mission_assign', ['missionid' => $mission->get_id()]),
                    'text' => get_string('navassign', 'block_gearup'),
                ],
                [
                    'id' => 'mission_advanced',
                    'url' => $urlresolver->reverse('mission_advanced', ['missionid' => $mission->get_id()]),
                    'text' => get_string('navadvanced', 'block_gearup'),
                ],
            ], function ($item) {
                return !array_key_exists('has', $item) || !empty($item['has']);
            })
        );
        return html_writer::div($this->tabtree($tabs, $currentpage), 'gu-my-4');
    }

    public function navigation_on_block(url_resolver $urlresolver, access_permissions $perms) {
        $achievementsurl = $urlresolver->reverse('my_achievements');
        $achievementsurl->param('returnurl', $this->page->url->out_as_local_url(false));
        $questsurl = $urlresolver->reverse('my_quests');
        $questsurl->param('returnurl', $this->page->url->out_as_local_url(false));
        return $this->render_from_template('block_gearup/block_nav', [
            'achievementsurl' => $achievementsurl->out(false),
            'questsurl' => $questsurl->out(false),
            'canaccess' => $perms->can_access(),
            'canmanage' => $perms->can_manage(),
            'manageurl' => $urlresolver->reverse('missions')->out(false),
        ]);
    }

    public function radio_group($radios, $name, $value) {
        return $this->render_from_template('block_gearup/radio_group', [
            'name' => $name,
            'radios' => array_map(function ($radio) use ($value) {
                $radio = (object) $radio;
                $availability = $radio->availability ?? new static_info(true);
                return [
                    'value' => $availability->is_available() ? $radio->value : '',
                    'label' => $radio->label,
                    'description' => $radio->description ?? '',
                    'isselected' => $value !== null && $value !== '' && $value == $radio->value,
                    'isavailable' => $availability->is_available(),
                    'unavailablereasons' => $availability->get_reasons(),
                ];
            }, $radios),
        ]);
    }

    public function output_achievement_instances_overview(url_resolver $urlresolver, \context $context) {
        global $USER;
        return $this->render_from_template('block_gearup/achievement_instances_overview',
            (new achievements_tracker_exporter($USER->id, [
                'context' => $context,
                'pageurl' => $this->page->url,
                'url_resolver' => $urlresolver,
            ]))->export($this)
        );
    }

    public function output_challenge_instances_overview(url_resolver $urlresolver, \context $context) {
        global $USER;
        return $this->render_from_template('block_gearup/challenge_instances_overview',
            (new challenges_tracker_exporter($USER->id, [
                'context' => $context,
                'pageurl' => $this->page->url,
                'url_resolver' => $urlresolver,
            ]))->export($this)
        );
    }

    public function output_quest_instances_overview(url_resolver $urlresolver, \context $context) {
        global $USER;
        return $this->render_from_template('block_gearup/quest_instances_overview',
            (new quests_tracker_exporter($USER->id, [
                'context' => $context,
                'pageurl' => $this->page->url,
                'url_resolver' => $urlresolver,
            ]))->export($this)
        );
    }

    public function output_mission_instance($missioninst, \moodle_url $baseurl, url_resolver $urlresolver, $returnto = null) {
        $exporter = di::get('exporter_factory')->get_mission_instance_exporter($missioninst);
        $starturl = new moodle_url($baseurl, ['action' => 'startmission', 'sesskey' => sesskey()]);
        $completeurl = new moodle_url($baseurl, ['action' => 'compmission', 'sesskey' => sesskey()]);
        $finishurl = new moodle_url($baseurl, ['action' => 'finishmission', 'sesskey' => sesskey()]);

        $listurl = $urlresolver->reverse('mission_users', ['missionid' => $missioninst->get_mission()->get_id()]);
        $listlabel = get_string('viewotherrecruits', 'block_gearup');
        if ($returnto === 'user') {
            $listlabel = get_string('back', 'core');
            $listurl = $urlresolver->reverse('mission_user', ['missionid' => $missioninst->get_mission()->get_id(),
                'userid' => $missioninst->get_subject_id()]);
        }

        /** @var array */
        $data = array_merge(
            [
                'listurl' => $listurl->out(false),
                'listlabel' => $listlabel,
                'startmissionurl' => $starturl->out(false),
                'finishmissionurl' => $finishurl->out(false),
                'completemissionurl' => $completeurl->out(false),
            ],
            (array) $exporter->export($this),
        );

        $data['objinsts'] = array_map(function ($objinst) use ($baseurl) {
            $objid = $objinst->objective->id;
            $incrurl = new moodle_url($baseurl, ['action' => 'incrobj', 'objid' => $objid, 'sesskey' => sesskey()]);
            $reseturl = new moodle_url($baseurl, ['action' => 'resetobj', 'objid' => $objid, 'sesskey' => sesskey()]);
            return array_merge((array) $objinst, [
                    'incrementobjectiveurl' => $incrurl->out(false),
                    'resetobjectiveurl' => $reseturl->out(false),
            ]);
        }, $data['objinsts']);

        $missionhelper = di::get('mission_helper');
        $canreset = !($missionhelper->is_repeating($missioninst) && $missionhelper->is_ended($missioninst));

        $menu = new menu(array_filter([
            $canreset ? [
                'label' => get_string('reset', 'core'),
                'href' => '#',
                'danger' => true,
                'data-gu-action' => 'open-form',
                'data-form-class' => 'block_gearup\form\reset_mission_instance_dynamic_form',
                'data-form-args__missionid' => $missioninst->get_mission()->get_id(),
                'data-form-args__missioninstid' => $missioninst->get_id(),
                'data-modal-buttons__save__danger' => "true",
                'data-modal-buttons__save__label' => get_string('reset', 'core'),
                'data-modal-title' => $data['subject']->name,
                'data-modal-large' => 'false',
            ] : null,
            [
                'label' => get_string('delete', 'core'),
                'href' => '#',
                'danger' => true,
                'data-gu-action' => 'open-form',
                'data-form-class' => 'block_gearup\form\delete_mission_instance_dynamic_form',
                'data-form-args__missionid' => $missioninst->get_mission()->get_id(),
                'data-form-args__missioninstid' => $missioninst->get_id(),
                'data-form-args__redirecturl' => $listurl->out_as_local_url(false),
                'data-modal-buttons__save__danger' => "true",
                'data-modal-buttons__save__label' => get_string('delete', 'core'),
                'data-modal-title' => $data['subject']->name,
                'data-modal-large' => 'false',
            ],
        ]));

        return $this->render_from_template('block_gearup/mission_instance', $data + $menu->export_for_template($this));
    }

    /**
     * Profile achievement list.
     *
     * @param object[] $missioninsts Instances.
     * @return string
     */
    public function profile_achievement_list($missioninsts) {
        $exporterfactory = di::get('exporter_factory');
        $data = [
            'missioninsts' => array_values(array_map(function ($missioninst) use ($exporterfactory) {
                return $exporterfactory->get_mission_instance_exporter($missioninst)->export($this);
            }, $missioninsts)),
        ];

        return $this->render_from_template('block_gearup/achievements/profile_list', $data);
    }

    /**
     * Render a single button primary.
     *
     * @param \moodle_url $url The URL.
     * @param string $label The button label.
     */
    public function single_button_primary($url, $label) {
        global $CFG;
        $button = new single_button($url, $label, 'get');
        if ($CFG->branch >= 402) {
            $button->type = single_button::BUTTON_PRIMARY;
        } else {
            $button->primary = true;
        }
        return $this->render($button);
    }

    /**
     * Initialise a react module.
     *
     * @param string $module The AMD name of the module.
     * @param object|array $props The props.
     * @return void
     */
    public function react_module($module, $props, $classname = '') {
        global $CFG;

        $id = html_writer::random_id('block_gearup-react-app');
        $propsid = html_writer::random_id('block_gearup-react-app-props');
        $iconname = $CFG->branch >= 32 ? 'y/loading' : 'i/loading';

        $o = '';
        $o .= html_writer::start_div('block_gearup block_gearup-react', ['id' => $id, 'class' => $classname]);
        $o .= html_writer::start_div('block_gearup-react-loading');
        $o .= html_writer::start_div('gu-grid gu-grid-cols-2 gu-gap-4 gu-animate-pulse');
        $o .= html_writeR::div('', 'gu-col-span-2 gu-bg-gray-100 gu-rounded gu-h-4');
        $o .= html_writeR::div('', 'gu-bg-gray-100 gu-rounded gu-h-4');
        $o .= html_writeR::div('', 'gu-bg-gray-100 gu-rounded gu-h-4');
        $o .= html_writer::end_div();
        $o .= html_writer::end_div();
        $o .= html_writer::end_div();

        $o .= $this->json_script($props, $propsid);

        $this->page->requires->js_amd_inline("
            require(['block_gearup/react-launcher'], function(ReactLauncher) {
                ReactLauncher.launch('$module', '$id', '$propsid');
            });
        ");

        return $o;
    }

    /**
     * Initialise a react module in a modal.
     *
     * @param string $module The AMD name of the module.
     * @param object|array $props The props.
     * @param string $selector The selector to open the modal.
     * @param object $modalconfig The modal config.
     * @return void
     */
    public function react_module_in_modal($module, $props, $selector, $modalconfig = null) {
        if (empty($modalconfig)) {
            $modalconfig = (object) [];
        }
        $modalconfigjson = json_encode($modalconfig, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        $propsid = html_writer::random_id('block_gearup-react-app-props');
        $o = $this->json_script($props, $propsid);

        // When needed, add support for modalConfig (3rd arg of launchInModal).
        $this->page->requires->js_amd_inline("
            require(['block_gearup/role_button', 'block_gearup/react-launcher'], function(RoleButton, ReactLauncher) {
                RoleButton.registerClick('$selector', function() {
                    ReactLauncher.launchInModal('$module', '$propsid', $modalconfigjson);
                });
            });
        ");

        return $o;
    }

    public function wizard_steps($steps, $currentstep) {
        $currentidx = array_search($currentstep, array_map(function ($step) {
            return $step['id'];
        }, $steps));
        return $this->render_from_template('block_gearup/wizard_steps', [
            'steps' => array_map(function ($step, $idx) use ($currentstep, $currentidx) {
                return array_merge($step, [
                    'url' => !empty($step['url']) ? $step['url']->out(false) : null,
                    'iscomplete' => $idx < $currentidx,
                    'iscurrent' => $step['id'] === $currentstep,
                    'isupcoming' => $idx > $currentidx,
                ]);
            }, $steps, array_keys($steps)),
        ]);
    }

    public function serialize_context(?\context $context = null) {
        return [
            'anywhere' => !$context,
            'iscourse' => $context instanceof \context_course,
            'issystem' => $context instanceof \context_system,
        ];
    }

    public function serialize_mission_instance(url_resolver $urlresolver, $missioninst) {
        $ef = di::get('exporter_factory'); // TODO Do not initialise here.
        $missionhelper = di::get('mission_helper'); // TODO Do not initialise here.
        $ratio = $missioninst->get_completion_ratio();
        $ratiopc = human_utils::percentage($ratio);
        $hascompleted = $missionhelper->has_completed($missioninst);
        $iscompleted = $missionhelper->is_completed($missioninst);

        $data = array_merge([
            'id' => $missioninst->get_id(),
            'completionratiopc' => $ratiopc,
            'hascompleted' => $hascompleted,
            'iscompleted' => $iscompleted,
        ], (array) $ef->get_mission_instance_exporter($missioninst)->export($this));

        return $data;
    }

    /**
     * Render a zero state.
     *
     * @param string $title The title.
     * @param string $subtitle The subtitle.
     * @param string $icon The icon.
     */
    public function zero_state($title, $subtitle, $icon) {
        return $this->render_from_template('block_gearup/zero_state', [
            'title' => $title,
            'subtitle' => $subtitle,
            'icon' => $icon,
        ]);
    }

    /**
     * Set lm.
     *
     * @param object $lm The object.
     */
    public function set_lm($lm) {
        $this->lm = $lm;
    }

}
