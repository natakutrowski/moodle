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
 * MCP tool definition.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_definition {
    /** @var string Risk flag for tools that read data. */
    public const RISK_READ = 1;
    /** @var string Risk flag for tools that write data. */
    public const RISK_WRITE = 2;
    /** @var string Risk flag for tools that delete data. */
    public const RISK_DELETE = 4;
    /** @var string Risk flag for tools that expose personal data. */
    public const RISK_PERSONAL = 8;
    /** @var string Risk flag for tools that can send messages or notifications. */
    public const RISK_COMMS = 16;

    /** @var string Description. */
    protected $description;
    /** @var schema Input schema. */
    protected $inputschema;
    /** @var string Name. */
    protected $name;
    /** @var schema|null Output schema. */
    protected $outputschema;
    /** @var int Risk bitmask. */
    protected $risk;
    /** @var int[] Supported Level Up XP context modes. */
    protected $supportedmodes;
    /** @var string Title. */
    protected $title;

    /**
     * Constructor.
     *
     * @param string $name Name.
     * @param string $title Title.
     * @param string $description Description.
     * @param int[] $supportedmodes Supported Level Up XP context modes.
     * @param int $risk Risk bitmask.
     * @param schema $inputschema Input schema.
     * @param schema|null $outputschema Output schema.
     */
    public function __construct(
        string $name,
        string $title,
        string $description,
        array $supportedmodes,
        int $risk,
        schema $inputschema,
        ?schema $outputschema = null
    ) {
        $this->description = $description;
        $this->inputschema = $inputschema;
        $this->name = $name;
        $this->outputschema = $outputschema;
        $this->risk = $risk;
        $this->supportedmodes = $supportedmodes;
        $this->title = $title;

        if (empty($this->name)) {
            throw new \coding_exception('Tool definition requires a name.');
        } else if (empty($this->title)) {
            throw new \coding_exception('Tool definition requires a title.');
        } else if (empty($this->description)) {
            throw new \coding_exception('Tool definition requires a description.');
        } else if (empty($this->supportedmodes)) {
            throw new \coding_exception('Tool definition requires a supported mode.');
        } else if (empty($this->risk)) {
            throw new \coding_exception('Tool definition requires at least a risk.');
        }
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Get the input schema.
     *
     * @return schema
     */
    public function get_input_schema(): schema {
        return $this->inputschema;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get the output schema.
     *
     * @return schema|null
     */
    public function get_output_schema(): ?schema {
        return $this->outputschema;
    }

    /**
     * Get the risk bitmask
     *
     * @return int
     */
    public function get_risk(): int {
        return $this->risk;
    }

    /**
     * Get the supported Level Up XP context modes.
     *
     * @return int[]
     */
    public function get_supported_modes(): array {
        return $this->supportedmodes;
    }

    /**
     * Get the title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }
}
