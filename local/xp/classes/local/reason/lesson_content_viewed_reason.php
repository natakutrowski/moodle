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
// https://levelup.plus

namespace local_xp\local\reason;

use local_xp\local\utils\context_utils;
use moodle_url;

/**
 * Lesson content viewed reason.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lesson_content_viewed_reason extends \block_xp\local\reason\lesson_content_viewed_reason implements reason_with_location {
    use module_context_trait;

    /** @var string|null */
    protected $objectname = null;

    /**
     * Get object name.
     *
     * @return string|null
     */
    protected function get_object_name(): ?string {
        if ($this->objectname === null) {
            $db = \block_xp\di::get('db');
            $this->objectname = $db->get_field('lesson_pages', 'title', ['id' => (int) $this->get_object_id()], IGNORE_MISSING);
        }
        return $this->objectname ?: null;
    }

    public function get_location_name() {
        $context = $this->get_module_context();
        if (!$context) {
            return null;
        }

        $objectname = $this->get_object_name();
        if (!$objectname) {
            return context_utils::get_activity_name($context);
        }
        return format_string($objectname, true, ['context' => $context]);
    }

    public function get_location_url() {
        $context = $this->get_module_context();
        if (!$context) {
            return null;
        }
        if (!$this->get_object_name()) {
            return context_utils::get_url($context);
        }
        return new moodle_url('/mod/lesson/view.php', ['id' => (int) $context->instanceid,
            'pageid' => (int) $this->get_object_id()]);
    }
}
