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

use block_gearup\local\controller\controller;
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
abstract class base implements controller {

    /**
     * Get the signing secret.
     *
     * @return string|null
     */
    abstract protected function get_signing_secret();

    /**
     * Handle the request.
     *
     * @param \block_gearup\local\routing\routed_request $request The request.
     * @return void
     */
    public function handle(\block_gearup\local\routing\request $request) {
        if ($request->get_method() !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            die();
        }

        // Read the request headers.
        $id = $_SERVER['HTTP_WEBHOOK_ID'] ?? '';
        $signature = $_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '';
        $timestamp = (int) ($_SERVER['HTTP_WEBHOOK_TIMESTAMP'] ?? 0);
        if (empty($id) || empty($signature) || empty($timestamp) || strlen($signature) !== 64) {
            header('HTTP/1.1 400 Bad Request');
            die();
        } else if ($timestamp < time() - 600 || $timestamp > time() + 600) {
            header('HTTP/1.1 400 Bad Request');
            die();
        }

        // Validate that we're expecting webhooks.
        $webhooksecret = $this->get_signing_secret();
        if (empty($webhooksecret) || strlen($webhooksecret) < 64) {
            header('HTTP/1.1 501 Not Implemented');
            die();
        }

        // Validate the authenticity of the request.
        $body = file_get_contents('php://input');
        $signedcontent = "{$id}.{$timestamp}.{$body}";
        $expectedsignature = hash_hmac('sha256', $signedcontent, $webhooksecret);
        if (!hash_equals($signature, $expectedsignature)) {
            header('HTTP/1.1 401 Unauthorized');
            die();
        }

        // Parse and basic validation of the content of the webhook.
        $data = json_decode($body);
        if (empty($data) || empty($data->type) || empty($data->payload) || !is_string($data->type) || !is_object($data->payload)) {
            header('HTTP/1.1 400 Bad Request');
            die();
        }

        // Ok we're good!
        try {
            header('Content-Type: application/json');
            $this->process($data->type, $data->payload);

        } catch (Throwable $err) {
            // We must handle all exceptions, otherwise Moodle returns a 200 for exceptions.
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
     * Process.
     *
     * @param string $type The type.
     * @param object $payload The payload.
     */
    abstract protected function process($type, $payload);

}
