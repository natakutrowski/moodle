<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\stripe\StripeConfiguration;
final class StripeCheckoutSessionStatusProbe implements StripePaymentStatusProbeInterface {
    public function probe(string $sessionid): StripePaymentProviderStatus {
        $sessionid = trim($sessionid);
        if ($sessionid === '') { throw new \invalid_parameter_exception('Stripe Checkout Session ID is required.'); }
        $this->ensure_sdk();
        $profiles = array_values(array_unique(array_merge([StripeConfiguration::active_profile()], StripeConfiguration::PROFILES)));
        $last = null;
        foreach ($profiles as $profile) {
            $cfg = StripeConfiguration::get($profile);
            if (trim((string)$cfg['secret_key']) === '') { continue; }
            try {
                \Stripe\Stripe::setApiKey($cfg['secret_key']);
                $session = \Stripe\Checkout\Session::retrieve($sessionid);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                $last = $e; continue;
            }
            $metadata = [];
            if (!empty($session->metadata)) {
                foreach ($session->metadata as $k=>$v) { $metadata[(string)$k] = (string)$v; }
            }
            $event = new InternalEvent('checkout_completed', [
                'payment_request_id' => isset($metadata['payment_request_id']) ? (int)$metadata['payment_request_id'] : 0,
                'provider_customer_id' => $session->customer ?? null,
                'provider_subscription_id' => $session->subscription ?? null,
                'currency' => strtoupper((string)($session->currency ?? '')),
                'amount_minor' => (int)($session->amount_total ?? 0),
                'meta' => array_merge($metadata, [
                    'provider' => Provider::STRIPE,
                    'session' => (string)$session->id,
                    'provider_payment_id' => (string)$session->id,
                    'payment_intent' => $session->payment_intent ?? null,
                    'payment_status' => (string)($session->payment_status ?? ''),
                    'checkout_status' => (string)($session->status ?? ''),
                    'stripe_profile' => $profile,
                    'customer_email' => $session->customer_details->email ?? $session->customer_email ?? null,
                ]),
            ]);
            return new StripePaymentProviderStatus(
                (string)$session->id, $profile,
                (string)($session->status ?? ''), (string)($session->payment_status ?? ''),
                isset($session->amount_total) ? (int)$session->amount_total : null,
                isset($session->currency) ? strtoupper((string)$session->currency) : null,
                $event
            );
        }
        throw $last ?? new \RuntimeException('Stripe Checkout Session not found in configured profiles.');
    }
    private function ensure_sdk(): void {
        global $CFG;
        if (class_exists(\Stripe\Checkout\Session::class)) { return; }
        $path = $CFG->dirroot . '/local/subscriptions/vendor/autoload.php';
        if (!file_exists($path)) { throw new \RuntimeException('Stripe SDK autoload not found.'); }
        require_once $path;
    }
}
