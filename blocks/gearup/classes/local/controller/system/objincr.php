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
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller\system;

use block_gearup\di;
use block_gearup\local\controller\controller;
use block_gearup\local\routing\routed_request;
use core_user;
use moodle_exception;
use Throwable;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objincr implements controller {

    /**
     * Handle the request.
     *
     * @param \block_gearup\local\routing\routed_request $request The request.
     */
    public function handle(\block_gearup\local\routing\request $request) {
        if ($request->get_method() !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            die();
        }

        try {
            header('Content-Type: application/json');
            $this->process_request($request);

        } catch (Throwable $err) {
            // We must handle all exceptions, otherwise Moodle die(s a 200 for exceptions).
            header('HTTP/1.1 500 Internal Server Error');
            $code = 'unknown';
            if ($err instanceof moodle_exception) {
                $code = $err->errorcode;
            }
            echo json_encode(['code' => $code, 'message' => $err->getMessage()]);
            die();
        }
    }

    /**
     * Process the request.
     *
     * @param routed_request $request The request.
     */
    protected function process_request(routed_request $request) {
        $params = $request->get_route()->get_params();
        $objectiveid = $params['objectiveid'];
        $secret = $params['secret'];
        $emailorid = $params['user'];
        $amount = (int) $params['amount'];

        $repository = di::get('repository');
        $missionhelper = di::get('mission_helper');
        $missionoperator = di::get('mission_operator');
        $objectiveoperator = di::get('objective_operator');
        $objectivetyperesolver = di::get('objective_type_resolver');

        // Validate the amount.
        if ($amount <= 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['code' => 'invalid_amount', 'message' => 'The amount to increment by is invalid.']);
            die();
        }

        // Retrieve the objective.
        $objective = $repository->get_objective($objectiveid);
        if (!$objective) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['code' => 'objective_not_found', 'message' => 'The objective was not found.']);
            die();
        }

        // Validate the mission's secret.
        $mission = $repository->get_mission($objective->get_mission_id());
        if (!$mission || !hash_equals($mission->get_secret(), $secret)) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['code' => 'objective_not_found', 'message' => 'The objective was not found.']);
            die();
        }

        // Validate the type.
        if ($objectivetyperesolver->get_type_name($objective->get_type()) !== 'webhook') {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['code' => 'incompatible_objective', 'message' => 'The objective type is incompatible.']);
            die();
        }

        // Resolve the user ID.
        $userid = $emailorid;
        if (strpos($emailorid, '@')) {
            $user = core_user::get_user_by_email($emailorid);
            $userid = $user ? $user->id : 0;
        }
        if (!$userid) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['code' => 'user_not_found', 'message' => 'The user was not found.']);
            die();
        }

        // Locate the instance of the mission.
        try {
            $missioninst = $repository->get_instance_by_subject_id($mission->get_id(), $userid);
        } catch (\moodle_exception $e) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['code' => 'not_a_recruit', 'message' => 'The user is not a recruit.']);
            die();
        }

        // Validate state.
        if (!$missionhelper->has_started($missioninst)) {
            header('HTTP/1.1 412 Precondition Failed');
            echo json_encode(['code' => 'mission_not_started', 'message' => 'The mission has not yet started.']);
            die();
        } else if ($missionhelper->has_completed($missioninst)) {
            header('HTTP/1.1 412 Precondition Failed');
            echo json_encode(['code' => 'mission_completed', 'message' => 'The mission has already been completed.']);
            die();
        } else if (!$missionhelper->is_active($missioninst)) {
            header('HTTP/1.1 412 Precondition Failed');
            echo json_encode(['code' => 'mission_not_active', 'message' => 'The mission is not active.']);
            die();
        }

        // Get and validate the objective instance.
        $objinst = $missioninst->get_instance_of_objective($objective->get_id());
        if ($objinst->is_completed()) {
            header('HTTP/1.1 412 Precondition Failed');
            echo json_encode(['code' => 'already_completed', 'message' => 'The objective has already been completed.']);
            die();
        }

        // Finally!
        $objectiveoperator->increment_instance_counter($objinst, $amount);
        $missionoperator->evaluate_instance($missioninst);
        header('HTTP/1.1 204 No Content');
    }

}
