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
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\local\controller\utils\mission_wizard_trait;
use block_gearup\tests\base_testcase;
use core_component;
use ReflectionClass;
use ReflectionMethod;
use SplFileObject;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\controller\controller
 */
final class compliance_test extends base_testcase {

    public function test_calls_parent_pre_content_method(): void {
        $classes = array_keys(core_component::get_component_classes_in_namespace('block_gearup', 'local\\controller'));
        foreach ($classes as $classname) {
            $class = new ReflectionClass($classname);
            $method = $class->hasMethod('pre_content') ? $class->getMethod('pre_content') : null;
            if (!$method || !$this->is_method_from_class($method, $class)) {
                continue;
            }

            $parenthasmethod = false;
            $parentclass = $class;
            while ($parentclass = $parentclass->getParentClass()) {
                $parenthasmethod = $parenthasmethod || $parentclass->hasMethod('pre_content');
            }

            if ($parenthasmethod) {
                $source = $this->get_method_code($method);
                $this->assertMatchesRegularExpression('/^(\s)*parent::pre_content\(\)/',
                    $source[0],
                    "Class {$class->getName()} must call the parent method {$method->getName()}."
                );
            }
        }
    }

    public function test_does_not_override_mission_trait_final_methods(): void {
        $trait = new ReflectionClass(mission_wizard_trait::class);
        $finalmethods = $trait->getMethods(ReflectionMethod::IS_FINAL);
        $classes = array_keys(core_component::get_component_classes_in_namespace('block_gearup', 'local\\controller'));

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $traits = $reflection->getTraits();

            // Skip classes that do not implement the trait.
            if (!in_array($trait, $traits)) {
                continue;
            }

            // Validate that the controller does not define any of the trait methods.
            foreach ($finalmethods as $finalmethod) {
                $method = $reflection->getMethod($finalmethod->getName());
                $this->assertTrue($method->getFileName() === $finalmethod->getFileName(),
                    "Class {$reflection->getName()} must not redeclare the method {$method->getName()} " .
                    "declared final in the trait {$trait->getName()}."
                );
            }
        }
    }

    protected function get_method_code(ReflectionMethod $method): array {
        $filename = $method->getFileName();
        $endline = $method->getEndLine();

        $sourcefile = new SplFileObject($filename);
        $sourcefile->seek($method->getStartLine());
        $source = [];
        do {
            $source[] = $sourcefile->current();
            if ($sourcefile->getCurrentLine() >= $endline) {
                break;
            }
        } while ($sourcefile->next());

        return $source;
    }

    protected function is_method_from_class(ReflectionMethod $method, ReflectionClass $class) {
        return $method->getFileName() === $class->getFileName()
            && $method->getDeclaringClass()->getName() === $class->getName();
    }

}
