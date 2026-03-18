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
 * Resolver.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\resolver;

use block_gearup\local\outcome\type\type;

/**
 * Resolver.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_resolver implements resolver {

    /** @var object */
    protected $lm;
    /** @var type[] The types. */
    protected $types;

    public function __construct($lm) {
        $this->lm = $lm;
    }

    /**
     * Get type by name.
     *
     * @param string $name The type name.
     * @return type The type.
     */
    public function get_type($name): type {
        $this->init_types();
        if (!array_key_exists($name, $this->types)) {
            throw new \moodle_exception('Invalid type', '', '', null, $name);
        }
        return $this->types[$name];
    }

    /**
     * Get the type's name.
     *
     * @param type $type The type instance.
     */
    public function get_type_name(type $type): string {
        return $this->get_type_name_from_class(get_class($type));
    }

    /**
     * Get type name from class.
     *
     * @param string $classname The class name.
     * @return string
     */
    protected function get_type_name_from_class($classname) {
        return str_replace('block_gearup\\local\\outcome\\type\\', '', $classname);
    }

    /**
     * Get all types.
     *
     * @return type[]
     */
    public function get_types(): array {
        $this->init_types();
        $typenames = $this->lm->get_outcome_types();
        return array_intersect_key($this->types, array_flip($typenames));
    }

    /**
     * Get the types available for the user.
     *
     * @param int $userid The user ID.
     * @param \context $context
     * @return type[]
     */
    public function get_types_available_for_user(int $userid, \context $context): array {
        return array_filter($this->get_types(), function ($type) use ($userid, $context) {
            if (is_subclass_of($type, availability\has_availability_info::class)) {
                $info = $type->get_availability_info();
                if (!$info->is_available()) {
                    return false;
                }
            }
            if (is_subclass_of($type, availability\has_availability_info_for_context::class)) {
                $info = $type->get_availability_info_for_context($context);
                if (!$info->is_available()) {
                    return false;
                }
            }
            if (is_subclass_of($type, availability\has_availability_info_for_user::class)) {
                $info = $type->get_availability_info_for_user($userid, $context);
                if (!$info->is_available()) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Initialise the types.
     */
    protected function init_types() {
        if (!isset($this->types)) {
            $types = [];
            $candidates = array_keys(\core_component::get_component_classes_in_namespace('block_gearup', 'local\outcome\type'));
            foreach ($candidates as $candidate) {
                if (!$this->is_type_class($candidate)) {
                    continue;
                }
                $name = $this->get_type_name_from_class($candidate);
                $types[$name] = new $candidate();
            }
            $this->types = $types;
        }
        return $this->types;
    }

    /**
     * Whether the class is a type.
     *
     * @param string $classname The class name.
     * @return bool
     */
    protected function is_type_class($classname) {
        try {
            $reflector = new \ReflectionClass($classname);
        } catch (\ReflectionException $e) {
            return false;
        }

        if (!$reflector->isInstantiable()) {
            return false;
        } else if (!$reflector->implementsInterface(type::class)) {
            return false;
        }
        return true;
    }

}
