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
 * URL resolver factory.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\factory;

use block_gearup\local\routing\url_resolver_param_injector;
use block_gearup\local\routing\url_resolver;
use context;

/**
 * URL resolver factory.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_url_resolver_factory implements url_resolver_context_factory {

    /** @var url_resolver The base resolver. */
    protected $baseresolver;

    /**
     * Constructor.
     *
     * @param url_resolver $baseresolver The base resolver.
     */
    public function __construct(url_resolver $baseresolver) {
        $this->baseresolver = $baseresolver;
    }

    public function get_resolver_for_context(context $context, ?context $pagecontext): url_resolver {
        $injector = new url_resolver_param_injector($this->baseresolver, $context);
        if ($pagecontext && $context->id != $pagecontext->id) {
            $injector = new url_resolver_param_injector($injector, $pagecontext, 'gupagectxid');
        }
        return $injector;
    }

}
