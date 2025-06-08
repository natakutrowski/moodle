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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\tests\objective\type;

use block_gearup\local\objective\type\not_access_platform;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class not_access_platform_testcase extends base_testcase {

    protected $mission;
    protected $missioninst;

    public function setUp(): void {
        parent::setUp();
        $pdg = $this->generator;
        $this->mission = $pdg->mock_quest();
        $this->missioninst = $pdg->mock_mission_instance($this->mission);
    }

    public function test_basic() {
        $pdg = $this->generator;
        $action = $pdg->mock_action_loggedin(2);

        $type = new not_access_platform();
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => 2]);

        $this->assertTrue($type->is_action_compatible($action));
        $this->assertTrue($type->is_action_passing_constraints($action, $objinst, $this->missioninst));
    }

    public function test_initialisation() {
        $dg = $this->getDataGenerator();
        $pdg = $this->generator;
        $type = new not_access_platform();

        // Test in New York.
        $user = $dg->create_user(['timezone' => 'America/New_York']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('America/New_York'));
        $type->initialise_state($objinst, $missioninst);

        $loginby = $now->add(new DateInterval('P1D'))->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $this->assertEquals($loginby->getTimestamp(), $objinst->get_type_state()->lb);
        $this->assertEquals($loginby->getTimestamp(), $objinst->get_stale_from()->getTimestamp());

        // Test UTC.
        $user = $dg->create_user(['timezone' => 'UTC']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'));
        $type->initialise_state($objinst, $missioninst);

        $loginby = $now->add(new DateInterval('P1D'))->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $this->assertEquals($loginby->getTimestamp(), $objinst->get_type_state()->lb);
        $this->assertEquals($loginby->getTimestamp(), $objinst->get_stale_from()->getTimestamp());

        // Test greater delay.
        $user = $dg->create_user(['timezone' => 'Europe/Brussels']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => WEEKSECS * 2]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('Europe/Brussels'));
        $type->initialise_state($objinst, $missioninst);

        $loginby = $now->add(new DateInterval('P14D'))->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $this->assertEquals($loginby->getTimestamp(), $objinst->get_type_state()->lb);
        $this->assertEquals($loginby->getTimestamp(), $objinst->get_stale_from()->getTimestamp());
    }

    public function test_consume_action() {
        $dg = $this->getDataGenerator();
        $pdg = $this->generator;
        $type = new not_access_platform();

        // No initialisation leads to completion.
        $user = $dg->create_user(['timezone' => 'America/New_York']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS * 2]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $action = $pdg->mock_action_loggedin($user->id);
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(1, $objinst->get_counter());

        // Initialisation means too early.
        $user = $dg->create_user(['timezone' => 'America/New_York']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS * 2]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('America/New_York'));
        $nextday = $now->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $type->initialise_state($objinst, $missioninst);
        $action = $pdg->mock_action_loggedin($user->id);
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());
        $this->assertEquals($nextday, $objinst->get_dormant_until());
        $this->assertEquals($nextday->add(new DateInterval('P2D')), $objinst->get_stale_from());

        // Logging in the next day postpones the stale.
        $futurenow = $now->add(new DateInterval('P1D'))->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));
        $nextday = $futurenow->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $action = $pdg->mock_action_loggedin($user->id, $futurenow);
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());
        $this->assertEquals($nextday, $objinst->get_dormant_until());
        $this->assertEquals($nextday->add(new DateInterval('P2D')), $objinst->get_stale_from());

        // Logging in two days postpones the stale.
        $futurenow = $futurenow->add(new DateInterval('P2D'))->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));
        $nextday = $futurenow->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $action = $pdg->mock_action_loggedin($user->id, $futurenow);
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());
        $this->assertEquals($nextday, $objinst->get_dormant_until());
        $this->assertEquals($nextday->add(new DateInterval('P2D')), $objinst->get_stale_from());

        // Logging exactly 2 days after the morning after is a fail (2 missed days).
        $futurenow = $nextday->add(new DateInterval('P2D'))->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));
        $nextday = $futurenow->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $action = $pdg->mock_action_loggedin($user->id, $futurenow);
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(1, $objinst->get_counter());

        // Never logging in, for a long period of time.
        $user = $dg->create_user(['timezone' => 'Europe/Brussels']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS * 14]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('Europe/Brussels'));
        $nextday = $now->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $type->initialise_state($objinst, $missioninst);

        $action = $pdg->mock_action_loggedin($user->id, $nextday->add(new DateInterval('P20D')));
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(1, $objinst->get_counter());
    }

    public function test_reevaluate_state() {
        $dg = $this->getDataGenerator();
        $pdg = $this->generator;
        $type = new not_access_platform();

        // No initialisation leads to completion.
        $user = $dg->create_user(['timezone' => 'America/New_York']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS * 2]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $this->assertEquals(0, $objinst->get_counter());
        $type->reevaluate_state($objinst);
        $this->assertEquals(1, $objinst->get_counter());

        // Initialisation means too early.
        $user = $dg->create_user(['timezone' => 'America/New_York']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS * 2]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('America/New_York'));
        $nextday = $now->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());
        $type->reevaluate_state($objinst);
        $this->assertEquals(0, $objinst->get_counter());
        $this->assertEquals(null, $objinst->get_dormant_until());
        $this->assertEquals($nextday->add(new DateInterval('P2D')), $objinst->get_stale_from());

        // Reevaluation passed login by.
        $user = $dg->create_user(['timezone' => 'America/New_York']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS * 2]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('America/New_York'));
        $prevday = $now->setTime(0, 0, 0, 0)->sub(new DateInterval('P1D'));
        $objinst->set_type_state((object) ['lb' => $prevday->getTimestamp()]);

        $this->assertEquals(0, $objinst->get_counter());
        $type->reevaluate_state($objinst);
        $this->assertEquals(1, $objinst->get_counter());

        // Reevaluation before login by.
        $user = $dg->create_user(['timezone' => 'America/New_York']);
        $missioninst = $pdg->mock_mission_instance($this->mission, ['subjectid' => $user->id]);
        $obj = $pdg->mock_objective($type, ['time' => DAYSECS * 2]);
        $objinst = $pdg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('America/New_York'));
        $nextday = $now->setTime(0, 0, 0, 0)->add(new DateInterval('P1D'));
        $loginby = $nextday->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));
        $objinst->set_type_state((object) ['lb' => $loginby->getTimestamp()]);

        $this->assertEquals(0, $objinst->get_counter());
        $type->reevaluate_state($objinst);
        $this->assertEquals(0, $objinst->get_counter());
        $this->assertEquals($loginby, $objinst->get_stale_from());
    }

}
