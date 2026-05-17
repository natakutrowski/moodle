<?php
namespace local_subscriptions\payment\stripe;

use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\PortalGatewayInterface;
use local_subscriptions\payment\dto\{CheckoutInitResult, InternalEvent, ProviderActionResult, ProviderCapabilities};
use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\constants\Operation;
use stdClass;

final class StripeGateway implements PaymentGatewayInterface, PortalGatewayInterface {

    private function cfg(array $overrides = []): array {

        $env = get_config('local_subscriptions', 'stripe_env') ?: 'test';
        $env = ($env === 'live') ? 'live' : 'test';

        // Nouvelles clés (test/live)
        $secret   = get_config('local_subscriptions', "stripe_{$env}_secret") ?: '';
        $pub      = get_config('local_subscriptions', "stripe_{$env}_publishable") ?: '';
        $whsecret = get_config('local_subscriptions', "stripe_{$env}_webhook_secret") ?: '';
        $portalid = get_config('local_subscriptions', "stripe_{$env}_portal_configuration_id") ?: '';


        // URLs par défaut (peuvent être écrasées par $overrides)
        $defaults = [
            'mode'                    => $env, // 'test' | 'live' — utile pour tes logs
            'secret_key'              => (string)$secret,
            'publishable_key'         => $pub ?: null,
            'webhook_secret'          => $whsecret ?: null,
            'portal_configuration_id' => $portalid ?: null,
            'success_url'             => UrlFactory::payment_success()->out(false),
            'cancel_url'              => UrlFactory::payment_cancel()->out(false),
            'portal_return'           => UrlFactory::my_subscriptions()->out(false),
        ];

        // Merge (les overrides priment)
        return array_replace($defaults, $overrides);   
    }

