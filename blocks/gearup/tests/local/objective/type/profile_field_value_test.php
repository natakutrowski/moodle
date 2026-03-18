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

namespace block_gearup\local\objective\type;

use block_gearup\di;
use block_gearup\local\action\profile_updated;
use block_gearup\local\action\user_modified;
use block_gearup\local\objective\type\profile_field_value;
use block_gearup\tests\base_testcase;
use core\event\user_updated;
use core_user;
use Generator;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\objective\type\profile_field_value
 */
final class profile_field_value_test extends base_testcase {

    /** @var \block_gearup\local\mission\mission */
    protected $mission;
    /** @var \block_gearup\local\mission\mission_instance */
    protected $missioninst;
    /** @var \stdClass */
    protected $u1;

    public function setUp(): void {
        parent::setUp();
        $gudg = $this->generator;
        $this->mission = $gudg->mock_quest();
        $this->missioninst = $gudg->mock_mission_instance($this->mission);
        $this->u1 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'name' => 'Text field',
            'shortname' => 'textfield',
        ]);
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function basic_consumption_counter_provider(): Generator {
        $int = profile_field_value::VALUE_TYPE_INT;
        $text = profile_field_value::VALUE_TYPE_TEXT;
        $bool = profile_field_value::VALUE_TYPE_BOOL;

        // Testing int equality.
        $config = [
            't' => $int,
            'c' => 'eq',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$config, -10, 0];
        yield [$config, 0, 0];
        yield [$config, 9, 0];
        yield [$config, 10, 1];
        yield [$config, 11, 0];

        // Testing int inequality.
        $config = [
            't' => $int,
            'c' => 'neq',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$config, -10, 1];
        yield [$config, 0, 1];
        yield [$config, 9, 1];
        yield [$config, 10, 0];
        yield [$config, 11, 1];

        // Testing int greater than.
        $config = [
            't' => $int,
            'c' => 'gt',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$config, -10, 0];
        yield [$config, 0, 0];
        yield [$config, 9, 0];
        yield [$config, 10, 0];
        yield [$config, 11, 1];
        yield [$config, 100, 1];

        // Testing int greater than or equal to.
        $config = [
            't' => $int,
            'c' => 'gte',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$config, -10, 0];
        yield [$config, 0, 0];
        yield [$config, 9, 0];
        yield [$config, 10, 1];
        yield [$config, 11, 1];
        yield [$config, 100, 1];

        // Testing int less than.
        $config = [
            't' => $int,
            'c' => 'lt',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$config, -10, 1];
        yield [$config, 0, 1];
        yield [$config, 9, 1];
        yield [$config, 10, 0];
        yield [$config, 11, 0];
        yield [$config, 100, 0];

        // Testing int less than or equal to.
        $config = [
            't' => $int,
            'c' => 'lte',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$config, -10, 1];
        yield [$config, 0, 1];
        yield [$config, 9, 1];
        yield [$config, 10, 1];
        yield [$config, 11, 0];
        yield [$config, 100, 0];

        // Testing text equality.
        $config = [
            't' => $text,
            'c' => 'eq',
            'v' => 'foo',
        ];
        yield [$config, 'foo', 1];
        yield [$config, 'Foo', 0];
        yield [$config, 'bar', 0];
        yield [$config, 'Bar', 0];

        // Testing text inequality.
        $config = [
            't' => $text,
            'c' => 'neq',
            'v' => 'foo',
        ];
        yield [$config, 'Foo', 1];
        yield [$config, 'foo', 0];
        yield [$config, 'Bar', 1];
        yield [$config, 'bar', 1];

        // Testing text contains.
        $config = [
            't' => $text,
            'c' => 'has',
            'v' => 'foo',
        ];
        yield [$config, 'Foobar', 1];
        yield [$config, 'foobar', 1];
        yield [$config, 'barfoo', 1];
        yield [$config, 'barFoo', 1];
        yield [$config, 'the Foo!', 1];
        yield [$config, 'stool', 0];
        yield [$config, 'baz', 0];
        yield [$config, 'oof', 0];

        // Testing text does not contain.
        $config = [
            't' => $text,
            'c' => 'nhas',
            'v' => 'foo',
        ];
        yield [$config, 'Foobar', 0];
        yield [$config, 'foobar', 0];
        yield [$config, 'barfoo', 0];
        yield [$config, 'barFoo', 0];
        yield [$config, 'the Foo!', 0];
        yield [$config, 'stool', 1];
        yield [$config, 'baz', 1];
        yield [$config, 'oof', 1];

        // Testing boolean true.
        $config = [
            't' => $bool,
            'c' => 'true',
        ];
        yield [$config, 1, 1];
        yield [$config, '1', 1];
        yield [$config, 'yes', 1];
        yield [$config, 'YES', 1];
        yield [$config, 'enabled', 1];
        yield [$config, 'Enabled', 1];
        yield [$config, 'true', 1];
        yield [$config, 'TRUE', 1];
        yield [$config, true, 1];
        yield [$config, 'on', 1];
        yield [$config, 'On', 1];
        yield [$config, false, 0];
        yield [$config, 0, 0];
        yield [$config, 'no', 0];
        yield [$config, 'None', 0];
        yield [$config, 'disabled', 0];
        yield [$config, '', 0];

        // Testing boolean true.
        $config = [
            't' => $bool,
            'c' => 'false',
        ];
        yield [$config, 1, 0];
        yield [$config, '1', 0];
        yield [$config, 'yes', 0];
        yield [$config, 'YES', 0];
        yield [$config, 'enabled', 0];
        yield [$config, 'Enabled', 0];
        yield [$config, 'true', 0];
        yield [$config, 'TRUE', 0];
        yield [$config, true, 0];
        yield [$config, 'on', 0];
        yield [$config, 'On', 0];
        yield [$config, 0, 1];
        yield [$config, false, 1];
        yield [$config, 'false', 1];
        yield [$config, 'no', 1];
        yield [$config, 'None', 1];
        yield [$config, 'disabled', 1];
        yield [$config, '', 1];
        yield [$config, 'nothing', 1];
        yield [$config, 'haha', 1];
    }

    /**
     * Test basic comparison.
     *
     * @dataProvider basic_consumption_counter_provider
     */
    public function test_basic_consumption_counter($config, $fieldvalue, int $expectedcounter): void {
        $gudg = $this->generator;

        $typeconfig = (object) ($config + ['f' => 'profile_field_textfield']);
        $action = new user_modified($this->u1->id);

        $type = new profile_field_value();
        $obj = $gudg->mock_objective($type, $typeconfig, ['countneeded' => 1]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $this->u1->id]);

        profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield' => $fieldvalue]);

        $this->assertTrue($type->is_action_compatible($action));
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $this->missioninst);
        $this->assertEquals($expectedcounter, $objinst->get_counter());
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function tracking_counter_provider(): Generator {
        $int = profile_field_value::VALUE_TYPE_INT;

        // Testing equality tracking.
        $data = ['countneeded' => 10];
        $config = [
            't' => $int,
            'c' => 'eq',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, [[-10, 0], [0, 0], [4, 4], [8, 8], [10, 10], [11, 0]]];

        // Tracking disabled if value is less than 1.
        $data['countneeded'] = 1;
        $config['v'] = 0;
        yield [$data, $config, [[-10, 0], [0, 1], [4, 0], [8, 0], [10, 0], [11, 0]]];
        $config['v'] = -5;
        yield [$data, $config, [[-10, 0], [-7, 0], [-5, 1], [-1, 0], [0, 0], [1, 0], [10, 0]]];

        // Tracking disabled if count needed is not greater than 1.
        $config['v'] = 10;
        $data['countneeded'] = 1;
        yield [$data, $config, [[-10, 0], [0, 0], [4, 0], [8, 0], [10, 1], [11, 0]]];

        // Testing inequality tracking (no tracking).
        $data = ['countneeded' => 1];
        $config = [
            't' => $int,
            'c' => 'neq',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, [[-10, 1], [-5, 1], [0, 1], [4, 1], [8, 1], [10, 0], [11, 1]]];

        // // Testing greater than tracking (no tracking).
        $data = ['countneeded' => 1];
        $config = [
            't' => $int,
            'c' => 'gt',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, [[-10, 0], [-5, 0], [0, 0], [4, 0], [8, 0], [10, 0], [11, 1], [20, 1]]];

        // Testing less than tracking (no tracking).
        $data = ['countneeded' => 1];
        $config = [
            't' => $int,
            'c' => 'lt',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, [[-10, 1], [-5, 1], [0, 1], [4, 1], [8, 1], [10, 0], [11, 0]]];

        // Testing less than or equal tracking (no tracking).
        $data = ['countneeded' => 1];
        $config = [
            't' => $int,
            'c' => 'lte',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, [[-10, 1], [-5, 1], [0, 1], [4, 1], [8, 1], [10, 1], [11, 0]]];

        // Testing greater than or equal to tracking.
        $data = ['countneeded' => 10];
        $config = [
            't' => $int,
            'c' => 'gte',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, [[-10, 0], [-5, 0], [0, 0], [4, 4], [8, 8], [10, 10], [11, 10], [20, 10]]];
        yield [$data, $config, [[0, 0], [5, 5], [10, 10], [5, 5], [0, 0], [1, 1], [-3, 0]]];

        // // Disabled with other target, in which case count needed is expected to be 1.
        $data['countneeded'] = 1;
        $config['v'] = -5;
        yield [$data, $config, [[-10, 0], [-5, 1], [0, 1], [4, 1], [8, 1], [10, 1], [11, 1], [20, 1]]];
        $config['v'] = 0;
        yield [$data, $config, [[-10, 0], [-5, 0], [0, 1], [4, 1], [8, 1], [10, 1], [11, 1], [20, 1]]];
        $config['v'] = 1;
        yield [$data, $config, [[-10, 0], [-5, 0], [0, 0], [1, 1], [4, 1], [8, 1], [10, 1], [11, 1], [20, 1]]];

        // Disabled with countneeded 1.
        $data['countneeded'] = 1;
        $config['v'] = 10;
        yield [$data, $config, [[-10, 0], [-5, 0], [0, 0], [1, 0], [4, 0], [8, 0], [10, 1], [11, 1], [20, 1]]];

        // Testing equality tracking with keepbest.
        $data = ['countneeded' => 10];
        $config = [
            't' => $int,
            'c' => 'eq',
            'v' => 10,
            'track' => true,
            'keepbest' => true,
        ];
        yield [$data, $config, [[-10, 0], [0, 0], [1, 1], [5, 5], [0, 5], [1, 5], [8, 8], [5, 8], [10, 10], [15, 10], [20, 10]]];

        // Testing greater than or equal to tracking with keepbest.
        $data = ['countneeded' => 10];
        $config = [
            't' => $int,
            'c' => 'gte',
            'v' => 10,
            'track' => true,
            'keepbest' => true,
        ];
        yield [$data, $config, [[-10, 0], [0, 0], [1, 1], [5, 5], [0, 5], [1, 5], [8, 8], [5, 8], [10, 10], [15, 10], [20, 10]]];
    }

    /**
     * Test tracking.
     *
     * @dataProvider tracking_counter_provider
     */
    public function test_consumption_with_tracking($data, $config, $increments): void {
        $gudg = $this->generator;

        $typeconfig = (object) ($config + ['f' => 'profile_field_textfield']);
        $action = new user_modified($this->u1->id);

        $type = new profile_field_value();
        $obj = $gudg->mock_objective($type, $typeconfig, $data + ['countneeded' => 1]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $this->u1->id]);

        foreach ($increments as $n => $increment) {
            $fieldvalue = $increment[0];
            $expectedcounter = $increment[1];

            // Save the field value.
            profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield' => $fieldvalue]);

            // Check the action compatibility and consume it.
            $this->assertTrue($type->is_action_compatible($action));
            $type->consume_action($action, $objinst, $this->missioninst);
            $this->assertEquals($expectedcounter, $objinst->get_counter(), "Failed at increment $n");
        }
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function initialisation_from_value_provider(): Generator {
        $int = profile_field_value::VALUE_TYPE_INT;
        $text = profile_field_value::VALUE_TYPE_TEXT;
        $bool = profile_field_value::VALUE_TYPE_BOOL;

        $data = ['countneeded' => 1];

        // Basic equality test.
        $config = [
            't' => $int,
            'c' => 'eq',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 0];
        yield [$data, $config, 0, 0];
        yield [$data, $config, 1, 0];
        yield [$data, $config, 5, 0];
        yield [$data, $config, 10, 1];
        yield [$data, $config, 15, 0];

        // Basic non-equality test.
        $config = [
            't' => $int,
            'c' => 'neq',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 1];
        yield [$data, $config, 0, 1];
        yield [$data, $config, 1, 1];
        yield [$data, $config, 5, 1];
        yield [$data, $config, 10, 0];
        yield [$data, $config, 15, 1];

        // Basic greater than test.
        $config = [
            't' => $int,
            'c' => 'gt',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 0];
        yield [$data, $config, 0, 0];
        yield [$data, $config, 1, 0];
        yield [$data, $config, 5, 0];
        yield [$data, $config, 10, 0];
        yield [$data, $config, 15, 1];
        yield [$data, $config, 20, 1];

        // Basic greater than or equal to test.
        $config = [
            't' => $int,
            'c' => 'gte',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 0];
        yield [$data, $config, 0, 0];
        yield [$data, $config, 1, 0];
        yield [$data, $config, 5, 0];
        yield [$data, $config, 10, 1];
        yield [$data, $config, 15, 1];
        yield [$data, $config, 20, 1];

        // Basic less than test.
        $config = [
            't' => $int,
            'c' => 'lt',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 1];
        yield [$data, $config, 0, 1];
        yield [$data, $config, 1, 1];
        yield [$data, $config, 5, 1];
        yield [$data, $config, 10, 0];
        yield [$data, $config, 15, 0];
        yield [$data, $config, 20, 0];

        // Basic less than or equal to test.
        $config = [
            't' => $int,
            'c' => 'lte',
            'v' => 10,
            'track' => false,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 1];
        yield [$data, $config, 0, 1];
        yield [$data, $config, 1, 1];
        yield [$data, $config, 5, 1];
        yield [$data, $config, 10, 1];
        yield [$data, $config, 15, 0];
        yield [$data, $config, 20, 0];

        // Basic equality with tracking.
        $data['countneeded'] = 10;
        $config = [
            't' => $int,
            'c' => 'eq',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 0];
        yield [$data, $config, 0, 0];
        yield [$data, $config, 1, 1];
        yield [$data, $config, 5, 5];
        yield [$data, $config, 10, 10];
        yield [$data, $config, 15, 0];

        // Basic greater than or equal with tracking.
        $config = [
            't' => $int,
            'c' => 'gte',
            'v' => 10,
            'track' => true,
            'keepbest' => false,
        ];
        yield [$data, $config, -10, 0];
        yield [$data, $config, 0, 0];
        yield [$data, $config, 1, 1];
        yield [$data, $config, 5, 5];
        yield [$data, $config, 10, 10];
        yield [$data, $config, 15, 10];
        yield [$data, $config, 20, 10];

        // Basic text equality test.
        $config = [
            't' => $text,
            'c' => 'eq',
            'v' => 'foo',
        ];
        yield [$data, $config, 'foo', 1];
        yield [$data, $config, 'Foo', 0];
        yield [$data, $config, 'bar', 0];
        yield [$data, $config, 'Bar', 0];
        yield [$data, $config, 'the football match', 0];

        // Basic text non-equality test.
        $config = [
            't' => $text,
            'c' => 'neq',
            'v' => 'foo',
        ];
        yield [$data, $config, 'Foo', 1];
        yield [$data, $config, 'foo', 0];
        yield [$data, $config, 'Bar', 1];
        yield [$data, $config, 'bar', 1];
        yield [$data, $config, 'the football match', 1];

        // Basic text contains test.
        $config = [
            't' => $text,
            'c' => 'has',
            'v' => 'foo',
        ];
        yield [$data, $config, 'Foobar', 1];
        yield [$data, $config, 'foobar', 1];
        yield [$data, $config, 'barfoo', 1];
        yield [$data, $config, 'barFoo', 1];
        yield [$data, $config, 'the Football match.', 1];
        yield [$data, $config, 'stool', 0];
        yield [$data, $config, 'baz', 0];
        yield [$data, $config, 'oof', 0];

        // Basic text does not contain test.
        $config = [
            't' => $text,
            'c' => 'nhas',
            'v' => 'foo',
        ];
        yield [$data, $config, 'Foobar', 0];
        yield [$data, $config, 'foobar', 0];
        yield [$data, $config, 'barfoo', 0];
        yield [$data, $config, 'barFoo', 0];
        yield [$data, $config, 'the Football match.', 0];
        yield [$data, $config, 'stool', 1];
        yield [$data, $config, 'baz', 1];
        yield [$data, $config, 'oof', 1];

        // Basic boolean true test.
        $config = [
            't' => $bool,
            'c' => 'true',
        ];
        yield [$data, $config, 1, 1];
        yield [$data, $config, '1', 1];
        yield [$data, $config, 'yes', 1];
        yield [$data, $config, 'YES', 1];
        yield [$data, $config, 'enabled', 1];
        yield [$data, $config, 'Enabled', 1];
        yield [$data, $config, 'true', 1];
        yield [$data, $config, 'TRUE', 1];
        yield [$data, $config, true, 1];
        yield [$data, $config, 'on', 1];
        yield [$data, $config, 'On', 1];
        yield [$data, $config, false, 0];
        yield [$data, $config, 0, 0];
        yield [$data, $config, 'no', 0];
        yield [$data, $config, 'None', 0];
        yield [$data, $config, 'disabled', 0];
        yield [$data, $config, '', 0];

        // Basic boolean false test.
        $config = [
            't' => $bool,
            'c' => 'false',
        ];
        yield [$data, $config, 1, 0];
        yield [$data, $config, '1', 0];
        yield [$data, $config, 'yes', 0];
        yield [$data, $config, 'YES', 0];
        yield [$data, $config, 'enabled', 0];
        yield [$data, $config, 'Enabled', 0];
        yield [$data, $config, 'true', 0];
        yield [$data, $config, 'TRUE', 0];
        yield [$data, $config, true, 0];
        yield [$data, $config, 'on', 0];
        yield [$data, $config, 'On', 0];
        yield [$data, $config, 0, 1];
        yield [$data, $config, false, 1];
        yield [$data, $config, 'false', 1];
        yield [$data, $config, 'no', 1];
        yield [$data, $config, 'None', 1];
        yield [$data, $config, 'disabled', 1];
        yield [$data, $config, '', 1];
        yield [$data, $config, 'nothing', 1];
        yield [$data, $config, 'haha', 1];
    }

    /**
     * Test initialisation from field value.
     *
     * @param array $data
     * @param array $config
     * @param mixed $fieldvalue
     * @param int $expected
     * @dataProvider initialisation_from_value_provider
     */
    public function test_initialisation_from_field_value($data, $config, $fieldvalue, $expected): void {
        $gudg = $this->generator;
        $mo = di::get('mission_operator');

        $mission = $gudg->create_persisted_mission(['objectives' => [
            $data + [
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield']),
            ]]]);

        profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield' => $fieldvalue]);

        $missioninst = $mo->assign_mission($mission, $this->u1->id);
        $objinst = $missioninst->get_objective_instances()[0];

        $this->assertEquals($expected, $objinst->get_counter());
    }

    /**
     * Test initialisation from default value.
     *
     * @param array $data
     * @param array $config
     * @param mixed $defaultvalue
     * @param int $expected
     * @dataProvider initialisation_from_value_provider
     */
    public function test_initialisation_from_default_value($data, $config, $defaultvalue, $expected): void {
        $gudg = $this->generator;
        $mo = di::get('mission_operator');

        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'name' => 'Text field',
            'shortname' => 'textfieldwithdefault',
            'defaultdata' => $defaultvalue,
        ]);
        $mission = $gudg->create_persisted_mission(['objectives' => [
            $data + [
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfieldwithdefault']),
            ]]]);

        $missioninst = $mo->assign_mission($mission, $this->u1->id);
        $objinst = $missioninst->get_objective_instances()[0];

        $this->assertEquals($expected, $objinst->get_counter());
    }

    /**
     * Test correct field is used.
     */
    public function test_correct_field_is_used(): void {
        $gudg = $this->generator;
        $mo = di::get('mission_operator');
        $ap = di::get('action_processor');
        $repo = di::get('repository');

        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'name' => 'Text field',
            'shortname' => 'textfield2',
        ]);

        $config = ['t' => 'text', 'c' => 'eq', 'v' => 'good'];
        $mission1 = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield']),
        ]]]);
        $config = ['t' => 'text', 'c' => 'eq', 'v' => 'bad'];
        $mission2 = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield2']),
        ]]]);

        $missioninst1 = $mo->assign_mission($mission1, $this->u1->id);
        $missioninst2 = $mo->assign_mission($mission2, $this->u1->id);

        profile_save_data((object) [
            'id' => $this->u1->id,
            'profile_field_textfield' => 'bad',
            'profile_field_textfield2' => 'good',
        ]);

        $missioninst1 = $repo->get_instance($missioninst1->get_id());
        $missioninst2 = $repo->get_instance($missioninst2->get_id());
        $objinst1 = $missioninst1->get_objective_instances()[0];
        $objinst2 = $missioninst2->get_objective_instances()[0];

        $this->assertEquals(0, $objinst1->get_counter());
        $this->assertEquals(0, $objinst2->get_counter());

        profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield' => 'good']);
        $ap->process_action(new user_modified($this->u1->id));

        $missioninst1 = $repo->get_instance($missioninst1->get_id());
        $missioninst2 = $repo->get_instance($missioninst2->get_id());
        $objinst1 = $missioninst1->get_objective_instances()[0];
        $objinst2 = $missioninst2->get_objective_instances()[0];

        $this->assertEquals(1, $objinst1->get_counter());
        $this->assertEquals(0, $objinst2->get_counter());
    }

    /**
     * Test correct field is used in initialisation.
     */
    public function test_correct_field_is_used_in_initialisation(): void {
        $gudg = $this->generator;
        $mo = di::get('mission_operator');

        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'name' => 'Text field',
            'shortname' => 'textfield2',
        ]);
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'name' => 'Text field',
            'shortname' => 'textfield3',
        ]);

        $config = ['t' => 'text', 'c' => 'eq', 'v' => 'good'];
        $mission1 = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield']),
            ]]]);
        $config = ['t' => 'text', 'c' => 'neq', 'v' => 'good'];
        $mission2 = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield2']),
        ]]]);
        $config = ['t' => 'text', 'c' => 'eq', 'v' => 'good'];
        $mission3 = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield3']),
        ]]]);

        profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield' => 'bad']);
        profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield2' => 'good']);
        profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield3' => 'good']);

        $missioninst1 = $mo->assign_mission($mission1, $this->u1->id);
        $missioninst2 = $mo->assign_mission($mission2, $this->u1->id);
        $missioninst3 = $mo->assign_mission($mission3, $this->u1->id);
        $objinst1 = $missioninst1->get_objective_instances()[0];
        $objinst2 = $missioninst2->get_objective_instances()[0];
        $objinst3 = $missioninst3->get_objective_instances()[0];

        $this->assertEquals(0, $objinst1->get_counter());
        $this->assertEquals(0, $objinst2->get_counter());
        $this->assertEquals(1, $objinst3->get_counter());
    }

    /**
     * Test graceful handling of missing field.
     */
    public function test_graceful_handling_of_missing_field(): void {
        $gudg = $this->generator;
        $mo = di::get('mission_operator');
        $ap = di::get('action_processor');

        $config = ['t' => 'text', 'c' => 'eq', 'v' => 'good'];
        $mission1 = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_invalidfield']),
            ]]]);
        $config = ['t' => 'text', 'c' => 'neq', 'v' => 'good'];
        $mission2 = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_invalidfield2']),
        ]]]);

        $missioninst1 = $mo->assign_mission($mission1, $this->u1->id);
        $missioninst2 = $mo->assign_mission($mission2, $this->u1->id);
        $objinst1 = $missioninst1->get_objective_instances()[0];
        $objinst2 = $missioninst2->get_objective_instances()[0];
        $this->assertEquals(0, $objinst1->get_counter());
        $this->assertEquals(0, $objinst2->get_counter());

        $ap->process_action(new user_modified($this->u1->id));

        $missioninst1 = $mo->assign_mission($mission1, $this->u1->id);
        $missioninst2 = $mo->assign_mission($mission2, $this->u1->id);
        $objinst1 = $missioninst1->get_objective_instances()[0];
        $objinst2 = $missioninst2->get_objective_instances()[0];
        $this->assertEquals(0, $objinst1->get_counter());
        $this->assertEquals(0, $objinst2->get_counter());
    }

    /**
     * Test correct user is used.
     */
    public function test_correct_user_is_used(): void {
        $gudg = $this->generator;
        $ap = di::get('action_processor');
        $repo = di::get('repository');

        $u2 = $this->getDataGenerator()->create_user();

        $mission = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ['t' => 'text', 'c' => 'eq', 'v' => 'good', 'f' => 'profile_field_textfield'],
        ]]]);

        $mi1 = $gudg->create_recruit(['missionid' => $mission->get_id(), 'subjectid' => $this->u1->id]);
        $mi2 = $gudg->create_recruit(['missionid' => $mission->get_id(), 'subjectid' => $u2->id]);
        $this->assertEquals(0, $mi1->get_objective_instances()[0]->get_counter());
        $this->assertEquals(0, $mi2->get_objective_instances()[0]->get_counter());

        profile_save_data((object) ['id' => $u2->id, 'profile_field_textfield' => 'good']);

        $ap->process_action(new user_modified($this->u1->id));
        $ap->process_action(new user_modified($u2->id));

        $mi1 = $repo->get_instance($mi1->get_id());
        $mi2 = $repo->get_instance($mi2->get_id());

        $this->assertEquals(0, $mi1->get_objective_instances()[0]->get_counter());
        $this->assertEquals(1, $mi2->get_objective_instances()[0]->get_counter());
    }

    /**
     * Test correct user is used during initialisation.
     */
    public function test_correct_user_is_used_during_initialisation(): void {
        $gudg = $this->generator;
        $u2 = $this->getDataGenerator()->create_user();

        $mission = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ['t' => 'text', 'c' => 'eq', 'v' => 'good', 'f' => 'profile_field_textfield'],
        ]]]);

        profile_save_data((object) ['id' => $u2->id, 'profile_field_textfield' => 'good']);

        $mi1 = $gudg->create_recruit(['missionid' => $mission->get_id(), 'subjectid' => $this->u1->id]);
        $mi2 = $gudg->create_recruit(['missionid' => $mission->get_id(), 'subjectid' => $u2->id]);

        $this->assertEquals(0, $mi1->get_objective_instances()[0]->get_counter());
        $this->assertEquals(1, $mi2->get_objective_instances()[0]->get_counter());
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function supported_action_types_provider(): Generator {
        yield [user_modified::class];
        yield [profile_updated::class];
    }

    /**
     * Test correct supported action types.
     *
     * @dataProvider supported_action_types_provider
     */
    public function test_supported_action_types($actiontype): void {
        $gudg = $this->generator;
        $ap = di::get('action_processor');

        $config = ['t' => 'text', 'c' => 'eq', 'v' => 'good'];
        $mission = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield']),
        ]]]);
        $missioninst = $gudg->create_recruit(['missionid' => $mission->get_id(), 'subjectid' => $this->u1->id]);

        profile_save_data((object) ['id' => $this->u1->id, 'profile_field_textfield' => 'good']);
        if ($actiontype === user_modified::class) {
            $action = new user_modified($this->u1->id);
        } else if ($actiontype === profile_updated::class) {
            $action = new profile_updated($this->u1->id);
        } else {
            throw new \coding_exception('Unsupported action type');
        }

        $ap->process_action($action);
        $missioninst = di::get('repository')->get_instance($missioninst->get_id());
        $objinst = $missioninst->get_objective_instances()[0];
        $this->assertEquals(1, $objinst->get_counter());
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function updated_user_event_provider(): Generator {
        yield ['u1', 'u1', 'u1', 1];
        yield ['admin', 'u1', 'u1', 1];
        yield ['u2', 'u2', 'u1', 0];
        yield ['u1', 'u2', 'u1', 0];
    }

    /**
     * Test update user call.
     *
     * @param string $actinguser
     * @param string $targetuser
     * @param int $expected
     * @dataProvider updated_user_event_provider
     */
    public function test_updated_user_event($actinguser, $recruit, $targetuser, $expected): void {
        $gudg = $this->generator;

        $u1 = $this->u1;
        $u2 = $this->getDataGenerator()->create_user();
        $admin = core_user::get_user_by_username('admin');

        $actinguser = ${$actinguser};
        $recruit = ${$recruit};
        $targetuser = ${$targetuser};

        $config = ['t' => 'text', 'c' => 'eq', 'v' => 'good'];
        $mission = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'profile_field_value',
            'configdata' => (object) ($config + ['f' => 'profile_field_textfield']),
        ]]]);
        $missioninst = $gudg->create_recruit(['missionid' => $mission->get_id(), 'subjectid' => $recruit->id]);
        $this->assertEquals(0, $missioninst->get_objective_instances()[0]->get_counter());

        profile_save_data((object) ['id' => $targetuser->id, 'profile_field_textfield' => 'good']);

        $this->setUser($actinguser);
        $event = user_updated::create_from_userid($targetuser->id);
        \block_gearup\local\observer\observer::catch_all($event);

        $missioninst = di::get('repository')->get_instance($missioninst->get_id());
        $objinst = $missioninst->get_objective_instances()[0];
        $this->assertEquals($expected, $objinst->get_counter());
    }

}
