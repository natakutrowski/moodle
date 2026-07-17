<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\assistant\rendering\CrmAssistantRenderer;
use local_subscriptions\crm\assistant\repositories\AssistantRecommendationRepository;
use local_subscriptions\dashboard\DashboardCard;

/**
 * CRM Assistant overview card.
 */
final class CrmAssistantCard implements DashboardCard {

    public static function render(): string {
        if (!Capabilities::can_view_users()) {
            return '';
        }

        $overview =
            (new AssistantRecommendationRepository())
                ->get_overview();

        return CrmAssistantRenderer::
            dashboard_summary($overview);
    }
}