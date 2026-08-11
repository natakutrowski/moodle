<?php
namespace local_subscriptions\payment\stripe;

defined('MOODLE_INTERNAL') || die();

class StripeTransactionIdResolver {
    public static function resolve(array $meta = [], ?array $payload = null): ?string {
        global $CFG;

        // 1) Id direct dans le payload webhook ? (checkout.session.completed, payment_intent.succeeded, etc.)
        //   - data.object.payment_intent (string) ou payment_intent.id (obj)
        $candidates = [
            $payload['data']['object']['payment_intent'] ?? null,
            $payload['data']['object']['payment_intent']['id'] ?? null,
            $payload['payment_intent'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (!empty($c) && is_string($c)) { return $c; }
        }

        // 2) Rattrapage via la Session si disponible en meta
        if (!empty($meta['session'])) {
            try {
                require_once($CFG->dirroot . '/local/subscriptions/vendor/autoload.php');
                \Stripe\Stripe::setApiKey(self::secretKey($meta['stripe_profile'] ?? null));
                $session = \Stripe\Checkout\Session::retrieve($meta['session']);
                if (!empty($session->payment_intent)) {
                    if (is_string($session->payment_intent)) {
                        return $session->payment_intent;
                    }
                    // objet PaymentIntent
                    $pi = \Stripe\PaymentIntent::retrieve($session->payment_intent->id);
                    return $pi->id ?? null;
                }
            } catch (\Throwable $ex) {
                debugging('[subs][stripe] resolve txnid failed: '.$ex->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return null;
    }

    // Récupère ta clé Stripe (adapte à ton code existant).
    private static function secretKey(?string $profile = null): string {
        return StripeConfiguration::secret_key($profile);
    }
}
