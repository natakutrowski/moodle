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
        int $userid,
        int $recommendationlimit = 10
    ): string {
        return self::render_recommendations(
            $userid,
            $recommendationlimit
        )
        . self::render_conversation(
            $userid
        );
    }

    /**
     * N11.5D — Recommendations can be rendered independently so the advanced
     * dashboard can place them in a dedicated left column.
     */
    public static function render_recommendations(
        int $userid,
        int $recommendationlimit = 10
    ): string {
        if (
            $userid <= 0 ||
            !Capabilities::can_view_users()
        ) {
            return '';
        }

        $recommendationlimit = max(
            0,
            min(10, $recommendationlimit)
        );

        $recommendations =
            (new CrmAssistantService())
                ->user_recommendations(
                    $userid,
                    $recommendationlimit
                );

        return CrmAssistantRenderer::
            user_section($recommendations);
    }

    /**
     * N11.5D — AI question panel can be rendered independently so the
     * advanced dashboard can place it in a dedicated right column.
     */
    public static function render_conversation(
        int $userid
    ): string {
        if (
            $userid <= 0 ||
            !Capabilities::can_view_users()
        ) {
            return '';
        }

        if (
            !has_capability(
                Capabilities::USE_CRM_ASSISTANT_AI,
                \context_system::instance()
            )
        ) {
            return '';
        }

        return CrmAssistantConversationRenderer::render(
            scope:
                CrmAssistantQuestion::SCOPE_USER,
            userid: $userid
        );
    }
}
