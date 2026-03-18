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

    const PACKAGE_BASIC = 'basic';
    const PACKAGE_PRO = 'pro';
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

    public function get_activation_id() {
        return $this->activationid;
    }

    public function is_active(): bool {
        return $this->is_activated()
            && (bool) $this->cache->get('is_active')
            && (!$this->is_expired() || !$this->is_expiry_cutoff_met());
    }

    public function is_activated(): bool {
        return (bool) $this->activationid;
    }

    public function is_evaluating(): bool {
        return $this->is_activated() && (bool) $this->cache->get('is_evaluation');
    }

    public function is_expired(): bool {
        return $this->cache->get('expiry') < time();
    }

    public function is_expiry_cutoff_met(): bool {
        return $this->cache->get('expiry_cutoff') < time();
    }

    public function get_assigner_types(): array {
        return $this->cache->get('list_assigner_types');
    }

    public function get_objective_types(): array {
        return $this->cache->get('list_objective_types');
    }

    public function get_outcome_types(): array {
        return $this->cache->get('list_outcome_types');
    }

    public function load() {
        $this->activationid = get_config('block_gearup', 'activationid') ?: null;
    }

    public function process_payload($payload) {
        set_config('activationid', $payload->id, 'block_gearup');
        set_config('activationtoken', $payload->token, 'block_gearup');
        set_config('webhooksecret', $payload->webhook_secret, 'block_gearup');
        $this->load();
    }

    public function max_achievement_badges(): ?int {
        $value = (int) $this->cache->get('max_achievement_badges');
        return $value <= -1 ? null : $value;
    }

    public function max_quest_narrators(): ?int {
        $value = (int) $this->cache->get('max_quest_narrators');
        return $value <= -1 ? null : $value;
    }

    public function use_challenges(): bool {
        return $this->is_activated() && (bool) $this->cache->get('use_challenges');
    }

    public function use_export_recruits(): bool {
        return (bool) $this->cache->get('use_export_recruits');
    }

    public function use_insights(): bool {
        return (bool) $this->cache->get('use_insights');
    }

    public function use_sitewide(): bool {
        return $this->is_activated() && (bool) $this->cache->get('use_sitewide');
    }

    public function use_speech(): bool {
        return (bool) $this->cache->get('use_speech');
    }

    public function use_streaks(): bool {
        return (bool) $this->cache->get('use_streaks');
    }

}
