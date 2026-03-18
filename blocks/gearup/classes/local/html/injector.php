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
 * Injector.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\html;

use block_gearup\di;
use block_gearup\local\factory\access_permissions_factory;
use block_gearup\local\objective;
use block_gearup\local\permission\access_permissions;
use block_gearup\local\utils\json_utils;
use context;
use html_writer;
use moodle_url;

/**
 * Injector.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class injector {

    /** @var access_permissions_factory */
    protected $accesspermsfactory;
    /** @var \cache */
    protected $cache;
    /** @var object */
    protected $repository;
    /** @var objective\resolver\resolver */
    protected $objtyperesolver;
    /** @var object */
    protected $output;
    /** @var object */
    protected $exporterfactory;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->accesspermsfactory = di::get('access_permissions_factory');
        $this->exporterfactory = di::get('exporter_factory');
        $this->objtyperesolver = di::get('objective_type_resolver');
        $this->output = di::get('renderer');
        $this->repository = di::get('repository');
        $this->cache = di::get('metadata_cache');
    }

    /**
     * Inject.
     *
     * @param \moodle_page $page The page.
     * @return string
     */
    public function inject($page): string {
        $context = $page->context;
        $accessperms = $this->accesspermsfactory->get_permissions_for_context($context);

        $content = '';
        $content .= $this->metadata($page) ?? '';
        $content .= $this->observer($page, $context, $accessperms) ?? '';
        $content .= $this->achievement_toast($page) ?? '';
        return $content;
    }

    /**
     * Achievement toast.
     *
     * @param \moodle_page $page The page.
     */
    protected function achievement_toast($page) {
        global $USER;
        $context = di::get('context_manager')->normalise_context($page->context);

        $ctxids = json_utils::decode_to_list(get_user_preferences('block_gearup_achievements_ctxids', '[]'));
        if (!in_array((int) $context->id, $ctxids)) {
            return null;
        }

        $missioninstids = di::get('repository')->get_achievement_ids_to_announce($context, $USER->id);
        if (empty($missioninstids)) {
            set_user_preference('block_gearup_achievements_ctxids',
                json_utils::encode_as_list(array_diff($ctxids, [(int) $context->id]))
            );
            return null;
        }

        $page->requires->js_call_amd('block_gearup/achievement_toast',
            'queueMissionInstanceIds',
            [array_map('intval', $missioninstids)]
        );
        return null;
    }

    /**
     * Metadata.
     *
     * @param \moodle_page $page The page.
     * @return null|string
     */
    protected function metadata($page) {
        $o = '';
        $o .= di::get('renderer')->json_script([
            'chatlib' => $this->cache->get('chatlib') ?: (object) [],
        ], html_writer::random_id('gu-metadata-'));
        return $o;
    }

    /**
     * Observer.
     *
     * @param \moodle_page $page The page.
     * @param context $context The context.
     * @param access_permissions $accessperms The access perms.
     */
    protected function observer($page, context $context, access_permissions $accessperms) {
        global $CFG, $USER;

        // Check access permissions.
        if (!$accessperms->can_access()) {
            return;
        }

        // TODO Retrieve types from subset of available types.
        $types = [];
        $typeresolver = $this->objtyperesolver;
        $videojsenabled = in_array('videojs', explode(',', $CFG->media_plugins_sortorder ?? ''));
        if ($videojsenabled) {
            $types[] = $typeresolver->get_type('watch_video');
            $types[] = $typeresolver->get_type('watch_time');
        }

        if (empty($types)) {
            return;
        }

        // TODO Cache results?
        $incompletetypes = $this->repository->get_incomplete_objective_instance_type_names_amongst_types($types,
            $USER->id,
            $context
        );
        if (empty($incompletetypes)) {
            return;
        }

        $url = new moodle_url($page->url);
        $url->set_anchor(null);
        $url->remove_params(['sesskey']);

        $propsid = html_writer::random_id();
        $page->requires->js_call_amd('block_gearup/observer', 'initForPage', [$propsid]);
        return di::get('renderer')->json_script([
            'contextid' => $context->id,
            'pageurl' => $url->out_as_local_url(false),
            'types' => $incompletetypes,
            'servertime' => time(),
            'config' => [
                'media_videojs' => [
                    'videocssclass' => get_config('media_videojs', 'videocssclass'),
                ],
            ],
        ], $propsid);
    }
}
