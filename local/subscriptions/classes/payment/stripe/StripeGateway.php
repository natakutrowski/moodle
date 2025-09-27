<?php
namespace local_subscriptions\payment\stripe;

use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\PortalGatewayInterface;
use local_subscriptions\payment\dto\{CheckoutInitResult, InternalEvent, ProviderActionResult, ProviderCapabilities};
use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\Provider;
use stdClass;

final class StripeGateway implements PaymentGatewayInterface, PortalGatewayInterface {

    private function cfg(): array {
        return [
            'secret_key'        => get_config('local_subscriptions', 'stripe_secret_key') ?? '',
            'webhook_secret'    => get_config('local_subscriptions', 'stripe_webhook_secret') ?? '',
            'success_url'       => (UrlFactory::payment_success())->out(false),
            'cancel_url'        => (UrlFactory::payment_cancel())->out(false),
            'portal_return'     => (UrlFactory::my_subscriptions())->out(false),
        ];
    }

    public function create_checkout_session(stdClass $payment_request, array $options = []): CheckoutInitResult {
        $this->ensure_sdk();
        $mode = $options['mode'] ?? 'payment';
        $priceId = $options['price_map']['stripe_price_id'] ?? null;

        $cfg = $this->cfg();
        \Stripe\Stripe::setApiKey($cfg['secret_key']);

        $params = [
            'mode' => $mode,
            'metadata'    => ['payment_request_id' => (string)$payment_request->id],
            'client_reference_id' => (string)$payment_request->id,
        ];

        // Si des URLs sont fournies, on les utilise
        if (!empty($options['success_url'])) { $params['success_url'] = $options['success_url']; }
        if (!empty($options['cancel_url']))  { $params['cancel_url']  = $options['cancel_url']; }

        if ($mode === 'payment') {
            $amountMinor = isset($payment_request->price) ? (int)round(((float)$payment_request->price) * 100) : null;
            if ($amountMinor === null) { throw new \coding_exception(get_string('stripe:missingamount', 'local_subscriptions')); }
            $params['line_items'] = [[
                'price_data' => [
                    'currency' => $payment_request->currency,
                    'product_data' => ['name' => $options['product_name'] ?? get_string('stripe:productname', 'local_subscriptions', 'CampusFR')],
                    'unit_amount' => $amountMinor,
                ],
                'quantity' => 1,
            ]];
            $params['payment_intent_data'] = [
                'metadata' => ['payment_request_id' => (string)$payment_request->id]
            ];
        } else {
            if (!$priceId) {
                throw new \coding_exception(get_string('stripe:missingpriceidforsubscription', 'local_subscriptions'));
            }
            $params['line_items'] = [[ 'price' => $priceId, 'quantity' => 1 ]];  
            $params['subscription_data'] = [
                'metadata' => ['payment_request_id' => (string)$payment_request->id]
            ];
        }

        // Pré-remplir l'email si pas de customer existant
        $reqEmail = $payment_request->email ?? null;
        if (!empty($reqEmail) && empty($params['customer'])) {
            $params['customer_email']    = $reqEmail;     // <-- préremplit le champ email
            if ($mode === 'payment') {               // <-- on limite 'customer_creation' au mode payment
                $params['customer_creation'] = 'always';
            }
        }

        error_log('[subs][stripe][create_session] PR#'.$payment_request->id.' amount='.$payment_request->price.' '.$payment_request->currency.' mode='.$mode);

        $session = \Stripe\Checkout\Session::create($params);
        return new CheckoutInitResult($session->url, $session->id);
    }

