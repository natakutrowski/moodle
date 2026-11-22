<?php
declare(strict_types=1);
namespace local_subscriptions\payment\stripe\webhook;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\payment\stripe\StripeConfiguration;
use local_subscriptions\payment\stripe\StripeGateway;
final class StripeWebhookVerifier {
    public function verify(string $payload,array $headers,?string $requestedprofile=null): StripeVerifiedWebhook {
        // D2.19.1: when Stripe TEST is selected, *only* TEST signatures may be
        // accepted, even if legacy LIVE webhook secrets remain configured.
        // Reject an explicit LIVE/unknown profile before profile normalisation,
        // which otherwise silently maps unknown values back to TEST.
        if (StripeConfiguration::active_profile() === StripeConfiguration::TEST) {
            if ($requestedprofile !== null && trim($requestedprofile) !== '' &&
                    strtolower(trim($requestedprofile)) !== StripeConfiguration::TEST) {
                throw new \UnexpectedValueException('Only Stripe TEST webhooks are accepted in TEST mode.');
            }
            $profiles = [StripeConfiguration::TEST];
        } else {
            // Keep the historical production multi-profile routing unchanged.
            $profiles = $requestedprofile !== null && trim($requestedprofile) !== ''
                ? [StripeConfiguration::normalise($requestedprofile)]
                : array_values(array_unique(array_merge(
                    [StripeConfiguration::active_profile()], StripeConfiguration::PROFILES
                )));
        }
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
