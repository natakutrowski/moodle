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

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class missions_tracker_exporter_base extends \core\external\exporter {

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

}
