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

use context;
use local_xp\local\utils\context_utils;

/**
 * Event reason.
 *
 * This implementation is archaic and has been updated for backwards compatibility but
 * it should no longer be used other than for event rules. The related user ID is hardcoded
 * in the parent ID, which does not make sense semanticly but is necessary to migrate to new
 * database structure.
 *
 * Note that this was not originally extending event_reason as it did not exist in block_xp,
 * but it now does the latter supports all the features we need.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_reason extends \block_xp\local\reason\event_reason implements reason_with_location, reason_with_short_description {

    /**
     * @var string
     * @deprecated Since XP+ 20
     */
    protected $name = '';
    /**
     * @var int
     * @deprecated Since XP+ 20
     */
    protected $contextid = 0;
    /**
     * @var int
     * @deprecated Since XP+ 20
     */
    protected $relateduserid = 0;

    /**
     * Constructor.
     *
     * @param string $name The event name. Deprecated.
     * @param int $contextid The context ID. Deprecated.
     * @param int|null $objectid The object ID. Deprecated.
     * @param int|null $relateduserid The related user ID. Deprecated.
     */
    public function __construct($name = null, $contextid = null, $objectid = null, $relateduserid = null) {
        if ($name !== null) {
            $this->set_subtype($name);
        }
        if ($contextid) {
            $this->set_env_id((int) $contextid);
        }
        if ($objectid) {
            $this->set_object_id((int) $objectid);
        }
        if ($relateduserid) {
            $this->set_parent_id((int) $relateduserid);
        }
    }

    /**
     * Get the context.
     *
     * @return context|null
     */
    protected function get_context() {
        return context::instance_by_id($this->get_env_id() ?? 0, IGNORE_MISSING) ?: null;
    }

    /**
     * Get the location name.
     *
     * @return string|null
     */
    public function get_location_name() {
        $context = $this->get_context();
        if (!$context || $context->contextlevel == CONTEXT_SYSTEM) {
            // The name of the site is unnecessary, hence why we skip system context.
            return null;
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            return context_utils::get_activity_name_prefixed($context);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            return context_utils::get_course_name_short($context);
        }
        return $context->get_context_name();
    }

    /**
     * Get the location URL.
     *
     * @return moodle_url|null
     */
    public function get_location_url() {
        $context = $this->get_context();
        if (!$context || $context->contextlevel == CONTEXT_SYSTEM) {
            // The URL of the site is unnecessary, so we skip system contexts.
            return null;
        }
        return context_utils::get_url($context);
    }

    /**
     * @deprecated Since XP+ 20
     */
    public function get_signature() {
        return implode(':', [
            $this->get_subtype() ?? '',
            $this->get_env_id() ?? '',
            $this->get_object_id() ?? '',
            $this->get_parent_id() ?? '', // Related user ID.
        ]);
    }

    /**
     * @deprecated Since XP+ 20
     */
    public static function get_type() {
        return __CLASS__;
    }

    /**
     * From signature.
     *
     * @param string $signature.
     * @return static
     * @deprecated Since XP+ 20
     */
    public static function from_signature($signature) {
        [$name, $ctx, $obj, $relid] = explode(':', $signature);
        return new static($name, (int) $ctx, $obj ?: null, $relid ?: null);
    }

}
