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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\tests\utils;

use block_gearup\local\utils\json_utils;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     block_gearup\local\utils\json_utils
 */
final class json_utils_test extends base_testcase {

    /**
     * Decode to list provider.
     *
     * @return array
     */
    public static function decode_to_list_provider(): array {
        return [
            ['[]', []],
            ['[1]', [1]],
            ['[1, 2, 3]', [1, 2, 3]],
            ['["a", "b", 10]', ["a", "b", 10]],
            ['[{}]', [[]]],
            ['[{"a":"2"}]', [["a" => "2"]]],
            ['{"0": 10, "1": 20, "2": 30}', [10, 20, 30]],
            ['{"0": 10, "5": 20, "10": 30}', [10, 20, 30]],
            ['{"1": 10, "2": 20, "3": 30}', [10, 20, 30]],
            ['{}', []],
            ['{"foo": [1]}', [[1]]],
            [null, []],
            [false, []],
            [true, []],
            ['0', []],
            ['abc', []],
            [0, []],
            [102030, []],
        ];
    }

    /**
     * Decode to list.
     *
     * @dataProvider decode_to_list_provider
     * @param mixed $data The data.
     * @param mixed $expected The expected.
     */
    public function test_decode_to_list($data, $expected): void {
        $this->assertEquals($expected, json_utils::decode_to_list($data));
    }

    /**
     * Decode to list provider.
     *
     * @return array
     */
    public static function encode_as_list_provider(): array {
        return [
            [[], '[]'],
            [[1, 2, 3], '[1,2,3]'],
            [["0" => 10, "1" => 20, "2" => 33], '[10,20,33]'],
            [["0" => 10, "3" => 20, "8" => 33], '[10,20,33]'],
            [["1" => 10, "2" => 20, "3" => 33], '[10,20,33]'],
            [["1" => (object) ['a' => 'b']], '[{"a":"b"}]'],
        ];
    }

    /**
     * Encode as list.
     *
     * @dataProvider encode_as_list_provider
     * @param mixed $data The data.
     * @param mixed $expected The expected.
     */
    public function test_encode_as_list($data, $expected): void {
        $this->assertEquals($expected, json_utils::encode_as_list($data));
    }

}
