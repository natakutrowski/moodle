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
 * URL resolver.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\routing;

use context;

/**
 * URL resolver.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url_resolver_param_injector implements url_resolver {

    /** @var int The context ID. */
    protected $contextid;
    /** @var string The param name. */
    protected $paramname;
    /** @var url_resolver The URL resolver. */
    protected $urlresolver;

    /**
     * Constructor.
     *
     * @param url_resolver $urlresolver The URL resolver.
     * @param context $context The context ID to inject.
     */
    public function __construct(url_resolver $urlresolver, context $context, $paramname = null) {
        $this->urlresolver = $urlresolver;
        $this->contextid = $context->id;
        $this->paramname = $paramname ?? 'guctxid';
    }

    public function get_route_url() {
        return $this->urlresolver->get_route_url();
    }

    public function match($uri) {
        return $this->urlresolver->match($uri);
    }

    public function reverse($name, array $params = []) {
        $url = $this->urlresolver->reverse($name, $params);

        // Inject the context ID when not defined.
        $params = $url->params();
        if (!array_key_exists($this->paramname, $params)) {
            $data = [];
            $data[$this->paramname] = $this->contextid;
            $url->params($data);
        }

        return $url;
    }
}
