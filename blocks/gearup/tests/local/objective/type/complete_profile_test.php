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
use block_gearup\local\objective\type\complete_profile;
use block_gearup\tests\base_testcase;
use Generator;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\objective\type\complete_profile
 */
final class complete_profile_test extends base_testcase {

    /** @var \stdClass */
    protected $u1;

    public function setUp(): void {
        parent::setUp();
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
    public static function field_data_provider(): Generator {
        $textfield = 'profile_field_textfield';

        // None required.
        yield [[], [], 1];

        // Single required, none set.
        yield [['description'], [], 0];
        yield [['city'], [], 0];
        yield [['country'], [], 0];
        yield [['institution'], [], 0];
        yield [['department'], [], 0];
        yield [['phone1'], [], 0];
        yield [['phone2'], [], 0];
        yield [['address'], [], 0];
        yield [[$textfield], [], 0];

        // Single set, single required.
        $data = ['city' => 'Perth'];
        yield [['description'], $data, 0];
        yield [['city'], $data, 1];
        yield [['country'], $data, 0];
        yield [['institution'], $data, 0];
        yield [['department'], $data, 0];
        yield [['phone1'], $data, 0];
        yield [['phone2'], $data, 0];
        yield [['address'], $data, 0];
        yield [[$textfield], $data, 0];

        // Multiple set.
        $data = ['city' => 'Perth', 'country' => 'AU'];
        yield [['description'], $data, 0];
        yield [['city'], $data, 1];
        yield [['country'], $data, 1];
        yield [['institution'], $data, 0];
        yield [['department'], $data, 0];
        yield [['phone1'], $data, 0];
        yield [['phone2'], $data, 0];
        yield [['address'], $data, 0];
        yield [[$textfield], $data, 0];

        // Multiple requirements, multiple set.
        $data = ['city' => 'Perth', 'country' => 'AU'];
        yield [['city', 'description'], $data, 0];
        yield [['city', 'country'], $data, 1];
        yield [['city', 'institution'], $data, 0];
        yield [['city', 'department'], $data, 0];
        yield [['city', 'phone1'], $data, 0];
        yield [['city', 'phone2'], $data, 0];
        yield [['city', 'address'], $data, 0];
        yield [['city', $textfield], $data, 0];

        // Multiple requirements, multiple set, with custom field.
        $data = ['city' => 'Perth', 'country' => 'AU', $textfield => 'Some text'];
        yield [['city', $textfield], $data, 1];
        yield [['city', 'country'], $data, 1];
        yield [['country', $textfield], $data, 1];
        yield [['city', 'country', $textfield], $data, 1];
        yield [['city', 'country', $textfield, 'department'], $data, 0];

        // Invalid custom field.
        $data = ['city' => 'Perth'];
        yield [['city', 'profile_field_invalid'], $data, 0];
    }

    /**
     * Test consumption.
     *
     * @dataProvider field_data_provider
     */
    public function test_consumption($fields, $data, int $expectedcounter): void {
        $gudg = $this->generator;

        $typeconfig = (object) ['f' => $fields];
        $action = new profile_updated($this->u1->id);

        $type = new complete_profile();
        $obj = $gudg->mock_objective($type, $typeconfig, ['countneeded' => 1]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $this->u1->id]);
        $mission = $gudg->mock_mission(['objectives' => [$obj]]);
        $missioninst = $gudg->mock_mission_instance($mission, ['subjectid' => $this->u1->id]);

        $this->update_user($this->u1, $data);

        $this->assertTrue($type->is_action_compatible($action));
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals($expectedcounter, $objinst->get_counter());
    }

    /**
     * Test initialisation.
     *
     * @dataProvider field_data_provider
     */
    public function test_initialisation($fields, $data, int $expectedcounter): void {
        $gudg = $this->generator;

        $mission = $gudg->create_persisted_mission(['objectives' => [[
            'type' => 'complete_profile',
            'configdata' => ['f' => $fields],
        ]]]);

        $this->update_user($this->u1, $data);
        $missioninst = di::get('mission_operator')->assign_mission($mission, $this->u1->id);
        $objinst = $missioninst->get_instance_of_objective($mission->get_objectives()[0]->get_id());
        $this->assertEquals($expectedcounter, $objinst->get_counter());
    }

    /**
     * Update a user.
     *
     * @param object $user The user.
     * @param array $data The data.
     */
    protected function update_user($user, $data) {
        $profilefields = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'profile_field_') === 0) {
                $profilefields[$key] = $value;
                continue;
            }
            $user->{$key} = $value;
        }

        user_update_user($user, false);
        if (!empty($profilefields)) {
            profile_save_data((object) (['id' => $user->id] + $profilefields));
        }
    }

}
