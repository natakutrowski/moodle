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
 * Shortcodes.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\shortcodes;

use block_gearup\di;
use block_gearup\output\tracker;
use context;
use context_system;

/**
 * Shortcodes.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class shortcodes {

    /**
     * Shortcodes.
     *
     * @param string $shortcode The tag.
     * @param array $args The arguments.
     * @param string|null $content The wrapped content.
     * @param object $env The environment.
     * @param Closure $next The next function.
     * @return string
     */
    public static function quest_discovery($shortcode, $args, $content, $env, $next) {
        global $PAGE, $USER;

        $mh = di::get('mission_helper');
        $mr = di::get('repository');
        $apf = di::get('access_permissions_factory');
        $cm = di::get('context_manager');
        $output = di::get('renderer');
        $missionid = (int) ($args['id'] ?? 0);
        $secret = (string) ($args['secret'] ?? '');

        if (!$missionid || strlen($secret) < 7) {
            return '';
        }

        // TODO We could save a query by returning the instance, or the mission.
        try {
            $missioninst = $mr->get_instance_by_subject_id($missionid, $USER->id);
        } catch (\moodle_exception $e) {
            $missioninst = null;
        }

        // Retrieve the mission, if possible.
        $mission = $missioninst ? $missioninst->get_mission() : $mr->get_mission($missionid);
        if (!$mission) {
            return '';
        } else if (!$mh->is_active($mission)) {
            return '';
        }

        // Verify that we are in the context of the PAGE. This prevents accidentally
        // using missions from one course into another. This does not apply to system missions which
        // can be used anywhere.
        if (!$mission->get_context() instanceof context_system) {
            $context = $cm->normalise_context($PAGE->context);
            if ($context->id != $mission->get_context()->id) {
                return '';
            }
        }

        // Only quests can be discovered.
        if (!$mh->is_a_quest($mission)) {
            return '';
        }

        // Validate the secret, even for managers. If they can't see the quest then it's incorrect.
        $missionsecret = $mission->get_secret() ?? '';
        if (substr($missionsecret, 0, strlen($secret)) !== $secret) {
            return '';
        }

        // Only discoverable quests are working with this shortcode.
        if (!$mh->is_discoverable($mission)) {
            return '';
        }

        $accessperms = $apf->get_permissions_for_context($mission->get_context());
        $canmanage = $accessperms->can_manage();

        // Managers don't need an instance.
        if (!$canmanage) {

            // Remove from the mobile app for non-managers.
            if (defined('WS_SERVER') && WS_SERVER) {
                return '';
            }

            // Validate that the user can see the instance.
            if (!$accessperms->can_access()) {
                return '';
            }

            // If the user is not assigned the mission.
            if (!$missioninst) {
                return '';
            }

            // If the mission has already started.
            if (!$mh->is_assigned($missioninst)) {
                return '';
            }
        }

        if ($canmanage) {
            $urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($mission->get_context(), $PAGE->context);
            $url = $urlresolver->reverse('mission', ['missionid' => $mission->get_id()]);
            return get_string('questdiscoveryhere', 'block_gearup', \html_writer::link($url, s($mission->get_title())));
        }

        $ef = di::get('exporter_factory');
        $exporter = $ef->get_mission_instance_exporter($missioninst);

        // TODO Move this to the renderer.
        return $output->render_from_template('block_gearup/shortcode_quest_discovery', [
            'missioninst' => $exporter->export($output),
        ]);
    }

    /**
     * Quest tracker.
     *
     * @param string $shortcode The tag.
     * @param array $args The arguments.
     * @param string|null $content The wrapped content.
     * @param object $env The environment.
     * @param Closure $next The next function.
     * @return string
     */
    public static function quest_tracker($shortcode, $args, $content, $env, $next) {
        global $PAGE, $USER;

        if (!$USER->id || isguestuser()) {
            return '';
        }

        $ctxid = (int) ($args['ctx'] ?? 0);
        $secret = (string) ($args['secret'] ?? '');

        if (empty($secret) || strlen($secret) < 7) {
            return '';
        }

        $candidatecontext = $ctxid ? context::instance_by_id($ctxid, IGNORE_MISSING) : null;
        if (!$candidatecontext) {
            return '';
        }

        $context = di::get('context_manager')->normalise_context($candidatecontext);
        if ($context instanceof context_system && !di::get('lm')->use_sitewide()) {
            return '';
        }

        if (substr(static::get_tracker_secret($context), 0, strlen($secret)) !== $secret) {
            return '';
        }

        $accessperms = di::get('access_permissions_factory')->get_permissions_for_context($context);
        if (!$accessperms->can_access()) {
            return '';
        }

        if ($accessperms->can_manage() && !di::get('repository')->has_any_visible_instances_in($USER->id, $context)) {
            return \html_writer::tag('p', get_string('questtrackerhere', 'block_gearup'));
        }

        if (defined('WS_SERVER') && WS_SERVER) {
            return '';
        }

        $output = di::get('renderer');
        $urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($context, $PAGE->context);
        $tracker = new tracker($context, $USER->id, $urlresolver, $PAGE->url);

        if (!empty($args['show'])) {
            $tracker->set_sections_to_show(array_map('trim', explode(',', (string) $args['show'])));
        }

        return \html_writer::div($output->render($tracker), 'block_gearup');
    }

    /**
     * Get the secret for the tracker.
     *
     * @param context $context
     * @return string
     */
    public static function get_tracker_secret(context $context) {
        $trackersecret = get_config('block_gearup', 'shortcodetrackersecret');
        if ($trackersecret === false) {
            $trackersecret = bin2hex(random_bytes(5));
            set_config('shortcodetrackersecret', $trackersecret, 'block_gearup');
        }
        // Make unique per context.
        return substr(sha1($trackersecret . '|' . $context->id), 0, 10);
    }

}
