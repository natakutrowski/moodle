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
 * Small JSON schema builder for MCP tools.
 *
 * This only covers the subset used by our MCP tools. Object properties are required by default.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class schema {
    /** @var array */
    protected $schema;
    /** @var schema[]|null */
    protected $properties = null;
    /** @var schema|null */
    protected $items = null;
    /** @var bool */
    protected $required = true;

    /**
     * Constructor.
     *
     * @param array $schema JSON schema fragment.
     */
    protected function __construct(array $schema) {
        $this->schema = $schema;
    }

    /**
     * Array schema.
     *
     * @param schema $items Item schema.
     * @param string|string[] $description Description.
     * @return self
     */
    public static function array_of(schema $items, $description = ''): self {
        $schema = new self(['type' => 'array']);
        $schema->items = $items;
        return $schema->description($description);
    }

    /**
     * Boolean schema.
     *
     * @param string|string[] $description Description.
     * @return self
     */
    public static function boolean($description = ''): self {
        return self::typed('boolean', $description);
    }

    /**
     * Integer schema.
     *
     * @param string|string[] $description Description.
     * @return self
     */
    public static function integer($description = ''): self {
        return self::typed('integer', $description);
    }

    /**
     * Number schema.
     *
     * @param string|string[] $description Description.
     * @return self
     */
    public static function number($description = ''): self {
        return self::typed('number', $description);
    }

    /**
     * Object schema.
     *
     * @param schema[] $properties Properties.
     * @param string|string[] $description Description.
     * @return self
     */
    public static function object(array $properties = [], $description = ''): self {
        $schema = new self([
            'type' => 'object',
            'additionalProperties' => false,
        ]);
        $schema->properties = $properties;
        return $schema->description($description);
    }

    /**
     * Raw schema keywords.
     *
     * @param array $schema JSON schema fragment.
     * @return self
     */
    public static function raw(array $schema): self {
        return new self($schema);
    }

    /**
     * String schema.
     *
     * @param string|string[] $description Description.
     * @return self
     */
    public static function string($description = ''): self {
        return self::typed('string', $description);
    }

    /**
     * Add a description.
     *
     * @param string|string[] $description Description.
     * @return self
     */
    public function description($description): self {
        $description = self::normalise_description($description);
        if ($description !== '') {
            $this->schema['description'] = $description;
        }
        return $this;
    }

    /**
     * Add enum values.
     *
     * @param array $values Values.
     * @return self
     */
    public function enum(array $values): self {
        $this->schema['enum'] = array_values($values);
        return $this;
    }

    /**
     * Add a maximum value.
     *
     * @param int|float $maximum Maximum.
     * @return self
     */
    public function maximum($maximum): self {
        $this->require_types(['integer', 'number'], 'maximum');
        $this->schema['maximum'] = $maximum;
        return $this;
    }

    /**
     * Add a maximum string length.
     *
     * @param int $length Length.
     * @return self
     */
    public function max_length(int $length): self {
        $this->require_types(['string'], 'maxLength');
        $this->schema['maxLength'] = $length;
        return $this;
    }

    /**
     * Add a minimum value.
     *
     * @param int|float $minimum Minimum.
     * @return self
     */
    public function minimum($minimum): self {
        $this->require_types(['integer', 'number'], 'minimum');
        $this->schema['minimum'] = $minimum;
        return $this;
    }

    /**
     * Add a minimum string length.
     *
     * @param int $length Length.
     * @return self
     */
    public function min_length(int $length): self {
        $this->require_types(['string'], 'minLength');
        $this->schema['minLength'] = $length;
        return $this;
    }

    /**
     * Allow null.
     *
     * @return self
     */
    public function nullable(): self {
        $types = (array) ($this->schema['type'] ?? []);
        if (!in_array('null', $types, true)) {
            $types[] = 'null';
        }
        $this->schema['type'] = count($types) === 1 ? reset($types) : array_values($types);
        return $this;
    }

    /**
     * Mark as optional when used as an object property.
     *
     * @return self
     */
    public function optional(): self {
        $this->required = false;
        return $this;
    }

    /**
     * Add an object property.
     *
     * @param string $name Property name.
     * @param schema $schema Property schema.
     * @return self
     */
    public function property(string $name, schema $schema): self {
        $this->require_types(['object'], 'properties');
        if ($this->properties === null) {
            $this->properties = [];
        }
        $this->properties[$name] = $schema;
        return $this;
    }

    /**
     * Add an optional Moodle PARAM_* hint.
     *
     * @param mixed $param PARAM_* constant.
     * @return self
     */
    public function param($param): self {
        $this->require_types(['string'], 'x-param');
        $this->schema['x-param'] = $param;
        return $this;
    }

    /**
     * Mark as required when used as an object property.
     *
     * @return self
     */
    public function required(): self {
        $this->required = true;
        return $this;
    }

    /**
     * Remove an object property.
     *
     * @param string $name Property name.
     * @return self
     */
    public function remove_property(string $name): self {
        $this->require_types(['object'], 'properties');
        if ($this->properties !== null) {
            unset($this->properties[$name]);
        }
        return $this;
    }

    /**
     * Add an object property before existing properties.
     *
     * @param string $name Property name.
     * @param schema $schema Property schema.
     * @return self
     */
    public function unshift_property(string $name, schema $schema): self {
        $this->require_types(['object'], 'properties');
        if ($this->properties === null) {
            $this->properties = [];
        }
        $this->properties = [$name => $schema] + $this->properties;
        return $this;
    }

    /**
     * Get as an object ready for JSON encoding.
     *
     * @param bool $includeinternal Include internal validation keywords.
     * @return \stdClass
     */
    public function to_object(bool $includeinternal = false): \stdClass {
        $schema = (object) ($includeinternal ? $this->schema : self::strip_internal_keywords($this->schema));

        if ($this->properties !== null) {
            $schema->properties = new \stdClass();
            $schema->required = [];

            foreach ($this->properties as $name => $property) {
                if ($property->is_required()) {
                    $schema->required[] = $name;
                }
                $schema->properties->{$name} = $property->to_object($includeinternal);
            }
        }

        if ($this->items !== null) {
            $schema->items = $this->items->to_object($includeinternal);
        }

        return $schema;
    }

    /**
     * Whether the schema is required when used as an object property.
     *
     * @return bool
     */
    protected function is_required(): bool {
        return $this->required;
    }

    /**
     * Normalise a description.
     *
     * @param string|string[] $description Description.
     * @return string
     */
    protected static function normalise_description($description): string {
        if (is_string($description)) {
            return trim($description);
        }

        if (!is_array($description)) {
            throw new \coding_exception('Schema description must be a string or a list of strings.');
        }

        $parts = [];
        foreach ($description as $part) {
            if (!is_string($part)) {
                throw new \coding_exception('Schema description parts must be strings.');
            }
            $part = trim($part);
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Strip internal extension keywords before publishing schemas.
     *
     * @param array $schema Schema.
     * @return array
     */
    protected static function strip_internal_keywords(array $schema): array {
        foreach ($schema as $key => $value) {
            if (is_string($key) && strpos($key, 'x-') === 0) {
                unset($schema[$key]);
                continue;
            }

            if (is_array($value)) {
                $schema[$key] = self::strip_internal_keywords($value);
            }
        }

        return $schema;
    }

    /**
     * Require a schema type before applying a type-specific keyword.
     *
     * @param array $allowed Allowed JSON schema types.
     * @param string $keyword Keyword.
     * @return void
     */
    protected function require_types(array $allowed, string $keyword): void {
        $types = array_values(array_diff((array) ($this->schema['type'] ?? []), ['null']));
        sort($types);
        sort($allowed);
        if (!$types || array_diff($types, $allowed)) {
            throw new \coding_exception(
                'Cannot apply JSON schema keyword ' . $keyword . ' to type ' . implode('|', $types)
            );
        }
    }

    /**
     * Typed schema.
     *
     * @param string $type Type.
     * @param string|string[] $description Description.
     * @return self
     */
    protected static function typed(string $type, $description): self {
        return (new self(['type' => $type]))->description($description);
    }
}