    public function parse_webhook(string $payload, array $headers): InternalEvent {
        $this->ensure_sdk();
        $cfg = $this->cfg();
        $sig = $headers['Stripe-Signature'] ?? ($headers['stripe-signature'] ?? '');
        $event = \Stripe\Webhook::constructEvent($payload, $sig, $cfg['webhook_secret']);

        $type = $event->type;
        $obj  = $event->data->object; // stdClass (invoice/subscription/session/intent)

        switch ($type) {

            case 'checkout.session.completed': {
                $pid = $obj->metadata->payment_request_id ?? $obj->client_reference_id ?? null;
                $customerEmail = $obj->customer_details->email ?? ($obj->customer_email ?? null);
                return new InternalEvent('checkout_completed', [
                    'payment_request_id'       => $pid,
                    'provider_customer_id'     => $obj->customer ?? null,
                    'provider_subscription_id' => $obj->subscription ?? null,
                    'meta' => [
                        'provider'       => Provider::STRIPE,
                        'session'        => $obj->id,
                        'customer_email' => $customerEmail, // utile côté service
                    ],
                ]);
            }

            case 'checkout.session.expired': {
                return new InternalEvent('checkout_expired', [
                    'meta' => ['provider' => Provider::STRIPE, 'session' => $obj->id],
                ]);
            }

            // Paiement réussi (toutes variantes possibles)
            case 'invoice.payment_succeeded': {
                $evtId    = $event->id;
                $subid    = $obj->subscription ?? null;
                $invoice  = $obj->id ?? null;
                $amount   = $obj->amount_paid ?? $obj->amount_due ?? null;
                $currency = $obj->currency ?? null;

                // period_end = lines.data[0].period.end
                $periodEnd = null;
                if (!empty($obj->lines) && !empty($obj->lines->data) && !empty($obj->lines->data[0]->period->end)) {
                    $periodEnd = $obj->lines->data[0]->period->end;
                }

                error_log("[gw][paid] sub={$subid} invoice={$invoice} amount={$amount} {$currency} period_end={$periodEnd}");

                return new InternalEvent('invoice_paid', [
                    'provider_subscription_id' => $subid,
                    'amount_minor'             => $amount,
                    'currency'                 => $currency,
                    'meta' => [
                        'provider'           => Provider::STRIPE,
                        'event_id'           => $evtId,   // evt_...
                        'invoice'            => $invoice, // in_...
                        'billing_reason'     => $obj->billing_reason ?? null,
                        'current_period_end' => $periodEnd,
                        'raw'                => ['type' => $type], // petit secours si besoin
                    ],
                ]);
            }

            // Paiement échoué (toutes variantes possibles)
            case 'invoice.payment_failed': {
                $evtId    = $event->id;
                $subid    = $obj->subscription ?? null;
                $invoice  = $obj->id ?? null;
                $amount   = $obj->amount_due ?? null;
                $currency = $obj->currency ?? null;
                $nexttry  = $obj->next_payment_attempt ?? null;

                $lpe = null;
                if (!empty($obj->last_payment_error) && is_object($obj->last_payment_error)) {
                    $lpe = $obj->last_payment_error->code ?? null;
                }

                error_log("[gw][failed] sub={$subid} invoice={$invoice} amount={$amount} {$currency}");

                return new InternalEvent('invoice_failed', [
                    'provider_subscription_id' => $subid,
                    'amount_minor'             => $amount,
                    'currency'                 => $currency,
                    'meta' => [
                        'provider'             => Provider::STRIPE,
                        'event_id'             => $evtId,
                        'invoice'              => $invoice,
                        'billing_reason'       => $obj->billing_reason ?? null,
                        'next_payment_attempt' => $nexttry,
                        'last_payment_error'   => $lpe,
                        'raw'                  => ['type' => $type],
                    ],
                ]);
            }

            case 'customer.subscription.deleted': {
                return new InternalEvent('subscription_canceled', [
                    'provider_subscription_id' => $obj->id ?? null,
                    'meta' => [
                        'provider' => Provider::STRIPE,
                        // optionnel: si tu veux passer un hint d’échéance
                        'event_id'             => $event->id,              // <-- AJOUT
                        'current_period_end'   => $obj->current_period_end ?? null,
                        'cancel_at_period_end' => $obj->cancel_at_period_end ?? null,
                    ],
                ]);
            }

            case 'customer.subscription.updated': {
                $evtId = $event->id;
                $subid = $obj->id ?? null;
                $cps   = $obj->current_period_start ?? null;
                $cpe   = $obj->current_period_end   ?? null;
                $cape  = $obj->cancel_at_period_end ?? null;
                $stat  = $obj->status ?? null;

                error_log("[gw][sub.updated] sub={$subid} cps={$cps} cpe={$cpe} cape=".json_encode($cape));

                return new InternalEvent('subscription_updated', [
                    'provider_subscription_id' => $subid,
                    'meta' => [
                        'provider'             => Provider::STRIPE,
                        'event_id'             => $evtId,
                        'status'               => $stat,
                        'current_period_start' => $cps,
                        'current_period_end'   => $cpe,
                        'cancel_at_period_end' => $cape,
                    ],
                ]);
            }

            default:
                return new InternalEvent('subscription_updated', [
                    'meta' => ['provider' => Provider::STRIPE, 'raw' => $type],
                ]);
        }
    }


