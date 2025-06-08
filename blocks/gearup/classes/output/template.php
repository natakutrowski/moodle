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

namespace block_gearup\output;

use core\output\named_templatable;
use renderable;
use renderer_base;

/**
 * Class template
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template implements renderable, named_templatable {

    /** @var string The template name. */
    protected $name;
    /** @var object The template context. */
    protected $context;

    /**
     * Constructor.
     *
     * @param string $name The template name.
     * @param array|object|null $context The template context.
     */
    public function __construct(string $name, $context = null) {
        $this->name = $name;
        $this->context = (object) ($context ?? []);
    }

    public function get_template_name(renderer_base $renderer): string {
        return $this->name;
    }

    public function export_for_template(\renderer_base $output) {
        return $this->context;
    }
}
