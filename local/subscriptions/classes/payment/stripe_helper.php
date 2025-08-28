<?php
namespace local_subscriptions\payment;

require_once(__DIR__ . '/../../vendor/autoload.php');

class stripe_helper {

    public function __construct() {
        \Stripe\Stripe::setApiKey(get_config('local_subscriptions', 'stripe_secret'));
    }

    public static function load_stripe(): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/subscriptions/vendor/autoload.php');

        \Stripe\Stripe::setApiKey(get_config('local_subscriptions', 'stripe_secret_key'));
    }


    public function create_checkout_session_for_guest(int $userid, $plan, $price): \Stripe\Checkout\Session {
        $this->load_stripe();

        $token = bin2hex(random_bytes(16)); // token unique pour associer la session

        $success_url = new \moodle_url('/local/subscriptions/payment_success.php', ['token' => $token]);
        $cancel_url  = new \moodle_url('/local/subscriptions/payment_cancel.php');

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'locale' => current_language(),
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($price->currency),
                    'unit_amount' => $price->price * 100, // en centimes
                    'product_data' => [
                        'name' => format_string($plan->name),
                    ],
                ],
                'quantity' => 1,
            ]],
            'customer_creation' => 'always',
            'custom_fields' => [
                [
                    'key' => 'firstname',
                    'label' => ['type' => 'custom', 'custom' => 'First name'],
                    'type' => 'text',
                    'text' => ['required' => true],
                ],
                [
                    'key' => 'lastname',
                    'label' => ['type' => 'custom', 'custom' => 'Last name'],
                    'type' => 'text',
                    'text' => ['required' => true],
                ]
            ],
            'success_url' => $success_url->out(false),
            'cancel_url' => $cancel_url->out(false),
        ]);

        // Enregistre la demande dans subscription_payment_request
        global $DB;

        $DB->insert_record('subscription_payment_request', (object)[
            'userid'           => $userid ?: null,
            'planid'           => $plan->id,
            'currency'         => $price->currency,
            'price'            => $price->price,
            'payment_provider' => 'stripe',
            'status'           => 'pending',
            'transactionid'    => null,
            'payment_link'     => $session->url,
            'response_json'    => json_encode($session),
            'creation_date'    => time(),
            'payment_date'     => null,
            'expiration_date'  => null,
        ]);

        return $session;
    }

}
