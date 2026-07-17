<?php

namespace local_subscriptions\crm\assistant\rendering;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\assistant\services\CrmAssistantService;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;
use local_subscriptions\crm\assistant\ai\rendering\CrmAssistantConversationRenderer;

/**
 * CRM Assistant section displayed on a User 360° profile.
 */
final class UserAssistantSection {

    public static function render(
        int $userid
    ): string {
        if (
            $userid <= 0 ||
            !Capabilities::can_view_users()
        ) {
            return '';
        }

        $recommendations =
            (new CrmAssistantService())
                ->user_recommendations(
                    $userid,
                    10
                );

        $out = CrmAssistantRenderer::
            user_section($recommendations);

        if (
            has_capability(
                Capabilities::USE_CRM_ASSISTANT_AI,
                \context_system::instance()
            )
        ) {
            $out .=
                CrmAssistantConversationRenderer::render(
                    scope:
                        CrmAssistantQuestion::SCOPE_USER,
                    userid: $userid
                );
        }

        return $out;
    }
}