    public function create_checkout_session(stdClass $payment_request, array $options = []): CheckoutInitResult {
        $this->ensure_sdk();
        $mode = $options['mode'] ?? 'payment';

        // Utilisation du LOCK ?
        $useLocked = !empty($options['use_locked_amount'])
            || (isset($payment_request->locked_final_price) && (float)$payment_request->locked_final_price > 0);

        // Si on doit utiliser le LOCK, on override le mode en 'payment' (montant fixe)
        if ($useLocked && $mode !== 'payment') {
            $mode = 'payment';
        }

        // (OPTION) Devise autorisée
        $allowed = ['EUR','USD','GBP','CHF'];
        if (!in_array(strtoupper($payment_request->currency), $allowed, true)) {
            throw new \moodle_exception('stripe_invalid_currency', 'local_subscriptions', '', $payment_request->currency);
        }

        $priceId = $options['price_map']['stripe_price_id'] ?? null;

        // === LOCK PRICING (si activé) ===========================================
        if ($useLocked) {
            if (!isset($payment_request->locked_final_price) || (float)$payment_request->locked_final_price <= 0) {
                throw new \moodle_exception('paylock_missing_lockdata', 'local_subscriptions');
            }

            $prCurrency   = strtoupper($payment_request->currency ?? '');
            if ($prCurrency === '') { $prCurrency = 'EUR'; }

            $lockedList   = (float)($payment_request->locked_list_price      ?? 0.0);
            $lockedPct    = (int)  ($payment_request->locked_discount_percent ?? 0);
            $lockedAmount = (float)($payment_request->locked_discount_amount  ?? 0.0);
            $lockedReason =         ($payment_request->locked_discount_reason  ?? null);
            $lockedFinal  = (float)  $payment_request->locked_final_price;

            $amountMinor = isset($payment_request->amount_minor)
                ? (int)$payment_request->amount_minor
                : (int)round($lockedFinal * 100);

            if ($amountMinor <= 0) {
                throw new \moodle_exception('paylock_invalid_minor', 'local_subscriptions');
            }
        }
        // =========================================================================

        $cfg = $this->cfg([
            'success_url' => $options['success_url'] ?? null,
            'cancel_url'  => $options['cancel_url']  ?? null,
        ]);
        // Nettoie si nulls
        $cfg['success_url'] = $cfg['success_url'] ?: UrlFactory::payment_success()->out(false);
        $cfg['cancel_url']  = $cfg['cancel_url']  ?: UrlFactory::payment_cancel()->out(false);

        \Stripe\Stripe::setApiKey($cfg['secret_key']);

        $params = [
            'mode' => $mode,
            'metadata' => array_merge(
                [
                    'payment_request_id' => (string)$payment_request->id,
                ],
                $options['metadata'] ?? []
            ),
            'client_reference_id' => (string)$payment_request->id,
        ];

        // Si des URLs sont fournies, on les utilise
        if (!empty($options['success_url'])) { $params['success_url'] = $options['success_url']; }
        if (!empty($options['cancel_url']))  { $params['cancel_url']  = $options['cancel_url']; }

        if ($mode === 'payment') {
            if ($useLocked) {
                // Paiement au montant verrouillé (sans coupon, sans price_id)
                $params['line_items'] = [[
                    'price_data' => [
                        'currency'     => strtolower($prCurrency),
                        'product_data' => [
                            'name' => $options['product_name'] ?? get_string('stripe:productname', 'local_subscriptions', 'CampusFR')
                        ],
                        'unit_amount'  => $amountMinor,
                    ],
                    'quantity' => 1,
                ]];

                // Double en metadata (utile support)
                $params['metadata'] = array_merge($params['metadata'] ?? [], [
                    'locked_list_price'        => (string)$lockedList,
                    'locked_discount_percent'  => (string)$lockedPct,
                    'locked_discount_amount'   => (string)$lockedAmount,
                    'locked_discount_reason'   => (string)($lockedReason ?? ''),
                    'locked_final_price'       => (string)$lockedFinal,
                    'locked_currency'          => (string)$prCurrency,
                ]);

                $params['payment_intent_data'] = [
                    'metadata' => array_merge(
                        ['payment_request_id' => (string)$payment_request->id],
                        $options['metadata'] ?? []
                    ),
                ];
            } else {
                // Cas classique (pas de LOCK) — on garde ton code existant
                $amountMinor = isset($payment_request->price) ? (int)round(((float)$payment_request->price) * 100) : null;
                if ($amountMinor === null || $amountMinor <= 0) {
                    throw new \moodle_exception('stripe_nonpositive_amount', 'local_subscriptions');
                }
                $params['line_items'] = [[
                    'price_data' => [
                        'currency'     => $payment_request->currency,
                        'product_data' => ['name' => $options['product_name'] ?? get_string('stripe:productname', 'local_subscriptions', 'CampusFR')],
                        'unit_amount'  => $amountMinor,
                    ],
                    'quantity' => 1,
                ]];
                $params['payment_intent_data'] = [
                    'metadata' => ['payment_request_id' => (string)$payment_request->id]
                ];
            }
        } else {
            // Mode 'subscription' NE DOIT PAS être utilisé quand useLocked est vrai
            if ($useLocked) {
                // Par sécurité on bascule en payment (au cas où)
                $mode = 'payment';
                // … et on retombe sur la branche ci-dessus (tu peux retourner en haut si tu préfères)
                // Pour rester simple : on relance la méthode en interne n'est pas nécessaire,
                // le bloc ci-dessus a déjà préparé $params pour 'payment'.
            } else {
                // Cas abonnement récurrent standard : on garde ta logique price_id
                if (!$priceId) {
                    throw new \coding_exception(get_string('stripe:missingpriceidforsubscription', 'local_subscriptions'));
                }
                $params['line_items'] = [[ 'price' => $priceId, 'quantity' => 1 ]];  
                $params['subscription_data'] = [
                    'metadata' => ['payment_request_id' => (string)$payment_request->id]
                ];
            }
        }

        // Pré-remplir l'email si pas de customer existant
        $reqEmail = $payment_request->email ?? null;
        if (!empty($reqEmail) && empty($params['customer'])) {
            $params['customer_email']    = $reqEmail;     // <-- préremplit le champ email
            if ($mode === 'payment') {               // <-- on limite 'customer_creation' au mode payment
                $params['customer_creation'] = 'always';
            }
        }

        $params['metadata'] = array_merge($params['metadata'] ?? [], [
            'payment_request_id' => (string)$payment_request->id,
        ]);

        if (!empty($useLocked)) {
            $params['metadata'] = array_merge($params['metadata'], [
                'locked_list_price'        => (string)$lockedList,
                'locked_discount_percent'  => (string)$lockedPct,
                'locked_discount_amount'   => (string)$lockedAmount,
                'locked_discount_reason'   => (string)($lockedReason ?? ''),
                'locked_final_price'       => (string)$lockedFinal,
                'locked_currency'          => (string)$prCurrency,
            ]);
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
                $metadata = [];

                if (!empty($obj->metadata)) {
                    foreach ($obj->metadata as $key => $value) {
                        $metadata[$key] = $value;
                    }
                }

                $pid = isset($metadata['payment_request_id'])
                    ? (int)$metadata['payment_request_id']
                    : 0;

                return new InternalEvent('checkout_completed', [
                    'payment_request_id' => $pid,
                    'provider_customer_id' => $obj->customer ?? null,
                    'provider_subscription_id' => $obj->subscription ?? null,
                    'currency' => strtoupper($obj->currency ?? ''),
                    'amount_minor' => (int)($obj->amount_total ?? 0),
                    'meta' => array_merge($metadata, [
                        'provider' => Provider::STRIPE,
                        'session' => $obj->id,
                        'payment_intent' => $obj->payment_intent ?? null,
                        'customer_email' => $obj->customer_details->email ?? $obj->customer_email ?? null,
                    ]),
                ]);
            }

            case 'checkout.session.expired': {
                $metadata = [];

                if (!empty($obj->metadata)) {
                    foreach ($obj->metadata as $key => $value) {
                        $metadata[$key] = $value;
                    }
                }

                $pid = isset($metadata['payment_request_id'])
                    ? (int)$metadata['payment_request_id']
                    : 0;

                return new InternalEvent('checkout_expired', [
                    'payment_request_id' => $pid,
                    'meta' => array_merge($metadata, [
                        'provider' => Provider::STRIPE,
                        'session' => $obj->id,
                    ]),
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
