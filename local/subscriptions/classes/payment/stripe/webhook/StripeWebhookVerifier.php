<?php
declare(strict_types=1);
namespace local_subscriptions\payment\stripe\webhook;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\payment\stripe\StripeConfiguration;
use local_subscriptions\payment\stripe\StripeGateway;
final class StripeWebhookVerifier {
    public function verify(string $payload,array $headers,?string $requestedprofile=null): StripeVerifiedWebhook {
        $profiles=$requestedprofile!==null && trim($requestedprofile)!==''
            ? [StripeConfiguration::normalise($requestedprofile)]
            : array_values(array_unique(array_merge([StripeConfiguration::active_profile()],StripeConfiguration::PROFILES)));
        $last=null;
        foreach($profiles as $profile){
            $cfg=StripeConfiguration::get($profile);
            if(trim((string)$cfg['webhook_secret'])==='') continue;
            try {
                return new StripeVerifiedWebhook($profile,(new StripeGateway($profile))->parse_webhook($payload,$headers));
            } catch(\Stripe\Exception\SignatureVerificationException|\UnexpectedValueException $e) { $last=$e; }
        }
        throw $last ?? new \RuntimeException('No Stripe webhook secret could verify the event.');
    }
}
