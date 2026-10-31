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

namespace local_xp\local\navigation;

use block_xp\di;
use block_xp\local\controller\promo_controller;
use block_xp\local\navigation\nav_item;
use block_xp\local\navigation\navigator;
use block_xp\local\permission\access_logs_permissions;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\routing\url;
use block_xp\local\routing\url_resolver;
use block_xp\local\utils\world_utils;
use local_xp\local\world\global_world;
use pix_icon;

/**
 * Global world navigator.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_world_navigator extends navigator {
    /** @var global_world The world. */
    protected global_world $world;
    /** @var url_resolver The resolver. */
    protected url_resolver $urlresolver;

    /**
     * Constructor.
     *
     * @param global_world $world
     */
    public function __construct(global_world $world) {
        $this->urlresolver = di::get('url_resolver');
        $this->world = $world;
    }
    /**
     * Get the block navigation.
     *
     * @return nav_item[]
     */
    public function get_block_navigation(): array {
        $accessperms = $this->world->get_access_permissions();
        $canedit = $accessperms->can_manage();
        $canaccessreport = $accessperms instanceof access_report_permissions && $accessperms->can_access_report();
        $config = $this->world->get_config();
        $links = [];

        if ($config->get('enableinfos')) {
            $links[] = (new nav_item(get_string('navinfos', 'block_xp'), $this->get_url('infos'), 'infos'))
                ->set_icon(new pix_icon('i/info', '', 'block_xp'));
        }
        if ($config->get('enableladder')) {
            $links[] = (new nav_item(get_string('navladder', 'block_xp'), $this->get_url('ladder')))
                ->set_icon(new pix_icon('i/ladder', '', 'block_xp'));
        }
        if ($canaccessreport) {
            $links[] = (new nav_item(get_string('navreport', 'block_xp'), $this->get_url('report'), 'report'))
                ->set_icon(new pix_icon('i/report', '', 'block_xp'));
        }
        if ($canedit) {
            $links[] = (new nav_item(get_string('navsettings', 'block_xp'), $this->get_url('config'), 'config'))
                ->set_icon(new pix_icon('i/settings', '', 'block_xp'));
        }

        return $links;
    }

    /**
     * Get the navigation.
     *
     * @return nav_item[]
     */
    public function get_navigation(): array {
        $links = [];
        $accessperms = $this->world->get_access_permissions();
        $config = $this->world->get_config();
        $canmanage = $accessperms->can_manage();

        if ($config->get('enableinfos') || $canmanage) {
            $links[] = new nav_item(get_string('navinfos', 'block_xp'), $this->get_url('infos'), 'infos');
        }

        $canviewladder = $config->get('enableladder') || $canmanage;
        $isteamladderenabled = $config->has('enablegroupladder') && (bool) $config->get('enablegroupladder');
        $canviewteamladder = world_utils::supports_team_leaderboard($this->world) && ($isteamladderenabled || $canmanage);
        if ($canviewladder || $canviewteamladder) {
            $mainurl = $this->get_url($canviewladder ? 'ladder' : 'group_ladder');
            $laddernav = new nav_item(get_string('navladder', 'block_xp'), $mainurl, 'ladder');
            if ($canviewladder) {
                $laddernav->add_child(new nav_item(
                    get_string('participants', 'block_xp'),
                    $this->get_url('ladder'),
                    'ladder'
                ));
            }
            if ($canviewteamladder) {
                $laddernav->add_child((new nav_item(
                    get_string('teams', 'block_xp'),
                    $this->get_url('group_ladder'),
                    'group_ladder'
                )));
            }
            $links[] = $laddernav;
        }

        $canviewlogs = $accessperms instanceof access_logs_permissions && $accessperms->can_access_logs();
        $canviewreport = $accessperms instanceof access_report_permissions && $accessperms->can_access_report();
        if ($canviewreport || $canviewlogs) {
            // The link is always called report, but leads to the logs if we can't view the report.
            $mainurl = $this->get_url($canviewreport ? 'report' : 'log');
            $reportnav = new nav_item(get_string('navreport', 'block_xp'), $mainurl, 'report');
            if ($canviewreport) {
                $reportnav->add_child(new nav_item(
                    get_string('navreport', 'block_xp'),
                    $this->get_url('report'),
                    'report'
                ));
            }
            if ($canviewlogs) {
                $reportnav->add_child(new nav_item(get_string('navlog', 'block_xp'), $this->get_url('log'), 'log'));
            }
            $links[] = $reportnav;
        }

        if ($canmanage) {
            $links[] = (new nav_item(
                get_string('navlevels', 'block_xp'),
                $this->get_url('levels'),
                'levels'
            ))->set_children([
                new nav_item(get_string('navlevelssetup', 'block_xp'), $this->get_url('levels'), 'levels'),
                new nav_item(get_string('navvisuals', 'block_xp'), $this->get_url('visuals'), 'visuals'),
            ]);

            $links[] = new nav_item(get_string('navpoints', 'block_xp'), $this->get_url('points'), 'points');
            if (di::get('config')->get('enablemcp') && has_capability('local/xp:usemcp', $this->world->get_context())) {
                $links[] = new nav_item(get_string('navai', 'block_xp'), $this->get_url('ai'), 'ai');
            }

            $links[] = new nav_item(get_string('navsettings', 'block_xp'), $this->get_url('config'), 'config');

            $links[] = (new nav_item(get_string('navpromo', 'block_xp'), $this->get_url('promo'), 'promo'))
                ->set_needs_attention(promo_controller::has_new_content());
        }

        return $links;
    }

    /**
     * Get a URL.
     *
     * @param string $routename The route name.
     * @param array $params The route params, if any.
     * @return url
     */
    public function get_url(string $routename, array $params = []): url {
        $params['courseid'] = SITEID;
        return $this->urlresolver->reverse($routename, $params);
    }
}