    public function cancel_subscription(string $provider_subscription_id, array $opts = []): ProviderActionResult {
        $this->ensure_sdk();
        \Stripe\Stripe::setApiKey($this->cfg()['secret_key']);
        $atperiodend = (bool)($opts['at_period_end'] ?? true);
        \Stripe\Subscription::update($provider_subscription_id, ['cancel_at_period_end' => $atperiodend]);
        return new ProviderActionResult(true);
    }

    public function resume_subscription(string $provider_subscription_id, array $opts = []): ProviderActionResult {
        $this->ensure_sdk();
        \Stripe\Stripe::setApiKey($this->cfg()['secret_key']);
        \Stripe\Subscription::update($provider_subscription_id, ['cancel_at_period_end' => false]);
        return new ProviderActionResult(true);
    }

    public function upgrade_subscription(string $provider_subscription_id, array $opts): ProviderActionResult {
        $this->ensure_sdk();
        \Stripe\Stripe::setApiKey($this->cfg()['secret_key']);
        $newPrice = $opts['price_id'] ?? null;
        if (!$newPrice) { return new ProviderActionResult(false, get_string('stripe:missingpriceid', 'local_subscriptions')); }
        $sub = \Stripe\Subscription::retrieve($provider_subscription_id);
        $itemId = $sub->items->data[0]->id ?? null;
        \Stripe\Subscription::update($provider_subscription_id, [
            'cancel_at_period_end' => false,
            'proration_behavior' => $opts['proration'] ?? 'create_prorations',
            'items' => [[ 'id' => $itemId, 'price' => $newPrice ]],
        ]);
        return new ProviderActionResult(true);
    }

    public function get_customer_portal_url(?string $provider_customer_id, array $opts = []): ?string {
        $this->ensure_sdk();
        if (empty($provider_customer_id)) return null;
        \Stripe\Stripe::setApiKey($this->cfg()['secret_key']);
        $sess = \Stripe\BillingPortal\Session::create([
            'customer' => $provider_customer_id,
            'return_url' => $this->cfg()['portal_return'],
        ]);
        return $sess->url;
    }

    public function capabilities(): ProviderCapabilities {
        $c = new ProviderCapabilities();
        $c->supports_recurring = true;
        $c->supports_portal = true;
        $c->currencies = ['EUR','USD','GBP','CHF'];
        return $c;
    }

    /** Charge le SDK stripe-php une seule fois */
    private function ensure_sdk(): void {
        static $done = false;
        if ($done) { return; }
        global $CFG;

        // Si le SDK est dans ton plugin :
        $path = $CFG->dirroot . '/local/subscriptions/vendor/autoload.php';

        // (si tu utilises le vendor global Moodle, remplace par:)
        // $path = $CFG->dirroot . '/vendor/autoload.php';

        if (file_exists($path)) {
            require_once($path);
            $done = true;
        } else {
            throw new \coding_exception(get_string('stripe:sdkautoloadnotfound', 'local_subscriptions', $path));
        }
    }

    public function create_portal_session(array $ctx) {
        // Expects: provider_customer_id (cus_xxx), return_url
        $customer = $ctx['provider_customer_id'] ?? null;
        $return   = $ctx['return_url'] ?? (UrlFactory::my_subscriptions())->out(false);

        if (!$customer) {
            // Si tu peux le retrouver depuis la sub.provider_subscription_id -> lookup Stripe sub -> customer
            // ou renvoyer une erreur propre
            throw new \moodle_exception('missing_customer_id', 'local_subscriptions');
        }

        $this->ensure_sdk(); // si tu as déjà ce helper
        \Stripe\Stripe::setApiKey($this->cfg()['secret_key']);

        $session = \Stripe\BillingPortal\Session::create([
            'customer'   => $customer,
            'return_url' => $return,
        ]);
        // Retourne un DTO ou un array simple – à toi de voir selon ton interface existante
        return ['url' => $session->url];
    }


}
