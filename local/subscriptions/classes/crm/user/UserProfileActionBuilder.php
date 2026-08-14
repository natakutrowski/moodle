<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

final class UserProfileActionBuilder {

    public function build_for_user(\stdClass $user): array {
        $userid = (int)$user->id;

        return [
            new UserProfileAction(
                'changeemail',
                get_string('commerce_customer_email_change_action', 'local_subscriptions'),
                (new \moodle_url(subscription_config::admin_user_change_email_page(), ['id' => $userid]))->out(false),
                'email',
                'secondary'
            ),

            new UserProfileAction(
                'email',
                get_string('command_action_user_email', 'local_subscriptions'),
                (new \moodle_url(subscription_config::admin_user_email_page(), ['id' => $userid]))->out(false),
                'email',
                'primary'
            ),

            new UserProfileAction(
                'note',
                get_string('command_action_user_note', 'local_subscriptions'),
                (new \moodle_url(subscription_config::admin_user_add_note_page(), ['id' => $userid]))->out(false),
                'note',
                'secondary'
            ),

            new UserProfileAction(
                'resetpassword',
                get_string('command_action_user_reset_password', 'local_subscriptions'),
                (new \moodle_url(subscription_config::admin_user_reset_password_page(), ['id' => $userid]))->out(false),
                'reset',
                'warning',
                true
            ),
        ];
    }

    public function build_legacy_objects_for_user(\stdClass $user): array {
        return array_map(static function(UserProfileAction $action): \stdClass {
            return $action->to_object();
        }, $this->build_for_user($user));
    }

    public function build_for_profile(\stdClass $user, array $digitalpayments = []): array {
        $actions = $this->build_for_user($user);

        foreach ($digitalpayments as $purchase) {
            if (empty($purchase->id)) {
                continue;
            }

            $actions[] = new UserProfileAction(
                'purchase_resend_' . (int)$purchase->id,
                get_string('command_action_purchase_resend_email', 'local_subscriptions') . ' #' . (int)$purchase->id,
                (new \moodle_url(subscription_config::digital_purchase_resend_email_admin_page(), [
                    'id' => (int)$purchase->id,
                ]))->out(false),
                'email',
                'secondary'
            );

            break;
        }

        return $actions;
    }

}