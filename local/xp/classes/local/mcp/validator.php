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

namespace local_xp\local\mcp;

/**
 * Minimal JSON schema validator for MCP tool arguments.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validator {
    /**
     * Validate a value.
     *
     * @param schema $schema Schema.
     * @param mixed $value Value.
     * @param string $path Root path.
     * @return mixed The validated value.
     */
    public static function validate(schema $schema, $value, string $path = 'value') {
        return self::validate_value($schema->to_object(true), $value, $path);
    }

    /**
     * Validate an object.
     *
     * @param \stdClass $schema Schema.
     * @param mixed $value Value.
     * @param string $path Path.
     * @return array The validated value.
     */
    protected static function validate_object(\stdClass $schema, $value, string $path): array {
        if (!is_array($value)) {
            self::invalid($path, 'must be an object.', 'type', ['expected' => ['object']]);
        }
        $properties = (array) ($schema->properties ?? []);
        foreach (($schema->required ?? []) as $name) {
            if (!array_key_exists($name, $value)) {
                self::invalid(
                    self::child_path($path, $name),
                    'is required.',
                    'required',
                    ['property' => $name]
                );
            }
        }
        if (($schema->additionalProperties ?? true) === false) {
            foreach ($value as $name => $unused) {
                if (!array_key_exists($name, $properties)) {
                    self::invalid(
                        self::child_path($path, $name),
                        'is not an allowed property.',
                        'additionalProperties',
                        ['property' => $name]
                    );
                }
            }
        }
        $result = $value;
        foreach ($properties as $name => $propertyschema) {
            if (array_key_exists($name, $value)) {
                $result[$name] = self::validate_value(
                    $propertyschema,
                    $value[$name],
                    self::child_path($path, $name)
                );
            }
        }
        return $result;
    }

    /**
     * Validate a value.
     *
     * @param \stdClass $schema Schema.
     * @param mixed $value Value.
     * @param string $path Path.
     * @return mixed The validated value.
     */
    protected static function validate_value(\stdClass $schema, $value, string $path) {
        global $CFG;

        $types = (array) ($schema->type ?? []);
        if ($types && !self::matches_type($types, $value)) {
            self::invalid(
                $path,
                'must be ' . self::format_types($types) . ', received ' . self::type_name($value) . '.',
                'type',
                ['expected' => array_values($types), 'actual' => self::type_name($value)]
            );
        }
        if (in_array('object', $types, true) && $value !== null) {
            $value = self::validate_object($schema, $value, $path);
        } else if (in_array('array', $types, true) && isset($schema->items) && is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::validate_value($schema->items, $item, self::item_path($path, $key));
            }
        }
        if (isset($schema->enum) && !in_array($value, $schema->enum, true)) {
            self::invalid(
                $path,
                'must be one of: ' . self::format_values($schema->enum) . '.',
                'enum',
                ['allowed' => array_values($schema->enum)]
            );
        }
        if (is_string($value)) {
            if (isset($schema->minLength) && \core_text::strlen($value) < $schema->minLength) {
                self::invalid(
                    $path,
                    'must be at least ' . $schema->minLength . ' characters.',
                    'minLength',
                    ['minimum' => $schema->minLength, 'actual' => \core_text::strlen($value)]
                );
            }
            if (isset($schema->maxLength) && \core_text::strlen($value) > $schema->maxLength) {
                self::invalid(
                    $path,
                    'must be at most ' . $schema->maxLength . ' characters.',
                    'maxLength',
                    ['maximum' => $schema->maxLength, 'actual' => \core_text::strlen($value)]
                );
            }
        }
        if (is_int($value) || is_float($value)) {
            if (isset($schema->minimum) && $value < $schema->minimum) {
                self::invalid(
                    $path,
                    'must be greater than or equal to ' . $schema->minimum . '.',
                    'minimum',
                    ['minimum' => $schema->minimum, 'actual' => $value]
                );
            }
            if (isset($schema->maximum) && $value > $schema->maximum) {
                self::invalid(
                    $path,
                    'must be less than or equal to ' . $schema->maximum . '.',
                    'maximum',
                    ['maximum' => $schema->maximum, 'actual' => $value]
                );
            }
        }
        $param = self::get_param_type($schema);
        if ($value !== null && $param !== null) {
            try {
                // Before MDL-87941, false would fail as a boolean.
                if ($CFG->branch < 502 && $param === PARAM_BOOL && is_bool($value)) {
                    $value = $value ? 1 : 0;
                }
                $value = validate_param($value, $param);
                $value = $param === PARAM_BOOL ? (bool) $value : $value;
            } catch (\core\exception\invalid_parameter_exception $e) {
                self::invalid(
                    $path,
                    'has an invalid format.',
                    'format'
                );
            }
        }
        return $value;
    }

    /**
     * Whether a value matches one of the types.
     *
     * @param string[] $types The types.
     * @param mixed $value The value.
     * @return bool
     */
    protected static function matches_type(array $types, $value): bool {
        foreach ($types as $type) {
            if ($type === 'array' && is_array($value)) {
                return true;
            } else if ($type === 'boolean' && is_bool($value)) {
                return true;
            } else if ($type === 'integer' && is_int($value)) {
                return true;
            } else if ($type === 'null' && $value === null) {
                return true;
            } else if ($type === 'number' && (is_int($value) || is_float($value))) {
                return true;
            } else if ($type === 'object' && is_array($value)) {
                return true;
            } else if ($type === 'string' && is_string($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the PARAM_* type to validate a scalar value.
     *
     * @param \stdClass $schema Schema.
     * @return mixed|null
     */
    protected static function get_param_type(\stdClass $schema) {
        if (isset($schema->{'x-param'})) {
            return $schema->{'x-param'};
        }

        $types = array_values(array_diff((array) ($schema->type ?? []), ['null']));
        if (count($types) !== 1) {
            return null;
        }

        $type = reset($types);
        if ($type === 'boolean') {
            return PARAM_BOOL;
        } else if ($type === 'integer') {
            return PARAM_INT;
        } else if ($type === 'number') {
            return PARAM_FLOAT;
        } else if ($type === 'string') {
            return PARAM_RAW;
        }
        return null;
    }

    /**
     * Format a child object path.
     *
     * @param string $path Parent path.
     * @param string $name Child name.
     * @return string
     */
    protected static function child_path(string $path, string $name): string {
        return $path . '.' . $name;
    }

    /**
     * Format a list item path.
     *
     * @param string $path Parent path.
     * @param int|string $key Item key.
     * @return string
     */
    protected static function item_path(string $path, $key): string {
        return $path . '[' . $key . ']';
    }

    /**
     * Throw an invalid params exception.
     *
     * @param string $path Path.
     * @param string $message Message.
     * @param string $keyword JSON schema keyword.
     * @param array $data Extra error data.
     * @return void
     */
    protected static function invalid(string $path, string $message, string $keyword, array $data = []): void {
        $error = ['path' => $path, 'keyword' => $keyword, 'message' => $message] + $data;
        throw new invalid_params_exception(
            'Validation failed: ' . $path . ' ' . $message,
            ['errors' => [$error]]
        );
    }

    /**
     * Format schema types.
     *
     * @param array $types Types.
     * @return string
     */
    protected static function format_types(array $types): string {
        return implode(' or ', array_map(function ($type) {
            return "'" . $type . "'";
        }, $types));
    }

    /**
     * Format values for display.
     *
     * @param array $values Values.
     * @return string
     */
    protected static function format_values(array $values): string {
        return implode(', ', array_map(function ($value) {
            return "'" . json_encode($value) . "'";
        }, $values));
    }

    /**
     * Get the JSON-ish type name of a value.
     *
     * @param mixed $value Value.
     * @return string
     */
    protected static function type_name($value): string {
        if (is_array($value)) {
            return 'object';
        } else if (is_bool($value)) {
            return 'boolean';
        } else if (is_int($value)) {
            return 'integer';
        } else if (is_float($value)) {
            return 'number';
        } else if ($value === null) {
            return 'null';
        } else if (is_string($value)) {
            return 'string';
        }
        return gettype($value);
    }
}
