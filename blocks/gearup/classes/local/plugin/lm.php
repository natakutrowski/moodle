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
// See <https://levelup.plus>.

/**
 * Manager.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\plugin;

use cache;

/**
 * Manager.
 *
 * Modifying this file may result in the associated licence being voided
 * and future attempts to obtain a licence being declined. Please contact
 * support to discuss licence terms.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lm {
    /** Package basic. */
    const PACKAGE_BASIC = 'basic';
    /** Package pro. */
    const PACKAGE_PRO = 'pro';
    /** Package enterprise. */
    const PACKAGE_ENTERPRISE = 'enterprise';

    /** @var string|null */
    protected $activationid;
    /** @var int|null */
    protected $expiry;
    /** @var string|null */
    protected $package;
    /** @var cache */
    protected $cache;

    /**
     * Constructor.
     *
     * @param cache $cache The cache.
     */
    public function __construct(cache $cache) {
        $this->cache = $cache;
        $this->load();
    }

    /**
     * Get activation id.
     *
     * @return mixed
     */
    public function get_activation_id() {
        return $this->activationid;
    }

    /**
     * Is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->is_activated()
            && (bool) $this->cache->get('is_active')
            && (!$this->is_expired() || !$this->is_expiry_cutoff_met());
    }

    /**
     * Is activated.
     *
     * @return bool
     */
    public function is_activated(): bool {
        return (bool) $this->activationid;
    }

    /**
     * Is evaluating.
     *
     * @return bool
     */
    public function is_evaluating(): bool {
        return $this->is_activated() && (bool) $this->cache->get('is_evaluation');
    }

    /**
     * Is expired.
     *
     * @return bool
     */
    public function is_expired(): bool {
        return $this->cache->get('expiry') < time();
    }

    /**
     * Is expiry cutoff met.
     *
     * @return bool
     */
    public function is_expiry_cutoff_met(): bool {
        return $this->cache->get('expiry_cutoff') < time();
    }

    /**
     * Get assigner types.
     *
     * @return array
     */
    public function get_assigner_types(): array {
        return $this->cache->get('list_assigner_types');
    }

    /**
     * Get objective types.
     *
     * @return array
     */
    public function get_objective_types(): array {
        return $this->cache->get('list_objective_types');
    }

    /**
     * Get outcome types.
     *
     * @return array
     */
    public function get_outcome_types(): array {
        return $this->cache->get('list_outcome_types');
    }

    /**
     * Load.
     *
     * @return void
     */
    public function load() {
        $this->activationid = get_config('block_gearup', 'activationid') ?: null;
    }

    /**
     * Process payload.
     *
     * @param mixed $payload Payload.
     * @return void
     */
    public function process_payload($payload) {
        set_config('activationid', $payload->id, 'block_gearup');
        set_config('activationtoken', $payload->token, 'block_gearup');
        set_config('webhooksecret', $payload->webhook_secret, 'block_gearup');
        $this->load();
    }

    /**
     * Max achievement badges.
     *
     * @return int|null
     */
    public function max_achievement_badges(): ?int {
        $value = (int) $this->cache->get('max_achievement_badges');
        return $value <= -1 ? null : $value;
    }

    /**
     * Max quest narrators.
     *
     * @return int|null
     */
    public function max_quest_narrators(): ?int {
        $value = (int) $this->cache->get('max_quest_narrators');
        return $value <= -1 ? null : $value;
    }

    /**
     * Use challenges.
     *
     * @return bool
     */
    public function use_challenges(): bool {
        return $this->is_activated() && (bool) $this->cache->get('use_challenges');
    }

    /**
     * Use export recruits.
     *
     * @return bool
     */
    public function use_export_recruits(): bool {
        return (bool) $this->cache->get('use_export_recruits');
    }

    /**
     * Use insights.
     *
     * @return bool
     */
    public function use_insights(): bool {
        return (bool) $this->cache->get('use_insights');
    }

    /**
     * Use sitewide.
     *
     * @return bool
     */
    public function use_sitewide(): bool {
        return $this->is_activated() && (bool) $this->cache->get('use_sitewide');
    }

    /**
     * Use speech.
     *
     * @return bool
     */
    public function use_speech(): bool {
        return (bool) $this->cache->get('use_speech');
    }

    /**
     * Use streaks.
     *
     * @return bool
     */
    public function use_streaks(): bool {
        return (bool) $this->cache->get('use_streaks');
    }
}
