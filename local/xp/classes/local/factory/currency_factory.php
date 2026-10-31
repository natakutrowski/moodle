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

namespace local_xp\local\factory;

use block_xp\di;
use block_xp\local\factory\context_world_factory;
use block_xp\local\world;
use context;
use local_xp\local\config\default_course_world_config;
use local_xp\local\currency\currency;
use SplObjectStorage;

/**
 * Currency factory.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class currency_factory {
    /** @var context_world_factory The world factory. */
    protected $worldfactory;
    /** @var currency The default currency. */
    protected $defaultcurrency;
    /** @var SplObjectStorage Currency cache, keyed by world. */
    protected $currencies;

    /**
     * Constructor.
     *
     * @param context_world_factory $worldfactory The world factory.
     * @param currency $defaultcurrency The default currency.
     */
    public function __construct(context_world_factory $worldfactory, currency $defaultcurrency) {
        $this->worldfactory = $worldfactory;
        $this->defaultcurrency = $defaultcurrency;
        $this->currencies = new SplObjectStorage();
    }

    /**
     * Get the currency from a context.
     *
     * @param context $context The context.
     * @return currency
     */
    public function get_currency_from_context(context $context): currency {
        return $this->get_currency_for_world($this->worldfactory->get_world_from_context($context));
    }

    /**
     * Get the currency for a world.
     *
     * @param world $world The world.
     * @return currency
     */
    public function get_currency_for_world(world $world): currency {
        if (!isset($this->currencies[$world])) {
            $config = $world->get_config();
            if ($config->get('currencystate') == default_course_world_config::CURRENCY_USE_DEFAULT) {
                $currency = $this->defaultcurrency;
            } else {
                $currencytheme = $config->get('currencytheme');
                $resolver = new \local_xp\local\currency\stack_sign_url_resolver(array_filter([
                    new \local_xp\local\currency\course_sign_url_resolver($world->get_context()),
                    $currencytheme ? new \local_xp\local\currency\theme_sign_url_resolver(
                        di::get('renderer'),
                        $currencytheme
                    ) : null,
                ]));
                $currency = new \local_xp\local\currency\default_currency($resolver);
            }
            $this->currencies[$world] = $currency;
        }
        return $this->currencies[$world];
    }
}
