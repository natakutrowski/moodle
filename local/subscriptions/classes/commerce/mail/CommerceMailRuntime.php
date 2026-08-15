<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\template\CommercePaymentCancelledTemplate;
use local_subscriptions\commerce\mail\template\CommerceAccountActivationTemplate;
use local_subscriptions\commerce\mail\template\CommercePaymentFailedTemplate;
use local_subscriptions\commerce\mail\template\CommercePaymentPendingTemplate;
use local_subscriptions\commerce\mail\template\CommercePurchaseAccessTemplate;
use local_subscriptions\commerce\mail\template\CommerceGrantAccessTemplate;
use local_subscriptions\commerce\mail\template\CommercePurchaseReceiptTemplate;
use local_subscriptions\commerce\mail\template\CommercePersonalOfferTemplate;
use local_subscriptions\commerce\mail\template\CommerceTrialWelcomeTemplate;
use local_subscriptions\commerce\mail\template\CommerceMarketingCampaignTemplate;
use local_subscriptions\commerce\mail\template\CommerceSalesFollowupTemplate;

/**
 * Composition root for the transactional mail queue.
 */
final class CommerceMailRuntime {

    public static function template_registry(): CommerceMailTemplateRegistry {
        return new CommerceMailTemplateRegistry([
            new CommercePurchaseAccessTemplate(),
            new CommerceGrantAccessTemplate(),
            new CommercePurchaseReceiptTemplate(),
            new CommercePaymentPendingTemplate(),
            new CommercePaymentFailedTemplate(),
            new CommercePaymentCancelledTemplate(),
            new CommerceAccountActivationTemplate(),
            new CommercePersonalOfferTemplate(),
            new CommerceTrialWelcomeTemplate(),
            new CommerceMarketingCampaignTemplate(),
            new CommerceSalesFollowupTemplate(),
        ]);
    }

    public static function queue_service(): CommerceMailQueueService {
        return new CommerceMailQueueService(new CommerceMailQueueRepository());
    }

    public static function processor(): CommerceMailQueueProcessor {
        $repository = new CommerceMailQueueRepository();
        $templates = self::template_registry();
        return new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, new MoodleCommerceMailTransport()),
            new CommerceMailRetryPolicy()
        );
    }
}
