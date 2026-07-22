<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\navigation\InternalMoodleUrlValidator;

/**
 * Opens an internal Moodle URL from the Command Center.
 */
final class OpenUrlAction extends AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::OPEN_URL;
    }

    public function capability(): string {
        return Capabilities::VIEW_DASHBOARD;
    }

    public function execute(
        array $payload
    ): CommandActionResult {
        $url = trim(
            $this->string_payload(
                $payload,
                'url'
            )
        );

        if ($url === '') {
            return CommandActionResult::error(
                get_string(
                    'command_center_action_missing_url',
                    'local_subscriptions'
                )
            );
        }

        $redirecturl =
            InternalMoodleUrlValidator::normalise_to_string(
                $url
            );

        if ($redirecturl === null) {
            return CommandActionResult::error(
                get_string(
                    'command_center_action_invalid_url',
                    'local_subscriptions'
                )
            );
        }

        return CommandActionResult::success(
            '',
            $redirecturl
        );
    }
}