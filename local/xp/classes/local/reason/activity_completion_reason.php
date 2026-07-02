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

use context_module;
use block_xp\local\reason\reason;
use block_xp\local\reason\reason_rule_trait;
use block_xp\local\reason\reason_tracking_trait;
use block_xp\local\reason\reason_with_rule;
use block_xp\local\reason\reason_with_tracking;
use context;
use local_xp\local\utils\context_utils;

/**
 * Activity completion reason.
 *
 * We also store the state in which the completion happened, in case we want
 * to reward for going from failing to completing, etc...
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_completion_reason implements
    reason,
    reason_with_location,
    reason_with_rule,
    reason_with_short_description,
    reason_with_tracking {
    use reason_rule_trait;
    use reason_tracking_trait;

    /**
     * @var int The state. Hardcoded to COMPLETION_COMPLETE.
     * @deprecated Since XP+ 20.
     */
    protected $state = 1;
    /**
     * @var int The CM ID.
     * @deprecated Since XP+ 20.
     */
    protected $cmid = 0;

    /**
     * Constructor.
     *
     * @param int $cmid The CM ID. Deprecated argument.
     * @param int $state The state. Deprecated argument.
     */
    public function __construct($cmid = 0, $state = 1) {
        if ($cmid) {
            $ctx = context_module::instance($cmid, IGNORE_MISSING);
            $this->set_env_id($ctx ? $ctx->id : null);
        }
    }

    protected function get_cm_id(): int {
        $ctx = $this->get_context();
        return $ctx ? (int) $ctx->instanceid : 0;
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
        return context_utils::get_activity_name_prefixed($this->get_context());
    }

    /**
     * Get the location URL.
     *
     * @return moodle_url|null
     */
    public function get_location_url() {
        return context_utils::get_url($this->get_context());
    }

    /**
     * @deprecated Since XP+ 20
     */
    public function get_signature() {
        return $this->get_cm_id() . ':' . $this->state;
    }

    public function get_short_description() {
        return get_string('activitycompleted', 'local_xp');
    }

    /**
     * @deprecated Since XP+ 20
     */
    public static function get_type() {
        return __CLASS__;
    }

    /**
     * Instantiate from the context.
     *
     * @param context $context The context.
     */
    public static function from_context($context): self {
        $reason = new static();
        $reason->set_env_id((int) $context->id);
        return $reason;
    }

    /**
     * @param string $signature The signature.
     * @deprecated Since XP+ 20
     */
    public static function from_signature($signature) {
        [$cmid, $state] = explode(':', $signature);
        return new static((int) $cmid, (int) $state);
    }

    /**
     * Make from event.
     *
     * @param \core\event\course_module_completion_updated $e The event.
     * @deprecated Since XP+ 20
     */
    public static function from_event(\core\event\course_module_completion_updated $e) {
        $data = $e->get_record_snapshot('course_modules_completion', $e->objectid);
        $state = $data->completionstate;
        $cmid = $e->get_context()->instanceid;
        return new static($cmid, $state);
    }

}
