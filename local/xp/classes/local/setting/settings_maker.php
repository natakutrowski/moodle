<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

/**
 * Settings maker.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\setting;

use admin_externalpage;
use admin_setting;
use admin_setting_confightmleditor;
use admin_setting_configmultiselect;
use admin_setting_configselect;
use admin_setting_configtext;
use admin_setting_heading;
use block_xp\di;
use block_xp\local\config\config;
use block_xp\local\config\course_world_config;
use block_xp\local\routing\url_resolver;
use block_xp\local\setting\environment;
use block_xp\local\setting\static_setting;
use html_writer;
use local_xp\local\config\default_course_world_config;
use local_xp\local\iomad\facade as iomadfacade;
use local_xp\local\model\oauth_client;
use local_xp\local\utils\agent_utils;
use part_of_admin_tree;

/**
 * Settings maker.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_maker extends \block_xp\local\setting\default_settings_maker {
    /** @var iomadfacade IOMAD. */
    protected $iomad;

    /**
     * Constructor.
     *
     * @param config $defaults The config object to get the defaults from.
     * @param url_resolver $urlresolver The URL resolver.
     * @param iomadfacade $iomad IOMAD.
     * @param config $configlocked The config object.
     */
    public function __construct(config $defaults, url_resolver $urlresolver, iomadfacade $iomad, config $configlocked) {
        parent::__construct($defaults, $urlresolver, $configlocked);
        $this->iomad = $iomad;
    }


    /**
     * Get the settings.
     *
     * @param environment $env The environment for creating the settings.
     * @return part_of_admin_tree|null
     */
    public function get_settings(environment $env) {
        $settings = parent::get_settings($env);
        $catname = $settings->name;

        $settingspage = new admin_externalpage(
            'block_xp_admin_mcp_tools',
            get_string('mcptools', 'block_xp'),
            $this->urlresolver->reverse('admin/mcptools')->get_compatible_url()
        );
        $settingspage->hidden = true;
        $settings->add($catname, $settingspage);

        $settingspage = new admin_externalpage(
            'block_xp_admin_oauth_clients',
            get_string('oauthclients', 'block_xp'),
            $this->urlresolver->reverse('admin/oauthclients')->get_compatible_url()
        );
        $settingspage->hidden = true;
        $settings->add($catname, $settingspage);

        return $settings;
    }

    /**
     * Get the general settings.
     *
     * @return admin_setting[]
     */
    protected function get_general_settings() {
        $parentsettings = parent::get_general_settings();
        $settings = [];

        foreach ($parentsettings as $setting) {
            // Replace keeplogs to contain other options.
            if ($setting->name == 'keeplogs') {
                $setting = (new admin_setting_configselect(
                    'block_xp/keeplogs',
                    get_string('keeplogs', 'block_xp'),
                    get_string('keeplogsdesc', 'block_xp'),
                    $this->defaults->get('keeplogs'),
                    [
                        '0' => get_string('forever', 'block_xp'),
                        '14' => get_string('numweeks', 'core', 2),
                        '30' => get_string('nummonth', 'core', 1),
                        '90' => get_string('nummonths', 'core', 3),
                        '180' => get_string('nummonths', 'core', 6),
                        '365' => get_string('numyear', 'core', 1),
                        '730' => get_string('numyears', 'core', 2),
                    ]
                ));
            }

            $settings[] = $setting;

            if ($setting->name == 'block_xp_context') {
                $settings[] = new admin_setting_configselect(
                    'local_xp/enableglobalworld',
                    get_string('enableglobalworld', 'block_xp'),
                    get_string('enableglobalworlddesc', 'block_xp'),
                    $this->defaults->get('enableglobalworld'),
                    [
                        '0' => get_string('no', 'core'),
                        '1' => get_string('yes', 'core'),
                    ]
                );
            }

            if ($setting->name == 'navbardisplay') {
                $settings[] = new admin_setting_configselect(
                    'local_xp/navbarshowoverall',
                    get_string('navbarshowoverall', 'block_xp'),
                    get_string('navbarshowoveralldesc', 'block_xp'),
                    $this->defaults->get('navbarshowoverall'),
                    [
                        '0' => get_string('no', 'core'),
                        '1' => get_string('yes', 'core'),
                    ]
                );
            }
        }

        foreach ($this->get_ai_settings() as $setting) {
            $settings[] = $setting;
        }

        return $settings;
    }

    /**
     * Get the default settings.
     *
     * @return admin_setting[]
     */
    protected function get_default_settings() {
        $parentsettings = parent::get_default_settings();
        $settings = [];
        $defaults = $this->defaults->get_all();

        // We loop over each setting to inject what more when we need.
        foreach ($parentsettings as $setting) {
            if ($setting->name == 'identitymode') {
                // Leaderboard isolation.
                if (di::get('addon')->supports_leaderboard_isolation()) {
                    $settings[] = (new admin_setting_configselect(
                        'local_xp/ladderiso',
                        get_string('ladderiso', 'block_xp'),
                        get_string('ladderiso_help', 'block_xp'),
                        $defaults['ladderiso'],
                        [
                            default_course_world_config::LEADERBOARD_ISO_DEFAULT => get_string('ladderisodefault', 'block_xp'),
                            default_course_world_config::LEADERBOARD_ISO_COHORTS => get_string('ladderisocohorts', 'block_xp'),
                        ]
                    ));
                }

                // Leaderboard participation.
                if (di::get('addon')->supports_leaderboard_participation()) {
                    $settings[] = new admin_setting_configselect(
                        'local_xp/ladderparticipation',
                        get_string('ladderparticipation', 'block_xp'),
                        get_string('ladderparticipation_help', 'block_xp'),
                        $defaults['ladderparticipation'],
                        [
                            default_course_world_config::LEADERBOARD_PARTICIPATION_FORCED =>
                                get_string('ladderparticipationforced', 'block_xp'),
                            default_course_world_config::LEADERBOARD_PARTICIPATION_OPTOUT =>
                                get_string('ladderparticipationoptout', 'block_xp'),
                            default_course_world_config::LEADERBOARD_PARTICIPATION_OPTIN =>
                                get_string('ladderparticipationoptin', 'block_xp'),
                        ]
                    );
                }

                // Replace the setting identity mode.
                $setting = new admin_setting_configselect(
                    $setting->plugin . '/' . $setting->name,
                    $setting->visiblename,
                    $setting->description,
                    $setting->defaultsetting,
                    array_merge($setting->choices, [
                        default_course_world_config::IDENTITY_FIRSTNAME_INITIAL_LASTNAME => get_string(
                            'displayfirstnameinitiallastname',
                            'local_xp'
                        ),
                    ])
                );
            }

            if ($setting->name == 'hdrcheatguard') {
                // Progress bar settings.
                $settings[] = (new admin_setting_heading('local_xp/hdrprogressbar', get_string('progressbar', 'block_xp'), ''));

                // Progress bar mode.
                $settings[] = (new admin_setting_configselect(
                    'local_xp/progressbarmode',
                    get_string('progressbarmode', 'local_xp'),
                    get_string('progressbarmode_help', 'local_xp'),
                    $defaults['progressbarmode'],
                    [
                        default_course_world_config::PROGRESS_BAR_MODE_LEVEL => get_string('progressbarmodelevel', 'local_xp'),
                        default_course_world_config::PROGRESS_BAR_MODE_OVERALL => get_string('progressbarmodeoverall', 'local_xp'),
                    ]
                ));

                // Group ladder settings.
                $settings[] = (new admin_setting_heading('local_xp/hdrgroupladder', get_string('teamleaderboard', 'block_xp'), ''));

                // Group ladder source.
                $sources = [
                    default_course_world_config::GROUP_LADDER_NONE => get_string('groupsourcenone', 'local_xp'),
                    default_course_world_config::GROUP_LADDER_COURSE_GROUPS => get_string('groupsourcecoursegroups', 'local_xp'),
                    default_course_world_config::GROUP_LADDER_COHORTS => get_string('groupsourcecohorts', 'local_xp'),
                ];
                if ($this->iomad->exists()) {
                    $sources[default_course_world_config::GROUP_LADDER_IOMAD_COMPANIES] = get_string(
                        'groupsourceiomadcompanies',
                        'local_xp'
                    );
                    $sources[default_course_world_config::GROUP_LADDER_IOMAD_DEPARTMENTS] = get_string(
                        'groupsourceiomaddepartments',
                        'local_xp'
                    );
                }
                $settings[] = (new admin_setting_configselect(
                    'local_xp/enablegroupladder',
                    get_string('groupladdersource', 'local_xp'),
                    get_string('groupladdersource_help', 'local_xp'),
                    $defaults['enablegroupladder'],
                    $sources
                ));

                // Group ladder identity mode.
                $settings[] = new admin_setting_configselect(
                    'local_xp/groupidentitymode',
                    get_string('groupanonymity', 'local_xp'),
                    get_string('groupanonymity_help', 'local_xp'),
                    $defaults['groupidentitymode'],
                    [
                        course_world_config::IDENTITY_OFF => get_string('hidegroupidentity', 'local_xp'),
                        course_world_config::IDENTITY_ON => get_string('displaygroupidentity', 'local_xp'),
                    ]
                );

                // Group ladder order.
                $settings[] = new admin_setting_configselect(
                    'local_xp/grouporderby',
                    get_string('grouporderby', 'local_xp'),
                    get_string('grouporderby_help', 'local_xp'),
                    $defaults['grouporderby'],
                    [
                        default_course_world_config::GROUP_ORDER_BY_POINTS => get_string('grouppoints', 'local_xp'),
                        default_course_world_config::GROUP_ORDER_BY_POINTS_COMPENSATED_BY_AVG =>
                            get_string('grouppointswithcompensation', 'local_xp'),
                        default_course_world_config::GROUP_ORDER_BY_PROGRESS => get_string('progress', 'block_xp'),
                    ]
                );

                // Group ladder columns.
                $settings[] = new admin_setting_configmultiselect(
                    'local_xp/groupladdercols',
                    get_string('groupladdercols', 'local_xp'),
                    get_string('groupladdercols_help', 'local_xp'),
                    explode(',', $defaults['groupladdercols']),
                    [
                        'xp' => get_string('grouppoints', 'local_xp'),
                        'progress' => get_string('progress', 'block_xp'),
                    ]
                );
            }

            $settings[] = $setting;

            if ($setting->name == 'timebetweensameactions') {
                // Max actions per time.
                $settings[] = (new admin_setting_configtext(
                    'local_xp/maxpointspertime',
                    get_string('maxpointspertime', 'local_xp'),
                    get_string('maxpointspertime_help', 'local_xp'),
                    $defaults['maxpointspertime'],
                    PARAM_INT
                ));

                // Time for max actions.
                $settings[] = (new admin_setting_configtext(
                    'local_xp/timeformaxpoints',
                    get_string('timeformaxpoints', 'local_xp'),
                    get_string('timeformaxpoints_help', 'local_xp'),
                    $defaults['timeformaxpoints'],
                    PARAM_INT
                ));
            }
        }

        return $settings;
    }

    /**
     * Get the AI settings.
     *
     * @return \Generator
     */
    protected function get_ai_settings() {
        $setting = new admin_setting_heading(
            'block_xp/hdrai',
            get_string('adminaisettings', 'block_xp'),
            get_string('adminaisettings_desc', 'block_xp')
        );
        yield $setting;

        $setting = (new admin_setting_configselect(
            'local_xp/enablemcp',
            get_string('enablemcp', 'block_xp'),
            get_string('enablemcp_desc', 'block_xp'),
            $this->defaults->get('enablemcp'),
            [
                '0' => get_string('no', 'core'),
                '1' => get_string('yes', 'core'),
            ]
        ));
        yield $setting;

        $setting = (new mcp_risks_setting(
            'local_xp/mcprisksallowed',
            get_string('mcprisksallowed', 'block_xp'),
            get_string(
                'mcprisksallowed_desc',
                'block_xp',
                ['toolslisturl' => $this->urlresolver->reverse('admin/mcptools')->get_compatible_url()]
            ),
            $this->defaults->get('mcprisksallowed')
        ));
        yield $setting;

        $setting = (new static_setting(
            'local_xp/mcpurl',
            get_string('mcpurl', 'block_xp'),
            get_string('mcpurl_desc', 'block_xp'),
            function () {
                return html_writer::tag('pre', di::get('oauth_service')->get_mcp_url()->out(false));
            }
        ));
        yield $setting;

        $setting = (new static_setting(
            'local_xp/oauthclients',
            get_string('oauthclients', 'block_xp'),
            get_string('oauthclients_desc', 'block_xp'),
            function () {
                $urlresolver = di::get('url_resolver');
                return html_writer::link(
                    $urlresolver->reverse('admin/oauthclients'),
                    get_string('manageoauthclients', 'block_xp'),
                );
            }
        ));
        yield $setting;

        $setting = (new admin_setting_configselect(
            'local_xp/enablechatgpt',
            get_string('enablechatgpt', 'block_xp'),
            get_string('enablechatgpt_desc', 'block_xp'),
            $this->defaults->get('enablechatgpt'),
            [
                '0' => get_string('no', 'core'),
                '1' => get_string('yes', 'core'),
            ]
        ));
        $setting->set_updatedcallback(function () {
            $domains = agent_utils::get_chatgpt_cimd_domains();
            $isenabled = (bool) get_config('local_xp', 'enablechatgpt');
            oauth_client::toggle_cimd_isenabled_by_domains($domains, $isenabled);
        });
        yield $setting;

        $setting = (new admin_setting_configselect(
            'local_xp/enableclaude',
            get_string('enableclaude', 'block_xp'),
            get_string('enableclaude_desc', 'block_xp'),
            $this->defaults->get('enableclaude'),
            [
                '0' => get_string('no', 'core'),
                '1' => get_string('yes', 'core'),
            ]
        ));
        $setting->set_updatedcallback(function () {
            $domains = agent_utils::get_claude_cimd_domains();
            $isenabled = (bool) get_config('local_xp', 'enableclaude');
            oauth_client::toggle_cimd_isenabled_by_domains($domains, $isenabled);
        });
        yield $setting;

        $setting = (new admin_setting_confightmleditor(
            'local_xp/mcpconnectinstructions',
            get_string('mcpconnectinstructions', 'block_xp'),
            get_string('mcpconnectinstructions_desc', 'block_xp'),
            $this->defaults->get('mcpconnectinstructions'),
            PARAM_RAW
        ));
        yield $setting;
    }
}
