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
 * Repository.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\visual;

use block_gearup\di;
use block_gearup\local\http\api_client;
use cache;
use context;
use moodle_url;

/**
 * Repository.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class achievement_badges_repository implements repository_with_context {

    /** @var api_client */
    protected $apiclient;
    /** @var cache */
    protected $cache;
    /** @var int|null Max local. */
    protected $maxlocal;
    /** @var visual[] */
    protected $visuals;
    /** @var visual[][] Indexed by context ID. */
    protected $visualsinctx = [];

    public function __construct(cache $cache, api_client $apiclient) {
        $this->cache = $cache;
        $this->apiclient = $apiclient;
        $this->maxlocal = di::get('lm')->max_achievement_badges();
    }

    public function get_visual(string $id): ?visual {
        $this->load();
        return $this->visuals[$id] ?? null;
    }

    public function get_visual_from_context(string $id, context $context): ?visual {
        if (strpos($id, 'local/') === 0) {
            $contextid = (int) $context->id;
            $this->load_in_context($contextid);
            return $this->visualsinctx[$contextid][$id] ?? null;
        }
        return $this->get_visual($id);
    }

    public function get_visuals(): array {
        $this->load();
        return array_values($this->visuals);
    }

    public function get_visuals_from_context(context $context): array {
        $contextid = (int) $context->id;
        $this->load();
        $this->load_in_context($contextid);
        return array_values(array_merge($this->visuals, $this->visualsinctx[$contextid]));
    }

    public function get_visuals_in_context(context $context): array {
        $contextid = (int) $context->id;
        $this->load_in_context($contextid);
        return array_values($this->visualsinctx[$contextid]);
    }

    /**
     * Load the visuals.
     */
    protected function load() {
        if (!isset($this->visuals)) {
            $this->visuals = array_reduce($this->cache->get('achievement_badges'), function ($carry, $visual) {
                $carry[$visual->id] = new static_visual(
                    $visual->id,
                    $visual->url
                );
                return $carry;
            }, []);
        }
    }

    /**
     * Load the visuals in context.
     *
     * @param int $contextid The context ID.
     */
    protected function load_in_context(int $contextid) {
        if (!isset($this->visualsinctx[$contextid])) {
            $fs = get_file_storage();

            $allfiles = [];
            if ($this->maxlocal === null || $this->maxlocal > 0) {
                $allfiles = $fs->get_area_files($contextid,
                    'block_gearup',
                    'achievementbadges',
                    0,
                    'filename',
                    false,
                    0,
                    0,
                    (int) $this->maxlocal
                );
            }

            $data = [];
            foreach ($allfiles as $file) {
                if (strpos($file->get_mimetype(), 'image/') !== 0) {
                    continue;
                }
                $id = 'local/' . $file->get_filename();
                $data[$id] = new static_visual($id,
                    moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename() . '/' . $file->get_timemodified()
                    )
                );
            }

            $this->visualsinctx[$contextid] = $data;
        }
    }

}
