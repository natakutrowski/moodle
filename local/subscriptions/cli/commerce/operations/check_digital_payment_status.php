
<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\payment\stripe\StripeConfiguration;
require_once($CFG->libdir . '/clilib.php');

global $DB, $CFG;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'id' => 0,
        'provider' => '',
        'limit' => 20,
    ],
    [
        'h' => 'help',
    ]
);

if ($options['help']) {
    echo "Check digital payment status without DB update or email sending.\n\n";
    echo "Usage:\n";
    echo "  php local/subscriptions/cli/commerce/operations/check_digital_payment_status.php --id=123\n";
    echo "  php local/subscriptions/cli/commerce/operations/check_digital_payment_status.php --provider=stripe --limit=20\n";
    echo "  php local/subscriptions/cli/commerce/operations/check_digital_payment_status.php --provider=alfa --limit=20\n\n";
    exit(0);
}

$id = (int)$options['id'];
$provider = strtolower(trim((string)$options['provider']));
$limit = max(1, (int)$options['limit']);

$params = [];
$where = [
    "status = :status",
    "sessionid IS NOT NULL",
    "sessionid <> ''",
];

$params['status'] = 'pending';

if ($id > 0) {
    $where[] = "id = :id";
    $params['id'] = $id;
}

if ($provider !== '') {
    $where[] = "payment_provider = :provider";
    $params['provider'] = $provider;
}

$sql = "
    SELECT *
      FROM {subscription_digital_payment_request}
     WHERE " . implode(' AND ', $where) . "
  ORDER BY id DESC
";

$records = $DB->get_records_sql($sql, $params, 0, $limit);

if (!$records) {
    mtrace("No pending digital payment request found.");
    exit(0);
}

foreach ($records as $pr) {
    mtrace(str_repeat('-', 80));
    mtrace("PR #{$pr->id}");
    mtrace("Provider : {$pr->payment_provider}");
    mtrace("Email    : {$pr->email}");
    mtrace("Amount   : {$pr->price} {$pr->currency}");
    mtrace("Session  : {$pr->sessionid}");
    mtrace("DB status: {$pr->status}");

    try {
        if ($pr->payment_provider === 'stripe') {
            check_stripe_status($pr);
        } else if ($pr->payment_provider === 'alfa') {
            check_alfa_status($pr);
        } else {
            mtrace("Unsupported provider: {$pr->payment_provider}");
        }
    } catch (Throwable $e) {
        mtrace("ERROR: " . $e->getMessage());
    }
}

mtrace(str_repeat('-', 80));
mtrace("Done. No DB update was performed. No email was sent.");


/**
 * Read-only Stripe status check.
 */
function check_stripe_status(stdClass $pr): void {
    global $CFG;

    $env = StripeConfiguration::active_profile();
    $secret = StripeConfiguration::secret_key($env);

    if ($secret === '') {
        throw new moodle_exception('Missing Stripe secret key for env: ' . $env);
    }

    $autoload = $CFG->dirroot . '/local/subscriptions/vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new coding_exception('Stripe SDK autoload not found: ' . $autoload);
    }

    require_once($autoload);

    \Stripe\Stripe::setApiKey($secret);

    $session = \Stripe\Checkout\Session::retrieve($pr->sessionid);

    mtrace("Stripe env          : {$env}");
    mtrace("Stripe session id   : " . ($session->id ?? ''));
    mtrace("Stripe status       : " . ($session->status ?? ''));
    mtrace("Stripe payment      : " . ($session->payment_status ?? ''));
    mtrace("Stripe amount total : " . (($session->amount_total ?? 0) / 100) . ' ' . strtoupper($session->currency ?? ''));
    mtrace("Stripe customer mail: " . ($session->customer_details->email ?? $session->customer_email ?? ''));

    if (($session->payment_status ?? '') === 'paid') {
        mtrace("RESULT: PAID ✅");
    } else {
        mtrace("RESULT: NOT PAID / NOT FINALIZED ⏳");
    }
}


/**
 * Read-only Alfa status check.
 */
function check_alfa_status(stdClass $pr): void {
    $env = get_config('local_subscriptions', 'alfa_env') ?: 'test';
    $env = ($env === 'live') ? 'live' : 'test';

    $base = rtrim((string)(get_config('local_subscriptions', "alfa_{$env}_api_base") ?: ''), '/');
    $username = get_config('local_subscriptions', "alfa_{$env}_username") ?: '';
    $password = get_config('local_subscriptions', "alfa_{$env}_password") ?: '';
    $token = get_config('local_subscriptions', "alfa_{$env}_token") ?: '';

    if ($base === '') {
        throw new moodle_exception('Missing Alfa API base for env: ' . $env);
    }

    $payload = [
        'orderId' => $pr->sessionid,
    ];

    if ($token !== '') {
        $payload = array_merge(['token' => $token], $payload);
    } else {
        if ($username !== '') {
            $payload['userName'] = $username;
        }
        if ($password !== '') {
            $payload['password'] = $password;
        }
    }

    $url = $base . '/payment/rest/getOrderStatusExtended.do';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new moodle_exception('CURL error: ' . $err);
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        parse_str($raw, $data);
    }

    if (!is_array($data)) {
        throw new moodle_exception("Invalid Alfa response. HTTP {$httpcode}: {$raw}");
    }

    $orderstatus = isset($data['orderStatus']) ? (int)$data['orderStatus'] : null;

    mtrace("Alfa env          : {$env}");
    mtrace("Alfa HTTP code    : {$httpcode}");
    mtrace("Alfa orderStatus  : " . var_export($orderstatus, true));
    mtrace("Alfa amount minor : " . ($data['amount'] ?? ''));
    mtrace("Alfa action       : " . ($data['actionCodeDescription'] ?? $data['errorMessage'] ?? ''));

    if ($orderstatus === 2) {
        mtrace("RESULT: PAID ✅");
    } else if ($orderstatus === 0) {
        mtrace("RESULT: REGISTERED BUT NOT PAID ⏳");
    } else if ($orderstatus === 6) {
        mtrace("RESULT: DECLINED ❌");
    } else {
        mtrace("RESULT: NOT PAID / UNKNOWN ⏳");
    }

    mtrace("Raw Alfa response:");
    mtrace(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}