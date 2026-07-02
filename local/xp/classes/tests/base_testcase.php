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

namespace local_xp\tests;

use core_component;
use ReflectionClass;

/**
 * Base testcase class.
 *
 * @package    local_xp
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_testcase extends \block_xp\tests\base_testcase {

    /** @var bool */
    private static $fixtureautoloadregistered = false;

    /** @var \block_xp_generator */
    protected $blockgenerator;
    /** @var \local_xp_generator */
    protected $localgenerator;

    /**
     * Get the generator.
     *
     * @return \block_xp_generator
     */
    protected function get_block_xp_generator() {
        return $this->get_xp_generator();
    }

    /**
     * Get the generator.
     *
     * @return \local_xp_generator
     */
    protected function get_local_xp_generator() {
        if (!$this->localgenerator) {
            $this->localgenerator = $this->getDataGenerator()->get_plugin_generator('local_xp');
        }
        return $this->localgenerator;
    }

    /**
     * Get instantiable classes.
     *
     * @param string $namespace The namespace relative to block_xp.
     * @param string|null $withinterface The interface the class must implement.
     * @return Generator<ReflectionClass>
     */
    protected function get_instantiable_classes($namespace, $withinterface = null) {
        $classes = array_keys(core_component::get_component_classes_in_namespace('local_xp', $namespace));
        foreach ($classes as $classname) {
            $class = new ReflectionClass($classname);
            if (!$class->isInstantiable()) {
                continue;
            } else if ($withinterface && !$class->implementsInterface($withinterface)) {
                continue;
            }
            yield $class;
        }
    }

    protected function reset_container() {
        \block_xp\di::set_container(new \local_xp\local\container());
    }

    /**
     * Get the autoload prefixes.
     *
     * @return array<string, string>
     */
    protected static function get_autoload_prefixes() {
        return parent::get_autoload_prefixes() + [
            'local_xp\\tests\\mocks\\' => '/local/xp/tests/mocks/',
        ];
    }

}
