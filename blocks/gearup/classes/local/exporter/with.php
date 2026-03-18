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

namespace block_gearup\local\exporter;

/**
 * With.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait with {

    /** @var string[] */
    private $withs = [];

    /**
     * Export the withs.
     *
     * @return string
     */
    private function export_withs(): string {
        return implode(',', $this->withs);
    }

    /**
     * Get the properties.
     *
     * @return array
     */
    protected static function get_with_properties(): array {
        return [
            'withs' => ['type' => PARAM_RAW],
            'haswiths' => ['type' => PARAM_BOOL],
        ];
    }

    /**
     * Get the values.
     *
     * @return array
     */
    protected function get_with_values(): array {
        return [
            'withs' => $this->export_withs(),
            'haswiths' => !empty($this->withs),
        ];
    }

    /**
     * Import withs, reverses the export.
     *
     * @param string $withs
     */
    public function import_withs(string $withs): void {
        $withs = explode(',', $withs);
        foreach ($withs as $with) {
            $this->with(trim($with));
        }
    }

    /**
     * Whether is with a specific with.
     *
     * @param string $with
     * @return bool
     */
    protected function is_with($with) {
        return in_array($with, $this->withs);
    }

    /**
     * Add a with.
     *
     * @param string $with
     */
    public function with(string $with) {
        $with = clean_param($with, PARAM_ALPHANUMEXT);
        if (empty($with) || $this->is_with($with)) {
            return;
        }
        $this->withs[] = $with;
    }

}
