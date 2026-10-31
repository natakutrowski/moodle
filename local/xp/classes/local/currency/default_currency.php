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

/**
 * Currency.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\currency;

/**
 * Currency.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_currency implements currency {
    /** @var objects */
    protected $urlresolver;
    /** @var bool */
    protected $urlknown = false;
    /** @var \moodle_url|null */
    protected $url;

    /**
     * Constructor.
     */
    public function __construct(?currency_sign_url_resolver $urlresolver = null) {
        $this->urlresolver = $urlresolver;
    }

    /**
     * Get sign.
     *
     * @return string
     */
    public function get_sign() {
        return 'xp';
    }

    /**
     * Get sign URL.
     *
     * @return moodle_url|null
     */
    public function get_sign_url() {
        if (!$this->urlknown) {
            if ($this->urlresolver) {
                $this->url = $this->urlresolver->get_currency_sign_url();
            }
            $this->urlknown = true;
        }
        return $this->url;
    }

    /**
     * Check superscript usage.
     *
     * @return bool
     */
    public function use_sign_as_superscript() {
        return true;
    }
}
